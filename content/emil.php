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

@require_once '../.config';

$mail = 'Location: mailto:';
$mail .= ADMIN_EMAIL;

if (isset($_GET['subject']))
	$mail .= '&subject='.mb_encode_mimeheader($_GET['subject'], 'ISO-8859-1', 'Q');

if (isset($_GET['body']))
	$mail .= '&body='.mb_encode_mimeheader($_GET['body'], 'ISO-8859-1', 'Q');

header($mail);

session_start();

if (!isset($_SESSION['lang']))
	$_SESSION['lang'] = 'en';

@require_once "language/$_SESSION[lang].php";

?>

<!DOCTYPE HTML>
<html>
<head>
<title><?php echo PROJECT; ?> &ndash; <?php echo ORGANISATION; ?></title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="language" content="<?php echo $_SESSION['lang']; ?>">
<meta name="author" content="Tobias Kühne">
<meta name="description" content="24h flight forecast for Frankfurt/Main airport with aircraft registrations">
<meta name="keywords" content="fra eddf frankfurt airport aircraft spotter schedule">
<meta name="robots" content="noindex, nofollow">
<meta name="generator" content="Programmer's Notepad">
</head>
<body>
	<h1><?php echo ORGANISATION; ?></h1>
	<div><?php echo $lang['emil']; ?></div>
	<div>
	<?php
		$ref = null;

		if (isset($_SERVER['HTTP_REFERER']))
			if (strlen($_SERVER['HTTP_REFERER']))
				$ref = $_SERVER['HTTP_REFERER'];

		echo '<a href="'.($ref ? $ref : 'javascript:history.back()').'">';
		echo $lang['back'];
	?></a></div>
</body>
</html>
