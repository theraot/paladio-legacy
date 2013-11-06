<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	final class Data
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function _AccessTable($table, $fields, $method, $data, $where)
		{
			if (class_exists('Database'))
			{
				if ($method == 'READ')
				{
					//Query items
					return Iteration::ListRecords(Database::Read($table, $fields, $where), $fields);
				}
				else if ($method == 'WRITE')
				{
					//Insert or Update items
					return Database::Write($data, $table, $where);
				}
				else if ($method == 'INSERT')
				{
					//Insert items
					return Database::Insert($data, $table);
				}
				else if ($method == 'UPDATE')
				{
					//Update items
					return Database::Update($data, $table, $where);
				}
				else if ($method == 'DELETE')
				{
					//Remove items
					return Database::Delete($table, $where);
				}
			}
			return false;
		}
		
		private static function GetJson(/*string*/ $key, /*array*/ $parameters, /*mixed*/ $fallback)
		{
			if (array_key_exists($key, $parameters))
			{
				//ONLY UTF-8
				return json_decode($parameters[$key], true);
			}
			else
			{
				return $fallback;
			}
		}
		
		private static function GetString(/*string*/ $key, /*array*/ $parameters, /*mixed*/ $fallback)
		{
			if (array_key_exists($key, $parameters))
			{
				return Utility::Sanitize($parameters[$key], 'html');
			}
			else
			{
				return $fallback;
			}
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function Access($entity, $method, $parameters = null, $extraWhere = null, $allowedFields = null, $allowedMethod = null)
		{
			if (!is_array($parameters))
			{
				$parameters = array();
			}
			$method = Data::GetString('_method', $parameters, $method);
			$data = Data::GetJson('_data', $parameters, null);
			$where = Data::GetJson('_where', $parameters, null);
			if
			(
				(
					is_null($allowedMethod)
				) ||
				(
					is_array
					(
						$allowedMethod
					) &&
					in_array
					(
						$method,
						$allowedMethod
					)
				) ||
				(
					is_string($allowedMethod) &&
					$method == $allowedMethod
				)
			)
			{
				if (is_array($where) && is_array($extraWhere))
				{
					if (class_exists('Database_Utility'))
					{
						$where = Database_Utility::MergeWheres($where, $extraWhere);
					}
					else
					{
						$where = array();
					}
				}
				else
				{
					$where = $extraWhere;
				}
				if (is_array($data) && is_array($allowedFields))
				{
					$data = Utility::ArrayTake($data, $allowedFields);
				}
				return Data::_AccessTable($entity, $allowedFields, $method, $data, $where);
			}
		}

		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------

		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
?>