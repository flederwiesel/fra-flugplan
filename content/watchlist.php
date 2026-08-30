<?php

require_once 'classes/assets.php';

class WatchlistPostData
{
	private $add;
	private $del;
	private $upd;

	public function __construct($postData)
	{
		foreach (["add", "del", "upd"] as $member)
		{
			$this->{$member} = property_exists($postData, $member) ?
				$postData->{$member} : [];

			// Simple validation for now
			// TODO: Use swaggest/json-schema
			if (gettype($this->{$member}) !== "array")
				throw new InvalidArgumentException("Expected array.");

			foreach ($this->{$member} as $obj)
			{
				$type = gettype($obj);

				if ($member === "del")
				{
					if ($type !== "string")
						throw new InvalidArgumentException("Expected string, got {$type}.");
				}
				else
				{
					if ($type !== "object")
						throw new InvalidArgumentException("Expected object, got {$type}.");

					if (property_exists($obj, "notify"))
						$obj->notify = $obj->notify === true ? true : $obj->notify === 1;
					else
						$obj->notify = 0;
				}
			}
		}
	}

	public function __get(string $name) : array
	{
		if (property_exists($this, $name))
			return $this->{$name};

			return null;
	}

	public function containsNotifications() : bool
	{
		foreach ($this->add as $entry)
		{
			if ($entry->notify ?? false)
				return true;
		}

		foreach ($this->upd as $entry)
		{
			if ($entry->notify ?? false)
				return true;
		}

		return false;
	}
}

class WatchlistModel
{
	private PDO $db;
	private int $uid;
	private array $watchlist = [];	// Associate array [$reg] => {}

	public function __construct(PDO $db, ?int $uid = null)
	{
		$this->db = $db;
		$this->uid = $uid;
	}

	public function add(WatchlistPostData $postData)
	{
		$add = $postData->add;

		if (count($add) > 0)
		{
			$st = $this->db->prepare(<<<SQL
				/*[Q18]*/
				INSERT INTO `watchlist`(
					`user`,
					`reg`,
					`comment`,
					`notify`
				)
				VALUES(
					:uid,
					:reg,
					:comment,
					:notify
				)
				ON DUPLICATE KEY UPDATE
					`user` = :uid,
					`reg` = :reg,
					`comment` = :comment,
					`notify` = :notify

				SQL
			);

			foreach ($add as $entry)
			{
				if (!property_exists($entry, 'reg'))
					throw new ValueError('`reg` is required for "add".');

				$reg = strtoupper(trim($entry->reg));
				$comment = trim($entry->comment ?? "");
				// PDO does not handle bool values properly
				// (unless we use `bind()`), so use 0/1 here.
				$notify = $entry->notify ? 1 : 0;

				$st->execute([
					"uid" => $this->uid,
					"reg" => $reg,
					"comment" => $comment,
					"notify" => $notify,
				]);

				$this->watchlist[$reg] = (object)[
					"comment" => $comment,
					"notify" => $notify,
				];
			}

			ksort($this->watchlist);
		}
	}

	public function delete(WatchlistPostData $postData)
	{
		$del = $postData->del;

		if (count($del) > 0)
		{
			$st = $this->db->prepare(<<<SQL
				/*[Q16]*/
				DELETE `watchlist-notifications`
				FROM `watchlist-notifications`
				INNER JOIN (
					SELECT `id`
					FROM `watchlist`
					WHERE `user` = :uid
						AND `reg` = :reg
				) AS `watchlist`
					ON `watchlist`.`id` = `watchlist-notifications`.`watch`
				SQL
			);

			foreach ($del as $reg)
			{
				$reg = strtoupper(trim($reg));

				$st->execute([
					"uid" => $this->uid,
					"reg" => $reg,
				]);
			}

			$st = $this->db->prepare(<<<SQL
				/*[Q17]*/
				DELETE FROM `watchlist`
				WHERE
					`user` = :uid AND
					`reg` = :reg
				SQL
			);

			foreach ($del as $reg)
			{
				$reg = strtoupper(trim($reg));

				$st->execute([
					"uid" => $this->uid,
					"reg" => $reg,
				]);

				unset($this->watchlist[$reg]);
			}
		}
	}

	public function update(WatchlistPostData $postData)
	{
		$upd = $postData->upd;

		if (count($upd) > 0)
		{
			$st = $this->db->prepare(<<<SQL
				/*[Q20]*/
				UPDATE `watchlist`
				SET
					`reg` = :reg,
					`comment` = :comment,
					`notify` = :notify
				WHERE
					`user` = :uid AND
					`reg` = :prev
				SQL
			);

			foreach ($upd as $entry)
			{
				if (!property_exists($entry, 'prev'))
					throw new ValueError('`prev` is required for "upd".');

				if (!property_exists($entry, 'reg'))
					throw new ValueError('`reg` is required for "upd".');

				$prev = strtoupper(trim($entry->prev));
				$reg = strtoupper(trim($entry->reg));

				if (!$reg)
					$reg = $prev;

				$comment = trim($entry->comment);
				// PDO does not handle bool values properly
				// (unless we use `bind()`), so use 0/1 here.
				$notify = $entry->notify ? 1 : 0;

				$st->execute([
					"uid" => $this->uid,
					"prev" => $prev,
					"reg" => $reg,
					"comment" => $comment,
					"notify" => $notify,
				]);

				$this->watchlist[$prev]->reg = $reg;
				$this->watchlist[$prev]->comment = $comment;
				$this->watchlist[$prev]->notify = $notify;
			}

			ksort($this->watchlist);
		}
	}

	public function get() : array
	{
		$this->watchlist = [];

		$st = $this->db->prepare(<<<SQL
			/*[Q19]*/
			SELECT
				`reg`,
				`comment`,
				`notify`
			FROM
				`watchlist`
			WHERE
				`user` = ?
			ORDER BY
				`reg`
			SQL
		);

		$st->execute([$this->uid]);

		while ($row = $st->fetchObject())
			$this->watchlist[$row->reg] = (object)[
				"comment" => $row->comment,
				"notify" => $row->notify
			];

		return $this->watchlist;
	}

	public function watchlist() : array
	{
		return $this->watchlist;
	}
}

class Watchlist
{
	private WatchlistModel $model;

	public function __construct(PDO $db, int $uid)
	{
		$this->model = new WatchlistModel($db, $uid);
		$this->model->get();
	}

	public function data() : array
	{
		return $this->model->watchlist();
	}

	public function get()
	{
		return $this->model->get();
	}

	public function update(WatchlistPostData $postData)
	{
		$this->model->delete($postData);
		$this->model->update($postData);
		$this->model->add($postData);
	}

	public function renderTable($photodb, $PhotodbSearchUrl)
	{
		global $STRINGS;

		$watchlist = $this->model->watchlist();

?>
							<table>
								<thead>
									<tr>
										<th data-key="reg"><?= $STRINGS['reg'] ?></th>
										<th data-key="comment"><?= $STRINGS['comment'] ?></th>
										<th data-key="notify"><a href="#" id="toggle-notifications"><img src="<?= Asset::src('img/mail.png') ?>" alt="e-mail"></a></th>
										<th></th>
										<th></th>
									</tr>
								</thead>
								<tbody>
<?php
		if (0 == count($watchlist))
		{
?>
									<tr data-submit="add">
										<!-- inputs do not have names, POST values will be generated upon submit -->
										<td><input type="text" class="reg" value="" maxlength="31"></td>
										<td><input type="text" class="comment" value="" maxlength="255"></td>
										<td><input type="checkbox" class="notify" value=""></td>
										<td><button type="button" class="del"></button></td>
										<td><button type="button" class="add"></button></td>
									</tr>
<?php
		}
		else
		{
			foreach ($watchlist as $reg => $entry)
			{
?>
									<tr>
										<td>
<?php
				if (preg_match('/^\/.*\/$|[*?]/', $reg))
				{
?>
											<div></div>
<?php
				}
				else
				{
?>
											<div>
												<a href="<?=
													str_replace(
														[ '&', '{reg}' ],
														[ '&amp;', $reg ],
														$PhotodbSearchUrl
													) ?>" target="<?= $photodb ?>">
													<div></div>
												</a>
											</div>
<?php
				}
?>
											<input type="text" class="reg" value="<?= $reg ?>" maxlength="31">
										</td>
										<td><input type="text" class="comment" value="<?= htmlspecialchars($entry->comment) ?>" maxlength="255"></td>
										<td><input type="checkbox" class="notify" value=""<?= $entry->notify ? " checked" : "" ?>></td>
										<td><button type="button" class="del"></button></td>
										<td><button type="button" class="add"></button></td>
									</tr>
<?php
			}
		}
?>
								</tbody>
							</table>
<?php
	}
}

// As it makes no sense to directly compare regs to wildcards/regexes contained
// in the Watchlist::data(), filter those out and put into separate lists to be
// handled accordingly.  Note that - as opposed to regular watchlist entries,
// those lists contain only the comment, rather than the full watchlist entry.

class WatchlistMatcher
{
	private $watchlist;

	public function __construct(array $watchlist)
	{
		$this->watchlist = (object)[
			"reg" => [],
			"regex" => [],
			"wildcard" => [],
		];

		foreach ($watchlist as $key => $value)
		{
			// Force regs like "701" (Republic of Armenia) to string
			$key = strval($key);

			if (strchr($key, '*') || strchr($key, '?'))
			{
				$this->watchlist->wildcard[$key] = $value->comment;
				unset($watchlist[$key]);
			}
			else if ($key[0] == '/' && $key[-1] == '/')
			{
				$this->watchlist->regex[$key] = $value->comment;
				unset($watchlist[$key]);
			}
		}

		$this->watchlist->reg = $watchlist;
	}

	public function getComment(string $reg) : ?string
	{
		if (isset($this->watchlist->reg[$reg]))
		{
			return $this->watchlist->reg[$reg]->comment;
		}
		else
		{
			foreach ($this->watchlist->regex as $key => $value)
			{
				if (preg_match($key, $reg))
					return $value;
			}

			foreach ($this->watchlist->wildcard as $key => $value)
			{
				if (fnmatch($key, $reg))
					return $value;
			}
		}

		return null;
	}
}

?>
