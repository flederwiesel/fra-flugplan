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

@require_once '../.config';

$mail = 'Location: mailto:';
$mail .= ADMIN_EMAIL;

if (isset($_GET['subject']))
	$mail .= '&subject='.mb_encode_mimeheader($_GET['subject'], 'ISO-8859-1', 'Q');

if (isset($_GET['body']))
	$mail .= '&body='.mb_encode_mimeheader($_GET['body'], 'ISO-8859-1', 'Q');

header($mail);
?>
