<?php

require_once('../../.config');
require_once('transfer-types.php');
require_once('transfer-deps.php');

$error = null;
$rows = null;
$hdbc_1 = null;
$hdbc_3 = null;

$hdbc_1 = @new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, 'usr_web416_1');

if (!$hdbc_1)
{
	$error =  '('.__LINE__.')';
}
else
{
	if ($hdbc_1->connect_errno)
	{
		$error =  '('.__LINE__.'): '.$hdbc_1->connect_error;
		$hdbc_1 = null;
	}
	else
	{
		if (!$hdbc_1->set_charset('utf8'))
		{
			$error =  '('.__LINE__.'): '.$hdbc_1->error;
		}
		else
		{
			if (!$hdbc_1->query('START TRANSACTION'))
				$error =  '('.__LINE__.'): '.$hdbc_1->error;
		}
	}
}

if (!$error)
{
	$hdbc_3 = @new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, 'usr_web416_3');

	if (!$hdbc_3)
	{
		$error =  '('.__LINE__.')';
	}
	else
	{
		if ($hdbc_3->connect_errno)
		{
			$error =  '('.__LINE__.'): '.$hdbc_3->connect_error;
			$hdbc_3 = null;
		}
		else
		{
			if (!$hdbc_3->set_charset('utf8'))
			{
				$error =  '('.__LINE__.'): '.$hdbc_3->error;
			}
		}
	}
}

if (!$error)
{
	$query = "SELECT `id`,`fac`,`fno`,`direction`,`scheduled`,`expected`,`airport`,`aircraft`,`type` ".
			 "FROM `flights` ".
//			 "WHERE `scheduled` BETWEEN '2000-06-06 00:00:00' AND '2013-07-01 00:00:00'".
			 "ORDER BY `scheduled`,`expected` ".
			 "LIMIT 100";

	$result = $hdbc_1->query($query);

	if (!$result)
	{
		$error =  "(".__LINE__."): $query: $hdbc_1->error";
	}
	else
	{
		$n = 0;

		do
		{
			$row = $result->fetch_assoc();

			if (!$row)
			{
				$n = -1;

				if ($hdbc_1->errno)
					$error =  "(".__LINE__."): $query: $hdbc_1->error";
			}
			else
			{
				$rows[$n]['id'] = $row['id'];

				$rows[$n]['airline'] = $airlines[$row['fac']];
				$rows[$n]['code'] = $row['fno'];
				$rows[$n]['direction'] = $row['direction'];
				$rows[$n]['scheduled'] = $row['scheduled'];

				if ($row['expected'])
					$rows[$n]['expected'] = $row['expected'];
				else
					$rows[$n]['expected'] = null;

				if ($row['airport'])
					$rows[$n]['airport'] = $airports[$row['airport']];
				else
					$rows[$n]['airport'] = null;

				if ($row['aircraft'])
					$rows[$n]['aircraft'] = $aircrafts[$row['aircraft']];
				else
					$rows[$n]['aircraft'] = null;

				if ($row['type'])
					$rows[$n]['model'] = $models[$row['type']];
				else
					$rows[$n]['model'] = null;

				unset($row['fac']);
				unset($row['fno']);
				unset($row['type']);

				$n++;
			}
		}
		while ($n > -1);

		$result->free();
	}
}

if (!$error)
{
	if (!$hdbc_1->query('START TRANSACTION'))
	{
		$error =  '('.__LINE__.'): '.$hdbc_1->error;
	}
	else
	{
		if (!$hdbc_3->query('START TRANSACTION'))
		{
			$error =  '('.__LINE__.'): '.$hdbc_3->error;
		}
		else
		{
			if ($rows)
			{
				for ($n = 0; $n < count($rows); $n++)
				{
					$row = $rows[$n];

					$query = "INSERT INTO `flights`".
							 "(`uid`, `type`, `direction`, `airline`, `code`,".
							 " `scheduled`, `expected`, `airport`, `model`, `aircraft`)".
							 " VALUES (2, 'pax-regular', '$row[direction]', ".
							 "$row[airline], '$row[code]', '$row[scheduled]', ".
							 ($row['expected'] ? "'$row[expected]'" : "NULL").", ".
							 ($row['airport']  ? "$row[airport]"  : "NULL").", ".
							 ($row['model']    ? "$row[model]"    : "NULL").", ".
							 ($row['aircraft'] ? "$row[aircraft]" : "NULL").")";

					if (!$hdbc_3->query($query))
					{
						$error =  "(".__LINE__."): $query: $hdbc_3->error";
						break;
					}
					else
					{
						$query = "DELETE FROM `flights` WHERE `id`=$row[id]";

						if (!$hdbc_1->query($query))
						{
							$error =  "(".__LINE__."): $query: $hdbc_1->error";
							break;
						}
					}
				}
			}

			if ($error)
			{
				if (!$hdbc_3->query('ROLLBACK'))
					$error =  '('.__LINE__.'): '.$hdbc_3->error;
			}
			else
			{
				if (!$hdbc_3->query('COMMIT'))
					$error =  '('.__LINE__.'): '.$hdbc_3->error;
			}

			if ($error)
			{
				if (!$hdbc_1->query('ROLLBACK'))
					$error =  '('.__LINE__.'): '.$hdbc_1->error;
			}
			else
			{
				if (!$hdbc_1->query('COMMIT'))
					$error =  '('.__LINE__.'): '.$hdbc_1->error;
			}
		}
	}
}

if ($hdbc_3)
	$hdbc_3->close();

if ($hdbc_1)
	$hdbc_1->close();

if ($error)
	echo "$error\n";

?>
