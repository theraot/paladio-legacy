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
				return mb_substr($this->document, 0, $this->documentPosition);
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
				return mb_substr($this->document, $this->documentPosition);
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
					if ($this->documentPosition + 1 <= $this->documentSize)
					{
						$result = mb_substr($this->document, $this->documentPosition, 1);
						$this->documentPosition += 1;
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
						$len = mb_strlen($item);
						if ($this->documentPosition + $len <= $this->documentSize)
						{
							$result = mb_substr($this->document, $this->documentPosition, $len);
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
						$len = $what;
						if ($this->documentPosition + $len <= $this->documentSize)
						{
							$result = mb_substr($this->document, $this->documentPosition, $len);
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
								$len = mb_strlen($item);
								if ($this->documentPosition + $len <= $this->documentSize)
								{
									$result = mb_substr($this->document, $this->documentPosition, $len);
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
				return mb_substr($this->document, $position);
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
					if ($this->documentPosition + 1 <= $this->documentSize)
					{
						$result = mb_substr($this->document, $this->documentPosition, 1);
						if (call_user_func($callback, $result))
						{
							$this->documentPosition += 1;
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
					return null;
				}
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function ConsumeToPosition(/*int*/ $position)
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
						return mb_substr($this->document, $documentPosition, $position - $documentPosition);
					}
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
						if (mb_strlen($what) > 0)
						{
							if (($position = mb_strpos($this->document, $what, $this->documentPosition)) !== false)
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
							if (is_string($what) && ($position = mb_strpos($this->document, $what, $this->documentPosition)) !== false)
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
							$input = $this->Consume();
							if (input === null)
							{
								return $result;
							}
							else
							{
								if (!call_user_func($callback, $input))
								{
									$result += $input;
								}
								else
								{
									break;
								}
							}
						}
						if ($input !== null)
						{
							$this->Unconsume();
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
						while (($input = $this->Consume()) == $what)
						{
							$result .= $what;
						}
						if (!is_null($input))
						{
							$this->Unconsume();
						}
					}
					else if (is_array($what))
					{
						$continue = true;
						while ($continue)
						{
							$input = $this->Consume();
							if (in_array($input, $what))
							{
								$result .= $input;
							}
							else
							{
								$continue = false;
							}
						}
						if (!is_null($input))
						{
							$this->Unconsume();
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
							$input = $this->Consume();
							if (input === null)
							{
								return $result;
							}
							else
							{
								if (call_user_func($callback, $input))
								{
									$result += $input;
								}
								else
								{
									break;
								}
							}
						}
						if ($input !== null)
						{
							$this->Unconsume();
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

		public function Unconsume(/*int*/ $amount = null)
		{
			if (isset($this->document))
			{
				if (func_num_args() == 1 && is_numeric($amount))
				{
					if ($this->documentPosition >= $amount)
					{
						$this->documentPosition -= $amount;
					}
				}
				else
				{
					if ($this->documentPosition >= 1)
					{
						$this->documentPosition--;
					}
				}
			}
			else
			{
				throw new Exception('parser have been closed');
			}
		}

		public function UnconsumeAll()
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
				$this->documentSize = mb_strlen($document);
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