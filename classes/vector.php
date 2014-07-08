<?php

/******************************************************************************
 *
 * Copyright © Tobias Kühne
 *
 * You may use and distribute this software free of charge for non-commercial
 * purposes. The software must be distributed in its entirety, i.e. containing
 * ALL binary and source files without modification.
 * Publication of modified versions of the source code provided herein,
 * is permitted only with the author's written consent. In this case the
 * copyright notice must not be removed or altered, all modifications to the
 * source code must be clearly marked as such.
 *
 ******************************************************************************/

class vector
{
	private $c;
	private $v;

	public function __construct()
	{
		$this->c = 0;
		$this->v = array();
	}

	public function push($e)
	{
		if ($e)
			$this->v[$this->c++] = $e;

		return $e;
	}

	public function pop()
	{
		return $this->c > 0 ? $this->v[--$this->c] : NULL;
	}

	public function shift()
	{
		if (0 == $this->c)
		{
			$elem = NULL;
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
			return NULL;
		else
			return $this->v[$n];
	}

	public function count()
	{
		return $this->c;
	}
}

?>
