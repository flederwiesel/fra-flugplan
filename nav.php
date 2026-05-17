<?php

function navitem($item, $href)
{
	global $STRINGS;
	global $mobile;

	if ($href)
	{
		?><a href="<?= $href ?>"><?php
	}

	if ($mobile) {
		$color = $href ? "grey" : "white";
		?><img src="<?= "img/{$item}-{$color}-24x24.png" ?>" width="24" height="24" alt="<?= $STRINGS[$item] ?>"><?php
	}
	else
	{
		?><?= $STRINGS[$item] ?><?php
	}

	if ($href)
	{
		?></a><?php
	}
}

?>
<nav>
<ul class="menu left">
<?php
	if (!$mobile)
	{
?>
		<li><a href="https://www.frankfurt-aviation-friends.de/"><?= $STRINGS['home'] ?></a></li>
<?php
	}

	if ($db)
	{
		if (isset($_GET['page']))
		{
?>
			<li class="sep"><?= navitem('arrival', '?arrival') ?></li>
			<li class="sep"><?= navitem('departure', '?departure') ?></li>
<?php

			if ($user && !$mobile && $_GET['page'] != 'addflight')
			{
				if ($user->IsMemberOf('addflights'))
				{
?>
					<li class="sep"><?= navitem('addflight', '?page=addflight') ?></li>
<?php
				}
			}
		}
		else if (isset($_GET['req']))
		{
?>
			<li class="sep"><?= navitem('arrival', '?arrival') ?></li>
			<li class="sep"><?= navitem('departure', '?departure') ?></li>
<?php

			if ($user && !$mobile)
			{
?>
				<li class="sep"><a href="?page=addflight"><?= $STRINGS['addflight'] ?></a></li>
<?php
			}
		}
		else
		{
			if ('arrival' == $dir)
			{
?>
			<li class="sep"><?= navitem('arrival', null) ?></li>
			<li class="sep"><?= navitem('departure', '?departure') ?></li>
<?php } else { ?>
			<li class="sep"><?= navitem('arrival', '?arrival') ?></li>
			<li class="sep"><?= navitem('departure', null) ?></li>
<?php
			}

			if ($user && !$mobile)
			{
?>
				<li class="sep"><a href="javascript:watchlist('show');"><?= $STRINGS['watchlist'] ?></a></li>
<?php
				if ($user->IsMemberOf('addflights'))
				{
?>
					<li class="sep"><a href="?page=addflight"><?= $STRINGS['addflight'] ?></a></li>
<?php
				}
			}
		}
	}

	if (isset($_GET['page']))
	{
?>
			<li class="sep"><?= navitem('help', 'help' == $_GET['page'] ? null : '?page=help') ?></li>
<?php
	}
	else
	{
?>
		<li class="sep"><?= navitem('help', '?page=help') ?></li>
<?php
	}
?>
</ul>
<ul class="menu right">
<?php
	if ($db)
	{
		if ($user)
		{
			/* user has successfully logged in */
			if (!isset($_GET['req']))
			{
?>
			<li class="sep"><?= navitem('profile', '?req=profile') ?></li>
<?php
			}
			else
			{
?>
			<li class="sep"><?= navitem('profile', 'profile' == $_GET['req'] ? null : '?req=profile') ?></li>
<?php
			}
?>
			<li class="sep"><?= navitem('logout', '?req=logout') ?></li>
<?php
		}
		else
		{
?>
			<li class="sep"><?= navitem('register', '?req=register') ?></li>
			<li class="sep"><?= navitem('login', '?req=login') ?></li>
<?php
		}
	}
?>
		<li style="vertical-align: baseline;">
			<a href="<?= get('lang=de') ?>">
				<img class="flag" src="img/de.png" alt="<?= $STRINGS['de'] ?>" width="16" height="12">
			</a>
		</li>
		<li style="vertical-align: baseline;">
			<a href="<?= get('lang=en') ?>">
				<img class="flag" src="img/en.png" alt="<?= $STRINGS['en'] ?>" width="16" height="12">
			</a>
		</li>
</ul>
</nav>
