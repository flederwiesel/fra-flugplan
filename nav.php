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

?>

<dl class="left">
	<dt><a href="http://www.frankfurt-aviation-friends.de/"><?php echo $lang['home']; ?></a></dt>
<?php
	if ($hdbc)
	{
		if (isset($_GET['page']))
		{
?>
			<dt class="sep"><a href="?arrival"><?php echo $lang['arrival']; ?></a></dt>
			<dt class="sep"><a href="?departure"><?php echo $lang['departure']; ?></a></dt>
<?php

			if ($user && $_GET['page'] != 'addflight')
			{
?>
				<dt class="sep"><a href="?page=addflight"><?php echo $lang['addflight']; ?></a></dt>
<?php
			}
		}
		else if (isset($_GET['req']))
		{
			if ('logout' == $_GET['req'])
			{
?>
				<dt class="sep"><a href="?<?php echo $rev; ?>"><?php echo $lang[$rev]; ?></a></dt>
<?php
			}
			else
			{
?>
				<dt class="sep"><a href="?arrival"><?php echo $lang['arrival']; ?></a></dt>
				<dt class="sep"><a href="?departure"><?php echo $lang['departure']; ?></a></dt>
<?php
			}

			if ($user)
			{
?>
				<dt class="sep"><a href="javascript: watchlist('show');"><?php echo $lang['watchlist']; ?></a></dt>
				<dt class="sep"><a href="?page=addflight"><?php echo $lang['addflight']; ?></a></dt>
<?php
			}
		}
		else
		{
			if ('arrival' == $dir)
			{
?>
			<dt class="sep"><?php echo $lang[$dir]; ?></dt>
			<dt class="sep"><a href="?<?php echo $rev; ?>"><?php echo $lang[$rev]; ?></a></dt>
<?php } else { ?>
			<dt class="sep"><a href="?<?php echo $rev; ?>"><?php echo $lang[$rev]; ?></a></dt>
			<dt class="sep"><?php echo $lang[$dir]; ?></dt>
<?php
			}

			if ($user)
			{
?>
				<dt class="sep"><a href="javascript: watchlist('show');"><?php echo $lang['watchlist']; ?></a></dt>
				<dt class="sep"><a href="?page=addflight"><?php echo $lang['addflight']; ?></a></dt>
<?php
			}
		}
	}
?>
	<dt class="sep"><a href="?page=help"><?php echo $lang['help']; ?></a></dt>
</dl>
<dl class="right">
<?php
	if ($hdbc)
	{
		if ($user)
		{
			/* user has successfully logged in */
?>
			<dt class="sep">Welcome, <?php echo $user->name(); ?></dt>
			<dt class="sep"><a href="?req=changepw"><?php echo $lang['changepassword']; ?></a></dt>
			<dt class="sep"><a href="?req=logout"><?php echo $lang['logout']; ?></a></dt>
<?php
		}
		else
		{
?>
			<dt class="sep"><a href="?req=register"><?php echo $lang['register']; ?></a></dt>
			<dt class="sep"><a href="?req=login"><?php echo $lang['login']; ?></a></dt>
<?php
		}
	}
?>
		<span style="white-space: nowrap;">
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
