<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author$
 *         $Date$
 *          $Rev$
 *
 ******************************************************************************
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

function trim_r(&$item, $key)
{
    // If the item is an array, recursively use array_walk
    if (is_array($item))
        array_walk($item, 'trim_r');
    // Trim the item
    else if (is_string($item))
        $item = trim($item);
}

function awk($rules, $text, $FS=" ", $RS="\n")
{
	/**/
	$action = array();
	$pregex = array();
	$re_fin = array();
	$active = array();

	$j = 0;

	for ($i = 0; $i < count($rules); $i += 2)
	{
		if (preg_match("?(/(\\\/|[^/])+/)[[:space:]]*,[[:space:]]*(/(\\\/|[^/])+/)?", $rules[$i], $match))
		{
			$pregex[$j] = $match[1];
			$re_fin[$j] = $match[3];
		}
		else
		{
			$pregex[$j] = $rules[$i];
			$re_fin[$j] = NULL;
		}

		$action[$j] = $rules[$i + 1];
		$active[$j] = 0;

		$j++;
	}

	/**/
	$record = strtok($text, $RS);

	while ($record)
	{
		for ($i = 0; $i < count($pregex); $i++)
		{
			if (preg_match($pregex[$i], $record) || $active[$i])
			{
				$record = trim($record);
				$fields = $f = preg_split("/$FS+/", $record);
				array_unshift($fields, $record);
				// Trim the array
				array_walk($fields, 'trim_r');

				call_user_func($action[$i], $pregex[$i], $fields);

				if ($active[$i])
				{
					if (preg_match($re_fin[$i], $record))
						$active[$i] = 0;
				}
				else
				{
					if ($re_fin[$i])
						$active[$i] = 1;
				}
			}
		}

		$record = strtok($RS);
	}
}

function getline()
{
	return strtok("\n");
}

?>
