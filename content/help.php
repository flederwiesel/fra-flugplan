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

include("help-$_SESSION[lang].php");

?>

<div style="max-width: 792px;">
	<a id="top"></a>
	<h3><?php echo $lang_help[1]; ?></h3>
	<ul>
		<li><a href="#basic"><?php echo $lang_help[2]; ?></a></li>
		<li><a href="#watchlist"><?php echo $lang_help[3]; ?></a></li>
		<ul>
			<li><a href="#watchlist_def"><?php echo $lang_help[4]; ?></a></li>
		</ul>
		<li><a href="#sorting"><?php echo $lang_help[5]; ?></a></li>
		<ul>
			<li><a href="#sorting_reg"><?php echo $lang_help[6]; ?></a></li>
		</ul>
		<li><a href="#mobile"><?php echo $lang_help[7]; ?></a></li>
		<li><a href="#trouble"><?php echo $lang_help[8]; ?></a></li>
	</ul>

	<hr>
	<div>
		<a id="basic"></a>
		<h4><?php echo $lang_help[2]; ?></h4>
		<ul class="help">
			<li><?php echo $lang_help[9]; ?>.
			<li><?php echo $lang_help[10]; ?>.
			<li><?php echo $lang_help[11]; ?>.
			<li><?php echo $lang_help[12]; ?>...
			<li><?php echo $lang_help[13]; ?>.
			<li><?php echo $lang_help[14]; ?>.
			<li><?php echo $lang_help[15]; ?>.
			<li><?php echo $lang_help[16]; ?>.
			<li class="img">
				<img src="content/img/<?php echo $_SESSION['lang']; ?>/1-basic.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<a id="watchlist"></a>
		<h4><?php echo $lang_help[3]; ?></h4>
		<ul class="help">
			<li><?php echo $lang_help[17]; ?>.
			<li><?php echo $lang_help[18]; ?>.
			<li><?php echo $lang_help[19]; ?>
			<li class="img">
				<img src="content/img/<?php echo $_SESSION['lang']; ?>/2-watchlist.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<a id="watchlist_def"></a>
		<h5><?php echo $lang_help[4]; ?></h5>
		<ul class="help">
			<li><?php echo $lang_help[20]; ?>
			<li><?php echo $lang_help[21]; ?>.
			<li><?php echo $lang_help[22]; ?>.
			<li><?php echo $lang_help[23]; ?>.
			<li class="img">
				<img src="content/img/<?php echo $_SESSION['lang']; ?>/3-watchlist-def.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<a id="sorting"></a>
		<h4><?php echo $lang_help[5]; ?></h4>
		<ul class="help">
			<li><?php echo $lang_help[24]; ?>.
			<li class="img">
				<img src="content/img/<?php echo $_SESSION['lang']; ?>/4-sorting.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<a id="sorting_reg"></a>
		<h5><?php echo $lang_help[6]; ?></h5>
		<ul class="help">
			<li><?php echo $lang_help[25]; ?>.
			<li class="img">
				<img src="content/img/<?php echo $_SESSION['lang']; ?>/5-sorting-reg.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<a id="mobile"></a>
		<h4><?php echo $lang_help[7]; ?></h4><?php echo $lang_help[26]; ?>.
		<a class="back" href="#"><?php echo $lang_help[0]; ?></a>

		<a id="trouble"></a>
		<h4><?php echo $lang_help[8]; ?></h4><?php echo $lang_help[27]; ?>.
		<a class="back" href="#"><?php echo $lang_help[0]; ?></a>

		<a id="copyright"></a>
		<h4>About</h4>
		<ul class="help">
			<li>Tobias Kühne</li>
			<li>Hans-Purrmann-Str. 2c
			<li>67227 Frankenthal
		</ul>

		<a id="at"></a>
		<div class="footnote">
			<div style="margin: 1em 0; height: 1em; width: 6em; border-bottom: 1px solid grey;"></div>
			<sup style="margin-right: 0.5em;">1</sup>
			<span><?php echo $lang_help[28]; ?>.</span>
		</div>
	</div>
</div>
