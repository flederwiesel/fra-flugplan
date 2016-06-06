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
	$db = array(
		'username' => array(
			'nospam' => array(
				'appears' => 0,
			),
			'spam' => array(
				'appears' => 1,
				'frequency' => 1,
				'lastseen' => '2015-12-18 19:38:00',
				'confidence' => 98.34,
			),
		),
		'email' => array(
			'nospam@flederwiesel.com' => array(
				'appears' => 0,
			),
			'spam@gmail.com' => array(
				'appears' => 1,
				'frequency' => 536,
				'lastseen' => '2015-12-18 20:35:11',
				'confidence' => 99.05,
			),
		),
		'ip' => array(
			'127.0.0.1' => array(
				'appears' => 0,
			),
			'46.118.155.73' => array(
				'appears' => 1,
				'frequency' => 65535,
				'lastseen' => '2015-12-18 20:36:15',
				'confidence' => 99.99,
			),
		),
	);

	$notfound = array(
		'appears' => 0,
		'frequency' => 0,
	);

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
