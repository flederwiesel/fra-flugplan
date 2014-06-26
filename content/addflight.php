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

function DefaultCheck($name, $value)
{
	if (is_array($_POST[$name]))
	{
		/* Checkboxes "day[1] .. day[7], day[0]" */
		$a = $_POST[$name];

		if (isset($a[$value]))
			echo ' checked ';
	}
	else
	{
		/* Everything else */
		if ($value == $_POST[$name])
			echo ' checked="checked" ';
	}
}

function CheckPostVariables(&$notice)
{
	global $lang;

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
		$error = $lang['unexpected'];
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
			$error = $lang['unexpected'];
		}

		if (!$error)
		{
			if (!('arrival'   == $_POST['direction'] ||
				  'departure' == $_POST['direction']))
			{
				/* Should never be seen in normal op... */
				$error = $lang['unexpected'];
			}
			else
			{
				if ('each' == $_POST['interval'] ||
					'daily' == $_POST['interval'])
				{
					if (!isset($_POST['until']))
					{
						$notice = $lang['untilinvalid'];
					}
					else
					{
						if ('' == $_POST['until'])
						{
							$notice = $lang['untilinvalid'];
						}
						else
						{
							if ('each' == $_POST['interval'])
							{
								if (!isset($_POST['day']))
								{
									$notice = $lang['wdays'];
								}
								else
								{
									if (0 == count($_POST['day']))
										$notice = $lang['wdays'];
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
	global $lang;

	$error = null;

	$type = $_POST['type'];
	$dir = $_POST['direction'];
	$flight = null;

	if (!isset($_POST['reg']))
	{
		$reg = NULL;
	}
	else
	{
		if (0 == strlen($_POST['reg']))
		{
			$reg = NULL;
		}
		else
		{
			if (preg_match('/^[a-zA-Z]+-?[a-zA-Z0-9]+$/', $_POST['reg'], $reg))
				$reg = $reg[0];
			else
				$error = $lang['invalidreg'];
		}
	}

	if (!$error)
	{
		if (!preg_match('/^([0-9][A-Z]|[A-Z][0-9]|[A-Z]{2,3})([0-9]{3,4}[A-Z]?)$/',
						strtoupper($_POST['flight']), $flight))
		{
			$error = $lang['invalidflight'];
		}
		else
		{
			array_shift($flight);

			$scheduled = mktime_c($_POST['from'], $_POST['time']);

			if (-1 == $scheduled)
			{
				$error = $lang['invaliddatetime'];
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
						$error = $lang['untilinvalid'];
				}
			}
		}
	}

	return $error;
}

function GetRegId(&$reg, &$model)
{
	global $lang;

	$error = null;

	$query = "SELECT `id`, `model` FROM `aircrafts` WHERE `reg`='$reg'";
	$result = mysql_query($query);

	if (!$result)
	{
		$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
	}
	else
	{
		$row = mysql_fetch_row($result);

		if ($row)
		{
			$reg = $row[0];
			$model = $row[1];
		}

		mysql_free_result($result);
	}

	return $error;
}

function GetPostRegId(&$reg, &$model)
{
	global $lang;
	global $user;

	$error = null;

	// TODO: curl http://www.airframes.org/ --data reg=D-AIRY | awk
	if (!isset($_POST['model']))
	{
		$reg = null;
		$model = null;
	}
	else
	{
		if ('' == $_POST['model'])
		{
			$reg = null;
			$model = null;
		}
		else
		{
			$query = "SELECT `id` FROM `models` WHERE `icao`='$_POST[model]'";
			$result = mysql_query($query);

			if (!$result)
			{
				$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
			}
			else
			{
				$row = mysql_fetch_row($result);

				if (!$row)
				{
					$model = null;
				}
				else
				{
					$model = $row[0];
					$query = "INSERT INTO `aircrafts`(`uid`, `reg`, `model`)".
							 " VALUES(".$user->id().", '$reg', $model)";

					if (!mysql_query($query))
					{
						$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
					}
					else
					{
						$result1 = mysql_query("SELECT LAST_INSERT_ID()");

						if (!$result1)
						{
							$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
						}
						else
						{
							$row = mysql_fetch_row($result1);
							$reg = $row ? $row[0] : null;

							mysql_free_result($result1);
						}
					}
				}

				mysql_free_result($result);
			}
		}
	}

	return $error;
}

function GetAirlineId(&$airline, $flight)
{
	global $lang;

	$error = null;
	$airline = null;

	$query = "SELECT `id` FROM `airlines` WHERE `code`='$flight[0]'";
	$result = mysql_query($query);

	if (!$result)
	{
		$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
	}
	else
	{
		$row = mysql_fetch_row($result);

		if ($row)
			$airline = $row[0];

		mysql_free_result($result);
	}

	return $error;
}

function GetPostAirlineId(&$airline)
{
	global $lang;
	global $user;

	$error = null;

	if (isset($_POST['code']) &&
		isset($_POST['airline']))
	{
		if (strlen($_POST['code']) &&
			strlen($_POST['airline']))
		{
			$query = "INSERT INTO `airlines`(`uid`, `code`, `name`)".
					 " VALUES(".$user->id().", '$_POST[code]', '$_POST[airline]')";

			if (!mysql_query($query))
			{
				$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
			}
			else
			{
				$result = mysql_query("SELECT LAST_INSERT_ID()");

				if (!$result)
				{
					$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
				}
				else
				{
					$row = mysql_fetch_row($result);
					$airline = $row ? $row[0] : null;

					mysql_free_result($result);
				}
			}
		}
	}

	return $error;
}

$error = null;
$airline = null;
$model = null;

if ($_POST)
{
	if (!$user)
	{
		$error = $lang['notloggedin'];
	}
	else
	{
		$perms = $user->permissions();

		if (!('1' == $perms[0]))
		{
			$error = $lang['nopermission'];
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
										//&&$notice = $reg ? $lang['typeunknown'] : $lang['needtype'];

										/* Set $airline to something different from null to suppress notice, since
										   we are already notifiying about unknown aircraft type... */
										$airline = $flight[0];
									}
								}
							}
						}
					}

					if (!$error)
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
								$notice = $lang['nosuchairline'];
							}
							else
							{
								$insert = true;
								$days = $_POST['day'];

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
										$query = sprintf(
											"INSERT INTO `flights`".
											" (`uid`, `type`, `direction`, `airline`, `code`, ".
											"  `scheduled`, `airport`, `model`, `aircraft`)".
											"VALUES(".
											" '%s', '$type', '$dir', $airline, '$flight[1]', ".
											" '%s', %lu, %s, %s);",
											$user->id(),
											strftime('%Y-%m-%d %H:%M:%S', $scheduled),
											$_POST['airport'],
											$model ? $model : 'NULL',
											$reg ? $reg : 'NULL');

										if (!mysql_query($query))
											$error = mysql_error();
									}

									if ($until)
										$scheduled = strtotime('+1 day', $scheduled);
								}
								while ($scheduled <= $until && !$error);

								if (!$error)
								{
									$message = $lang['addflsuccess'];

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
		<legend><?php echo $lang['addflight']; ?></legend>
<?php if (isset($error)) { ?>
		<div id="notification" class="error"><?php echo $error; ?></div>
<?php } else if (isset($notice)) { ?>
		<div id="notification" class="notice"><?php echo $notice; ?></div>
<?php } else if (isset($message)) { ?>
		<div id="notification" class="success"><?php echo $message; ?></div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo $lang['reg']; ?></div>
				<div class="cell">
					<input type="text" name="reg" id="reg"
					 value="<?php Input_SetValue('reg', INP_POST, 'D-AIRY'); ?>"/>
				</div>
			</div>
<?php if (isset($_POST['flight']) && !$error && !$model) { ?>
			<div class="row">
				<div class="cell label"><?php echo $lang['icaomodel']; ?></div>
				<div class="cell">
					<input type="text" name="model" id="model"
						value="<?php Input_SetValue('model', INP_POST, 'A321'); ?>"/>
						<span>
							<a href="http://www.airlinecodes.co.uk/arctypes.asp">[?]</a>
							<a href="http://www.airframes.org/">[?]</a>
						</span>
				</div>
			</div>
<?php } ?>
			<div class="row">
				<div class="cell label"><?php echo $lang['flight']; ?></div>
				<div class="cell">
					<input type="text" name="flight" id="flight"
					 value="<?php Input_SetValue('flight', INP_POST, 'QQ9999'); ?>"/>
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
							 value="pax-regular" checked="checked"><?php echo $lang['pax-regular']; ?>
						</label>
						<label>
							<input type="radio" name="type"
							 value="cargo"><?php echo $lang['cargo']; ?>
						</label>
						<label>
							<input type="radio" name="type"
							 value="ferry"><?php echo $lang['ferry']; ?>
						</label>
<?php
						}
						else
						{
?>
						<label>
							<input type="radio" name="type"
							 value="pax-regular" <?php DefaultCheck('type', 'pax-regular'); ?>/><?php echo $lang['pax-regular']; ?>
						</label>
						<label>
							<input type="radio" name="type"
							 value="cargo" <?php DefaultCheck('type', 'cargo'); ?>/><?php echo $lang['cargo']; ?>
						</label>
						<label>
							<input type="radio" name="type"
							 value="ferry" <?php DefaultCheck('type', 'ferry'); ?>/><?php echo $lang['ferry']; ?>
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
				<div class="cell label"><?php echo $lang['airline']; ?></div>
				<div class="cell">
					<input type="text" name="code" value="<?php Input_SetValue('code', INP_POST, ''); ?>"/>
					<input type="text" name="airline" value="<?php Input_SetValue('airline', INP_POST, ''); ?>"/>
						<span>
						<!--
							Show tooltip leading to
							http://www.frankfurt-airport.de/content/frankfurt_airport/de/check-in_gepaeck/check-in/airlines_a-z.suffix.html/letter=A.html
						 -->[Code] | [Name]</span>
				</div>
			</div>
<?php } ?>
			<div class="row">
				<div class="cell"></div>
				<div class="cell">
					<label><input type="radio" name="direction" value="arrival" <?php if (!('departure' == $dir)) echo ' checked="checked" '; ?>/><?php echo $lang['arrival']; ?></label>
					<label><input type="radio" name="direction" value="departure" <?php if ('departure' == $dir) echo ' checked="checked" '; ?>/><?php echo $lang['departure']; ?></label>
				</div>
			</div>

			<div class="row">
				<div class="cell label"><?php echo $lang['from']; ?></div>
				<div class="cell">
					<select id="airport-icao" name="airport">
<?php
						$result = mysql_query('SELECT `id`,`icao`,`name` FROM `airports` ORDER BY `name`');

						if (!$result)
						{
							$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
						}
						else
						{
							while ($row = mysql_fetch_row($result))
							{
								echo "<option value=\"$row[0]\"";

								if (isset($_POST['airport']))
									if ($_POST['airport'] == $row[0])
										echo " selected=\"selected\"";

								echo ">$row[2] - $row[1]</option>\n";
							}

							mysql_free_result($result);
						}
?>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="cell label"><?php echo $lang['date']; ?></div>
				<div class="cell">
					<input type="text" name="from" id="from" value="<?php Input_SetValue('from', INP_POST | INP_FORCE, date('d.m.Y')); ?>"/>
					<div style="display: inline;"><?php echo 'arrival' == $dir ? $lang['sta'] : $lang['std']; ?>:</div>
					<div style="display: inline;">
						<input type="text" name="time" id="time" style="margin-right: 0.5em;"
						 value="<?php Input_SetValue('time', INP_POST | INP_FORCE, date('H:i')); ?>"/>HH:MM (<?php echo $lang['local']; ?>)
					</div>
					<div class="cell">
<?php
					if (!isset($_POST['interval']))
					{
?>
						<label><input type="radio" name="interval" value="once" id="once" checked="checked" /><?php echo $lang['once']; ?></label><br>
						<label><input type="radio" name="interval" value="daily" id="daily"/><?php echo $lang['daily']; ?></label><br>
						<label><input type="radio" name="interval" value="each" id="each"/><?php echo $lang['each']; ?></label>
						<div style="margin-left: 1em; display: inline;">
							<label><input type="checkbox" name="day[1]" id="mon" disabled /><?php echo $lang['mon']; ?></label>
							<label><input type="checkbox" name="day[2]" id="tue" disabled /><?php echo $lang['tue']; ?></label>
							<label><input type="checkbox" name="day[3]" id="wed" disabled /><?php echo $lang['wed']; ?></label>
							<label><input type="checkbox" name="day[4]" id="thu" disabled /><?php echo $lang['thu']; ?></label>
							<label><input type="checkbox" name="day[5]" id="fri" disabled /><?php echo $lang['fri']; ?></label>
							<label><input type="checkbox" name="day[6]" id="sat" disabled /><?php echo $lang['sat']; ?></label>
							<label><input type="checkbox" name="day[7]" id="sun" disabled /><?php echo $lang['sun']; ?></label>
							<label><input type="checkbox" name="day[0]" id="all" disabled /><b><?php echo $lang['all']; ?></b></label>
						</div>
						<div class="cell">
							<div style="display: inline;"><?php echo $lang['until']; ?>:</div>
							<input type="text" name="until" id="until"
							 value="<?php Input_SetValue('until', INP_POST | INP_FORCE, date('d.m.Y')); ?>" disabled>
						</div>
<?php
					}
					else
					{
						if ('each' == $_POST['interval'])
						{
?>
						<label><input type="radio" name="interval" value="once" id="once" <?php DefaultCheck('interval', 'once'); ?>/><?php echo $lang['once']; ?></label><br>
						<label><input type="radio" name="interval" value="daily" id="daily" <?php DefaultCheck('interval', 'daily'); ?>/><?php echo $lang['daily']; ?></label><br>
						<label><input type="radio" name="interval" value="each" id="each" <?php DefaultCheck('interval', 'each'); ?>/><?php echo $lang['each']; ?></label>
						<div style="margin-left: 1em; display: inline;">
							<label><input type="checkbox" name="day[1]" id="mon" <?php DefaultCheck('day', 1); ?>/><?php echo $lang['mon']; ?></label>
							<label><input type="checkbox" name="day[2]" id="tue" <?php DefaultCheck('day', 2); ?>/><?php echo $lang['tue']; ?></label>
							<label><input type="checkbox" name="day[3]" id="wed" <?php DefaultCheck('day', 3); ?>/><?php echo $lang['wed']; ?></label>
							<label><input type="checkbox" name="day[4]" id="thu" <?php DefaultCheck('day', 4); ?>/><?php echo $lang['thu']; ?></label>
							<label><input type="checkbox" name="day[5]" id="fri" <?php DefaultCheck('day', 5); ?>/><?php echo $lang['fri']; ?></label>
							<label><input type="checkbox" name="day[6]" id="sat" <?php DefaultCheck('day', 6); ?>/><?php echo $lang['sat']; ?></label>
							<label><input type="checkbox" name="day[7]" id="sun" <?php DefaultCheck('day', 7); ?>/><?php echo $lang['sun']; ?></label>
							<label><input type="checkbox" name="day[0]" id="all" <?php DefaultCheck('day', 0); ?>/><b><?php echo $lang['all']; ?></b></label>
						</div>
						<div class="cell">
							<div style="display: inline;"><?php echo $lang['until']; ?>:</div>
							<input type="text" name="until" id="until"
							 value="<?php Input_SetValue('until', INP_POST | INP_FORCE, date('d.m.Y')); ?>">
						</div>
<?php
						}
						else
						{
?>
						<label><input type="radio" name="interval" value="once" id="once" <?php DefaultCheck('interval', 'once'); ?>/><?php echo $lang['once']; ?></label><br>
						<label><input type="radio" name="interval" value="daily" id="daily" <?php DefaultCheck('interval', 'daily'); ?>/><?php echo $lang['daily']; ?></label><br>
						<label><input type="radio" name="interval" value="each" id="each" <?php DefaultCheck('interval', 'each'); ?>/><?php echo $lang['each']; ?></label>
						<div style="margin-left: 1em; display: inline;">
							<label><input type="checkbox" name="day[1]" id="mon" disabled <?php DefaultCheck('day', 1); ?>/><?php echo $lang['mon']; ?></label>
							<label><input type="checkbox" name="day[2]" id="tue" disabled <?php DefaultCheck('day', 2); ?>/><?php echo $lang['tue']; ?></label>
							<label><input type="checkbox" name="day[3]" id="wed" disabled <?php DefaultCheck('day', 3); ?>/><?php echo $lang['wed']; ?></label>
							<label><input type="checkbox" name="day[4]" id="thu" disabled <?php DefaultCheck('day', 4); ?>/><?php echo $lang['thu']; ?></label>
							<label><input type="checkbox" name="day[5]" id="fri" disabled <?php DefaultCheck('day', 5); ?>/><?php echo $lang['fri']; ?></label>
							<label><input type="checkbox" name="day[6]" id="sat" disabled <?php DefaultCheck('day', 6); ?>/><?php echo $lang['sat']; ?></label>
							<label><input type="checkbox" name="day[7]" id="sun" disabled <?php DefaultCheck('day', 7); ?>/><?php echo $lang['sun']; ?></label>
							<label><input type="checkbox" name="day[0]" id="all" disabled <?php DefaultCheck('day', 0); ?>/><b><?php echo $lang['all']; ?></b></label>
						</div>
						<div class="cell">
							<div style="display: inline;"><?php echo $lang['until']; ?>:</div>
							<input type="text" name="until" id="until"
							 value="<?php Input_SetValue('until', INP_POST | INP_FORCE, date('d.m.Y')); ?>" disabled>
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
	<div class="center"><input id="submit" type="submit" name="submit"/></div>
</form>
