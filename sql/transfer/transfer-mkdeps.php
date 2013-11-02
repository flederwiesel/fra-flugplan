<?php

require_once('.config');
require_once('transfer-types.php');

$error = null;

$models = array();
$aircrafts = array();
$airlines = array();
$airports = array();

$hdbc = mysql_connect('localhost', $user, $passwd);

if (!$hdbc)
{
	$error =  '('.__LINE__.'): '.mysql_error();
}
else
{
	if (mysql_select_db('usr_web416_1', $hdbc))
		mysql_set_charset('utf8');
	else
		$error =  '('.__LINE__.'): '.mysql_error();
}

function SelectAll($table, $callback)
{
	$error = null;

	$query = "SELECT * FROM `$table`";

	$result = mysql_query($query);

	if (!$result)
	{
		$error =  '('.__LINE__.'): '.mysql_error().' ('.$table.')';
	}
	else
	{
		do
		{
			$row = mysql_fetch_assoc($result);

			if ($row)
				call_user_func($callback, $row);
		}
		while ($row);

		mysql_free_result($result);
	}

	return $error;
}

function ModelFromDb1($row)
{
	global $models;
	$models[$row['id']] = new Model(0, $row['icao'], $row['name']);
}

function AircraftFromDb1($row)
{
	global $aircrafts;
	$aircrafts[$row['id']] = new Aircraft(0, $row['type'], $row['reg']);
}

function AirlineFromDb1($row)
{
	global $airlines;
	$airlines[$row['id']] = new Airline(0, $row['abbrev'], $row['name']);
}

function AirportFromDb1($row)
{
	global $airports;
	$airports[$row['id']] = new Airport(0, $row['iata'], $row['icao'], $row['name']);
}

if (!$error)
{
	$error = SelectAll('aircraft-types', 'ModelFromDb1');

	if (!$error)
	{
		$error = SelectAll('aircrafts', 'AircraftFromDb1');

		if (!$error)
		{
			$error = SelectAll('airlines', 'AirlineFromDb1');

			if (!$error)
				$error = SelectAll('airports', 'AirportFromDb1');
		}
	}
}

if (!$error)
{
	if (!mysql_select_db('usr_web416_3', $hdbc))
		$error =  '('.__LINE__.'): '.mysql_error();
}

function AssignAll($table, &$array, $callback)
{
	$error = null;

	$query = "SELECT * FROM `$table`";
	$result = mysql_query($query);

	if (!$result)
	{
		$error =  '('.__LINE__.'): '.mysql_error().' ('.$table.')';
	}
	else
	{
		do
		{
			$row = mysql_fetch_assoc($result);

			if ($row)
			{
				foreach ($array as $obj)
				{
					if (call_user_func($callback, $obj, $row))
						break;
				}
			}
		}
		while ($row);

		mysql_free_result($result);
	}

	return $error;
}

function ModelAssignIdFromDb3($model, $row)
{
	if ($model->icao == $row['icao'])
	{
		$model->id = $row['id'];
		return true;
	}

	return false;
}

function AircraftAssignIdFromDb3($aircraft, $row)
{
	if ($aircraft->reg == $row['reg'] &&
		$aircraft->model == $row['model'])
	{
		$aircraft->id = $row['id'];
		return true;
	}

	return false;
}

function AirlineAssignIdFromDb3($airline, $row)
{
	if ($airline->code == $row['code'] &&
		$airline->name == $row['name'])
	{
		$airline->id = $row['id'];
		return true;
	}

	return false;
}

function AirportAssignIdFromDb3($airport, $row)
{
	if ($airport->iata == $row['iata'] &&
		$airport->icao == $row['icao'])
	{
		$airport->id = $row['id'];
		return true;
	}

	return false;
}

if (!$error)
{
	$error = AssignAll('models', $models, 'ModelAssignIdFromDb3');

	// Map aircraft models prior to comparing reg AND model in AircraftAssignIdFromDb3()
	foreach ($aircrafts as $aircraft)
		$aircraft->model = $models[$aircraft->model]->id;

	if (!$error)
	{
		$error = AssignAll('aircrafts', $aircrafts, 'AircraftAssignIdFromDb3');

		if (!$error)
		{
			$error = AssignAll('airlines', $airlines, 'AirlineAssignIdFromDb3');

			if (!$error)
				$error = AssignAll('airports', $airports, 'AirportAssignIdFromDb3');
		}
	}
}

$insert = 0;

// Echo queries to insert previous db dependencies into current db
if (!$error)
{
	// models
	$n = 0;
	$query = '';

	foreach ($models as $i => $model)
	{
		$model->name = addslashes($model->name);

		if (0 == $model->id)
        	$query .= ($n++ ? ",\n" : "")."(2, '$model->icao', '$model->name')";
	}

	if ($n)
	{
		$insert++;
		echo "INSERT INTO `models`(`uid`, `icao`, `name`)\nVALUES\n$query;\n\n";
	}

	// aircrafts
	$n = 0;
	$query = '';

	foreach ($aircrafts as $i => $aircraft)
	{
		if (0 == $aircraft->id)
			$query .= ($n++ ? ",\n" : "")."(2, $aircraft->model, '$aircraft->reg')";
	}

	if ($n)
	{
		$insert++;
		echo "INSERT INTO `aircrafts`(`uid`, `model`, `reg`)\nVALUES\n$query;\n\n";
	}

	// airlines
	$n = 0;
	$query = '';

	foreach ($airlines as $i => $airline)
	{
		$airline->name = addslashes($airline->name);

		if (0 == $airline->id)
			$query .= ($n++ ? ",\n" : "")."(2, '$airline->code', '$airline->name')";
	}

	if ($n)
	{
		$insert++;
		echo "INSERT INTO `airlines`(`uid`, `code`, `name`)\nVALUES\n$query;\n\n";
	}

	// airports
	$query = '';
	$n = 0;

	foreach ($airports as $i => $airport)
	{
		$airport->name = addslashes($airport->name);

		if (0 == $airport->id)
			$query .= ($n++ ? ",\n" : "")."(2, '$airport->iata', '$airport->icao', '$airport->name')";
	}

	if ($n)
	{
		$insert++;
		echo "INSERT INTO `airports`(`uid`, `iata`, `icao`, `name`)\nVALUES\n$query;\n";
	}
}

// Save dependencies for latter use with `flights` table
if (!$error)
{
	if (0 == $insert)
	{
		echo "<?php\n\nrequire_once('transfer-types.php');\n\n";

		// models
		echo "\$models = array(\n";

		foreach ($models as $i => $model)
//			echo "$i => new Model($model->id, NULL /*'$model->icao'*/, NULL /*'$model->name'*/),\n";
			echo "$i => $model->id,\n";

		echo ");\n\n";

		// aircrafts
		echo "\$aircrafts = array(\n";

		foreach ($aircrafts as $i => $aircraft)
//			echo "$i => new Aircraft($aircraft->id, $aircraft->model, NULL /*'$aircraft->reg'*/),\n";
			echo "$i => $aircraft->id,\n";

		echo ");\n\n";

		// airlines
		echo "\$airlines = array(\n";

		foreach ($airlines as $i => $airline)
//			echo "$i => new Airline($airline->id, NULL /*'$airline->code'*/, NULL /*'$airline->name'*/),\n";
			echo "$i => $airline->id,\n";

		echo ");\n\n";

		// airports
		echo "\$airports = array(\n";

		foreach ($airports as $i => $airport)
//			echo "$i => new Airport($airport->id, NULL /*'$airport->iata'*/, NULL /*'$airport->icao'*/, NULL /*'$airport->name'*/),\n";
			echo "$i => $airport->id,\n";

		echo ");\n\n";

		echo "?>\n";
	}
}

if ($error)
	echo $error;

if ($hdbc)
	mysql_close($hdbc);

?>
