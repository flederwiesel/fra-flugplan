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

/******************************************************************************
 * This is a simple mimicry of awk
 * (http://en.wikipedia.org/wiki/AWK,
 *  http://www.gnu.org/software/gawk/manual/gawk.html#Foreword),
 * supporting regex rules only
 ******************************************************************************/

class awkrule
{
	/* Those should be protected, but since there are no
	 * friend classes, leave them public for the pure
	 * reason of greater speed over access functions */
	public $start    = NULL;
	public $fin      = NULL;
	public $action   = NULL;
	public $underway = false;

	function __construct($regex, $action)
	{
		/* If token consists of start and end token for everything to be mathed in between ... */
		if (preg_match("?(/(\\\/|[^/])+/)[[:space:]]*,[[:space:]]*(/(\\\/|[^/])+/)?", $regex, $match))
		{
			$this->start = $match[1];
			$this->fin   = $match[3];
		}
		else
		{
			$this->start = $regex;
		}

		$this->action = $action;
	}
};

class awk
{
	function __construct($rules, $FS = NULL, $RS = NULL)
	{
		foreach ($rules as $regex => $action)
			$this->rules[] = new awkrule($regex, $action);
	}

	public function execute($text)
	{
		if (isset($this->rules['BEGIN']))
			call_user_func($this->rules['BEGIN']);

		/* Get first line */
		$record = strtok($text, $this->RS);

		while ($record)
		{
			$this->next = false;

			/* Check against each rule */
			foreach ($this->rules as $rule)
			{
				if (preg_match($rule->start, $record) || $rule->underway)
				{
					/* Split into groups "$1" .. "$N" */
					$record = trim($record);
					$fields = preg_split("/$this->FS+/", $record);

					/* Prepend "$0" */
					array_unshift($fields, $record);

					foreach ($fields as $key => $value)
						$fields[$key] = trim($value);

					/* Execute action */
					call_user_func($rule->action, $this, $fields);

					if ($rule->fin)
						$rule->underway = true;
				}

				if ($rule->underway)
				{
					/* If rule is to be valid between two tokens,
					 * check for matching end token */
					if (preg_match($rule->fin, $record))
						$rule->underway = false;
				}

				if ($this->next)
					break;
			}

			/* Basically getline(), but no stack frame required */
			$record = strtok($this->RS);
		}

		if (isset($this->rules['END']))
			call_user_func($this->rules['END']);
	}

	protected function next()
	{
		$this->next = true;
	}

	protected function getline()
	{
		return strtok($this->RS);
	}

	protected $FS = " ";
	protected $RS = "\n";

	private $rules = NULL;
	private $next = false;

};

?>
