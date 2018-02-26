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

// This class is a PDO wrapper able to EXPLAIN queries.
// Whenever a query contains an id in the form /*[QXXX]*/,
// (with XXX being a number), this id will be looked up in the global
// array $ExplainSQL, and, if present, a successful query will entail an
// explanation (the same query with an EXPLAIN prepended), the result thereof
// will be echoed to stdout.
class xPDO
{
	protected $pdo;

	public function __construct($dsn, $user=NULL, $pass=NULL, $driver_options=NULL)
	{
		$this->pdo = new PDO($dsn, $user, $pass, $driver_options);
	}

	public function __call($func, $args)
	{
		return call_user_func_array(array(&$this->pdo, $func), $args);
	}

	public function prepare()
	{
		global $ExplainSQL;

		$args = func_get_args();
		$pdos = call_user_func_array(array(&$this->pdo, 'prepare'), $args);
		$expl = NULL;

		foreach ($args as &$arg)
		{
			$queryid = preg_replace('/.*\/\* *\[ *(Q[0-9]+) *\] *\*\/.*/s', '\1', $arg);

			if (in_array($queryid, $ExplainSQL))
			{
				$arg = "EXPLAIN $arg";
				$expl = call_user_func_array(array(&$this->pdo, 'prepare'), $args);
				break;
			}
		}

		return new xPDOStatement($pdos, $expl);
	}

	public function query()
	{
		global $ExplainSQL;

		$expl = NULL;
		$args = func_get_args();
		$pdos = call_user_func_array(array(&$this->pdo, 'query'), $args);

		if ($pdos)
		{
			foreach ($args as &$arg)
			{
				$queryid = preg_replace('/.*\/\* *\[ *(Q[0-9]+) *\] *\*\/.*/s', '\1', $arg);

				if (in_array($queryid, $ExplainSQL))
				{
					$arg = "EXPLAIN $arg";
					$expl = call_user_func_array(array(&$this->pdo, 'query'), $args);
					break;
				}
			}
		}

		return new xPDOStatement($pdos, $expl);
	}

	public function exec()
	{
		global $ExplainSQL;

		$args = func_get_args();
		$result = call_user_func_array(array(&$this->pdo, 'exec'), $args);

		if ($result !== false)
		{
			foreach ($args as &$arg)
			{
				$queryid = preg_replace('/.*\/\* *\[ *(Q[0-9]+) *\] *\*\/.*/s', '\1', $arg);

				if (in_array($queryid, $ExplainSQL))
				{
					$arg = "EXPLAIN $arg";
					$expl = call_user_func_array(array(&$this->pdo, 'query'), $args);

					if ($expl !== false)
						explain($expl);
					else
						errorInfo($this->pdo, $args[0]);

					break;
				}
			}
		}

		return $result;
	}
}

class xPDOStatement
{
	protected $pdos;
	protected $expl;

	public function __construct($pdos, $expl)
	{
		$this->pdos = $pdos;
		$this->expl = $expl;
	}

	public function __call($func, $args)
	{
		$result = call_user_func_array(array(&$this->pdos, $func), $args);

		if ('fetch'       == $func ||
			'fetchObject' == $func)
		{
			if ($result !== FALSE)
			{
				explain($this->expl);
				$this->expl = NULL;
			}
		}

		if ('bindValue' == $func)
		{
			if ($result !== FALSE)
				$result = call_user_func_array(array(&$this->expl, $func), $args);
		}

		return $result;
	}

	public function bindColumn($column, &$param, $type=NULL)
	{
		if ($type === NULL)
		{
			$this->pdos->bindColumn($column, $param);

			if ($this->expl)
				$this->expl->bindColumn($column, $param);
		}
		else
		{
			$this->pdos->bindColumn($column, $param, $type);

			if ($this->expl)
				$this->expl->bindColumn($column, $param, $type);
		}
	}

	public function bindParam($column, &$param, $type=NULL)
	{
		if ($type === NULL)
		{
			$this->pdos->bindParam($column, $param);

			if ($this->expl)
				$this->expl->bindParam($column, $param);
		}
		else
		{
			$this->pdos->bindParam($column, $param, $type);

			if ($this->expl)
				$this->expl->bindParam($column, $param, $type);
		}
	}

	public function execute()
	{
		$args = func_get_args();
		$result = call_user_func_array(array(&$this->pdos, 'execute'), $args);

		if ($result && $this->expl)
		{
			if (call_user_func_array(array(&$this->expl, 'execute'), $args))
				explain($this->expl);
			else
				errorInfo($this->expl);
		}

		return $result;
	}

	public function __get($property)
	{
		return $this->pdos->$property;
	}
}

function explain($st)
{
	if ($st)
	{
		$rows = 0;
		$cols = 0;

		echo <<<END
<!--
{$st->queryString}
/*==============================================

END;

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$cols = count($row);

			if (0 == $rows++)
			{
				$c = 0;

				foreach (array_keys($row) as $col)
					printf("%s%s", $col, ++$c == $cols ? "\n" : "\t");

				print("------------------------------------------------\n");
			}

			$c = 0;

			foreach ($row as $col)
				printf("%s%s", $col, ++$c == $cols ? "\n" : "\t");
		}

		echo <<<END
==============================================*/
-->

END;
	}
}

function errorInfo($obj, $query = NULL)
{
	if (NULL == $query)
		if ('xPDOStatement' == get_class($obj))
        	$query = $obj->queryString;

	$result = $obj->errorInfo();

	echo <<<END
<!--
$query
/*==============================================
{$result[0]} {$result[1]} {$result[2]}
==============================================*/
-->
END;
}

?>
