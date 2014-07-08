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

header("Content-type: image/png");

$email = ADMIN_EMAIL;

$fontsize = 3;
//Get the size of the string
$width = ImageFontWidth($fontsize) * strlen($email);
$height = ImageFontHeight($fontsize);
//Create the image
$img = @ImageCreateTrueColor($width, $height);
//Make it transparent
ImageSaveAlpha($img, true);
$bg = ImageColorAllocateAlpha($img, 255, 255, 255, 127);
ImageFill($img, 0, 0, $bg);
//Draw the string
$fg = ImageColorAllocate($img, 0, 0, 0);
ImageString($img, $fontsize, 0, 0, $email, $fg);
//Output the image
ImagePNG($img);
ImageDestroy($img);

?>
