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
		require_once('parser.lib.php');
		require_once('pen.lib.php');
	}
	//TODO: allow importing another INI [low priority]

	/**
	 * INI
	 * @package Paladio
	 */
	final class INI
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function _Merge_Category($category, $newCategory, $overwrite)
		{
			if (!is_array($category))
			{
				$category = array();
			}
			if (is_array($newCategory))
			{
				$keys = array_keys($newCategory);
				foreach ($keys as $key)
				{
					$val = $newContent[$key];
					if (is_array($val))
					{
						if ($overwrite || !array_key_exists($key, $category))
						{
							$content[$key] = $val;
						}
					}
				}
			}
			return $category;
		}

		private static function _Merge_Content($content, $newContent, $overwrite)
		{
			if (!is_array($content))
			{
				$content = array();
			}
			if (is_array($newContent))
			{
				$keys = array_keys($newContent);
				foreach ($keys as $key)
				{
					$val = $newContent[$key];
					if (is_array($val))
					{
						if ($overwrite || !array_key_exists($key, $content))
						{
							$content[$key] = $val;
						}
						else
						{
							$content[$key] = _Merge_Category($content[$key], $val, $overwrite);
						}
					}
					else
					{
						if (!array_key_exists('', $content) || !is_array($content['']))
						{
							$content[''] = array();
						}
						if ($overwrite || !array_key_exists($key, $content['']))
						{
							$content[''][$key] = $val;
						}
					}
				}
			}
			return $content;
		}

		private static function ProcessCategory(/*string*/ $parser, /*string*/ &$categoryName)
		{
			if ($parser->Consume('[') !== null)
			{
				$categoryName = $parser->ConsumeUntil(']');
				$parser->Consume(']');
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
		private $sync;
		
		public function _LoadFile(/*string*/ $file)
		{
			if (!is_array($this->content))
			{
				$this->Clear();
			}
			$this->Process(new Parser(file_get_contents($file)));
		}

		private function _SaveFile(/*hFile*/ $hFile, /*string*/ $header)
		{
			$output = (string)$this;
			if (strlen($header) > 0)
			{
				fwrite($hFile, $header);
				fwrite($hFile, "\n");
			}
			fwrite($hFile, $output);
			fflush($hFile);
		}

		private function ProcessValue(/*Parser*/ $parser, /*string*/ &$value)
		{
			if ($result = PEN::ConsumeQuotedString($parser, true))
			{
				return $result;
			}
			else
			{
				return $parser->ConsumeUntil(array("\n", "\r"));
			}
		}

		private function Process(/*Parser*/ $parser)
		{
			$this->sync = false;
			$whitespace = array(' ', "\t");
			$whitespaceOrNewLine = array(' ', "\t", "\r", "\n");
			$unquotedStringEnd = array(' ', "\t", "\n", "\r", '=', ';', '#');
			$newLine = array("\r", "\n");
			$currentCategoryName = '';
			$categoryCreated = true;
			$continue = false;
			while($parser->CanConsume())
			{
				PEN::ConsumeWhitespace($parser, true); $parser->Flush();
				if ($parser->Consume('<?php') !== null)
				{
					$parser->ConsumeUntil('?>');
					$parser->Consume('?>');
					continue;
				}
				if (INI::ProcessCategory($parser, $currentCategoryName))
				{
					if (!array_key_exists($currentCategoryName, $this->content))
					{
						$categoryCreated = false;
					}
					continue;
				}
				else
				{
					if ($parser->Consume('@') !== null)
					{
						if ($parser->Consume('import') !== null)
						{
							PEN::ConsumeWhitespace($parser, false);  $parser->Flush();
							if ($parser->Consume('<?php') !== null)
							{
								$data = $parser->ConsumeUntil('?>');
								$parser->Consume('?>');
								$this->merge_Category($currentCategoryName, eval($data), true);
							}
							else
							{
								trigger_error('INI: Unknown directive: '.$parser->ConsumeUntil($newLine), E_USER_WARNING);
							}
						}
						else if ($parser->Consume('option') !== null)
						{
							if ($currentCategoryName === '')
							{
								PEN::ConsumeWhitespace($parser, false);  $parser->Flush();
								trigger_error('INI: Ignored option directive: '.$parser->ConsumeUntil($newLine), E_USER_NOTICE);
							}
							else
							{
								trigger_error('INI: option directives must be at the begginin of the file');
							}
						}
						else
						{
							trigger_error('INI: Ignored directive: '.$parser->ConsumeUntil($newLine), E_USER_NOTICE);
						}
						$didNewLine = PEN::ConsumeWhitespace($parser, true); $parser->Flush();
						if (!$didNewLine)
						{
							$unexpected = $parser->ConsumeUntil($newLine);
							trigger_error('INI: Unexpected: '.$unexpected, E_USER_WARNING);
						}
						continue;
					}
					else if (($fieldName = PEN::ConsumeQuotedString($parser, false)) === null)
					{
						$fieldName = $parser->ConsumeUntil($unquotedStringEnd);
					}
					$parser->ConsumeWhile($whitespace);
					if ($fieldName !== '')
					{
						if ($parser->Consume('=') !== null)
						{
							$fieldValue = PEN::ConsumeValue($parser, null, true);
							if ($categoryCreated)
							{
								$this->content[$currentCategoryName][$fieldName] = $fieldValue;
							}
							else
							{
								$this->content[$currentCategoryName] = array($fieldName => $fieldValue);
								$categoryCreated = true;
							}
						}
						else
						{
							if ($categoryCreated)
							{
								$this->content[$currentCategoryName][$fieldName] = null;
							}
							else
							{
								$this->content[$currentCategoryName] = array($fieldName => null);
								$categoryCreated = true;
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
				if ($categoryName !== '')
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
			$this->content = array();
			return true;
		}

		/**
		 * Loads the contents of the file $file.
		 *
		 * If $file is a file: Loads the contents of the file.
		 * Otherwise: does nothing.
		 *
		 * Returns true if the file $file is loaded, false otherwise.
		 *
		 * @access public
		 * @return true
		 */
		public function Load(/*string*/ $file)
		{
			$ini = new INI($file);
			$this->merge_Content($ini->get_Content(), true);
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
			if (!is_array($this->content))
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
			if (!is_array($this->content))
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

		public function merge_Category(/*string*/ $categoryName, /*array*/ $value, /*boolean*/ $overwrite)
		{
			if (!is_array($this->content))
			{
				$this->Clear();
			}
			if (is_array($value))
			{
				if (!array_key_exists($categoryName, $this->content) || !is_array($this->content[$categoryName]))
				{
					$this->content[$categoryName] = array();
				}
				$this->content[$categoryName] = INI::_Merge_Category($this->content[$categoryName], $value, $overwrite);
				return true;
			}
			else
			{
				return false;
			}
		}

		public function set_Category(/*string*/ $categoryName, /*array*/ $value)
		{
			if (!is_array($this->content))
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
			if (!is_array($this->content))
			{
				$this->Clear();
			}
			unset($this->content[$categoryName]);
		}

		//------------------------------------------------------------

		public function get_Content()
		{
			if (!is_array($this->content))
			{
				$this->Clear();
			}
			return $this->content;
		}

		public function set_Content(/*array*/ $value)
		{
			$this->Clear();
			$this->merge_Content($value, true);
		}

		public function merge_Content(/*array*/ $value, /*boolean*/ $overwrite)
		{
			if (!is_array($this->content))
			{
				$this->Clear();
			}
			if (is_array($value))
			{
				$this->content = INI::_Merge_Content($this->content, $value, $overwrite);
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
			if (!is_array($this->content))
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
			if (!is_array($this->content))
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
			if (!is_array($this->content))
			{
				$this->Clear();
			}
			$this->content[$categoryName][$fieldName] = $value;
			return true;
		}

		public function unset_Field(/*string*/ $categoryName, /*string*/ $fieldName)
		{
			if (!is_array($this->content))
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

		public function __construct(/*string*/ $file = null, /*bool*/ $skipCache = false)
		{
			if ($file !== null)
			{
				$file = realpath($file);
				$this->_LoadFile($file);
			}
		}
	}
?>