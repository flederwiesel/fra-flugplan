<?php

@require_once '../.config';

$mail = 'Location: mailto:';
$mail .= ADMIN_EMAIL;

if (isset($_GET['subject']))
	$mail .= '&subject='.mb_encode_mimeheader($_GET['subject'], 'ISO-8859-1', 'Q');

if (isset($_GET['body']))
	$mail .= '&body='.mb_encode_mimeheader($_GET['body'], 'ISO-8859-1', 'Q');

header($mail);
?>
