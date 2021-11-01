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

include "help-$_SESSION[lang].php";

?>

<div style="max-width: 792px;">
	<h3><a id="top"></a><?php echo $lang_help[1]; ?></h3>
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
		<li><a href="#data-privacy"><?php echo $lang_help[33]; ?></a></li>
		<li><a href="#trouble"><?php echo $lang_help[8]; ?></a></li>
		<li><a href="#about"><?php echo $lang_help[49]; ?></a></li>
	</ul>

	<hr>
	<div>
		<h4><a id="basic"></a><?php echo $lang_help[2]; ?></h4>
		<ul class="naked help">
			<li><?php echo $lang_help[9]; ?>.
			<li><?php echo $lang_help[10]; ?>.
			<li><?php echo $lang_help[11]; ?>.
			<li><?php echo $lang_help[12]; ?>...
			<li><?php echo $lang_help[13]; ?>.
			<li><?php echo $lang_help[14]; ?>.
			<li><?php echo $lang_help[15]; ?>.
			<li><?php echo $lang_help[32]; ?>.
			<li><?php echo $lang_help[16]; ?>.
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/1-basic.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<h4><a id="watchlist"></a><?php echo $lang_help[3]; ?></h4>
		<ul class="naked help">
			<li><?php echo $lang_help[17]; ?>.
			<li><?php echo $lang_help[18]; ?>.
			<li><?php echo $lang_help[19]; ?>
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/2-watchlist.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<h5><a id="watchlist_def"></a><?php echo $lang_help[4]; ?></h5>
		<ul class="naked help">
			<li><?php echo $lang_help[20]; ?>
			<li><?php echo $lang_help[21]; ?>.
			<li><?php echo $lang_help[22]; ?>.
			<li><?php echo $lang_help[23]; ?>.
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/3-watchlist-def.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<h4><a id="sorting"></a><?php echo $lang_help[5]; ?></h4>
		<ul class="naked help">
			<li><?php echo $lang_help[24]; ?>.
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/4-sorting.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<h5><a id="sorting_reg"></a><?php echo $lang_help[6]; ?></h5>
		<ul class="naked help">
			<li><?php echo $lang_help[25]; ?>.
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/5-sorting-reg.png">
				<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
			</li>
		</ul>

		<h4><a id="mobile"></a><?php echo $lang_help[7]; ?></h4>
		<ul class="help">
			<li><?php echo $lang_help[26]; ?>.
				<div><?php echo $lang_help[31]; ?>:
					<div id="mobile">
						<ul class="help">
							<li><img src="img/arrival-grey-24x24.png"><?php echo $lang['arrival']; ?></li>
							<li><img src="img/departure-grey-24x24.png"><?php echo $lang['departure']; ?></li>
							<li><img src="img/help-grey-24x24.png"><?php echo $lang['help']; ?></li>
							<li><img src="img/register-grey-24x24.png"><?php echo $lang['register']; ?></li>
							<li><img src="img/login-grey-24x24.png"><?php echo $lang['login']; ?></li>
							<li><img src="img/logout-grey-24x24.png"><?php echo $lang['logout']; ?></li>
							<li><img src="img/profile-grey-24x24.png"><?php echo $lang['profile']; ?></li>
							<li>&nbsp;</li>
							<li><img src="img/dispinterval-grey-24x24.png"><?php echo $lang['dispinterval']; ?></li>
							<li><img src="img/notifinterval-grey-24x24.png"><?php echo $lang['notifinterval']; ?></li>
							<li><img src="img/changepw-grey-24x24.png"><?php echo $lang['changepw']; ?></li>
						</ul>
					</div>
				</div>
			</li>
			<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
		</ul>

		<h4><a id="data-privacy"></a><?php echo $lang_help[33]; ?></h4>
		<ul class="naked help">
			<li>
				<div><?php echo $lang_help[34]; ?></div>
				<div><?php echo $lang_help[35]; ?></div>
			</li>
			<li>
				<div><?php echo $lang_help[36]; ?>
					<ul>
						<li><?php echo $lang_help[37]; ?></li>
						<li><?php echo $lang_help[38]; ?></li>
						<li><?php echo $lang_help[39]; ?></li>
						<li><?php echo $lang_help[40]; ?></li>
						<li><?php echo $lang_help[41]; ?></li>
					</ul>
				</div>
			</li>
			<li>
				<div><?php echo $lang_help[42]; ?>
					<ul>
						<li><?php echo $lang_help[43]; ?></li>
						<li><?php echo $lang_help[44]; ?>
							<ul>
								<li><?php echo $lang_help[45]; ?></li>
								<li><?php echo $lang_help[46]; ?></li>
								<li><?php echo $lang_help[47]; ?></li>
							</ul>
						</li>
					</ul>
				</div>
			</li>
			<li>
				<div><?php echo $lang_help[48]; ?></div>
			</li>
			<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
		</ul>

		<h4><a id="trouble"></a><?php echo $lang_help[8]; ?></h4>
		<ul class="naked help">
			<?php echo $lang_help[27]; ?>.
			<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
		</ul>

		<h4><a id="about"></a>About</h4>
		<ul class="naked help">
			<?php echo $lang_help[28]; ?>:
			<div>
				<ul class="help">
					<li><a href="http://jqueryui.com/">jQuery UI Library</a></li>
					<li><a href="http://www.kryogenix.org/code/browser/sorttable/">SortTable <?php echo $lang_help[29]; ?> Stuart Langridge</a></li>
					<li><a href="http://mobiledetect.net">Serban Ghita / Mobile_Detect PHP class</a></li>
				</ul>
			</div>
			<?php echo $lang_help[30]; ?>
			<div>
				<ul class="help">
					<li>
						<a href="content/emil.php?subject=<?php echo $lang_help[50]; ?>"
							target="_blank">
							<img id="address" class="emil" alt="email" src="content/mkpng.php?font=verdana&amp;size=10&amp;bg=white&amp;fg=%2300007f&amp;res=ADMIN_SNAILMAIL">
						</a>
					</li>
				</ul>
			</div>
			<a class="back" href="#"><?php echo $lang_help[0]; ?></a>
		</ul>

		<div class="right footnote">
		<?php
			$rev = file('revision');

			if ($rev)
			{
				foreach ($rev as $line)
					echo "$line.<br>";
			}
		?></div>
	</div>
</div>
