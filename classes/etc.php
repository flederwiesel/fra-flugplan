<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author: flederwiesel $
 *         $Date: 2013-08-29 19:43:54 +0200 (Do, 29 Aug 2013) $
 *          $Rev: 415 $
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

function php_self($https = 0)
{
	if ($https)
	{
		$pageURL = 'https';
	}
	else
	{
		$pageURL = 'http';

		if (isset($_SERVER['HTTPS']))
			if ($_SERVER['HTTPS'] == 'on')
				$pageURL .= 's';
	}

	$pageURL .= '://'.$_SERVER['SERVER_NAME'];

	if ($_SERVER['SERVER_PORT'] != 80)
		$pageURL .= ":".$_SERVER['SERVER_PORT'];

	$pageURL .= $_SERVER['PHP_SELF'];

	return $pageURL;
}

function mktime_c($ddmmyyyy /* dd.mm.YYYY */, $hhmm = '00:00')
{
	if (!preg_match('/([0-9]+).([0-9]+).([0-9]+)/', str_replace(' ', '', $ddmmyyyy), $day))
	{
		$date = -1;
	}
	else
	{
		if (!preg_match('/([0-9]+):([0-9]+)/', str_replace(' ', '', $hhmm), $time))
		{
			$date = -1;
		}
		else
		{
			$date = mktime($time[1], $time[2], 0, $day[2], $day[1], $day[3]);

			if ($date > -1)
			{
				if (date("d.m.Y H:i", $date) != "$ddmmyyyy $hhmm")
					$date = -1;
			}
		}
	}

	return $date;
}

define('INP_FORCE', 0x1);
define('INP_POST', 0x2);
define('INP_GET',  0x4);

function Input_SetValue($name, $whence, $debug)
{
	$value = null;

	if (INP_POST & $whence)
	{
		if (isset($_POST[$name]))
			$value = $_POST[$name];
	}

	if (INP_GET & $whence)
	{
		if (!$value)
 			if (isset($_GET[$name]))
 				$value = $_GET[$name];
	}

	if (null == $value)
	{
		if (INP_FORCE & $whence)
			$value = $debug;
	}

	if (defined('DEBUG'))
		if (!$value)
			$value = $debug;

	if ($value)
		echo htmlspecialchars($value);
}

?>
