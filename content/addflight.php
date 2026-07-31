<?php

/*
	reg=D-AIRY
	flight=LH123
	type=regular
	airline=
	direction=arrival
	airport=2
	from=31.12.2012
	time=00%3A00
	interval=once|each|daily
	until=
	mon,tue,wed,thu,fri,sat,sun
*/

function checkedIfInPost($name, $value, $type = 'checkbox')
{
	$state = null;

	if (isset($_POST[$name]))
	{
		if ($type == 'checkbox')
		{
			$a = $_POST[$name];

			if (isset($a[$value]))
				$state = ' checked ';
		}
		else
		{
			if ($value == $_POST[$name])
				$state = ' checked="checked" ';
		}
	}

	return $state ?? "";
}

function CheckPostVariables(&$notice)
{
	global $STRINGS;

	$error = null;

	if (!(isset($_POST['flight']) &&
		  isset($_POST['type']) &&
		  isset($_POST['direction']) &&
		  isset($_POST['airport'])) &&
		  isset($_POST['from']) &&
		  isset($_POST['time']) &&
		  isset($_POST['interval']))
	{
		/* Should never be seen in normal op... */
		$error = $STRINGS['unexpected'];
	}
	else
	{
		switch ($_POST['type'])
		{
		case 'pax-regular':
		case 'cargo':
		case 'ferry':
			$type = $_POST['type'];
			break;

		default:
			$error = $STRINGS['unexpected'];
		}

		if (!$error)
		{
			if (!('arrival'   == $_POST['direction'] ||
				  'departure' == $_POST['direction']))
			{
				/* Should never be seen in normal op... */
				$error = $STRINGS['unexpected'];
			}
			else
			{
				if ('each' == $_POST['interval'] ||
					'daily' == $_POST['interval'])
				{
					if (!isset($_POST['until']))
					{
						$notice = $STRINGS['untilinvalid'];
					}
					else
					{
						if ('' == $_POST['until'])
						{
							$notice = $STRINGS['untilinvalid'];
						}
						else
						{
							if ('each' == $_POST['interval'])
							{
								if (!isset($_POST['day']))
								{
									$notice = $STRINGS['wdays'];
								}
								else
								{
									if (0 == count($_POST['day']))
										$notice = $STRINGS['wdays'];
								}
							}
						}
					}
				}
			}
		}
	}

	return $error;
}

function GetPostVariables(&$type, &$reg, &$flight, &$dir, &$scheduled, &$until)
{
	global $STRINGS;

	$error = null;

	$type = $_POST['type'];
	$dir = $_POST['direction'];
	$flight = null;

	if (!isset($_POST['reg']))
	{
		$reg = null;
	}
	else
	{
		if (0 == strlen($_POST['reg']))
		{
			$reg = null;
		}
		else
		{
			if (preg_match('/^[a-zA-Z]+-?[a-zA-Z0-9]+$/', $_POST['reg'], $reg))
				$reg = $reg[0];
			else
				$error = $STRINGS['invalidreg'];
		}
	}

	if (!$error)
	{
		if (!preg_match('/^([0-9][A-Z]|[A-Z][0-9]|[A-Z]{2,3})([0-9]{3,4}[A-Z]?)$/',
						strtoupper($_POST['flight']), $flight))
		{
			$error = $STRINGS['invalidflight'];
		}
		else
		{
			array_shift($flight);

			$scheduled = mktime_c($_POST['from'], $_POST['time']);

			if (-1 == $scheduled)
			{
				$error = $STRINGS['invaliddatetime'];
			}
			else
			{
				if ('once' == $_POST['interval'])
				{
					$until = 0;
				}
				else
				{
					$until = mktime_c($_POST['until'], $_POST['time']);

					if (-1 == $until)
						$error = $STRINGS['untilinvalid'];
				}
			}
		}
	}

	return $error;
}

function GetRegId(&$reg, &$model)
{
	global $STRINGS;

	$error = null;

	// $query = "SELECT `id`, `model` FROM `aircrafts` WHERE `reg`='$reg'";

	return $error;
}

function GetPostRegId(&$reg, &$model)
{
	global $STRINGS;
	global $user;

	$error = null;

	// TODO: curl https://www.airframes.org/ --data reg=D-AIRY | awk
	// $query = "SELECT `id` FROM `models` WHERE `icao`='$_POST[model]'";
	// $query = "INSERT INTO `models`(`icao`) VALUES('$model')";
	// $query = "INSERT INTO `aircrafts`(`reg`, `model`)".
	// 		 " VALUES('$reg', $model)";
	// $result1 = mysql_query("SELECT LAST_INSERT_ID()");

	return $error;
}

function GetAirlineId(&$airline, $flight)
{
	global $STRINGS;

	$error = null;
	$airline = null;

	// $query = "SELECT `id` FROM `airlines` WHERE `code`='$flight[0]'";

	return $error;
}

function GetPostAirlineId(&$airline)
{
	global $STRINGS;
	global $user;

	$error = null;

	// $query = "INSERT INTO `airlines`(`code`, `name`)".
	// 		 " VALUES('$_POST[code]', '$_POST[airline]')";
	// $result = mysql_query("SELECT LAST_INSERT_ID()");

	return $error;
}

$error = null;
$airline = null;
$model = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	if (!$user)
	{
		$error = $STRINGS['notloggedin'];
	}
	else
	{
		if (!$user->IsMemberOf('addflights'))
		{
			$error = $STRINGS['nopermission'];
		}
		else
		{
			$error = CheckPostVariables($notice);

			if (!$error)
			{
				if (!$notice)
				{
					$error = GetPostVariables($type, $reg, $flight, $dir, $scheduled, $until);

					if (!$error)
					{
						$model = null;
						$error = GetRegId($reg, $model);

						if (!$error)
						{
							if (!$model)
							{
								$error = GetPostRegId($reg, $model);

								if (!$error)
								{
									if (!$model)
									{
										$notice = $reg ? $STRINGS['typeunknown'] : $STRINGS['needtype'];
									}
									else
									{
										$airline = $flight[0];
									}
								}
							}
						}
					}

					if (!$error && !$notice)
					{
						$airline = null;
						$error = GetAirlineId($airline, $flight);

						if (!$error)
						{
							if (!$airline)
								$error = GetPostAirlineId($airline);
						}

						if (!$error)
						{
							if (!$airline)
							{
								$notice = $STRINGS['nosuchairline'];
							}
							else
							{
								$insert = true;
								$days = isset($_POST['day']) ? $_POST['day'] : [];

								do
								{
									if ('each' == $_POST['interval'])
									{
										if (isset($_POST['day[0]']))
										{
											$insert = true;
										}
										else
										{
											$wday = date('N', $scheduled);
											$insert = isset($days[$wday]) ? true : false;
										}
									}

									if ($insert)
									{
										/*
										$query = sprintf(
											"INSERT INTO `flights`".
											" (`type`, `direction`, `airline`, `code`, ".
											"  `scheduled`, `airport`, `model`, `aircraft`)".
											"VALUES(".
											" '$type', '$dir', $airline, '$flight[1]', ".
											" '%s', %lu, %s, %s);",
											strftime('%Y-%m-%d %H:%M:%S', $scheduled),
											$_POST['airport'],
											$model ? $model : 'NULL',
											$reg ? $reg : 'NULL');
										*/
									}

									if ($until)
										$scheduled = strtotime('+1 day', $scheduled);
								}
								while ($scheduled <= $until && !$error);

								if (!$error)
								{
									$message = $STRINGS['addflsuccess'];

									unset($_POST['reg']);
									unset($_POST['model']);
									unset($_POST['flight']);
									//unset($_POST['type']);
									unset($_POST['code']);
									unset($_POST['airline']);
									//unset($_POST['direction']);
									//unset($_POST['airport']);
									unset($_POST['time']);
									//unset($_POST['from']);
									//unset($_POST['interval']);
									//unset($_POST['until']);
								}
							}
						}
					}
				}
			}
		}
	}
}

if ($lang == "de")
{
?>
<script type="text/javascript" src="<?= "script/{$jqueryui}/i18n/datepicker-de.js" ?>"></script>
<?php
}
?>
<script type="text/javascript" src="<?= Asset::src('script/addflight.js') ?>" defer></script>
<form id="addflight" method="post" action="?page=addflight">
	<fieldset>
		<legend><?= $STRINGS['addflight'] ?></legend>
<?php if (isset($error)) { ?>
		<div id="notification" class="error"><?= $error ?></div>
<?php } else if (isset($notice)) { ?>
		<div id="notification" class="notice"><?= $notice ?></div>
<?php } else if (isset($message)) { ?>
		<div id="notification" class="success"><?= $message ?></div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= $STRINGS['flight'] ?></div>
				<div class="cell">
					<input type="text" name="flight" id="flight"
						placeholder="XX 0000"
						value="<?= valueFromRequest('flight', INP_POST) ?>"/>
					<div id="flighttype">
						<label>
							<input type="radio" name="type"
								value="pax-regular" checked="checked"><?= $STRINGS['pax-regular'] ?>
						</label>
						<label>
							<input type="radio" name="type"
								value="cargo"><?= $STRINGS['cargo'] ?>
						</label>
						<label>
							<input type="radio" name="type"
								value="ferry"><?= $STRINGS['ferry'] ?>
						</label>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="cell"></div>
				<div class="cell">
					<label><input type="radio" name="direction" value="arrival" <?= $dir == 'arrival' ? ' checked="checked" ' : '' ?>/><?= $STRINGS['arrival'] ?></label>
					<label><input type="radio" name="direction" value="departure" <?= $dir == 'departure' ? ' checked="checked" ' : '' ?>/><?= $STRINGS['departure'] ?></label>
				</div>
			</div>

			<div class="row">
				<div class="cell label"><?= ucFirst($STRINGS['scheduled']) ?></div>
				<fieldset>
					<div class="row">
						<div class="cell label"><?= $STRINGS['date'] ?></div>
						<div class="cell">
							<input type="text" name="from" id="from"
								placeholder="dd.mm.YYYY"
								value="<?= valueFromRequest('from', INP_POST) ?>"/>
						</div>
					</div>

					<div class="row">
						<div class="cell label"><?= $STRINGS['time'] ?></div>
						<div class="cell">
							<input type="text" name="time" id="time"
								placeholder="HH:MM"
								value="<?= valueFromRequest('time', INP_POST) ?>"/>(<?= $STRINGS['local'] ?>)
							<div id="addflight_interval">
								<label><input type="radio" name="interval" value="once" id="once" checked="checked" /><?= $STRINGS['once'] ?></label>
								<label><input type="radio" name="interval" value="daily" id="daily"/><?= $STRINGS['daily'] ?></label>
								<label><input type="radio" name="interval" value="each" id="each"/><?= $STRINGS['each'] ?></label>

								<div id="each" class="row">
									<div class="cell">
										<div>
											<label><input type="checkbox" name="day[1]" id="mon" disabled /><?= $STRINGS['mon'] ?></label>
											<label><input type="checkbox" name="day[2]" id="tue" disabled /><?= $STRINGS['tue'] ?></label>
											<label><input type="checkbox" name="day[3]" id="wed" disabled /><?= $STRINGS['wed'] ?></label>
											<label><input type="checkbox" name="day[4]" id="thu" disabled /><?= $STRINGS['thu'] ?></label>
											<label><input type="checkbox" name="day[5]" id="fri" disabled /><?= $STRINGS['fri'] ?></label>
											<label><input type="checkbox" name="day[6]" id="sat" disabled /><?= $STRINGS['sat'] ?></label>
											<label><input type="checkbox" name="day[7]" id="sun" disabled /><?= $STRINGS['sun'] ?></label>
										</div>
										<div>
											<label><input type="checkbox" name="day[0]" id="all" disabled /><b><?= $STRINGS['all'] ?></b></label>
										</div>
									</div>
								</div>
							</div>

							<div id="each" class="row">
								<div>
									<div class="cell shrink"><?= $STRINGS['until'] ?></div>
									<div class="cell">
										<input type="text" name="until" id="until"
											placeholder="dd.mm.YYYY"
											value="<?= valueFromRequest('until', INP_POST) ?>" disabled>
									</div>
								</div>
							</div>
						</div>
					</div>
				</fieldset>
			</div>

			<div class="row">
				<div class="cell label"><?= $STRINGS['airline'] ?></div>
				<fieldset>
					<div class="row">
						<div class="cell label"></div>
						<div class="cell">
							<select id="airport-icao" name="airport">
								<option value="" selected="selected">&lt;Add new&gt;</option>
								<option value="67">LH | DLH | Lufthansa</option>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="cell label">IATA</div>
						<div class="cell">
							<input type="text" name="airline_iata" placeholder="AA" value=""/>
						</div>
					</div>
					<div class="row">
						<div class="cell label">ICAO</div>
						<div class="cell">
							<input type="text" name="airline_icao" placeholder="AAA" value=""/>
						</div>
					</div>
					<div class="row">
						<div class="cell label">Name</div>
						<div class="cell">
							<input type="text" name="airline_name" value=""/>
						</div>
					</div>
				</fieldset>
			</div>

			<div class="row">
				<div class="cell label"><?= ucfirst($STRINGS['airport']) ?></div>
				<fieldset>
					<div class="row">
						<div class="cell label"></div>
						<div class="cell">
							<select id="airport" name="airport">
								<option value="" selected="selected">&lt;Add new&gt;</option>
								<option value="0">FRA | EDDF | Frankfurt</option>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="cell label">IATA</div>
						<div class="cell">
							<input name="airport-iata" placeholder="AAA">
						</div>
					</div>
					<div class="row">
						<div class="cell label">ICAO</div>
						<div class="cell">
						<input name="airport-icao" placeholder="AAAA">
						</div>
					</div>
					<div class="row">
						<div class="cell label">Name</div>
						<div class="cell">
						<input name="airport-name">
						</div>
					</div>
					<div class="row">
						<div class="cell label">Geo</div>
						<div class="cell">
							<input name="airport-gps" placeholder="0.0000000 0.0000000">
						</div>
					</div>
					<div class="row">
						<div class="cell label"><?= ucfirst($STRINGS['country']) ?></div>
						<div class="cell">
							<select name="airport-country">
								<option selected="selected">🇺🇳 | XX</option>
							</select>
						</div>
					</div>
				</fieldset>
			</div>

			<div class="row">
				<div class="cell label"><?= ucfirst($STRINGS['aircraft']) ?></div>
				<fieldset>
					<div class="row">
						<div class="cell label"><?= $STRINGS['reg'] ?></div>
						<div class="cell">
							<input name="reg"/>
						</div>
					</div>
					<div class="row">
						<div class="cell label"><?= $STRINGS['icaomodel'] ?></div>
						<div class="cell">
							<input type="text" name="model" id="model"
								placeholder="AAAA"
								value="<?= valueFromRequest('model', INP_POST) ?>"/>
								<span>
									<a href="https://www.airframes.org/" target="airframes.org">[?]</a>
								</span>
						</div>
					</div>
					<div class="row">
						<div class="cell label">Name</div>
						<div class="cell">
							<input type="text" name="aircraft_name" value=""/>
						</div>
					</div>
				</fieldset>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center"><input id="submit" type="submit" name="submit"/></div>
</form>
