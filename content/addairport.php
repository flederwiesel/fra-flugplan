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

require_once '.config';

// http://www.php.net/manual/en/security.magicquotes.disabling.php
if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

if (!empty($_GET))
{
	$hdbc = mysql_connect(HOSTNAME, USERNAME, PASSWORD);

	if (!$hdbc)
	{
		echo mysql_error();
	}
	else
	{
		if (!mysql_select_db(DBNAME, $hdbc))
		{
			echo mysql_error();
		}
		else
		{
			mysql_set_charset("utf8");
		}

		if (isset($_GET['query']))
		{
			$statements = explode(";", $_GET['query']);

			foreach ($statements as $statement)
			{
				if (strlen($statement))
				{
					$result = mysql_query($statement);

					if (!$result)
					{
						echo "$statement: ".mysql_error();
						break;
					}
				}
			}
		}
	}
}
else
{

$theme = 'F70';

?>
<!DOCTYPE html>
<html  lang="en">
<head>
	<meta charset="utf-8">
	<meta name="author" content="Tobias Kühne">
	<meta name="description" content="24h flight forecast for Frankfurt/Main airport with aircraft registrations">
	<meta name="keywords" content="fra frankfurt airport spotter">
	<meta name="robots" content="index, nofollow">
	<meta name="generator" content="Programmer's Notepad">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

	<link rel="stylesheet" type="text/css" media="screen, projection, handheld, print" href="themes/<?php echo "$theme"; ?>/css/global.css">
	<link rel="stylesheet" type="text/css" media="screen, projection" href="themes/<?php echo "$theme"; ?>/css/desktop.css">
	<link rel="stylesheet" type="text/css" media="print" href="themes/<?php echo "$theme"; ?>/css/print.css">
	<script type="text/javascript" src="script/ajax.js"></script>
	<script>
	function post()
	{
		result = false;

		iata = document.getElementById('iata').value;
		icao = document.getElementById('icao').value;
		name = document.getElementById('name').value;

		if ('' == iata)
		{
			document.getElementById('iata').style.background = "#ffcccc";
		}
		else
		{
			document.getElementById('iata').style.background = "#ffffff";

			if ('' == icao)
			{
				document.getElementById('icao').style.background = "#ffcccc";
			}
			else
			{
				document.getElementById('icao').style.background = "#ffffff";

				if ('' == name)
				{
					document.getElementById('name').style.background = "#ffcccc";
				}
				else
				{
					document.getElementById('name').style.background = "#ffffff";

					url = "?query=INSERT INTO `airports`(`iata`, `icao`, `name`) VALUES ('" + iata + "', '" + icao + "', '" + name + "'); " +
						  "INSERT INTO `alias`(`table`, `object`, `name`) VALUES (0, LAST_INSERT_ID(), '" + name + "');"

					response = AjaxCallServer(url);

					if (0 == response.length)
					{
						result = true;
					}
					else
					{
						// error
						document.getElementById('result').innerHTML = response;
						document.getElementById('result').style.background = "#ff4040";
						document.getElementById('result-div').style.display = 'inline';
						document.getElementById('result-div').value = 'visible';
					}
				}
			}
		}

		return result;
	}
	</script>
</head>

<body>
<form method="post" action="?" onsubmit="return post()">
<div class="white">
<div class="table">
 <div class="row" id="result-div" style="display: none;">
  <div class="result" id="result"></div>
 </div>
 <div class="row">
  <div class="cell">IATA:</div>
  <div class="cell">ICAO:</div>
  <div class="cell">Name:</div>
 </div>
 <div class="row">
  <div class="cell"><input type="text" id="iata" value="" /></div>
  <div class="cell"><input type="text" id="icao" value="" /></div>
  <div class="cell"><input type="text" id="name" value="" /></div>
 </div>
</div>
<input type="submit" />
</div>
</form>
</body>
</html>

<?php
}
?>
