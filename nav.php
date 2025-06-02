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

function navitem($item, $href)
{
	global $STRINGS;
	global $mobile;

	if ($href)
		echo '<a href="'.$href.'">';

	if ($mobile)
		echo '<img src="img/'.$item.'-'.($href ? 'grey' : 'white').'-24x24.png" width="24" height="24" alt="'.$STRINGS[$item].'">';
	else
		echo $STRINGS[$item];

	if ($href)
		echo '</a>';
}

?>
<nav>
<ul class="menu left">
<?php
	if (!$mobile)
	{
?>
		<li><a href="http://www.frankfurt-aviation-friends.de/"><?php echo $STRINGS['home']; ?></a></li>
<?php
	}

	if ($db)
	{
		if (isset($_GET['page']))
		{
?>
			<li class="sep"><?php navitem('arrival', '?arrival'); ?></li>
			<li class="sep"><?php navitem('departure', '?departure'); ?></li>
<?php

			if ($user && !$mobile && $_GET['page'] != 'addflight')
			{
				if ($user->IsMemberOf('addflights'))
				{
?>
					<li class="sep"><?php navitem('addflight', '?page=addflight'); ?></li>
<?php
				}
			}
		}
		else if (isset($_GET['req']))
		{
?>
			<li class="sep"><?php navitem('arrival', '?arrival'); ?></li>
			<li class="sep"><?php navitem('departure', '?departure'); ?></li>
<?php

			if ($user && !$mobile)
			{
?>
				<li class="sep"><a href="?page=addflight"><?php echo $STRINGS['addflight']; ?></a></li>
<?php
			}
		}
		else
		{
			if ('arrival' == $dir)
			{
?>
			<li class="sep"><?php navitem('arrival', null); ?></li>
			<li class="sep"><?php navitem('departure', '?departure'); ?></li>
<?php } else { ?>
			<li class="sep"><?php navitem('arrival', '?arrival'); ?></li>
			<li class="sep"><?php navitem('departure', null); ?></li>
<?php
			}

			if ($user && !$mobile)
			{
?>
				<li class="sep"><a href="javascript:watchlist('show');"><?php echo $STRINGS['watchlist']; ?></a></li>
<?php
				if ($user->IsMemberOf('addflights'))
				{
?>
					<li class="sep"><a href="?page=addflight"><?php echo $STRINGS['addflight']; ?></a></li>
<?php
				}
			}
		}
	}

	if (isset($_GET['page']))
	{
?>
			<li class="sep"><?php navitem('help', 'help' == $_GET['page'] ? null : '?page=help'); ?></li>
<?php
	}
	else
	{
?>
		<li class="sep"><?php navitem('help', '?page=help'); ?></li>
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
			<li class="sep"><?php navitem('profile', '?req=profile'); ?></li>
<?php
			}
			else
			{
?>
			<li class="sep"><?php navitem('profile', 'profile' == $_GET['req'] ? null : '?req=profile'); ?></li>
<?php
			}
?>
			<li class="sep"><?php navitem('logout', '?req=logout'); ?></li>
<?php
		}
		else
		{
?>
			<li class="sep"><?php navitem('register', '?req=register'); ?></li>
			<li class="sep"><?php navitem('login', '?req=login'); ?></li>
<?php
		}
	}
?>
		<li style="vertical-align: baseline;">
			<a href="<?php echo get('lang=de'); ?>">
				<img class="flag" src="img/de.png" alt="<?php echo $STRINGS['de']; ?>" width="16" height="12">
			</a>
		</li>
		<li style="vertical-align: baseline;">
			<a href="<?php echo get('lang=en'); ?>">
				<img class="flag" src="img/en.png" alt="<?php echo $STRINGS['en']; ?>" width="16" height="12">
			</a>
		</li>
</ul>
</nav>
