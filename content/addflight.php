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
<script type="text/javascript">
$(function()
{
	$('#from').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: 0,
		maxDate: '+1Y',
		changeMonth: true,
		changeYear: true,
		onSelect: function(selectedDate) { $('#until').datepicker('option', 'minDate', selectedDate);  }
	});

	$('#until').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: 0,
		maxDate: '+1Y',
		changeMonth: true,
		changeYear: true,
	});

	$(document).ready(function()
	{
		$('#reg').focus();
	});

	$('#form').submit(function() {
		$('#submit').attr('disabled', 'disabled');
	});

	/* Input event handlers */
	$('#once').click(function()	{
		$('#until').attr('disabled', '');
		days_enable(0);
	});

	$('#daily').click(function() {
		$('#until').removeAttr('disabled');
		$('#all').attr('checked', '');
		days_check(1);
		days_enable(0);
	});

	$('#each').click(function()	{
		$('#until').removeAttr('disabled');
		$('#all').removeAttr('disabled');
		days_enable(1);
	});

	$('#all').click(function() {
		days_check($(this).prop('checked'));
	});

	var days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

	function days_check(b) {

		jQuery.each(days, function() {
			$('#' + this).prop('checked', b);
		});
	}

	function days_enable(b) {

		if (b)
		{
			$('#all').removeAttr('disabled');

			jQuery.each(days, function() {
				$('#' + this).removeAttr('disabled', '');
			});
		}
		else
		{
			$('#all').attr('disabled', '');

			jQuery.each(days, function() {
				$('#' + this).attr('disabled', '');
			});
		}
	}
});
</script>
<form method="post" action="?page=addflight">
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
				<div class="cell label"><?= $STRINGS['reg'] ?></div>
				<div class="cell">
					<input type="text" name="reg" id="reg"
					 value="<?= valueFromRequest('reg', INP_POST) ?>"/>
				</div>
			</div>
<?php if (isset($_POST['flight']) && !$error && !$model) { ?>
			<div class="row">
				<div class="cell label"><?= $STRINGS['icaomodel'] ?></div>
				<div class="cell">
					<input type="text" name="model" id="model"
						value="<?= valueFromRequest('model', INP_POST) ?>"/>
						<span>
							<a href="https://www.airframes.org/">[?]</a>
						</span>
				</div>
			</div>
<?php } ?>
			<div class="row">
				<div class="cell label"><?= $STRINGS['flight'] ?></div>
				<div class="cell">
					<input type="text" name="flight" id="flight"
					 value="<?= valueFromRequest('flight', INP_POST) ?>"/>
<?php
					if ($mobile)
					{
?>
						<div>
<?php
					}

					if (!isset($_POST['type']))
					{
?>
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
<?php
					}
					else
					{
?>
						<label>
							<input type="radio" name="type"
							 value="pax-regular" <?= checkedIfInPost('type', 'pax-regular', 'radio') ?>/><?= $STRINGS['pax-regular'] ?>
						</label>
						<label>
							<input type="radio" name="type"
							 value="cargo" <?= checkedIfInPost('type', 'cargo', 'radio') ?>/><?= $STRINGS['cargo'] ?>
						</label>
						<label>
							<input type="radio" name="type"
							 value="ferry" <?= checkedIfInPost('type', 'ferry', 'radio') ?>/><?= $STRINGS['ferry'] ?>
						</label>
<?php
					}

					if ($mobile)
					{
?>
					</div>
<?php
					}
?>
				</div>
			</div>
<?php if (isset($_POST['flight']) && !$error && !$airline) { ?>
			<div class="row">
				<div class="cell label"><?= $STRINGS['airline'] ?></div>
				<div class="cell">
					<input type="text" name="code" value="<?= valueFromRequest('code', INP_POST) ?>"/>
					<input type="text" name="airline" value="<?= valueFromRequest('airline', INP_POST) ?>"/>
						<span>[Code] | [Name]</span>
				</div>
			</div>
<?php } ?>
			<div class="row">
				<div class="cell"></div>
				<div class="cell">
					<label><input type="radio" name="direction" value="arrival" <?= $dir == 'arrival' ? ' checked="checked" ' : '' ?>/><?= $STRINGS['arrival'] ?></label>
					<label><input type="radio" name="direction" value="departure" <?= $dir == 'departure' ? ' checked="checked" ' : '' ?>/><?= $STRINGS['departure'] ?></label>
				</div>
			</div>

			<div class="row">
				<div class="cell label"><?= ucfirst($STRINGS['from']) ?></div>
				<div class="cell">
					<select id="airport-icao" name="airport">
						<option value="+" selected="selected">&lt;Add new&gt;</option>
						<?php // 'SELECT `id`,`icao`,`name` FROM `airports` ORDER BY `name`' ?>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="cell label"><?= $STRINGS['date'] ?></div>
				<div class="cell">
					<input type="text" name="from" id="from" value="<?= valueFromRequest('from', INP_POST) ?>"/>
					<div style="display: inline;"><?= 'arrival' == $dir ? $STRINGS['sta'] : $STRINGS['std'] ?>:</div>
					<div style="display: inline;">
						<input type="text" name="time" id="time" style="margin-right: 0.5em;"
						 value="<?= valueFromRequest('time', INP_POST) ?>"/>HH:MM (<?= $STRINGS['local'] ?>)
					</div>
					<div class="cell">
<?php
					if (!isset($_POST['interval']))
					{
?>
						<label><input type="radio" name="interval" value="once" id="once" checked="checked" /><?= $STRINGS['once'] ?></label><br>
						<label><input type="radio" name="interval" value="daily" id="daily"/><?= $STRINGS['daily'] ?></label><br>
						<label><input type="radio" name="interval" value="each" id="each"/><?= $STRINGS['each'] ?></label>
						<div style="margin-left: 1em; display: inline;">
							<label><input type="checkbox" name="day[1]" id="mon" disabled /><?= $STRINGS['mon'] ?></label>
							<label><input type="checkbox" name="day[2]" id="tue" disabled /><?= $STRINGS['tue'] ?></label>
							<label><input type="checkbox" name="day[3]" id="wed" disabled /><?= $STRINGS['wed'] ?></label>
							<label><input type="checkbox" name="day[4]" id="thu" disabled /><?= $STRINGS['thu'] ?></label>
							<label><input type="checkbox" name="day[5]" id="fri" disabled /><?= $STRINGS['fri'] ?></label>
							<label><input type="checkbox" name="day[6]" id="sat" disabled /><?= $STRINGS['sat'] ?></label>
							<label><input type="checkbox" name="day[7]" id="sun" disabled /><?= $STRINGS['sun'] ?></label>
							<label><input type="checkbox" name="day[0]" id="all" disabled /><b><?= $STRINGS['all'] ?></b></label>
						</div>
						<div class="cell">
							<div style="display: inline;"><?= $STRINGS['until'] ?>:</div>
							<input type="text" name="until" id="until"
							 value="<?= valueFromRequest('until', INP_POST) ?>" disabled>
						</div>
<?php
					}
					else
					{
						if ('each' == $_POST['interval'])
						{
?>
						<label><input type="radio" name="interval" value="once" id="once" <?= checkedIfInPost('interval', 'once', 'radio') ?>/><?= $STRINGS['once'] ?></label><br>
						<label><input type="radio" name="interval" value="daily" id="daily" <?= checkedIfInPost('interval', 'daily', 'radio') ?>/><?= $STRINGS['daily'] ?></label><br>
						<label><input type="radio" name="interval" value="each" id="each" <?= checkedIfInPost('interval', 'each', 'radio') ?>/><?= $STRINGS['each'] ?></label>
						<div style="margin-left: 1em; display: inline;">
							<label><input type="checkbox" name="day[1]" id="mon" <?= checkedIfInPost('day', 1) ?>/><?= $STRINGS['mon'] ?></label>
							<label><input type="checkbox" name="day[2]" id="tue" <?= checkedIfInPost('day', 2) ?>/><?= $STRINGS['tue'] ?></label>
							<label><input type="checkbox" name="day[3]" id="wed" <?= checkedIfInPost('day', 3) ?>/><?= $STRINGS['wed'] ?></label>
							<label><input type="checkbox" name="day[4]" id="thu" <?= checkedIfInPost('day', 4) ?>/><?= $STRINGS['thu'] ?></label>
							<label><input type="checkbox" name="day[5]" id="fri" <?= checkedIfInPost('day', 5) ?>/><?= $STRINGS['fri'] ?></label>
							<label><input type="checkbox" name="day[6]" id="sat" <?= checkedIfInPost('day', 6) ?>/><?= $STRINGS['sat'] ?></label>
							<label><input type="checkbox" name="day[7]" id="sun" <?= checkedIfInPost('day', 7) ?>/><?= $STRINGS['sun'] ?></label>
							<label><input type="checkbox" name="day[0]" id="all" <?= checkedIfInPost('day', 0) ?>/><b><?= $STRINGS['all'] ?></b></label>
						</div>
						<div class="cell">
							<div style="display: inline;"><?= $STRINGS['until'] ?>:</div>
							<input type="text" name="until" id="until"
							 value="<?= valueFromRequest('until', INP_POST) ?>">
						</div>
<?php
						}
						else
						{
?>
						<label><input type="radio" name="interval" value="once" id="once" <?= checkedIfInPost('interval', 'once', 'radio') ?>/><?= $STRINGS['once'] ?></label><br>
						<label><input type="radio" name="interval" value="daily" id="daily" <?= checkedIfInPost('interval', 'daily', 'radio') ?>/><?= $STRINGS['daily'] ?></label><br>
						<label><input type="radio" name="interval" value="each" id="each" <?= checkedIfInPost('interval', 'each', 'radio') ?>/><?= $STRINGS['each'] ?></label>
						<div style="margin-left: 1em; display: inline;">
							<label><input type="checkbox" name="day[1]" id="mon" disabled <?= checkedIfInPost('day', 1) ?>/><?= $STRINGS['mon'] ?></label>
							<label><input type="checkbox" name="day[2]" id="tue" disabled <?= checkedIfInPost('day', 2) ?>/><?= $STRINGS['tue'] ?></label>
							<label><input type="checkbox" name="day[3]" id="wed" disabled <?= checkedIfInPost('day', 3) ?>/><?= $STRINGS['wed'] ?></label>
							<label><input type="checkbox" name="day[4]" id="thu" disabled <?= checkedIfInPost('day', 4) ?>/><?= $STRINGS['thu'] ?></label>
							<label><input type="checkbox" name="day[5]" id="fri" disabled <?= checkedIfInPost('day', 5) ?>/><?= $STRINGS['fri'] ?></label>
							<label><input type="checkbox" name="day[6]" id="sat" disabled <?= checkedIfInPost('day', 6) ?>/><?= $STRINGS['sat'] ?></label>
							<label><input type="checkbox" name="day[7]" id="sun" disabled <?= checkedIfInPost('day', 7) ?>/><?= $STRINGS['sun'] ?></label>
							<label><input type="checkbox" name="day[0]" id="all" disabled <?= checkedIfInPost('day', 0) ?>/><b><?= $STRINGS['all'] ?></b></label>
						</div>
						<div class="cell">
							<div style="display: inline;"><?= $STRINGS['until'] ?>:</div>
							<input type="text" name="until" id="until"
							 value="<?= valueFromRequest('until', INP_POST) ?>" disabled>
						</div>
<?php
						}
					}
?>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center"><input id="submit" type="submit" name="submit"/></div>
</form>
