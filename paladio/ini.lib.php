<?php
	/* ini.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('string_utility.lib.php');
		require_once('ini_utility.lib.php');
		require_once('pen.lib.php');
	}

	/**
	 * INI
	 * @package Paladio
	 */
	final class INI
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function ProcessCategory(/*string*/ $text, /*string*/ &$categoryName)
		{
			//ONLY UTF-8
			if (preg_match('#^\[(.+?)\]#u', $text, $match))
			{
				$categoryName = $match[1];
				return true;
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------

		private $content;

		private function _SaveFile(/*hFile*/ $hFile, /*string*/ $header)
		{
			$output = (string)$this;
			if (mb_strlen($header) > 0)
			{
				fwrite($hFile, $header);
				fwrite($hFile, "\n");
			}
			fwrite($hFile, $output);
			fflush($hFile);
		}

		private function ProcessLines(/*array*/ $lines, /*int*/ $startLine, /*array*/ $validCategories, /*bool*/ $keepCategories)
		{
			$currentCategoryName = '';
			$linesCount = count($lines);
			$useValidCategories = !is_null($validCategories) && is_array($validCategories);
			$continue = false;
			for ($index = $startLine; $index < $linesCount; $index++)
			{
				$line = trim($lines[$index]);
				if (String_Utility::StartsWith($line, ';') || String_Utility::StartsWith($line, '#'))
				{
					continue;
				}
				if ($continue)
				{
					if (INI_Utility::ProcessValue($line, $extra))
					{
						if ($keepCategories)
						{
							$this->content[$currentCategoryName][$fieldName] .= $line;
						}
						else
						{
							$this->content[''][$fieldName] .= $line;
						}
						$continue = String_Utility::StartsWith($extra, "\\");
					}
				}
				else
				{
					if (INI::ProcessCategory($line, $currentCategoryName))
					{
						if (!$useValidCategories || in_array($currentCategoryName, $validCategories))
						{
							if (!isset($this->content[$currentCategoryName]))
							{
								$this->content[$currentCategoryName] = array();
							}
						}
					}
					else
					{
						if (!$useValidCategories || in_array($currentCategoryName, $validCategories))
						{
							if (String_Utility::StartsWith($line, '@'))
							{
								if (String_Utility::StartsWith($line, '@import '))
								{
									$data = String_Utility::EnsureEnd(String_Utility::EnsureStart(trim(mb_substr($line, 8)), "return "), ";");
									$this->merge_Content(eval($data));
								}
								else
								{
									//Ignore
								}
							}
							else if (INI_Utility::ProcessLine($line, $fieldName, $fieldValue, $extra))
							{
								if (is_string($extra))
								{
									if (String_Utility::StartsWith($extra, "\\"))
									{
										$continue = true;
									}
									else
									{
										$continue = false;
									}
								}
								else if ($extra === false)
								{
									//take out comments at the end of the line
									//ONLY UTF-8
									if (preg_match('@^([^;#]*)?@u', $fieldValue, $match))
									{
										$fieldValue = $match[1];
									}
								}
								if ($keepCategories)
								{
									$this->content[$currentCategoryName][$fieldName] = PEN::Decode($fieldValue);
								}
								else
								{
									$this->content[''][$fieldName] = PEN::Decode($fieldValue);
								}
							}
							else
							{
								if ($fieldName == '')
								{
									$fieldName = $line;
								}
								if ($keepCategories)
								{
									$this->content[$currentCategoryName][$fieldName] = null;
								}
								else
								{
									$this->content[''][$fieldName] = null;
								}
							}
						}
					}
				}
			}
		}

		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		public function __toString()
		{
			$result = $this->CategoryToString('');
			$categoryNames = array_keys($this->content);
			foreach ($categoryNames as $categoryName)
			{
				if ($categoryName != '')
				{
					$result .= '['.$categoryName.']'."\n";
					$result .= $this->CategoryToString($categoryName);
				}
			}
			return $result;
		}

		/**
		 * Creates a string that has the contents of the category with the name $categoryName.
		 *
		 * Note: The values of the fields are encoded as PEN.
		 *
		 * Returns a string with the contents of the category indentified with the name $category name if the category is available, false otherwise.
		 *
		 * @access public
		 * @return string
		 */
		public function CategoryToString(/*string*/ $categoryName)
		{
			if (array_key_exists($categoryName, $this->content))
			{
				$characters = array('\0', '\t', '\r', '\n', "\\", '"', "'", ';', '#');
				$category = $this->content[$categoryName];
				$fieldNames = array_keys($category);
				$result = '';
				foreach ($fieldNames as $fieldName)
				{
					$fieldValue = PEN::Encode($category[$fieldName]);
					$result .= $fieldName.' = '.$fieldValue."\n";
				}
				return $result;
			}
			else
			{
				return false;
			}
		}

		/**
		 * Resets the contents of the INI instance leaving it emtpy.
		 *
		 * @access public
		 * @return true
		 */
		public function Clear()
		{
			$this->content = array(array());
			return true;
		}

		/**
		 * Loads the contents of the file $file from the line $starLine.
		 *
		 * Note 1: if $validCategories is an array of string, only the categories with a name that's in $validCategories are loaded.
		 * Note 2: if $keepCategories is false, all the fields are loaded to the category with name "".
		 *
		 * If $file is a file: Loads the contents of the file.
		 * Otherwise: does nothing.
		 *
		 * Returns true if the file $file is loaded, false otherwise.
		 *
		 * @access public
		 * @return true
		 */
		public function Load(/*string*/ $file, /*int*/ $startLine, /*array*/ $validCategories = null, /*bool*/ $keepCategories = true)
		{
			if (!is_string($file) || mb_strlen($file) == 0 || !is_file($file))
			{
				return false;
			}
			else
			{
				if (!isset($this->content))
				{
					$this->Clear();
				}
				$this->ProcessLines(file($file), $startLine, $validCategories, $keepCategories);
				return true;
			}
		}

		/**
		 * Writes the content of the INI instance to the file $file.
		 *
		 * Note 1: the value of $header is prepended to the contents of the INI instance.
		 * Note 2: the values of the fields are converted to string before storing them.
		 * Note 3: $file is expected to be a valid file, no check is performed.
		 * Note 4: write permissions are required.
		 *
		 * @access public
		 * @return void
		 */
		public function Save(/*string*/ $file, /*string*/ $header = '')
		{
			$file = fopen($file, 'wb');
			$this->_SaveFile($file, $header);
			fclose ($file);
		}

		//------------------------------------------------------------

		public function get_Category(/*string*/ $categoryName)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			if (array_key_exists($categoryName, $this->content))
			{
				return $this->content[$categoryName];
			}
			else
			{
				return null;
			}
		}

		public function isset_Category(/*string*/ $categoryName)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			if (array_key_exists($categoryName, $this->content))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		public function set_Category(/*string*/ $categoryName, /*array*/ $value)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			if (is_array($value))
			{
				$this->unset_Category($categoryName);
				$keys = array_keys($value);
				foreach ($keys as $key)
				{
					$val = $value[$key];
					$this->set_Field($categoryName, $key, $val);
				}
				return true;
			}
			else
			{
				return false;
			}
		}

		public function unset_Category(/*string*/ $categoryName)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			unset($this->content[$categoryName]);
		}

		//------------------------------------------------------------

		public function get_Content()
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			return $this->contenido;
		}

		public function set_Content(/*array*/ $value)
		{
			$this->Clear();
			$this->merge_Content($value);
		}

		public function merge_Content(/*array*/ $value)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			if (is_array($value))
			{
				$keys = array_keys($value);
				foreach ($keys as $key)
				{
					$val = $value[$key];
					if (is_array($val))
					{
						$this->set_Category($key, $val);
					}
					else
					{
						$this->set_Field('', $key, $val);
					}
				}
				return true;
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------

		public function get_Field(/*string*/ $categoryName, /*string*/ $fieldName)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			if ($this->isset_Field($categoryName, $fieldName))
			{
				return $this->content[$categoryName][$fieldName];
			}
			else
			{
				return null;
			}
		}

		public function isset_Field(/*string*/ $categoryName, /*string*/ $fieldName)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			if (array_key_exists($categoryName, $this->content))
			{
				if (array_key_exists($fieldName, $this->content[$categoryName]))
				{
					return true;
				}
			}
			else
			{
				return false;
			}
		}

		public function set_Field(/*string*/ $categoryName, /*string*/ $fieldName, /*mixed*/ $value)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			$this->content[$categoryName][$fieldName] = $value;
			return true;
		}

		public function unset_Field(/*string*/ $categoryName, /*string*/ $fieldName)
		{
			if (!isset($this->content))
			{
				$this->Clear();
			}
			if (array_key_exists($categoryName, $this->content))
			{
				unset($this->content[$categoryName][$fieldName]);
				return true;
			}
			else
			{
				return false;
			}
		}

		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------

		public function __construct(/*string*/ $file = null, /*int*/ $startLine = null, /*array*/ $validCategories = null, /*bool*/ $keepCategories = true)
		{
			$this->Load($file, $startLine, $validCategories, $keepCategories);
		}
	}
?>