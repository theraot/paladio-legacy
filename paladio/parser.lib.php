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
		// Private (Instance)
		//------------------------------------------------------------

		private $document;
		private $documentPosition;
		private $documentSize;

		private function ConsumeToPosition(/*int*/ $position)
		{
			if (isset($this->document))
			{
				if ($position == $this->documentPosition)
				{
					return '';
				}
				else
				{
					if ($position < $this->documentPosition)
					{
						throw new Exception('Cannot unconsume');
					}
					else
					{
						$documentPosition = $this->documentPosition;
						$this->documentPosition = $position;
						return substr($this->document, $documentPosition, $position - $documentPosition);
					}
				}
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
				if (func_num_args() == 0)
				{
					$chunk = substr($this->document, $this->documentPosition, 6);
					$result = mb_substr($chunk, 0, 1);
					$len = strlen($result);
					if ($len > 0)
					{
						$this->documentPosition += $len;
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
						if ($this->documentPosition + $len <= $this->documentSize)
						{
							$result = substr($this->document, $this->documentPosition, $len);
							if ($result == $item)
							{
								$this->documentPosition += $len;
								return $result;
							}
						}
						return null;
					}
					else if (is_numeric($what))
					{
						$chunk = substr($this->document, $this->documentPosition, $len * 6);
						$result = mb_substr($chunk, 0, $what);
						$len = strlen($result);
						if ($this->documentPosition + $len <= $this->documentSize)
						{
							$this->documentPosition += $len;
							return $result;
						}
						else
						{
							return null;
						}
					}
					else if (is_array($what))
					{
						if ($this->documentPosition < $this->documentSize)
						{
							foreach ($what as $item)
							{
								$len = strlen($item);
								if ($this->documentPosition + $len <= $this->documentSize)
								{
									$result = substr($this->document, $this->documentPosition, $len);
									if ($result == $item)
									{
										$this->documentPosition += $len;
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
				if (is_callable($callback))
				{
					$chunk = substr($this->document, $this->documentPosition, 6);
					$result = mb_substr($chunk, 0, 1);
					$len = strlen($result);
					if ($len > 0 && call_user_func($callback, $result))
					{
						$this->documentPosition += $len;
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
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeUntil(/*mixed*/ $what)
		{
			if (isset($this->document))
			{
				if ($this->CanConsume())
				{
					if (is_string($what))
					{
						if (strlen($what) > 0)
						{
							if (($position = strpos($this->document, $what, $this->documentPosition)) !== false)
							{
								return $this->ConsumeToPosition($position);
							}
							else
							{
								return $this->ConsumeAll();
							}
						}
						else
						{
							return $this->ConsumeAll();
						}
					}
					else if (is_array($what))
					{
						$whats = $what;
						$bestPosition = 0;
						$all = true;
						foreach ($whats as $what)
						{
							if (is_string($what) && strlen($what) > 0 && ($position = strpos($this->document, $what, $this->documentPosition)) !== false)
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
							return $this->ConsumeAll();
						}
						else
						{
							return $this->ConsumeToPosition($bestPosition);
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
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeUntilCallback(/*function*/ $callback)
		{
			if (isset($this->document))
			{
				if ($this->CanConsume())
				{
					if (is_callable($callback))
					{
						$result = '';
						while (true)
						{
							$input = $this->Peek();
							if (input === null)
							{
								return $result;
							}
							else
							{
								if (!call_user_func($callback, $input))
								{
									$this->Consume();
									$result .= $input;
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
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeWhile(/*mixed*/ $what)
		{
			if (isset($this->document))
			{
				if ($this->CanConsume())
				{
					$result = '';
					if (is_string($what))
					{
						while (($input = $this->Peek()) == $what)
						{
							$this->Consume();
							$result .= $what;
						}
					}
					else if (is_array($what))
					{
						$continue = true;
						while ($continue)
						{
							$input = $this->Peek();
							if (in_array($input, $what))
							{
								$this->Consume();
								$result .= $input;
							}
							else
							{
								$continue = false;
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
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeWhileCallback(/*function*/ $callback)
		{
			if (isset($this->document))
			{
				if ($this->CanConsume())
				{
					if (is_callable($callback))
					{
						$result = '';
						while (true)
						{
							$input = $this->Peek();
							if (input === null)
							{
								return $result;
							}
							else
							{
								if (call_user_func($callback, $input))
								{
									$this->Consume();
									$result .= $input;
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
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function Peek(/*mixed*/ $what = null)
		{
			if (isset($this->document))
			{
				if (func_num_args() == 0)
				{
					$chunk = substr($this->document, $this->documentPosition, 6);
					$result = mb_substr($chunk, 0, 1);
					$len = strlen($result);
					if ($len > 0)
					{
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
						if ($this->documentPosition + $len <= $this->documentSize)
						{
							$result = substr($this->document, $this->documentPosition, $len);
							if ($result == $item)
							{
								return $result;
							}
						}
						return null;
					}
					else if (is_numeric($what))
					{
						$chunk = substr($this->document, $this->documentPosition, $what * 6);
						$result = mb_substr($chunk, 0, $what);
						$len = strlen($result);
						if ($this->documentPosition + $len <= $this->documentSize)
						{
							return $result;
						}
						else
						{
							return null;
						}
					}
					else if (is_array($what))
					{
						if ($this->documentPosition < $this->documentSize)
						{
							foreach ($what as $item)
							{
								$len = strlen($item);
								if ($this->documentPosition + $len <= $this->documentSize)
								{
									$result = substr($this->document, $this->documentPosition, $len);
									if ($result == $item)
									{
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