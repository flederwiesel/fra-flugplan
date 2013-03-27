<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author: flederwiesel $
 *         $Date: 2013-02-25 21:48:05 +0100 (Mo, 25 Feb 2013) $
 *          $Rev: 120 $
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
<div id="auth">
<?php if (isset($error)) { ?>
	<div id="notification" class="auth-error">
		<?php echo $error; ?>
	</div>
<?php } else if (isset($message)) { ?>
	<div id="notification" class="auth-ok">
		<?php echo $message; ?>
	</div>
<?php }?>
	<div class="center">
		<a href="?<?php echo $dir; ?>"><?php echo $lang['back']; ?></a>
	</div>
</div>
