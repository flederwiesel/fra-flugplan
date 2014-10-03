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

header("Location: http://www.flederwiesel.com/fra-schedule$_get"); /* Redirect browser */
?>

<!DOCTYPE html>
<html dir="ltr" lang="de-DE" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://ogp.me/ns/fb#">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width" />
<title>Expected &#8211; Live Schedule | Frankfurt Aviation Friends</title>
<link rel="pingback" href="http://www.frankfurt-aviation-friends.de/xmlrpc.php" />
	<link rel="canonical" href="http://www.frankfurt-aviation-friends.de/expected-2/" />
	<meta property="og:type" content="article" />
	<meta property="og:title" content="Expected &#8211; Live Schedule" />
	<meta property="og:url" content="http://www.frankfurt-aviation-friends.de/expected-2/" />
	<meta property="article:published_time" content="2012-09-06" />
	<meta property="article:modified_time" content="2012-09-07" />
	<meta property="article:author" content="http://www.frankfurt-aviation-friends.de/author/flederwiesel/" />
	<meta property="og:site_name" content="Frankfurt Aviation Friends" />
	<meta name="twitter:card" content="summary" />
</head>
<body>
If you are not being redirected properly, please use the following link to go to the
<a href="http://www.flederwiesel.com/fra-schedule">Live Schedule</a>
</body>
