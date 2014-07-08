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

function seterrorinfo($line, $info)
{
	global $error;

	if (!$error)
		$error = '';

	$error .= __FILE__."($line): ERROR: $info\n";

	return $error;
}

/******************************************************************************
 *
 ******************************************************************************/

$error = NULL;
$message = NULL;

if (isset($_POST['table']) &&
	isset($_POST['id']))
{
	$query = <<<SQL
		DELETE FROM `$_POST[table]`
		WHERE `id`=$_POST[id]
SQL
;

	if (mysql_query($query))
	{
		unset($_POST);
		$message = 'OK';
	}
	else
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
	}
}

if (isset($_POST['key']))
{
	$key = explode('-', $_POST['key']);	// 'arrival-67-417-2013-07-07 05:35:00'

	if (count($key) != 6)
	{
		$error = seterrorinfo(__LINE__, 'Invalid key.');
	}
	else
	{
		$key = array_combine(array('direction', 'airline', 'code', 'Y', 'm', 'dHMS'), $key);

		$template = <<<SQL
			SELECT '%' as `table`,
				`id`, `expected`, `airport`, `aircraft`, `model`
			FROM `%`
			WHERE `direction`='$key[direction]'
				AND `airline`=$key[airline]
				AND `code`=$key[code]
				AND `scheduled`='$key[Y]-$key[m]-$key[dHMS]'
SQL
;

		$query = <<<SQL
			SELECT
				CONCAT(`airlines`.`code`, '$key[code]') AS `flight`,
				`airlines`.`name` AS `airline`,
				`table`, `flights`.`id`,
				DATE_FORMAT('$key[Y]-$key[m]-$key[dHMS]', '%s') AS `scheduled`,
				DATE_FORMAT(`flights`.`expected`, '%s') AS `expected`,
				`airports`.`iata`,
				`airports`.`icao`,
				`airports`.`name` AS `airport`,
				`models`.`icao` AS `model`,
				`aircrafts`.`reg` as `reg`
			FROM (%s UNION ALL %s) AS `flights`
				LEFT JOIN `airlines` ON `airlines`.`id` = $key[airline]
				LEFT JOIN `airports` ON `airports`.`id` = `flights`.`airport`
				LEFT JOIN `aircrafts` ON `aircrafts`.`id` = `flights`.`aircraft`
				LEFT JOIN `models` ON `models`.`id` =`flights`.`model`
SQL
;

		$query = sprintf($query,
						 '%Y-%m-%d %H:%i',
						 '%Y-%m-%d %H:%i',
						 str_replace("%", "history", $template),
						 str_replace("%", "flights", $template));

		$result = mysql_query($query);

		if (!$result)
		{
			$error = seterrorinfo(__LINE__,
						sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
		}
		else
		{
			$count = mysql_num_rows($result);

			if ($count)
			{
				$row = mysql_fetch_assoc($result);
?>
<div id="schedule">
	<h4>Remove Duplicate: <?php echo "$row[flight] ($row[airline])"; ?></h4>
	<!-- http://de.flightaware.com/live/flight/DLH511 -->
	<table summary="schedule">
		<thead>
			<tr>
				<th>
				<th>Table
				<!--th class="sep">Flight-->
				<!--th class="sep">Airline-->
				<th class="sep">Scheduled
				<th class="sep">Expected
				<th class="sep">IATA
				<th class="sep">ICAO
				<th class="sep">From
				<th class="sep">Type
				<th class="sep">Reg
			</tr>
		</thead>
		<tfoot></tfoot>
		<tbody>
<?php
				while ($row)
				{
?>
			<tr>
				<td class="button">
<?php
					if ($count > 1)
					{
?>
					<form class="stretched" method="post" action="?page=rmdup"
						style="padding: 0"
						onsubmit="document.getElementById('submit').disabled=true;">
						<input type="hidden" name="table" value="<?php echo $row['table']; ?>">
						<input type="hidden" name="id" value="<?php echo $row['id']; ?>">
						<input type="submit" class="del" type="button" value="">
					</form>
<?php
					}
?>
				</td>
<?php
					unset($row['id']);
					unset($row['flight']);
					unset($row['airline']);

					foreach ($row as $key => $val)
						echo "<td>$val</td>";
?>
			</tr>
<?php
					$row = mysql_fetch_assoc($result);
				}
			}

			mysql_free_result($result);
		}
	}
?>
		</tbody>
	</table>
</div>
<?php
}

if ($error || !isset($_POST['key']))
{
?>
<form class="stretched" method="post" action="?page=rmdup"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend>Remove duplicate</legend>
<?php if ($error) { ?>
		<div id="notification" class="error">
			<?php echo $error; ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="success">
			<?php echo $message; ?>
		</div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label">Key</div>
				<div class="cell">
					<input type="text" id="key" name="key" tabindex="1"
					 style="width: 40em;"
					 value="<?php Input_SetValue('key', INP_POST | INP_GET, 'arrival-'); ?>" autofocus>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="submit" id="submit"  tabindex="2" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
<?php
}
?>
