<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('string_utility.lib.php');
		if (is_file('addendum_utility.lib.php'))
		{
			require_once('addendum_utility.lib.php');
		}
	}
	//TODO: support mapping entities to multiple databases [low priority]

	Paladio::Request
	(
		'Addendum_Utility',
		create_function
		(
			'',
			'
				/**
				 * Mapping
				 * @package Paladio
				*/
				class Mapping extends Annotation {public $table, $primaryKey;}
				/**
				 * Reference
				 * @package Paladio
				*/
				class Reference extends Annotation {public $alias, $reference, $method;}
			'
		)
	);

	/**
	 * EntityBase
	 * @package Paladio
	 */
	abstract class EntityBase
	{
		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * Creates a new entity in the database and return it.
		 *
		 * @param $primaryKeyValue: the value of the primary key for the new entity.
		 * @param $write: indicates whatever or not to write the entity to the database immediately.
		 * @param $class: before PHP 5.3 $class is needed to tell the entity class to use.
		 *
		 * @access public
		 * @return entity object
		 */
		public static function Create(/*mixed*/ $primaryKeyValue, /*bool*/ $write = true, /*string*/ $class = null)
		{
			if (!is_string($class))
			{
				if (function_exists('get_called_class'))
				{
					$class = get_called_class();
				}
				else
				{
					throw new Exception('Unable to infer class');
				}
			}
			$data = Entity::GetEntityData($class);
			$table = $data['table'];
			$primaryKey = $data['primaryKey'];
			if (Entity::Exists($table, $primaryKey, $primaryKeyValue))
			{
				return null;
			}
			else
			{
				$entity = new Entity($table, $primaryKey, $primaryKeyValue, $write);
				$result = new $class($entity);
				return $result;
			}
		}

		/**
		 * Verifies if an entity exists in the database.
		 *
		 * Returns true if the entity exists, false otherwise.
		 *
		 * @param $primaryKeyValue: the value of the primary key for the entity.
		 * @param $class: before PHP 5.3 $class is needed to tell the entity class to use.
		 *
		 * @access public
		 * @return bool
		 */
		public static function Exists(/*mixed*/ $primaryKeyValue, /*string*/ $class = null)
		{
			if (!is_string($class))
			{
				if (function_exists('get_called_class'))
				{
					$class = get_called_class();
				}
				else
				{
					throw new Exception('Unable to infer class');
				}
			}
			$data = Entity::GetEntityData($class);
			return Entity::Exists($data['table'], $data['primaryKey'], $primaryKeyValue);
		}

		/**
		 * Get a entity that already exists in the dabase.
		 *
		 * Returns a entity object if the entity exists, null otherwise.
		 *
		 * @param $primaryKeyValue: the value of the primary key for the existing entity.
		 * @param $class: before PHP 5.3 $class is needed to tell the entity class to use.
		 *
		 * @access public
		 * @return entity object
		 */
		public static function Existing(/*mixed*/ $primaryKeyValue, /*string*/ $class = null)
		{
			if (!is_string($class))
			{
				if (function_exists('get_called_class'))
				{
					$class = get_called_class();
				}
				else
				{
					throw new Exception('Unable to infer class');
				}
			}
			$data = Entity::GetEntityData($class);
			$table = $data['table'];
			$primaryKey = $data['primaryKey'];
			if (Entity::Exists($table, $primaryKey, $primaryKeyValue))
			{
				$entity = new Entity($table, $primaryKey, $primaryKeyValue);
				$result = new $class($entity);
				return $result;
			}
			else
			{
				return null;
			}
		}

		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------

		private $_class;

		//------------------------------------------------------------
		// Single field, fully mapped

		/**
		 * Internally used to retrive the value of a field
		 * @access private
		 */
		private function _get(/*string*/ $fieldName)
		{
			$fieldName = (string)$fieldName;
			if (is_array($this->_primaryKey) && (($pos = array_search($fieldName, $this->_primaryKey)) !== false))
			{
				return $this->_primaryKeyValue[$pos];
			}
			else if ($fieldName == (string)$this->_primaryKey)
			{
				return $this->_primaryKeyValue;
			}
			else if (method_exists($this, ($method = 'get_'.$fieldName)))
			{
				return $this->$method();
			}
			else if (array_key_exists($fieldName, $this->_references))
			{
				$reference = $this->_references[$fieldName];
				$method = $reference['method'];
				if (is_callable($method))
				{
					return call_user_func($method, $this->get($reference['reference']));
				}
				else
				{
					return null;
				}
			}
			else if ($this->_entity->try_get($fieldName, $result))
			{
				return $result;
			}
			else if (EntityBase::CreateBasesUntilGet($fieldName, $result))
			{
				return $result;
			}
			else
			{
				return null;
			}
		}

		/**
		 * Internally used to verify if a field exists
		 * @access private
		 */
		private function _isset(/*string*/ $fieldName)
		{
			$fieldName = (string)$fieldName;
			if (is_array($this->_primaryKey) && in_array($fieldName, $this->_primaryKey))
			{
				return true;
			}
			else if ($fieldName == (string)$this->_primaryKey)
			{
				return true;
			}
			else if (method_exists($this, ($method = 'isset_'.$fieldName)))
			{
				return $this->$method();
			}
			else if (method_exists($this, ($method = 'get_'.$fieldName)))
			{
				return true;
			}
			else if (array_key_exists($fieldName, $this->_references))
			{
				$reference = $this->_references[$fieldName];
				$method = $reference['method'];
				if (is_callable($method))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else if (isset($this->_entity->$fieldName))
			{
				return true;
			}
			else if (EntityBase::CreateBasesUntilIsset($fieldName))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Internally used to sets the value of a field
		 * @access private
		 */
		private function _set(/*string*/ $fieldName, /*mixed*/ $value)
		{
			$fieldName = (string)$fieldName;
			if (is_array($this->_primaryKey) && in_array($fieldName, $this->_primaryKey))
			{
				throw new Exception('Unable to write primary key');
			}
			else if ($fieldName == (string)$this->_primaryKey)
			{
				throw new Exception('Unable to write primary key');
			}
			else if (method_exists($this, ($method = 'set_'.$fieldName)))
			{
				return $this->$method($value);
			}
			else if (array_key_exists($fieldName, $this->_references))
			{
				throw new Exception('Unable to write refence');
			}
			else if ($this->_entity->_set($fieldName, $value))
			{
				if ($this->_autoSave)
				{
					$this->Save();
				}
			}
			else if (EntityBase::CreateBasesUntilSet($fieldName, $value))
			{
				return true;
			}
			else
			{
				return null;
			}
		}

		/**
		 * Internally used to unset the value of a field
		 * @access private
		 */
		private function _unset(/*string*/ $fieldName)
		{
			$fieldName = (string)$fieldName;
			if (is_array($this->_primaryKey) && in_array($fieldName, $this->_primaryKey))
			{
				throw new Exception('Unable to write primary key');
			}
			else if ($fieldName == (string)$this->_primaryKey)
			{
				throw new Exception('Unable to write primary key');
			}
			else if (method_exists($this, ($method = 'unset_'.$fieldName)))
			{
				$this->$method();
			}
			else if (array_key_exists($fieldName, $this->_references))
			{
				throw new Exception('Unable to write refence');
			}
			else if ($this->entity->try_un_set($fieldName))
			{
				//Empty
			}
			else if (EntityBase::CreateBasesUntilUnset($fieldName))
			{
				//Empty
			}
			else
			{
				return;
			}
		}

		//------------------------------------------------------------

		/**
		 * Internally used to create inheritance hierarchy
		 * @access private
		 */
		private function CreateBases()
		{
			$current = $this;
			while (true)
			{
				$current = $current->ProcessInheritance(true);
				if (is_null($current))
				{
					return;
				}
			}
		}

		/**
		 * Internally used to create inheritance hierarchy until a field is set
		 * @access private
		 */
		private function CreateBasesUntilIsset(/*string*/ $fieldName)
		{
			$current = $this;
			while (true)
			{
				if (isset($current->_entity->$fieldName))
				{
					return true;
				}
				$current = $current->ProcessInheritance(true);
				if (is_null($current))
				{
					return false;
				}
			}
		}

		/**
		 * Internally used to create inheritance hierarchy until can get a field
		 * @access private
		 */
		private function CreateBasesUntilGet(/*string*/ $fieldName, /*mixed*/ &$result)
		{
			$current = $this;
			while (true)
			{
				if ($current->_entity->try_get($fieldName, $result))
				{
					return true;
				}
				$current = $current->ProcessInheritance(true);
				if (is_null($current))
				{
					return false;
				}
			}
		}

		/**
		 * Internally used to create inheritance hierarchy until can set a field
		 * @access private
		 */
		private function CreateBasesUntilSet(/*string*/ $fieldName, /*mixed*/ $value)
		{
			$current = $this;
			while (true)
			{
				if ($current->_entity->set($fieldName, $value))
				{
					return true;
				}
				$current = $current->ProcessInheritance(true);
				if (is_null($current))
				{
					return false;
				}
			}
		}

		/**
		 * Internally used to create inheritance hierarchy until can unset a field
		 * @access private
		 */
		private function CreateBasesUntilUnset(/*string*/ $fieldName)
		{
			$current = $this;
			while (true)
			{
				if ($current->_entity->try_un_set($fieldName))
				{
					return true;
				}
				$current = $current->ProcessInheritance(true);
				if (is_null($current))
				{
					return false;
				}
			}
		}

		/**
		 * Creates one level of inheritance hierarchy
		 */
		private function ProcessInheritance(/*bool*/ $create = false)
		{
			if (is_null($this->_baseEntityBase))
			{
				$class = get_parent_class($this);
				if ($class != __CLASS__)
				{
					$data = Entity::GetEntityData($class);
					$primaryKey = $data['primaryKey'];
					$primaryKeyValue = array();
					if (is_array($primaryKey))
					{
						foreach ($primaryKey as $key)
						{
							if (is_array($this->_primaryKeyValue))
							{
								if (array_key_exists($key, $this->_primaryKeyValue))
								{
									$primaryKeyValue[$key] = $this->_primaryKeyValue[$key];
								}
							}
							else if ($this->_primaryKey == $key)
							{
								$primaryKeyValue = $this->primaryKeyValue;
							}
							else
							{
								$primaryKeyValue[$key] = $this->$key;
							}
						}
					}
					else
					{
						$key = (string)$primaryKey;
						if (is_array($this->_primaryKeyValue))
						{
							if (array_key_exists($key, $this->_primaryKeyValue))
							{
								$primaryKeyValue[$key] = $this->_primaryKeyValue[$key];
							}
						}
						else if ($this->_primaryKey == $key)
						{
							$primaryKeyValue = $this->primaryKeyValue;
						}
						else
						{
							$primaryKeyValue[$key] = $this->$key;
						}
					}
					$this->_baseEntityBase = EntityBase::Existing($primaryKeyValue, $class);
					if ($create && is_null($this->_baseEntityBase))
					{
						$this->_baseEntityBase = EntityBase::Create($this->primaryKeyValue, true, $class);
					}
					return $this->_baseEntityBase;
				}
				else
				{
					return null;
				}
			}
			else
			{
				return $this->_baseEntityBase;
			}
		}

		//------------------------------------------------------------
		// Protected (Instance)
		//------------------------------------------------------------

		protected $_autoSave;
		protected $_primaryKey;
		protected $_entity;
		protected $_primaryKeyValue;
		protected $_references;
		protected $_baseEntityBase;

		//------------------------------------------------------------
		// Public (Instace)
		//------------------------------------------------------------

		//------------------------------------------------------------
		// Multiple fields, delegate to multiple fields fully mapped

		public function __get(/*mixed*/ $fieldName)
		{
			return $this->get($fieldName);
		}

		public function __isset(/*mixed*/ $fieldName)
		{
			return $this->is_set($fieldName);
		}

		public function __set(/*mixed*/ $fieldName, /*mixed*/ $value)
		{
			$this->set($fieldName, $value);
			return $this;
		}

		public function __unset(/*mixed*/ $fieldName)
		{
			$this->un_set($fieldName);
			return $this;
		}

		//------------------------------------------------------------

		/**
		 * Sets AutoSave on or off.
		 *
		 * If AutoSave is on, the entity will be saved each time any of its value is modified.
		 *
		 * @access public
		 * @return void
		 */
		public function AutoSave(/*bool*/ $value)
		{
			if ($value)
			{
				$this->_autoSave = true;
			}
			else
			{
				$this->_autoSave = false;
			}
		}

		/**
		 * Clear the values of this instance.
		 *
		 * @param $recursive: if true executes this command for all the base entities.
		 *
		 * @access public
		 * @return this
		 */
		public function Clear(/*bool*/ $recursive = true)
		{
			$this->_entity->Clear();
			if (!is_null($this->_baseEntityBase) && $recursive)
			{
				$this->_baseEntityBase->Clear($recursive);
			}
			return $this;
		}

		/**
		 * Retrieves the values of all the fields from the database.
		 *
		 * @param $recursive: if true executes this command for all the base entities.
		 *
		 * @access public
		 * @return this
		 */
		public function Load(/*bool*/ $recursive = true)
		{
			if ($this->_entity->Load())
			{
				if (!is_null($this->_baseEntityBase) && $recursive)
				{
					$this->_baseEntityBase->Load($recursive);
				}
				return $this;
			}
			else
			{
				throw new Exception('Error');
			}
		}

		/**
		 * Stores the values of all the fields to the database.
		 *
		 * @param $recursive: if true executes this command for all the base entities.
		 *
		 * @access public
		 * @return this
		 */
		public function Save(/*bool*/ $recursive = true)
		{
			if ($this->_entity->Save())
			{
				if (!is_null($this->_baseEntityBase) && $recursive)
				{
					$this->_baseEntityBase->Save($recursive);
				}
				return $this;
			}
			else
			{
				throw new Exception('Error');
			}
		}

		/**
		 * Sets the values of the fields to the values of $record.
		 *
		 * @param $record: the values to set the fields to.
		 *
		 * @access public
		 * @return this
		 */
		public function Write(/*array*/ $record)
		{
			if (is_array($record))
			{
				$autoSave = $this->_autoSave;
				if ($autoSave)
				{
					$this->_autoSave = false;
				}
				$fields = array_keys($record);
				foreach ($fields as $field)
				{
					if ($field != $this->_primaryKey)
					{
						$this->_set((string)$field, $record[$field]);
					}
				}
				if ($autoSave)
				{
					$this->Save();
					$this->_autoSave = true;
				}
			}
			return $this;
		}

		//------------------------------------------------------------
		// Multiple fields, delegated to single field fully mapped

		/**
		 * Retrives the value of the field $fieldName.
		 *
		 * @param $fieldName: the name of the field to retrieve.
		 *
		 * @access public
		 * @return mixed
		 */
		public function get(/*mixed*/ $fieldName)
		{
			if (is_string($fieldName))
			{
				return $this->_get($fieldName);
			}
			else if (is_array($fieldName))
			{
				$result = array();
				foreach ($fieldName as $field)
				{
					$result[] = $this->_get($field);
				}
				return $result;
			}
		}

		/**
		 * Verifies the value of the field $fieldName has value set.
		 *
		 * @param $fieldName: the name of the field to verify.
		 *
		 * @access public
		 * @return mixed
		 */
		public function is_set(/*mixed*/ $fieldName)
		{
			if (is_string($fieldName))
			{
				return $this->_isset($fieldName);
			}
			else if (is_array($fieldName))
			{
				$result = array();
				foreach ($fieldName as $field)
				{
					$result[] = $this->_isset($field);
				}
				return $result;
			}
		}

		/**
		 * Sets the value of the field $fieldName.
		 *
		 * @param $fieldName: the name of the field to set.
		 * @param $value: the value to set the field to.
		 *
		 * @access public
		 * @return this
		 */
		public function set(/*mixed*/ $fieldName, /*mixed*/ $value)
		{
			if (is_array($fieldName))
			{
				$count = count($fieldName);
				if (is_array($value))
				{
					for ($index = 0; $index < $count; $index++)
					{
						$this->_set($fieldName[$index], $value[$index]);
					}
				}
				else
				{
					for ($index = 0; $index < $count; $index++)
					{
						$this->_set($fieldName[$index], $value);
					}
				}
			}
			else if (is_string($fieldName))
			{
				$this->_set($fieldName, $value);
			}
			return $this;
		}

		/**
		 * Clears the value of the field $fieldName.
		 *
		 * @param $fieldName: the name of the field to clear.
		 *
		 * @access public
		 * @return this
		 */
		public function un_set(/*mixed*/ $fieldName)
		{
			if (is_string($fieldName))
			{
				$this->_unset($fieldName, $value);
			}
			else if (is_array($fieldName))
			{
				foreach ($fieldName as $field)
				{
					$this->_unset($field, $value);
				}
			}
			return $this;
		}

		//------------------------------------------------------------
		// Protected (Constructor)
		//------------------------------------------------------------

		/**
		 * Creates a new instance of EntiryBase
		 * @param $entity: an Entity object.
		 * @param $autoSave: determinated if the entity will be saved each time any of its value is modified.
		 */
		protected function __construct(/*Entity*/ $entity, /*bool*/ $autoSave = true)
		{
			$this->_class = get_class($this);
			$this->_autoSave = $autoSave;

			$this->_entity = $entity;
			$primaryKey = $entity->PrimaryKey();
			$this->_primaryKeyValue = $entity->get($primaryKey);
			$this->_primaryKey = $primaryKey;

			$this->_references = Entity::GetEntityReferences($this->_class);
			$this->ProcessInheritance();
		}
	}

	/**
	 * Entity
	 * @package Paladio
	 */
	final class Entity
	{
		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		/**
		 * Used to create a where condition for the primarykey.
		 * @access public
		 */
		public static function CreateWhere (/*mixed*/ $primaryKey, /*mixed*/ $primaryKeyValue)
		{
			$where = array();
			if (is_string($primaryKey))
			{
				if (is_array($primaryKeyValue))
				{
					if (count($primaryKeyValue) > 0)
					{
						if (array_key_exists($primaryKey, $primaryKeyValue))
						{
							$where = array($primaryKey => $primaryKeyValue[$primaryKey]);
						}
						else
						{
							if (array_key_exists(0, $primaryKeyValue))
							{
								$where = array($primaryKey => $primaryKeyValue[0]);
							}
							else
							{
								throw new Exception('Invalid Primary Key Value');
							}
						}
					}
					else
					{
						throw new Exception('Invalid Primary Key Value');
					}
				}
				else
				{
					$where = array($primaryKey => $primaryKeyValue);
				}
			}
			else if (is_array($primaryKey))
			{
				if (is_array($primaryKeyValue))
				{
					$index = 0;
					foreach ($primaryKey as $key)
					{
						$where[$key] = $primaryKeyValue[$index];
						$index++;
					}
				}
				else
				{
					foreach ($primaryKey as $key)
					{
						$where[$key] = $primaryKeyValue;
					}
				}
			}
			return $where;
		}

		/**
		 * Verifies if the entity exists on the database.
		 *
		 * @param $table: the name of the table of the entity.
		 * @param $primaryKey: the primary key.
		 * @param $primaryKeyValue: the value of the primary key.
		 *
		 * @access plubic
		 * @return bool
		 */
		public static function Exists(/*string*/ $table, /*mixed*/ $primaryKey, /*mixed*/ $primaryKeyValue)
		{
			return (Database::CountRecords($table, Entity::CreateWhere($primaryKey, $primaryKeyValue)) == 1);
		}

		/**
		 * Used to retrieve the table and primary key of an entity.
		 * @access public
		 */
		public static function GetEntityData(/*string*/ $class)
		{
			if (is_callable($class.'::Mapping'))
			{
				return call_user_func(array($class, 'Mapping'));
			}
			else
			{
				return Addendum_Utility::ReadAnnotation($class, 'Mapping', array('table', 'primaryKey'));
			}
		}
		
		/**
		 * Used to retrieve the references of the entity.
		 * @access public
		 */
		public static function GetEntityReferences(/*string*/ $class)
		{
			$result = array();
			if (is_callable($class.'::References'))
			{
				$references = call_user_func(array($class, 'References'));
			}
			else
			{
				$references = Addendum_Utility::ReadAnnotations($class, 'Reference', array('alias', 'reference', 'method'));
			}
			foreach ($references as $reference)
			{
				$result[$reference['alias']] = $reference;
			}
			return $result;
		}

		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------

		private $_primaryKey;
		private $_record;
		private $_table;
		private $_where;

		private function tryGet(/*mixed*/ $fieldName)
		{
			return $this->get($fieldName);
		}

		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		//------------------------------------------------------------
		// Multiple fields, delegate to multiple fields database mapped

		public function __get(/*mixed*/ $fieldName)
		{
			return $this->get($fieldName);
		}

		public function __set(/*mixed*/ $fieldName, /*mixed*/ $value)
		{
			$this->set($fieldName, $value);
		}

		public function __isset(/*mixed*/ $fieldName)
		{
			$result = Database::HasFields($this->_table, array($fieldName));
			return $result;
		}

		public function __unset(/*string*/ $fieldName)
		{
			unset($this->_registro[$fieldName]);
		}
		
		//------------------------------------------------------------
		// Single field, database mapped

		public function try_get(/*string*/ $fieldName, /*mixed*/ &$result)
		{
			$fieldName = (string)$fieldName;
			if (!array_key_exists($fieldName, $this->_record))
			{
				$record = array();
				if (Database::TryReadOneRecord($record, $this->_table, array($fieldName), $this->_where))
				{
					$this->_record[$fieldName] = $record[$fieldName];
				}
				else
				{
					return false;
				}
			}
			$result = $this->_record[$fieldName];
			return true;
		}

		public function try_un_set(/*string*/ $fieldName)
		{
			if (array_key_exists($fieldName, $this->_registro))
			{
				unset($this->_registro[$fieldName]);
				return true;
			}
			else
			{
				return false;
			}
		}

		public function _get(/*string*/ $fieldName)
		{
			$fieldName = (string)$fieldName;
			if (!array_key_exists($fieldName, $this->_record))
			{
				$record = array();
				if (Database::TryReadOneRecord($record, $this->_table, array($fieldName), $this->_where))
				{
					$this->_record[$fieldName] = $record[$fieldName];
				}
				else
				{
					return null;
				}
			}
			return $this->_record[$fieldName];
		}

		public function _set(/*string*/ $fieldName, /*mixed*/ $value)
		{
			$fieldName = (string)$fieldName;
			if (array_key_exists($fieldName, $this->_record))
			{
				$this->_registro[$fieldName] = $value;
				return true;
			}
			else
			{
				$record = array();
				if (Database::HasFields($this->_table, array($fieldName)))
				{
					$this->_record[$fieldName] = $value;
					return true;
				}
				else
				{
					return false;
				}
			}
		}

		//------------------------------------------------------------

		/**
		 * Clears the values of this instance.
		 *
		 * @access plubic
		 * @return void
		 */
		public function Clear()
		{
			$this->_registro = array();
		}

		/**
		 * Inserts the values of this instance to the database.
		 *
		 * Returns true if the operation was successful, false otherwise.
		 *
		 * @access plubic
		 * @return bool
		 */
		public function Create()
		{
			return Database::Insert($this->_where, $this->_table);
		}

		/**
		 * Reads the values of this instance from the database.
		 *
		 * Returns true if the operation was successful, false otherwise.
		 *
		 * @access plubic
		 * @return bool
		 */
		public function Load()
		{
			return Database::TryReadOneRecord($this->_record, $this->_table, null, $this->_where);
		}

		/**
		 * Returns the primary.
		 *
		 * @access plubic
		 * @return mixed
		 */
		public function PrimaryKey()
		{
			return $this->_primaryKey;
		}

		/**
		 * Returns the values of this instance.
		 *
		 * @access plubic
		 * @return mixed
		 */
		public function Record()
		{
			return $this->_record;
		}

		/**
		 * Writes the values of this instance to the database.
		 *
		 		 * Returns true if the operation was successful, false otherwise.
		 *
		 * @access plubic
		 * @return bool
		 */
		public function Save()
		{
			return Database::Write($this->_record, $this->_table, $this->_where);
		}

		/**
		 * Returns the table of this instance.
		 *
		 * @access plubic
		 * @return string
		 */
		public function Table()
		{
			return $this->_table;
		}

		//------------------------------------------------------------
		// Multiple fields, delegated to single field database mapped

		public function get(/*mixed*/ $fieldName)
		{
			if (is_array($fieldName))
			{
				$result = array();
				foreach ($fieldName as $field)
				{
					$result[] = $this->_get($field);
				}
				return $result;
			}
			else if (is_string($fieldName))
			{
				return $this->_get($fieldName);
			}
		}

		public function set(/*mixed*/ $fieldName, /*mixed*/ $value)
		{
			if (is_array($fieldName))
			{
				$count = count($fieldName);
				if (is_array($value))
				{
					for ($index = 0; $index < $count; $index++)
					{
						$this->_set($fieldName[$index], $value[$index]);
					}
				}
				else
				{
					for ($index = 0; $index < $count; $index++)
					{
						$this->_set($fieldName[$index], $value);
					}
				}
			}
			else if (is_string($fieldName))
			{
				return $this->_set($fieldName, $value);
			}
		}

		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------

		/**
		 * Creates a new instance of Entity
		 * @param $table: the table of the entity.
		 * @param $primaryKey: the primariKey of the table of this entity.
		 * @param $primaryKeyValue: the value of the the primariKey of this entity.
		 * @param $create: determinates whatever or not write this entity to the database immediately.
		 */
		public function __construct(/*string*/ $table, /*mixed*/ $primaryKey, /*mixed*/ $primaryKeyValue, /*bool*/ $create = false)
		{
			$this->_table = $table;
			$this->_primaryKey = $primaryKey;
			$this->_where = Entity::CreateWhere($primaryKey, $primaryKeyValue);
			$this->_record = array();
			if ($create)
			{
				if (!$this->Create())
				{
					throw new Exception('Unable to create entry');
				}
			}
		}
	}

	require_once('filesystem.lib.php');
	require_once('configuration.lib.php');
	Configuration::Callback
	(
		array('paladio-database', 'paladio-paths'),
		create_function
		(
			'',
			'
				$entitiesFolder = \'entities\';
				Configuration::TryGet(\'paladio-paths\', \'entities\', $entitiesFolder);
				FileSystem::RequireAll(\'*.lib.php\', FileSystem::FolderCore().$entitiesFolder);
			'
		)
	);
?>