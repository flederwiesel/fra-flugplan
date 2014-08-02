<?php

/******************************************************************************
 *
 * Copyright © Tobias Kühne
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

// Get the size of the string
$font = 'verdana.ttf';

if (isset($_GET['font']))
	if (file_exists($_GET['font'].'ttf'))
	$font = $_GET['font'].'ttf';


if (isset($_GET['size']))
	$size = $_GET['size'];
else
	$size = 10;

$text = '';

if (isset($_GET['text']))
	if (defined($_GET['text']))
		$text = constant($_GET['text']);

$box = ImageTTFBbox($size, 0, $font, $text);
$width = abs($box[4] - $box[0]) + 4;
$height = abs($box[3] - $box[7]) + 4;

$img = ImageCreateTrueColor($width, $height);

// Fill background with transparent colour
ImageAlphaBlending($img, false);
ImageFill($img, 0, 0, ImageColorAllocateAlpha($img, 255, 255, 255, 127));

// Draw Text
ImageTTFText($img, $size, 0, 0, $size + 2, ImageColorAllocate($img, 0, 0, 0), $font, $text);

// Output image
header('Content-Type: image/png');

ImageSaveAlpha($img, true);
ImagePNG($img);
ImageDestroy($img);

?>
