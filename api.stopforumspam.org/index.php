<?php
/*
{
  "success": 1,
  "username": {
    "lastseen": "2015-12-18 19:38:00",
    "frequency": 1,
    "appears": 1,
    "confidence": 16.34
  },
  "email": {
    "lastseen": "2015-12-18 20:35:11",
    "frequency": 536,
    "appears": 1,
    "confidence": 99.05
  },
  "ip": {
    "lastseen": "2015-12-18 20:36:15",
    "frequency": 65535,
    "appears": 1,
    "confidence": 99.99
  }
},
{
  "success": 0,
  "error": "request not understood"
}
*/
	$db = [
		'username' => [
			'nospam' => [
				'appears' => 0,
			],
			'spammer' => [
				'appears' => 1,
				'frequency' => 1,
				'lastseen' => '2015-12-18 19:38:00',
				'confidence' => 98.34,
			],
		],
		'email' => [
			'nospam@flederwiesel.com' => [
				'appears' => 0,
			],
			'notsure@gmail.com' => [
				'appears' => 1,
				'frequency' => 1,
				'lastseen' => '2015-12-18 20:35:11',
				'confidence' => 50.0,
			],
			'spam@gmail.com' => [
				'appears' => 1,
				'frequency' => 536,
				'lastseen' => '2015-12-18 20:35:11',
				'confidence' => 99.05,
			],
		],
		'ip' => [
			'127.0.0.1' => [
				'appears' => 0,
			],
			'46.118.155.73' => [
				'appears' => 1,
				'frequency' => 65535,
				'lastseen' => '2015-12-18 20:36:15',
				'confidence' => 99.99,
			],
		],
	];

	$notfound = [
		'appears' => 0,
		'frequency' => 0,
	];

	$status = new StdClass();

	if ($status)
	{
		$status->success = 0;

		foreach ($_GET as $key => $value)
		{
			if (isset($db[$key]))
			{
				if (isset($db[$key][$value]))
					$status->{$key} = $db[$key][$value];
				else
					$status->{$key} = $notfound;

				$status->success = 1;
			}
		}

		if (!$status->success)
			$status->error = "request not understood";

		echo json_encode($status);
	}
?>
