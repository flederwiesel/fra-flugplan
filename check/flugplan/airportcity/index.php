<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author: flederwiesel $
 *         $Date: 2013-12-25 16:11:59 +0100 (Mi, 25 Dez 2013) $
 *          $Rev: 540 $
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

class airline
{
	public $code;
	public $name;

	public function __construct($code, $name)
	{
		$this->code = $code;
		$this->name = $name;
	}
};

class airport
{
	public $iata;
	public $icao;
	public $name;
	public $country;

	public function __construct($iata, $icao, $name, $country)
	{
		$this->iata = $iata;
		$this->icao = $icao;
		$this->name = $name;
		$this->country = $country;
	}
};

class flight
{
	public $scheduled;
	public $expected;
	public $airline;
	public $code;
	public $model;
	public $reg;
	public $airport;
	public $remark;

	public function __construct($scheduled, $expected, $airline, $code, $model, $reg, $airport, $remark)
	{
		$this->scheduled = $scheduled;
		$this->expected  = $expected;
		$this->airline   = $airline;
		$this->code      = $code;
		$this->model     = $model;
		$this->reg       = $reg;
		$this->airport   = $airport;
		$this->remark    = $remark;
	}
};

function LineReplaceFlightData($row, $flight)
{
	if (strlen($flight->expected))
	{
		$expected = sprintf("Erwartet: %s Uhr\n        , %s\n        <br/>",
						    $flight->expected, strftime('%d.%m.%Y'));
	}

	return str_replace(

		array(
			'${YYYYmmdd}',
			'${dd-mm-YYYY}',
			'${scheduled-date}',
			'${scheduled-time}',
			'${expected}',
			'${expected-time}',
			'${airline-code}',
			'${airline-name}',
			'${code}',
			'${airport-iata}',
			'${airport-icao}',
			'${airport-name}',
			'${airport-country}',
			'${model}',
			'${reg}',
			'${remark}',
		),

		array(
			strftime('%Y%m%d'),
			strftime('%d-%m-%Y'),
			strftime('%d.%m.%Y'),
			$flight->scheduled,
			$expected,
			$flight->expected,
			$flight->airline->code,
			$flight->airline->name,
			$flight->code,
			$flight->airport->iata,
			$flight->airport->icao,
			$flight->airport->name,
			$flight->airport->country,
			$flight->model,
			$flight->reg,
			$flight->remark,
		),

		$row
	);
}

/* Description of the csv */
$keys = array(
	'scheduled',
	'expected',
	'airline-code',
	'code',
	'airline-name',
	'model',
	'reg',
	'airport-iata',
	'airport-icao',
	'airport-name',
	'airport-country',
	'remark',
);

$direction = NULL;

if (isset($_GET['type']))
{
	$type = 'list';

	if ('arrival' == $_GET['type'] || 'departure' == $_GET['type'])
		$direction = $_GET['type'];
}
else
{
	if (isset($_GET['fia']))
	{
		$flightno = $_GET['fia'];
		$direction = 'arrival';
		$type = 'details';
	}

	if (isset($_GET['fid']))
	{
		$flightno = $_GET['fid'];
		$direction = 'departure';
		$type = 'details';
	}
}

if (NULL == $direction)
	die;

$file = file("$direction.csv");

if ($file)
{
	foreach ($file as $line)
	{
		/* Remove trailing '\n' */
		$line = substr($line, 0,-1);

		/* Split line into associative array */
		$flight = array_combine($keys, explode(';', $line));

		$flights[] = new flight(
				$flight['scheduled'],
				$flight['expected'],
				new airline(
					$flight['airline-code'],
					$flight['airline-name']
				),
				$flight['code'],
				$flight['model'],
				$flight['reg'],
				new airport(
					$flight['airport-iata'],
					$flight['airport-icao'],
					$flight['airport-name'],
					$flight['airport-country']
				),
				$flight['remark']
		);
	}
}

if ('details' == $type)
{
	$flight = NULL;

	/* extract flight number */
	/* fi[ad]=A{2,3}C{3,4}YYYYmmdd */
	$flightno = substr($flightno, 0, -8);
	$date = strftime('%Y%m%d');

	for ($i = 0; $i < count($flights); $i++)
	{
		if ($flights[$i]->airline->code.
			$flights[$i]->code == $flightno)
		{
			$flight = $flights[$i];
			break;
		}
	}

	if ($flight)
	{
		$file = "templates/fi$direction[0].htm";
		$file = file($file);

		foreach ($file as $line)
			echo LineReplaceFlightData($line, $flights[$i]);
	}
}
else
{
	// type=arrival&typ=p&context=0&sprache=de&items=12&init=true&page=1
	// type=departure&typ=p&context=0&sprache=de&items=12&usepager=true&page=2
	$file = file("templates/$direction.htm");

	if ($file)
	{
		$items = 3;
		$page = 1;
		$next = NULL;
		$start = 0;

		/* Show at most count($flights) */
		if (isset($_GET['items']))
			if ($_GET['items'] <= count($flights) && $_GET['items'] > 0)
				$items = $_GET['items'];

		/* Validate page index, set start index */
		if (isset($_GET['page']))
		{
			if ($_GET['page'] > 0)
			{
				if (($_GET['page'] - 1) * $items <= count($flights))
				{
					$page = $_GET['page'];
					$start = ($page - 1) * $items;
				}
			}
		}

		$pages = (int)(count($flights) / $items);

		if (count($flights) % $items > 0)
			$pages++;

		$nav = '<li class="active"><a href="#1">1</a></li>';

		if ($pages > 3)
			$nav .= '<li>...</li>';

		if ($pages > 2)
			$nav .= '<li class="active"><a href="#'.$pages.'">'.$pages.'</a></li>';

		/* Limit items */
		if ($start + $items > count($flights))
			$items = count($flights) - $start;

		/* set 'next' href appropriately */
		if ($start + $items < count($flights))
			$next = $page + 1;

		if (NULL == $next)
			$next = '<span class="next-page-disabled">weiter</span>';
		else
			$next = '<a class="next-page" href="#'.$next.'">weiter</a>';

		/* Copy up to "<tbody>" -> leave $head section */
		/* Then gather lines up to "</tbody>" (not included) - */
		/*   which starts $tail - in $row, replace flight data */
		/* Copy remainder, unless we need to replace pager navigation */
		$head = true;
		$tail = false;
		$row = '';

		foreach ($file as $line)
		{
			if ($head)
			{
				if (strstr($line, '<tbody>'))
					$head = false;

				echo $line;
			}
			else if ($tail)
			{
				$line = str_replace('${nav-next}', $next, $line);
				$line = str_replace('${nav-pages}', $nav, $line);

				echo $line;
			}
			else
			{
				if (!strstr($line, '</tbody>'))
				{
					$row .= $line;
				}
				else
				{
					$tail = true;

					for ($i = $start; $i < $start + $items; $i++)
						echo LineReplaceFlightData($row, $flights[$i]);

					unset($row);
				}
			}
		}
	}
}

?>
