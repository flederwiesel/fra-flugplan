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

function DefaultValue($name, $dflt)
{
	if (isset($_POST[$name]))
	{
		echo ' value = "'.$_POST[$name].'" ';
	}
	else
	{
		if (DEBUG)
			echo ' value="'.$dflt.'" ';
	}
}

function DefaultCheck($name, $value)
{
	if ($value == $_POST[$name])
		echo ' checked="checked" ';
}

function mktime_c($ddmmyyyy /* dd.mm.YYYY */, $hhmm /* HH.MM */)
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

function CheckPostVariables(&$notice)
{
	global $lang;

	$error = null;

	if (!(isset($_POST['reg']) &&
		  isset($_POST['flight']) &&
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

	if (!preg_match('/^[a-zA-Z]+-?[a-zA-Z0-9]+$/', $_POST['reg'], $reg))
	{
		$error = $lang['invalidreg'];
	}
	else
	{
		$reg = $reg[0];

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
					$query = "INSERT INTO `aircrafts`(`reg`, `model`) VALUES('$reg', $model)";

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

	$error = null;

	if (isset($_POST['code']) &&
		isset($_POST['airline']))
	{
		if (strlen($_POST['code']) &&
			strlen($_POST['airline']))
		{
			$query = "INSERT INTO `airlines`(`code`, `name`)".
					 " VALUES('$_POST[code]', '$_POST[airline]')";

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
								$notice = $reg ? $lang['typeunknown'] : $lang['needtype'];

								/* Set $airline to something different from null to suppress notice, since
								   we are already notifiying about unknown aircraft type... */
								$airline = $flight[0];
							}
						}
					}
				}
			}

			if (!$error && $model)
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
									" (`type`, `direction`, `airline`, `code`, ".
									"  `scheduled`, `airport`, `model`, `aircraft`)".
									"VALUES(".
									" '$type', '$dir', $airline, '$flight[1]', ".
									" '%s', %lu, $model, $reg);",
									strftime('%Y-%m-%d %H:%M:%S', $scheduled),
									$_POST['airport']);

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
							unset($_POST['time']);
							unset($_POST['from']);
							unset($_POST['interval']);
							unset($_POST['until']);
						}
					}
				}
			}
		}
	}
}

?>

<link type="text/css" rel="stylesheet" href="css/jquery.ui.datepicker.css">
<script type="text/javascript" src="script/ui/jquery.ui.core.js"></script>
<script type="text/javascript" src="script/ui/jquery.ui.widget.js"></script>
<script type="text/javascript" src="script/ui/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="script/ajax.js"></script>
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
		days_check($(this).attr('checked'));
	});

	var days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

	function days_check(b) {

		if (b)
		{
			jQuery.each(days, function() {
				$('#' + this).attr('checked', '');
			});
		}
		else
		{
			jQuery.each(days, function() {
				$('#' + this).removeAttr('checked', '');
			});
		}
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

<form id="form" method="post" action="?page=addflight">
	<fieldset>
		<legend><?php echo $lang['addflight']; ?></??></legend>
<?php if (isset($error)) { ?>
		<div id="notification" class="auth-error">
			<?php echo $error; ?>
		</div>
<?php } else if (isset($notice)) { ?>
		<div id="notification" class="auth-notice">
			<?php echo $notice; ?>
		</div>
<?php } else if (isset($message)) { ?>
		<div id="notification" class="auth-ok">
			<?php echo $message; ?>
		</div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo $lang['reg']; ?></div>
				<div class="cell">
					<input type="text" name="reg" id="reg" <?php DefaultValue('reg', 'D-AIRY'); ?>/>
				</div>
			</div>
<?php if ($_POST && !$model) { ?>
			<div class="row">
				<div class="cell label"><?php echo $lang['icaomodel']; ?></div>
				<div class="cell">
					<input type="text" name="model" id="model"
						<?php DefaultValue('model', 'A321'); ?>/>
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
					<input type="text" name="flight" id="flight" <?php DefaultValue('flight', 'QQ9999'); ?>/>
					<?php
						if (!isset($_POST['type']))
						{
					?>
						<label><input type="radio" name="type" value="pax-regular" checked="checked"><?php echo $lang['pax-regular']; ?></label>
						<label><input type="radio" name="type" value="cargo"><?php echo $lang['cargo']; ?></label>
						<label><input type="radio" name="type" value="ferry"><?php echo $lang['ferry']; ?></label>
					<?php
						}
						else
						{
					?>
						<label>
							<input type="radio" name="type" value="pax-regular"
							 <?php DefaultCheck('type', 'pax-regular'); ?>/><?php echo $lang['pax-regular']; ?>
						</label>
						<label>
							<input type="radio" name="type" value="cargo"
							 <?php DefaultCheck('type', 'cargo'); ?>/><?php echo $lang['cargo']; ?>
						</label>
						<label>
							<input type="radio" name="type" value="ferry"
							 <?php DefaultCheck('type', 'ferry'); ?>/><?php echo $lang['ferry']; ?>
						</label>
					<?php
						}
					?>
				</div>
			</div>
<?php if ($_POST && !$airline) { ?>
			<div class="row">
				<div class="cell label"><?php echo $lang['airline']; ?></div>
				<div class="cell">
					<input type="text" name="code" <?php DefaultValue('code', ''); ?>/>
					<input type="text" name="airline" <?php DefaultValue('airline', ''); ?>/>
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
								echo "<option value=\"$row[0]\">$row[2] - $row[1]</option>\n";

							mysql_free_result($result);
						}
?>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="cell label"><?php echo $lang['date']; ?></div>
				<div class="cell">
					<input type="text" name="from" id="from" <?php DefaultValue('from', date('d.m.Y')); ?>/>
					<div style="display: inline;"><?php echo 'arrival' == $dir ? $lang['sta'] : $lang['std']; ?>:</div>
					<div style="display: inline;">
						<input type="text" name="time" id="time" style="margin-right: 0.5em;"
							<?php DefaultValue('time', date('H:i')); ?>/>HH:MM (<?php echo $lang['local']; ?>)
					</div>
					<div class="cell">
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
							<input type="text" name="until" id="until" <?php DefaultValue('from', date('d.m.Y')); ?> disabled>
						</div>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center"><input id="submit" type="submit" name="submit"/></div>
</form>
