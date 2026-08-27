<?php

$PHOTODB_SEARCH_URLS = [
	'airfleets.net' => 'https://www.airfleets.net/recherche/?key={reg}',
	'airliners.net' => 'https://www.airliners.net/search?sortBy=datePhotographedYear&sortOrder=desc&keywords={reg}',
	'flugzeugbilder.de' => 'https://www.flugzeugbilder.de/v3/xresult.php?srt=d&ord=descending&rg-srch={reg}',
	'jetphotos.com' => 'https://www.jetphotos.com/showphotos.php?search-type=Advanced&keywords-type=reg&keywords-contain=0&sort-order=2&keywords={reg}',
	'netairspace.cc' => 'https://www.netairspace.cc/photos/search.php?search=Search&presentation=info&sortorder=latestfirst&registration={reg}',
	'planespotters.net' => 'https://www.planespotters.net/search?q={reg}',
];

if ($user)
	$photodb = $user->opt('photodb');
else
	$photodb = 'airliners.net';

$PhotodbSearchUrl = $PHOTODB_SEARCH_URLS[$photodb];

?>
