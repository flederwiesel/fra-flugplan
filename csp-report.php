<?php

/*******************************************************************************

Add a CSP report to JSON file log. Instead of logging each report, calculate a
sha256sum as key and increase counter if such record already exists. This way the
file should not grow infinitely, as the CSP report content should not vary that much.
Add a timestamp to the file as well as each record, so we can see whether certain
reports are still being generated on changing the rules.

JSON Example:

```
{
  "reports": {
    "6692e272a2b8b3ce95e378758b4d8fe6ad8fcdc216d3eb1f1d374e8c49bbeaae": {
      "count": 1,
      "updated": "2026-10-19T21:21:24+0200",
      "csp-report": {
        ...
      }
    }
  },
  "updated": "2026-10-19T21:21:24+0200"
}
```

Environment variables being used:

* `FRA_FLUGPLAN_LOCALSTATEDIR` - where the log dir for the report is located under.
* `FRA_FLUGPLAN_DOCUMENT_ROOT` - derive LOCALSTATEDIR from this, if the above is n/a.
* `REQUEST_BODY` - file path to contain the POST body for cli debugging.

*****************************************************************************`*/

class CspRequestError extends ErrorException { };

$status = 204;
$message = null;
$file = null;

try
{
	if ($_SERVER['REQUEST_METHOD'] != 'POST')
		throw new CspRequestError('Method not allowed.', 405);

	if ($_SERVER['CONTENT_TYPE'] != 'application/csp-report')
		throw new CspRequestError('Content-Type not Acceptable.', 406);

	// Use environment variables for cli script debugging
	if (isset($_SERVER['FRA_FLUGPLAN_LOCALSTATEDIR']))
	{
		$LOCALSTATEDIR = $_SERVER['FRA_FLUGPLAN_LOCALSTATEDIR'];
	}
	else
	{
		$DOCUMENT_ROOT = $_SERVER['FRA_FLUGPLAN_DOCUMENT_ROOT'] ?? $_SERVER['DOCUMENT_ROOT'];
		$LOCALSTATEDIR = "{$DOCUMENT_ROOT}/var";
	}

	$LOGDIR = "{$LOCALSTATEDIR}/log";

	$now = date('Y-m-d\TH:i:sO');
	$post = file_get_contents($_SERVER['REQUEST_BODY'] ?? 'php://input');

	if (!$post)
		throw new CspRequestError('Empty request.', 400);

	$report = json_decode($post, true, 16, JSON_THROW_ON_ERROR);

	if (!key_exists('csp-report', $report))
		throw new CspRequestError('Key "csp-report" is required.', 400);

	$sha256 = hash('sha256', $post);

	$file = fopen("{$LOGDIR}/csp.reports", "c+");

	if ($file === false)
	{
		$error = error_get_last();

		throw new ErrorException(
			$error['message'], $error['type'], E_ERROR, $error['file'], $error['line']
		);
	}

	flock($file, LOCK_EX);

	$json = stream_get_contents($file);
	$csp = $json ? json_decode($json, false, 16, JSON_THROW_ON_ERROR) : null;

	if ($csp === null)
	{
		// Empty file
		$csp = (object)[
			'reports' => (object)[],
			'updated' => null
		];
	}
	else
	{
		if (property_exists($csp->reports, $sha256))
		{
			$csp->reports->{$sha256}->count++;
			$csp->reports->{$sha256}->updated = date('Y-m-d\TH:i:sO');
			$post = null;
		}
	}

	if ($post)
	{
		$csp->reports->{$sha256} = (object)[
			'count' => 1,
			'updated' => $now,
			'csp-report' => $report['csp-report']
		];
	}

	$csp->updated = $now;

	$json = json_encode($csp, JSON_PRETTY_PRINT);

	rewind($file);
	ftruncate($file, 0);
	fwrite($file, $json);
}
catch (Exception $e)
{
	$uid = uniqid("csp-");

	syslog(
		LOG_ERR,
		"[{$uid}] {$e->getFile()}({$e->getLine()}): {$e->getMessage()}.\n".
		"{$e->getTraceAsString()}\n"
	);

	if (get_class($e) == 'CspRequestError')
	{
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	else
	{
		$status = 500;
		$message = 'An error occured while handling the request.';
	}

	$message = json_encode((object)[
		"error" => $message,
		"ref" => $uid
	]);
}
finally
{
	if ($file != null)
	{
		flock($file, LOCK_UN);
		fclose($file);
	}
}

http_response_code($status);

if ($message)
{
	header("Content-Type: application/json");

	echo $message;
}
?>
