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

namespace flederwiesel\sql\xpose
{

/* Get the expanded query string atfer PDOStatement::execute($params).
 * Even with PDO::ATTR_EMULATE_PREPARES being set, PDO does not expose the
 * modified SQL query after expanding parameter placeholders.
 * If we want to log the "final query string" (although without emulation, there
 * will not be such thing), We need to do this ourselves.
 * Unfortunately PDOStatement::queryString is a read-only property, so we have
 * to carry around our own queryString along with original PDO(Statement) here.
 * Using query parameters via bind(Column|Param|Value) is not implemented.
 */

class PDO
{
	protected $pdo;

	public function __construct($dsn, $user = null, $pass = null, $driver_options = null)
	{
		$this->pdo = new \PDO($dsn, $user, $pass, $driver_options);
	}

	public function __call($func, $args)
	{
		return call_user_func_array(array(&$this->pdo, $func), $args);
	}

	public function prepare($query)
	{
		$st = call_user_func_array(array(&$this->pdo, 'prepare'), [ $query ]);

		return new PDOStatement($st, $query);
	}
}

class PDOStatement
{
	// \PDOStatement::queryString is read-only, so we cannot extend \PDOStatement,
	// but need to use our own copy of `queryString`.
	protected $st;
	protected $queryString;

	public function __construct($st, $query)
	{
		$this->st = $st;
		$this->queryString = $query;
	}

	public function __call($func, $args)
	{
		return call_user_func_array(array(&$this->st, $func), $args);
	}

	public function execute($params = null)
	{
		$result = call_user_func_array(array(&$this->st, 'execute'), [ $params ]);

		if ($params)
		{
			// As named params must be identifiers, $params will be an associative
			// array in this case. For this a check for an integer index would fail.
			// As params may either be named or "?", this is sufficient here...
			if (isset($params[0]))
				$this->replaceQuestionMark($params);
			else
				$this->replaceNamed($params);
		}

		return $result;
	}

	public function __get($property)
	{
		if ("queryString" == $property)
			return $this->queryString;

		return $this->st->$property;
	}

	private function replaceNamed($params)
	{
		// Build up array, then replace using strtr()
		$args = [];

		foreach ($params as $key => $value)
			$args[":{$key}"] =
				is_null($value) ? "NULL" : (
					is_string($value) ? "'{$value}'" : strval($value)
				);

		$this->queryString = strtr($this->queryString, $args);
	}

	private function replaceQuestionMark($params)
	{
		$query =& $this->queryString;
		$replaced = "";

		$n = 0;
		$offset = 0;
		$pos = strpos($query, "?", $offset);

		// Copy substring before next "?", then append next $param[@]
		while ($pos !== false)
		{
			$value = $params[$n++];
			// $offset relates to the beginning of $query, not $query[$offset]!
			$replaced .= substr($query, $offset, $pos - $offset);
			$replaced .= is_null($value) ? "NULL" : (
				is_string($value) ? "'{$value}'" : strval($value)
			);
			$offset = $pos + 1;
			$pos = strpos($query, "?", $offset);
		}

		// Copy remainder after last "?"
		$replaced .= substr($query, $offset);
		$this->queryString = $replaced;
	}
}

} // namespace SqlDebug

?>
