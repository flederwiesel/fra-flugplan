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

/* Simple RGB colour class */
class RGB
{
	public $r;
	public $g;
	public $b;

	function __construct($string)
	{
		$pos = 0;

		if ('#' == $string[0])
		{
			if (sscanf($string, "#%2x%2x%2x",
					   $this->r, $this->g, $this->b) != 3)
			{
				$this->r = 255;
				$this->g = 255;
				$this->b = 0;
			}
		}
		else
		{
			switch ($string)
			{
			case 'white':
				$this->r = 255;
				$this->g = 255;
				$this->b = 255;
				break;

			case 'red':
				$this->r = 255;
				$this->g = 0;
				$this->b = 0;
				break;

			case 'green':
				$this->r = 0;
				$this->g = 255;
				$this->b = 0;
				break;

			case 'blue':
				$this->r = 0;
				$this->g = 0;
				$this->b = 255;
				break;

			case 'yellow':
				$this->r = 255;
				$this->g = 255;
				$this->b = 0;
				break;

			case 'black':
				$this->r = 0;
				$this->g = 0;
				$this->b = 0;
				break;
			}
		}
	}
};

/* Parameters */
$font = 'verdana.ttf';

if (isset($_GET['font']))
	if (file_exists($_GET['font'].'ttf'))
		$font = $_GET['font'].'ttf';

if (isset($_GET['size']))
	$size = $_GET['size'];
else
	$size = 9;

if (isset($_GET['bg']))
	$bg = new RGB($_GET['bg']);
else
	$bg = new RGB('white');

if (isset($_GET['fg']))
	$fg = new RGB($_GET['fg']);
else
	$fg = new RGB('black');

$text = '';

if (isset($_GET['text']))
	if (defined($_GET['text']))
		$text = constant($_GET['text']);

if (isset($_GET['res']))
	if (defined($_GET['res']))
		$text = constant($_GET['res']);

/* Get image geometry */
$box = ImageTTFBbox($size, 0, $font, $text);
$width = abs($box[4] - $box[0]) + 2;
$height = abs($box[3] - $box[7]) + 2;

$img = ImageCreateTrueColor($width, $height);

/* Fill background with transparent colour */
ImageAlphaBlending($img, true);
ImageFill($img, 0, 0, ImageColorAllocateAlpha($img, $bg->r, $bg->g, $bg->b, 0));

/* Draw Text */
ImageTTFText($img, $size, 0, 0, $size + 2,
	ImageColorAllocate($img, $fg->r, $fg->g, $fg->b), $font, $text);

/* Finally... */
header('Content-Type: image/png');

ImageSaveAlpha($img, true);
ImagePNG($img);
ImageDestroy($img);

?>
