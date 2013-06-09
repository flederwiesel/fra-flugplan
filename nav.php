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

function navitem($item, $active)
{
	global $lang;
	global $mobile;

	if ($mobile)
		echo '<img src="img/'.$item.'-'.($active ? 'white' : 'grey').'-24x24.png">';
	else
		echo $lang[$item];
}

?>

<dl class="left">
<?php
	if (!$mobile)
	{
?>
		<dt><a href="http://www.frankfurt-aviation-friends.de/"><?php echo $lang['home']; ?></a></dt>
<?php
	}

	if ($hdbc)
	{
		if (isset($_GET['page']))
		{
?>
			<dt class="sep"><a href="?arrival"><?php navitem('arrival', false); ?></a></dt>
			<dt class="sep"><a href="?departure"><?php navitem('departure', false); ?></a></dt>
<?php

			if ($user && !$mobile && $_GET['page'] != 'addflight')
			{
				$perm = $user->permissions();

				if ($perm[0] = '1')
				{
?>
					<dt class="sep"><a href="?page=addflight"><?php navitem('addflight', false); ?></a></dt>
<?php
				}
			}
		}
		else if (isset($_GET['req']))
		{
?>
			<dt class="sep"><a href="?arrival"><?php navitem('arrival', false); ?></a></dt>
			<dt class="sep"><a href="?departure"><?php navitem('departure', false); ?></a></dt>
<?php

			if ($user && !$mobile)
			{
?>
				<dt class="sep"><a href="?page=addflight"><?php echo $lang['addflight']; ?></a></dt>
<?php
			}
		}
		else
		{
			if ('arrival' == $dir)
			{
?>
			<dt class="sep"><?php navitem('arrival', true); ?></dt>
			<dt class="sep"><a href="?departure"><?php navitem('departure', false); ?></a></dt>
<?php } else { ?>
			<dt class="sep"><a href="?arrival"><?php navitem('arrival', false); ?></a></dt>
			<dt class="sep"><?php navitem('departure', true); ?></dt>
<?php
			}

			if ($user && !$mobile)
			{
?>
				<dt class="sep"><a href="javascript:watchlist('show');"><?php echo $lang['watchlist']; ?></a></dt>
<?php
				$perm = $user->permissions();

				if ($perm[0] == '1')
				{
?>
					<dt class="sep"><a href="?page=addflight"><?php echo $lang['addflight']; ?></a></dt>
<?php
				}
			}
		}
	}

	if (isset($_GET['page']))
	{
		if ('help' == $_GET['page'])
		{
?>
			<dt class="sep"><a href="?page=help"><?php navitem('help', true); ?></a></dt>
<?php
		}
		else
		{
?>
			<dt class="sep"><a href="?page=help"><?php navitem('help', false); ?></a></dt>
<?php
		}
	}
	else
	{
?>
		<dt class="sep"><a href="?page=help"><?php navitem('help', false); ?></a></dt>
<?php
	}
?>
</dl>
<dl class="right">
<?php
	if ($hdbc)
	{
		if ($user)
		{
			/* user has successfully logged in */
?>
			<dt class="sep"><a href="?req=changepw"><?php navitem('changepw', false); ?></a></dt>
			<dt class="sep"><a href="?req=logout"><?php navitem('logout', false); ?></a></dt>
<?php
		}
		else
		{
?>
			<dt class="sep"><a href="?req=register"><?php navitem('register', false); ?></a></dt>
			<dt class="sep"><a href="?req=login"><?php navitem('login', false); ?></a></dt>
<?php
		}
	}
?>
		<span style="vertical-align: baseline;">
			<dt>
				<a href="<?php echo get('lang=de'); ?>">
					<img src="img/de.png" alt="<?php echo $lang['de']; ?>" width="16" height="12">
				</a>
			</dt>
			<dt>
				<a href="<?php echo get('lang=en'); ?>">
					<img src="img/en.png" alt="<?php echo $lang['en']; ?>" width="16" height="12">
				</a>
			</dt>
		</span>
</dl>
