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

$_get = '';

if (isset($_GET))
{
	foreach ($_GET as $key => $value)
	{
		if (0 == strlen($_get))
			$_get .= '?';
		else
			$_get .= '&';

		$_get .= urlencode($key);

		if (strlen($value) > 0)
			$_get .= '='.urlencode($value);
	}
}

header("Location: https://www.fra-flugplan.de$_get"); /* Redirect browser */
?>

<!DOCTYPE html>
<html dir="ltr" lang="de-DE" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://ogp.me/ns/fb#">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width" />
<title>Expected &#8211; Live Schedule | Frankfurt Aviation Friends</title>
</head>
<body>
If you are not being redirected properly, please use the following link to go to the
<a href="http://www.fra-flugplan.de/fra-flugplan">Live Schedule</a>
</body>
