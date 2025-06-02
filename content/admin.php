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

if ('userdel' == $_GET['admin'])
{
	if (!$user)
	{
		$error = $STRINGS['notloggedin'];//.': '.htmlget();
	}
	else
	{
		if (!$user->IsMemberOf('admin'))
		{
			$error = $STRINGS['nopermission'];
		}
		else
		{
			$result = mysql_query("DELETE FROM `users` WHERE `id`=$_GET[uid]");

			if ($result)
				$message = 'OK';
			else
				$error = sprintf($STRINGS['dberror'], __FILE__, __LINE__, mysql_error());
		}
	}
}

if ($error)
{
?>
<div id="notification" class="error">
	<?php echo $error; ?>
</div>
<?php
}
else
{
	if ($message)
	{
?>
<div id="notification" class="success">
	<?php echo $message; ?>
</div>
<?php
	}
}
?>
