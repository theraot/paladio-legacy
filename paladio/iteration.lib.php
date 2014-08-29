<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	/**
	 * Iteration
	 * @package Paladio
	 */
	final class Iteration
	{

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function Count(/*iterator*/ $iterator)
		{
			$result = 0;
			foreach($iterator as $record)
			{
				$result++;
			}
			return $result;
		}

		public static function OnEach(/*iterator*/ $iterator, /*function*/ $callback)
		{
			if ($iterator === false)
			{
				return false;
			}
			else if ($iterator === null)
			{
				return null;
			}
			else if ($iterator instanceof Iterator)
			{
				if (is_callable($callback))
				{
					$extra = null;
					$use_extra = func_num_args() > 2;
					if ($use_extra)
					{
						$extra = array_slice(func_get_args(), 2);
					}
					foreach($iterator as $record)
					{
						if ($record === null)
						{
							break;
						}
						else
						{
							if ($use_extra)
							{
								call_user_func_array($callback, array_merge(array($record), $extra));
							}
							else
							{
								call_user_func_array($callback, array($record));
							}
						}
					}
				}
				else
				{
					throw new Exception('invalid callback');
				}
			}
			else
			{
				throw new Exception('Invalid Iterator');
			}
		}

		public static function Graph($iterator, /*string*/ $sourceKey, /*string*/ $targetKey)
		{
			if ($iterator === false)
			{
				return false;
			}
			else if ($iterator === null)
			{
				return null;
			}
			else if ($iterator instanceof Iterator)
			{
				$result = array();
				foreach($iterator as $record)
				{
					if ($record === null)
					{
						break;
					}
					else
					{
						$source = $record[$sourceKey];
						$target = $record[$targetKey];
						if (!array_key_exists($source, $result))
						{
							$node = new GraphNode();
							$node->id = $source;
							$result[$source] = $node;
						}
						if (!array_key_exists($target, $result))
						{
							$node = new GraphNode();
							$node->id = $target;
							$result[$target] = $node;
						}
						$sourceNode = $result[$source];
						$targetNode = $result[$target];
						$sourceNode->outgoing[] = $targetNode;
						$targetNode->incoming[] = $sourceNode;
					}
				}
				return $result;
			}
			else
			{
				throw new Exception('Invalid Iterator');
			}
		}

		public static function GetRecords($iterator)
		{
			if ($iterator === false)
			{
				return false;
			}
			else if ($iterator === null)
			{
				return null;
			}
			else if ($iterator instanceof Iterator)
			{
				$result = array();
				foreach($iterator as $record)
				{
					if ($record === null)
					{
						break;
					}
					else
					{
						$result[] = $record;
					}
				}
				return $result;
			}
			else
			{
				throw new Exception('Invalid Iterator');
			}
		}

		public static function ListRecords($iterator, /*mixed*/ $key)
		{
			if ($iterator === false)
			{
				return false;
			}
			else if ($iterator === null)
			{
				return null;
			}
			else if ($iterator instanceof Iterator)
			{
				$result = array();
				if (is_array($key))
				{
					foreach($iterator as $record)
					{
						if ($record === null)
						{
							break;
						}
						else
						{
							$newEntry = array();
							foreach($key as $keyItem)
							{
								$newEntry[$keyItem] = $record[$keyItem];
							}
							$result[] = $newEntry;
						}
					}
				}
				else
				{
					foreach($iterator as $record)
					{
						if ($record === null)
						{
							break;
						}
						else
						{
							$result[] = $record[$key];
						}
					}
				}
				return $result;
			}
			else
			{
				throw new Exception('Invalid Iterator');
			}
		}

		public static function MapRecords($iterator, /*string*/ $keyKey, /*mixed*/ $keyValue)
		{
			if ($iterator === false)
			{
				return false;
			}
			else if ($iterator === null)
			{
				return null;
			}
			else if ($iterator instanceof Iterator)
			{
				$result = array();
				if (is_array($keyValue))
				{
					foreach ($iterator as $record)
					{
						if ($record === null)
						{
							break;
						}
						else
						{
							$newEntry = array();
							foreach($keyValue as $keyItem)
							{
								$newEntry[$keyItem] = $record[$keyItem];
							}
							$result[$record[$keyKey]] = $newEntry;
						}
					}
				}
				else
				{
					foreach($iterator as $record)
					{
						if ($record === null)
						{
							break;
						}
						else
						{
							$result[$record[$keyKey]] = $record[$keyValue];
						}
					}
				}
				return $result;
			}
			else
			{
				throw new Exception('Invalid Iterator');
			}
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		/**
		 * Creating instances of this class is not allowed.
		 */
		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}

	/**
	 * GraphNode
	 * @package Paladio
	 */
	final class GraphNode
	{
		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		/**
		 * The id of the node
		 */
		public $id;

		/**
		 * The incomming relationships
		 */
		public $incoming;

		/**
		 * The outgoing relationships
		 */
		public $outgoing;
	}
?>