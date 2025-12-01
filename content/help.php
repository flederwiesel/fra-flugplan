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
	<h3><a id="top"></a><?php echo $HELPSTRINGS[1]; ?></h3>
	<ul>
		<li><a href="#basic"><?php echo $HELPSTRINGS[2]; ?></a></li>
		<li><a href="#watchlist"><?php echo $HELPSTRINGS[3]; ?></a></li>
		<ul>
			<li><a href="#watchlist_def"><?php echo $HELPSTRINGS[4]; ?></a></li>
		</ul>
		<li><a href="#sorting"><?php echo $HELPSTRINGS[5]; ?></a></li>
		<ul>
			<li><a href="#sorting_reg"><?php echo $HELPSTRINGS[6]; ?></a></li>
		</ul>
		<li><a href="#mobile"><?php echo $HELPSTRINGS[7]; ?></a></li>
		<li><a href="#data-privacy"><?php echo $HELPSTRINGS[33]; ?></a></li>
		<li><a href="#trouble"><?php echo $HELPSTRINGS[8]; ?></a></li>
		<li><a href="#about"><?php echo $HELPSTRINGS[49]; ?></a></li>
	</ul>

	<hr>
	<div>
		<h4><a id="basic"></a><?php echo $HELPSTRINGS[2]; ?></h4>
		<ul class="naked help">
			<li><?php echo $HELPSTRINGS[9]; ?>.
			<li><?php echo $HELPSTRINGS[10]; ?>.
			<li><?php echo $HELPSTRINGS[11]; ?>.
			<li><?php echo $HELPSTRINGS[12]; ?>...
			<li><?php echo $HELPSTRINGS[13]; ?>.
			<li><?php echo $HELPSTRINGS[14]; ?>.
			<li><?php echo $HELPSTRINGS[15]; ?>.
			<li><?php echo $HELPSTRINGS[32]; ?>.
			<li><?php echo $HELPSTRINGS[16]; ?>.
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/1-basic.png">
				<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
			</li>
		</ul>

		<h4><a id="watchlist"></a><?php echo $HELPSTRINGS[3]; ?></h4>
		<ul class="naked help">
			<li><?php echo $HELPSTRINGS[17]; ?>.
			<li><?php echo $HELPSTRINGS[18]; ?>.
			<li><?php echo $HELPSTRINGS[19]; ?>
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/2-watchlist.png">
				<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
			</li>
		</ul>

		<h5><a id="watchlist_def"></a><?php echo $HELPSTRINGS[4]; ?></h5>
		<ul class="naked help">
			<li><?php echo $HELPSTRINGS[20]; ?>
			<li><?php echo $HELPSTRINGS[21]; ?>.
			<li><?php echo $HELPSTRINGS[22]; ?>.
			<li><?php echo $HELPSTRINGS[23]; ?>.
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/3-watchlist-def.png">
				<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
			</li>
		</ul>

		<h4><a id="sorting"></a><?php echo $HELPSTRINGS[5]; ?></h4>
		<ul class="naked help">
			<li><?php echo $HELPSTRINGS[24]; ?>.
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/4-sorting.png">
				<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
			</li>
		</ul>

		<h5><a id="sorting_reg"></a><?php echo $HELPSTRINGS[6]; ?></h5>
		<ul class="naked help">
			<li><?php echo $HELPSTRINGS[25]; ?>.
			<li class="img">
				<img class="screen" src="content/img/<?php echo $_SESSION['lang']; ?>/5-sorting-reg.png">
				<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
			</li>
		</ul>

		<h4><a id="mobile"></a><?php echo $HELPSTRINGS[7]; ?></h4>
		<ul class="help">
			<li><?php echo $HELPSTRINGS[26]; ?>.
				<div><?php echo $HELPSTRINGS[31]; ?>:
					<div id="mobile">
						<ul class="help">
							<li><img src="img/arrival-grey-24x24.png"><?php echo $STRINGS['arrival']; ?></li>
							<li><img src="img/departure-grey-24x24.png"><?php echo $STRINGS['departure']; ?></li>
							<li><img src="img/help-grey-24x24.png"><?php echo $STRINGS['help']; ?></li>
							<li><img src="img/register-grey-24x24.png"><?php echo $STRINGS['register']; ?></li>
							<li><img src="img/login-grey-24x24.png"><?php echo $STRINGS['login']; ?></li>
							<li><img src="img/logout-grey-24x24.png"><?php echo $STRINGS['logout']; ?></li>
							<li><img src="img/profile-grey-24x24.png"><?php echo $STRINGS['profile']; ?></li>
							<li>&nbsp;</li>
							<li><img src="img/dispinterval-grey-24x24.png"><?php echo $STRINGS['dispinterval']; ?></li>
							<li><img src="img/notifinterval-grey-24x24.png"><?php echo $STRINGS['notifinterval']; ?></li>
							<li><img src="img/changepw-grey-24x24.png"><?php echo $STRINGS['changepw']; ?></li>
						</ul>
					</div>
				</div>
			</li>
			<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
		</ul>

		<h4><a id="data-privacy"></a><?php echo $HELPSTRINGS[33]; ?></h4>
		<ul class="naked help">
			<li>
				<div><?php echo $HELPSTRINGS[34]; ?></div>
				<div><?php echo $HELPSTRINGS[35]; ?></div>
			</li>
			<li>
				<div><?php echo $HELPSTRINGS[36]; ?>
					<ul>
						<li><?php echo $HELPSTRINGS[37]; ?></li>
						<li><?php echo $HELPSTRINGS[38]; ?></li>
						<li><?php echo $HELPSTRINGS[39]; ?></li>
						<li><?php echo $HELPSTRINGS[40]; ?></li>
						<li><?php echo $HELPSTRINGS[41]; ?></li>
					</ul>
				</div>
			</li>
			<li>
				<div><?php echo $HELPSTRINGS[42]; ?>
					<ul>
						<li><?php echo $HELPSTRINGS[43]; ?></li>
						<li><?php echo $HELPSTRINGS[44]; ?>
							<ul>
								<li><?php echo $HELPSTRINGS[45]; ?></li>
								<li><?php echo $HELPSTRINGS[46]; ?></li>
								<li><?php echo $HELPSTRINGS[47]; ?></li>
							</ul>
						</li>
					</ul>
				</div>
			</li>
			<li>
				<div><?php echo $HELPSTRINGS[48]; ?></div>
			</li>
			<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
		</ul>

		<h4><a id="trouble"></a><?php echo $HELPSTRINGS[8]; ?></h4>
		<ul class="naked help">
			<?php echo $HELPSTRINGS[27]; ?>.
			<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
		</ul>

		<h4><a id="about"></a>About</h4>
		<ul class="naked help">
			<?php echo $HELPSTRINGS[28]; ?>:
			<div>
				<ul class="help">
					<li><a href="http://jqueryui.com/">jQuery UI Library</a></li>
					<li><a href="http://www.kryogenix.org/code/browser/sorttable/">SortTable <?php echo $HELPSTRINGS[29]; ?> Stuart Langridge</a></li>
					<li><a href="http://mobiledetect.net">Serban Ghita / Mobile_Detect PHP class</a></li>
				</ul>
			</div>
			<?php echo $HELPSTRINGS[30]; ?>
			<div>
				<ul class="help">
					<li>
						<a href="content/emil.php?subject=<?php echo $HELPSTRINGS[50]; ?>"
							target="_blank">
							<img id="address" class="emil" alt="email" src="content/mkpng.php?font=verdana&amp;size=10&amp;bg=white&amp;fg=%2300007f&amp;res=ADMIN_SNAILMAIL">
						</a>
					</li>
				</ul>
			</div>
			<a class="back" href="#"><?php echo $HELPSTRINGS[0]; ?></a>
		</ul>

		<div class="right footnote">
		<?php
			if (is_file('git-rev'))
			{
				$rev = file('git-rev');

				if ($rev)
				{
					foreach ($rev as $line)
						echo "$line";
				}
			}
		?></div>
	</div>
</div>
