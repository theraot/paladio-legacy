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

	/**
	 * Intended for internal use only
	 */
	function declare_entity_annotations()
	{
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
	}

	Paladio::Request('Addendum_Utility', 'declare_entity_annotations');

	/**
	 * EntityBase
	 * @package Paladio
	 */
	abstract class EntityBase
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		/**
		 * Internally used to retrieve the table and primary key of an entity.
		 * @access private
		 */
		private static function GetEntityData(/*string*/ $class)
		{
			if (is_callable($class.'::Mapping'))
			{
				return call_user_func($class.'::Mapping');
			}
			else
			{
				return Addendum_Utility::ReadAnnotation($class, 'Mapping', array('table', 'primaryKey'));
			}
		}

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
			$data = EntityBase::GetEntityData($class);
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
			$data = EntityBase::GetEntityData($class);
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
		public static function Existing(/*/mixed*/ $primaryKeyValue, /*string*/ $class = null)
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
			$data = EntityBase::GetEntityData($class);
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

		/**
		 * Loads ent files.
		 *
		 * If the ent describes a table that doens't exist, the table is created.
		 * If a class with name equal to the name of table changed to initial capital letter doens't exist, the class is created as an entity class for the table.
		 *
		 * @param $path: the folder from which to load the ent files.
		 *
		 * @access public
		 * @return void
		 */
		public static function LoadEnts($path)
		{
			$classTemplate =
			'class @class extends EntityBase
			{
				public static function Mapping(){return array(\'table\' => \'@table\', \'primaryKey\' => @primarykey);}
				public static function Create(/*mixed*/ $primaryKeyValue, /*bool*/ $write = true, /*string*/ $class = null){return EntityBase::Create($primaryKeyValue, $write, \'@class\');}
				public static function Exists(/*mixed*/ $primaryKeyValue, /*string*/ $class = null){return EntityBase::Exists($primaryKeyValue, \'@class\');}
				public static function Existing(/*mixed*/ $primaryKeyValue, /*string*/ $class = null){return EntityBase::Existing($primaryKeyValue, \'@class\');}
			}';
			$constraints = array('primary key', 'unique', 'not null');
			//------------------------------------------------------------
			$ending = '.ent.php';
			$entityFiles = FileSystem::GetFolderFiles('*'.$ending, FileSystem::FolderCore().$path);
			foreach ($entityFiles as $entityFile)
			{
				$filename = basename($entityFile);
				$table = mb_substr(basename($entityFile), 0, mb_strlen($filename) - mb_strlen($ending));

				$INI = new INI();
				$INI->Load($entityFile, 1);
				$category = $INI->get_Category('fields');
				if (!Database::TableExists($table))
				{
					$keys = array_keys($category);
					$pieces = array();
					foreach ($keys as $key)
					{
						//ONLY UTF-8
						if (preg_match('@([a-z ]+)(.*)@u', trim(mb_strtolower($category[$key]['type'])), $matches))
						{
							$type = DB::MapType($matches[1]);
							if ($type === false)
							{
								$type = $matches[1];
							}
							$piece = $key.' '.$type;
							if (preg_match('@\(([0-9, ]*)\)@', $matches[2], $matches))
							{
								//ONLY UTF-8
								$typeModifier = array_map('trim', explode(',', $matches[1]));
								//ONLY UTF-8
								$piece .= '('.implode(', ', $typeModifier).')';
							}
							if (isset($category[$key]['constraint']))
							{
								//ONLY UTF-8
								$constraint = trim(mb_strtolower($category[$key]['constraint']));
								if (in_array($constraint, $constraints))
								{
									$piece .= ' '.$category[$key]['constraint'];
								}
							}
							if (isset($category[$key]['default']))
							{
								$piece .= ' default '.$category[$key]['default'];
							}
							$pieces[] = $piece;
						}
					}
					$statement = 'CREATE TABLE '.$table.' ('.implode(', ', $pieces).')';
					echo $statement;
					Database::Execute($statement);
				}
				$className = mb_convert_case($table, MB_CASE_TITLE);
				if (!class_exists($className))
				{
					$pieces = array();
					$keys = array_keys($category);
					foreach ($keys as $key)
					{
						if ($category[$key]['constraint'] == 'primary key')
						{
							$pieces[] = '\''.$key.'\'';
						}
					}
					$primaryKey = 'array('.implode($pieces).')';
					$classCode = str_replace(array('@class', '@table', '@primarykey'), array($className, $table, $primaryKey), $classTemplate);
					eval($classCode);
				}
			}
		}

		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------

		private $_class;

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
				if (is_callable('method'))
				{
					return call_user_func($method, $this->get($reference['reference']));
				}
				else
				{
					return null;
				}
			}
			else if (isset($this->_entity->$fieldName))
			{
				return $this->_entity->$fieldName;
			}
			else if (!is_null($entity = CreateBasesUntilField($fieldName)))
			{
				return $entity->$fieldName;
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
				if (is_callable('method'))
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
			else if (!is_null($entity = CreateBasesUntilField($fieldName)))
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
			else if (isset($this->_entity->$fieldName))
			{
				$this->_entity->$fieldName = $value;
				if ($this->_autoSave)
				{
					$this->Save();
				}
			}
			else if (!is_null($entity = CreateBasesUntilField($fieldName)))
			{
				return $entity->$fieldName = $value;
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
			else if (isset($this->entity->$fieldName))
			{
				unset($this->entity->$fieldName);
			}
			else if (!is_null($entity = CreateBasesUntilField($fieldName)))
			{
				unset($entity->$fieldName);
			}
			else
			{
				return;
			}
		}

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
		 * Internally used to create inheritance hierarchy until find a field
		 * @access private
		 */
		private function CreateBasesUntilField(/*string*/ $fieldName)
		{
			$current = $this;
			while (true)
			{
				if (isset($current->_entity->$fieldName))
				{
					return $current->_entity;
				}
				$current = $current->ProcessInheritance(true);
				if (is_null($current))
				{
					return null;
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
					$data = EntityBase::GetEntityData($class);
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

		/**
		 * Internally used to process the references defined in the entity class
		 * @access private
		 */
		private function ProcessReferences()
		{
			$result = array();
			if (is_callable($this.'::References'))
			{
				$references = call_user_func($this->_class.'::References');
			}
			else
			{
				$references = Addendum_Utility::ReadAnnotationes($this, 'Reference', array('alias', 'reference', 'method'));
			}
			foreach ($references as $reference)
			{
				$result[$reference['alias']] = $references;
			}
			$this->_references = $result;
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

			$this->ProcessReferences();
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
		// Private (Class)
		//------------------------------------------------------------

		/**
		 * Internally used to create a where condition for the primarykey.
		 * @access private
		 */
		private static function CreateWhere (/*mixed*/ $primaryKey, /*mixed*/ $primaryKeyValue)
		{
			$where = array();
			if (is_string($primaryKey))
			{
				if (is_array($primaryKeyValue))
				{
					$where = array($primaryKey => $primaryKeyValue[0]);
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

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

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

		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------

		private $_primaryKey;
		private $_record;
		private $_table;
		private $_where;

		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

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

		public function _get(/*string*/ $fieldName)
		{
			$fieldName = (string)$fieldName;
			if (!array_key_exists($fieldName, $this->_record))
			{
				$record = array();
				if (Database::ReadOneRecord($record, $this->_table, array($fieldName), $this->_where))
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
			}
			else
			{
				$record = array();
				if (Database::HasFields($this->_table, array($fieldName)))
				{
					$this->_record[$fieldName] = $value;
				}
				else
				{
					return;
				}
			}
		}

		public function __unset(/*string*/ $fieldName)
		{
			unset($this->_registro[$fieldName]);
		}

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
			return Database::ReadOneRecord($this->_record, $this->_table, null, $this->_where);
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
	$entitiesFolder = 'entities';
	function Entity_Configure()
	{
		Configuration::TryGet('paladio-paths', 'entities', $entitiesFolder);
		FileSystem::RequireAll('*.lib.php', FileSystem::FolderCore().$entitiesFolder);
		EntityBase::LoadEnts($entitiesFolder);
	}
	Configuration::Callback(array('paladio-database', 'paladio-paths'), 'Entity_Configure');
?>