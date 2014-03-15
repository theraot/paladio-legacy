<?php
	/* parser.lib.php by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}

	final class Parser
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function _ConsumeToPosition(/*string*/ $input, /*int*/ $offset, /*int*/ $length, /*int*/ $position, /*int*/ &$consumedLength)
		{
			if ($position == $offset)
			{
				$consumedLength = 0;
				return '';
			}
			else
			{
				if ($position < $offset)
				{
					throw new Exception('Cannot unconsume');
				}
				else
				{
					$consumedLength = $position - $offset;
					return substr($input, $offset, $consumedLength);
				}
			}
		}

		private static function _Consume(/*string*/ $input, /*int*/ $offset, /*int*/ $length, /*mixed*/ $what = null, /*int*/ &$consumedLength)
		{
			if ($what === null)
			{
				$chunk = substr($input, $offset, 6);
				$result = mb_substr($chunk, 0, 1);
				$len = strlen($result);
				if ($len > 0)
				{
					$consumedLength = $len;
					return $result;
				}
				else
				{
					return null;
				}
			}
			else
			{
				if (is_string($what))
				{
					$item = $what;
					$len = strlen($item);
					if ($offset + $len <= $length)
					{
						$result = substr($input, $offset, $len);
						if ($result == $item)
						{
							$consumedLength = $len;
							return $result;
						}
					}
					return null;
				}
				else if (is_numeric($what))
				{
					$chunk = substr($input, $offset, $len * 6);
					$result = mb_substr($chunk, 0, $what);
					$len = strlen($result);
					if ($offset + $len <= $length)
					{
						$consumedLength = $len;
						return $result;
					}
					else
					{
						return null;
					}
				}
				else if (is_array($what))
				{
					if ($offset < $length)
					{
						foreach ($what as $item)
						{
							$len = strlen($item);
							if ($offset + $len <= $length)
							{
								$result = substr($input, $offset, $len);
								if ($result == $item)
								{
									$consumedLength = $len;
									return $result;
								}
							}
						}
					}
					return null;
				}
				else
				{
					return null;
				}
			}
		}

		private static function _ConsumeCallback(/*string*/ $input, /*int*/ $offset, /*int*/ $length, /*function*/ $callback = null, /*int*/ &$consumedLength)
		{
			if (is_callable($callback))
			{
				$chunk = substr($input, $offset, 6);
				$result = mb_substr($chunk, 0, 1);
				$len = strlen($result);
				if ($len > 0 && call_user_func($callback, $result))
				{
					$consumedLength = $len;
					return $result;
				}
				else
				{
					return null;
				}
			}
			else
			{
				return null;
			}
		}

		private static function _ConsumeUntil(/*string*/ $input, /*int*/ $offset, /*int*/ $length, /*mixed*/ $what, /*int*/ &$consumedLength)
		{
			if ($offset < $length)
			{
				if (is_string($what))
				{
					if (strlen($what) > 0)
					{
						if (($position = strpos($input, $what, $offset)) !== false)
						{
							return Parser::_ConsumeToPosition($input, $offset, $length, $position, $consumedLength);
						}
						else
						{
							$consumedLength = $length - $offset;
							return substr($input, $offset);
						}
					}
					else
					{
						$consumedLength = $length - $offset;
						return substr($input, $offset);
					}
				}
				else if (is_array($what))
				{
					$whats = $what;
					$bestPosition = 0;
					$all = true;
					foreach ($whats as $what)
					{
						if (is_string($what) && strlen($what) > 0 && ($position = strpos($input, $what, $offset)) !== false)
						{
							if ($all || $position < $bestPosition)
							{
								$bestPosition = $position;
								$all = false;
							}
						}
					}
					if ($all)
					{
						$consumedLength = $length - $offset;
						return substr($input, $offset);
					}
					else
					{
						return Parser::_ConsumeToPosition($input, $offset, $length, $bestPosition, $consumedLength);
					}
				}
				else
				{
					return '';
				}
			}
			else
			{
				return '';
			}
		}

		private static function _ConsumeUntilCallback(/*string*/ $input, /*int*/ $offset, /*int*/ $length, /*function*/ $callback, /*int*/ &$consumedLength)
		{
			$consumedLength = 0;
			if ($offset < $length)
			{
				if (is_callable($callback))
				{
					$result = '';
					while (true)
					{
						$chunk = substr($input, $offset, 6);
						$_input = mb_substr($chunk, 0, 1);
						if ($_input === '')
						{
							return $result;
						}
						else
						{
							if (!call_user_func($callback, $_input))
							{
								$len = strlen($_input);
								$consumedLength += $len;
								$offset += $len;
								$result .= $_input;
							}
							else
							{
								break;
							}
						}
					}
				}
				else
				{
					return '';
				}
			}
			else
			{
				return '';
			}
		}

		private static function _ConsumeWhile(/*string*/ $input, /*int*/ $offset, /*int*/ $length, /*mixed*/ $what, /*int*/ &$consumedLength)
		{
			$consumedLength = 0;
			if ($offset < $length)
			{
				$result = '';
				if (is_string($what))
				{
					while (true)
					{
						$chunk = substr($input, $offset, 6);
						$_input = mb_substr($chunk, 0, 1);
						if ($_input == $what)
						{
							$len = strlen($_input);
							$consumedLength += $len;
							$offset += $len;
							$result .= $_input;
						}
						else
						{
							break;
						}
					}
				}
				else if (is_array($what))
				{
					while (true)
					{
						$chunk = substr($input, $offset, 6);
						$_input = mb_substr($chunk, 0, 1);
						if (in_array($_input, $what))
						{
							$len = strlen($_input);
							$consumedLength += $len;
							$offset += $len;
							$result .= $_input;
						}
						else
						{
							break;
						}
					}
				}
				return $result;
			}
			else
			{
				return '';
			}
		}

		private static function _ConsumeWhileCallback(/*string*/ $input, /*int*/ $offset, /*int*/ $length, /*function*/ $callback, /*int*/ &$consumedLength)
		{
			$consumedLength = 0;
			if ($offset < $length)
			{
				if (is_callable($callback))
				{
					$result = '';
					while (true)
					{
						$chunk = substr($input, $offset, 6);
						$_input = mb_substr($chunk, 0, 1);
						if ($_input === '')
						{
							return $result;
						}
						else
						{
							if (call_user_func($callback, $_input))
							{
								$len = strlen($_input);
								$consumedLength += $len;
								$offset += $len;
								$result .= $_input;
							}
							else
							{
								break;
							}
						}
					}
				}
				else
				{
					return '';
				}
			}
			else
			{
				return '';
			}
		}


		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function StringConsume(/*string*/ $input, /*int*/ $offset, /*mixed*/ $what = null, /*int*/ &$consumedLength = null)
		{
			if (is_string($input))
			{
				return Parser::_Consume($input, $offset, strlen($input), $what, $consumedLength);
			}
			else
			{
				throw new Exception('expected input as string');
			}
		}


		public static function StringConsumeCallback(/*string*/ $input, /*int*/ $offset, /*mixed*/ $callback = null, /*int*/ &$consumedLength = null)
		{
			if (is_string($input))
			{
				return Parser::_ConsumeCallback($input, $offset, strlen($input), $callback, $consumedLength);
			}
			else
			{
				throw new Exception('expected input as string');
			}
		}

		public static function StringConsumeUntil(/*string*/ $input, /*int*/ $offset, /*mixed*/ $what = null, /*int*/ &$consumedLength = null)
		{
			if (is_string($input))
			{
				return Parser::_ConsumeUntil($input, $offset, strlen($input), $what, $consumedLength);
			}
			else
			{
				throw new Exception('expected input as string');
			}
		}
		
		public static function StringConsumeUntilCallback(/*string*/ $input, /*int*/ $offset, /*mixed*/ $callback = null, /*int*/ &$consumedLength = null)
		{
			if (is_string($input))
			{
				return Parser::_ConsumeUntilCallback($input, $offset, strlen($input), $callback, $consumedLength);
			}
			else
			{
				throw new Exception('expected input as string');
			}
		}

		public static function StringConsumeWhile(/*string*/ $input, /*int*/ $offset, /*mixed*/ $what = null, /*int*/ &$consumedLength = null)
		{
			if (is_string($input))
			{
				return Parser::_ConsumeWhile($input, $offset, strlen($input), $what, $consumedLength);
			}
			else
			{
				throw new Exception('expected input as string');
			}
		}
		
		public static function StringConsumeWhileCallback(/*string*/ $input, /*int*/ $offset, /*mixed*/ $callback = null, /*int*/ &$consumedLength = null)
		{
			if (is_string($input))
			{
				return Parser::_ConsumeWhileCallback($input, $offset, strlen($input), $callback = $consumedLength);
			}
			else
			{
				throw new Exception('expected input as string');
			}
		}

		//------------------------------------------------------------
		// Private (Instance)
		//------------------------------------------------------------

		private $document;
		private $documentPosition;
		private $documentSize;

		private function ConsumeToPosition(/*int*/ $position)
		{
			if (isset($this->document))
			{
				$result = Parser::_ConsumeToPosition($this->document, $this->documentPosition, $this->documentSize, $position, $consumedLength);
				if ($result !== null)
				{
					$this->documentPosition += $consumedLength;
				}
				return $result;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		//------------------------------------------------------------
		// Public (Instance)
		//------------------------------------------------------------

		public function Close()
		{
			unset($document);
			unset($documentPosition);
			unset($documentSize);
		}

		public function CanConsume()
		{
			if (isset($this->document))
			{
				return $this->documentPosition < $this->documentSize;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function Consumed()
		{
			if (isset($this->document))
			{
				return substr($this->document, 0, $this->documentPosition);
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function Flush()
		{
			if (isset($this->document))
			{
				$document = $this->NotConsumed();
				$this->document = $document;
				$this->documentSize = strlen($document);
				$this->documentPosition = 0;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function NotConsumed()
		{
			if (isset($this->document))
			{
				return substr($this->document, $this->documentPosition);
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function Consume(/*mixed*/ $what = null)
		{
			if (isset($this->document))
			{
				$result = Parser::_Consume($this->document, $this->documentPosition, $this->documentSize, $what, $consumedLength);
				if ($result !== null)
				{
					$this->documentPosition += $consumedLength;
				}
				return $result;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeAll()
		{
			if (isset($this->document))
			{
				$position = $this->documentPosition;
				$this->documentPosition = $this->documentSize;
				return substr($this->document, $position);
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeCallback(/*function*/ $callback = null)
		{
			if (isset($this->document))
			{
				$result = Parser::_Consume($this->document, $this->documentPosition, $this->documentSize, $callback, $consumedLength);
				if ($result !== null)
				{
					$this->documentPosition += $consumedLength;
				}
				return $result;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeUntil(/*mixed*/ $what)
		{
			if (isset($this->document))
			{
				$result = Parser::_ConsumeUntil($this->document, $this->documentPosition, $this->documentSize, $what, $consumedLength);
				if ($result !== null)
				{
					$this->documentPosition += $consumedLength;
				}
				return $result;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeUntilCallback(/*function*/ $callback)
		{
			if (isset($this->document))
			{
				$result = Parser::_ConsumeUntilCallback($this->document, $this->documentPosition, $this->documentSize, $callback, $consumedLength);
				if ($result !== null)
				{
					$this->documentPosition += $consumedLength;
				}
				return $result;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeWhile(/*mixed*/ $what)
		{
			if (isset($this->document))
			{
				$result = Parser::_ConsumeWhile($this->document, $this->documentPosition, $this->documentSize, $what, $consumedLength);
				if ($result !== null)
				{
					$this->documentPosition += $consumedLength;
				}
				return $result;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeWhileCallback(/*function*/ $callback)
		{
			if (isset($this->document))
			{
				$result = Parser::_ConsumeUWhileCallback($this->document, $this->documentPosition, $this->documentSize, $callback, $consumedLength);
				if ($result !== null)
				{
					$this->documentPosition += $consumedLength;
				}
				return $result;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function Peek(/*mixed*/ $what = null)
		{
			if (isset($this->document))
			{
				return Parser::_Consume($this->document, $this->documentPosition, $this->documentSize, $what, $consumedLength);
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function Reset()
		{
			if (isset($this->document))
			{
				$this->documentPosition = 0;
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		//------------------------------------------------------------
		// Public (Constructor)
		//------------------------------------------------------------

		public function __construct(/*string*/ $document)
		{
			if (is_string($document))
			{
				$this->document = $document;
				$this->documentSize = strlen($document);
				$this->documentPosition = 0;
			}
			else
			{
				throw new Exception('document must be string');
			}
		}

		public function __destruct()
		{
			$this->Close();
		}
	}
?>