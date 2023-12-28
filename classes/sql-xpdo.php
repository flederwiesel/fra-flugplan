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

	public function __construct($dsn, $user = null, $pass = null, $driver_options = null)
	{
		$this->pdo = new PDO($dsn, $user, $pass, $driver_options);
	}

	public function __call($func, $args)
	{
		return call_user_func_array([&$this->pdo, $func], $args);
	}

	public function
	/*  xPDOStatement|false */ prepare(/* string $query, array $options = [] */)
	{
		global $ExplainSQL;

		$args = func_get_args();
		$orig = call_user_func_array([&$this->pdo, 'prepare'], $args);
		$expl = null;

		$query = array_shift($args);
		$queryid = preg_replace('/.*\/\* *\[ *(Q[0-9]+) *\] *\*\/.*/s', '\1', $query);

		if (in_array($queryid, $ExplainSQL))
		{
			array_unshift($args, "EXPLAIN $query");

			$expl = call_user_func_array([&$this->pdo, 'prepare'], $args);

			if (false === $expl)
				printErrorInfo($this->pdo, $query);
		}

		return new xPDOStatement($orig, $expl);
	}

	public function
	/*  xPDOStatement|false */ query(/* string $query, ?int $fetchMode = null */)
	{
		global $ExplainSQL;

		$expl = null;
		$args = func_get_args();
		$orig = call_user_func_array([&$this->pdo, 'query'], $args);

		if ($orig)
		{
			$query = array_shift($args);
			$queryid = preg_replace('/.*\/\* *\[ *(Q[0-9]+) *\] *\*\/.*/s', '\1', $query);

			if (in_array($queryid, $ExplainSQL))
			{
				array_unshift($args, "EXPLAIN $query");

				$expl = call_user_func_array([&$this->pdo, 'query'], $args);

				if (false === $expl)
					printErrorInfo($this->pdo, $query);
			}
		}

		return new xPDOStatement($orig, $expl);
	}

	public function
	/* $rows|false */ exec(/* string $statement */)
	{
		global $ExplainSQL;

		$args = func_get_args();
		$result = call_user_func_array([&$this->pdo, 'exec'], $args);

		if ($result !== false)
		{
			$query = array_shift($args);
			$queryid = preg_replace('/.*\/\* *\[ *(Q[0-9]+) *\] *\*\/.*/s', '\1', $query);

			if (in_array($queryid, $ExplainSQL))
			{
				array_unshift($args, "EXPLAIN $query");
				$expl = call_user_func_array([&$this->pdo, 'query'], $args);

				if (false === $expl)
					printErrorInfo($this->pdo, $query);
				else
					explain($expl);
			}
		}

		return $result;
	}
}

class xPDOStatement
{
	protected $orig;	// Original prepared statement
	protected $expl;	// Handle for the EXPLAIN statement

	public function __construct($orig, $expl)
	{
		$this->orig = $orig;
		$this->expl = $expl;
	}

	public function __call($func, $args)
	{
		$result = call_user_func_array([&$this->pdos, $func], $args);

		if ('fetch'       == $func ||
			'fetchAll'    == $func ||
			'fetchColumn' == $func ||
			'fetchObject' == $func)
		{
			if ($result !== false)
			{
				explain($this->expl);
				$this->expl = NULL;
			}
		}

		if ('bindValue' == $func)
		{
			if ($result !== FALSE)
				$result = call_user_func_array([&$this->expl, $func], $args);
		}

		return $result;
	}

	public function bindColumn($column, &$param, $type = null)
	{
		if ($type === NULL)
		{
			$this->orig->bindColumn($column, $param);

			if ($this->expl)
				$this->expl->bindColumn($column, $param);
		}
		else
		{
			$this->orig->bindColumn($column, $param, $type);

			if ($this->expl)
				$this->expl->bindColumn($column, $param, $type);
		}
	}

	public function bindParam($column, &$param, $type = null)
	{
		if ($type === NULL)
		{
			$this->orig->bindParam($column, $param);

			if ($this->expl)
				$this->expl->bindParam($column, $param);
		}
		else
		{
			$this->orig->bindParam($column, $param, $type);

			if ($this->expl)
				$this->expl->bindParam($column, $param, $type);
		}
	}

	public function /* true|false */ execute(/* ?array $params = null */)
	{
		$args = func_get_args();
		$result = call_user_func_array([&$this->pdos, 'execute'], $args);

		if ($result && $this->expl)
		{
			if (call_user_func_array([&$this->expl, 'execute'], $args))
				explain($this->expl);
			else
				printErrorInfo($this->expl);
		}

		return $result;
	}

	public function __get($property)
	{
		return $this->orig->$property;
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

function printErrorInfo($obj, $query = NULL)
{
	if (null == $query)
		if ('xPDOStatement' == get_class($obj))
			$query = $obj->queryString;

	$result = $obj->printErrorInfo();

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
