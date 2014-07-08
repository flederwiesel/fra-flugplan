<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author$
 *         $Date$
 *          $Rev$
 *
 ******************************************************************************
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
	global $lang;
	global $mobile;

	if ($href)
		echo '<a href="'.$href.'">';

	if ($mobile)
		echo '<img src="img/'.$item.'-'.($href ? 'grey' : 'white').'-24x24.png">';
	else
		echo $lang[$item];

	if ($href)
		echo '</a>';
}

?>

<ul class="menu left">
<?php
	if (!$mobile)
	{
?>
		<li><a href="http://www.frankfurt-aviation-friends.de/"><?php echo $lang['home']; ?></a></li>
<?php
	}

	if ($hdbc)
	{
		if (isset($_GET['page']))
		{
?>
			<li class="sep"><?php navitem('arrival', '?arrival'); ?></li>
			<li class="sep"><?php navitem('departure', '?departure'); ?></li>
<?php

			if ($user && !$mobile && $_GET['page'] != 'addflight')
			{
				$perm = $user->permissions();

				if ($perm[0] = '1')
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
				<li class="sep"><a href="?page=addflight"><?php echo $lang['addflight']; ?></a></li>
<?php
			}
		}
		else
		{
			if ('arrival' == $dir)
			{
?>
			<li class="sep"><?php navitem('arrival', NULL); ?></li>
			<li class="sep"><?php navitem('departure', '?departure'); ?></li>
<?php } else { ?>
			<li class="sep"><?php navitem('arrival', '?arrival'); ?></li>
			<li class="sep"><?php navitem('departure', NULL); ?></li>
<?php
			}

			if ($user && !$mobile)
			{
?>
				<li class="sep"><a href="javascript:watchlist('show');"><?php echo $lang['watchlist']; ?></a></li>
<?php
				$perm = $user->permissions();

				if ($perm[0] == '1')
				{
?>
					<li class="sep"><a href="?page=addflight"><?php echo $lang['addflight']; ?></a></li>
<?php
				}
			}
		}
	}

	if (isset($_GET['page']))
	{
?>
			<li class="sep"><?php navitem('help', 'help' == $_GET['page'] ? NULL : '?page=help'); ?></li>
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
	if ($hdbc)
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
			<li class="sep"><?php navitem('profile', 'profile' == $_GET['req'] ? NULL : '?req=profile'); ?></li>
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
				<img class="flag" src="img/de.png" alt="<?php echo $lang['de']; ?>" width="16" height="12">
			</a>
		</li>
		<li style="vertical-align: baseline;">
			<a href="<?php echo get('lang=en'); ?>">
				<img class="flag" src="img/en.png" alt="<?php echo $lang['en']; ?>" width="16" height="12">
			</a>
		</li>
</ul>
