<?php

class vector
{
	private $c;
	private $v;

	public function __construct()
	{
		$this->c = 0;
		$this->v = [];
	}

	public function push($e)
	{
		if ($e)
			$this->v[$this->c++] = $e;

		return $e;
	}

	public function pop()
	{
		if (0 == $this->c)
		{
			$elem = null;
		}
		else
		{
			$elem = array_shift($this->v);
			$this->c--;
		}

		return $elem;
	}

	public function get($n)
	{
		if ($n < 0 || $this->c <= $n)
			return null;
		else
			return $this->v[$n];
	}

	public function count()
	{
		return $this->c;
	}
}

?>
