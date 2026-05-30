<?php

include "help-$_SESSION[lang].php";

?>

<div style="max-width: 792px;">
	<h3><a id="top"></a><?= $HELPSTRINGS[1] ?></h3>
	<ul>
		<li><a href="#basic"><?= $HELPSTRINGS[2] ?></a></li>
		<li><a href="#watchlist"><?= $HELPSTRINGS[3] ?></a></li>
		<ul>
			<li><a href="#watchlist_def"><?= $HELPSTRINGS[4] ?></a></li>
		</ul>
		<li><a href="#sorting"><?= $HELPSTRINGS[5] ?></a></li>
		<ul>
			<li><a href="#sorting_reg"><?= $HELPSTRINGS[6] ?></a></li>
		</ul>
		<li><a href="#mobile"><?= $HELPSTRINGS[7] ?></a></li>
		<li><a href="#data-privacy"><?= $HELPSTRINGS[33] ?></a></li>
		<li><a href="#trouble"><?= $HELPSTRINGS[8] ?></a></li>
		<li><a href="#about"><?= $HELPSTRINGS[49] ?></a></li>
	</ul>

	<hr>
	<div>
		<h4><a id="basic"></a><?= $HELPSTRINGS[2] ?></h4>
		<ul class="naked help">
			<li><?= $HELPSTRINGS[9] ?>.
			<li><?= $HELPSTRINGS[10] ?>.
			<li><?= $HELPSTRINGS[11] ?>.
			<li><?= $HELPSTRINGS[12] ?>...
			<li><?= $HELPSTRINGS[13] ?>.
			<li><?= $HELPSTRINGS[14] ?>.
			<li><?= $HELPSTRINGS[15] ?>.
			<li><?= $HELPSTRINGS[32] ?>.
			<li><?= $HELPSTRINGS[16] ?>.
			<li class="img">
				<img class="screen" src="<?= Asset::src("content/img/{$_SESSION['lang']}/1-basic.png") ?>">
				<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
			</li>
		</ul>

		<h4><a id="watchlist"></a><?= $HELPSTRINGS[3] ?></h4>
		<ul class="naked help">
			<li><?= $HELPSTRINGS[17] ?>.
			<li><?= $HELPSTRINGS[18] ?>.
			<li><?= $HELPSTRINGS[19] ?>
			<li class="img">
				<img class="screen" src="<?= Asset::src("content/img/{$_SESSION['lang']}/2-watchlist.png") ?>">
				<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
			</li>
		</ul>

		<h5><a id="watchlist_def"></a><?= $HELPSTRINGS[4] ?></h5>
		<ul class="naked help">
			<li><?= $HELPSTRINGS[20] ?>
			<li><?= $HELPSTRINGS[21] ?>.
			<li><?= $HELPSTRINGS[22] ?>.
			<li><?= $HELPSTRINGS[23] ?>.
			<li class="img">
				<img class="screen" src="<?= Asset::src("content/img/{$_SESSION['lang']}/3-watchlist-def.png") ?>">
				<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
			</li>
		</ul>

		<h4><a id="sorting"></a><?= $HELPSTRINGS[5] ?></h4>
		<ul class="naked help">
			<li><?= $HELPSTRINGS[24] ?>.
			<li class="img">
				<img class="screen" src="<?= Asset::src("content/img/{$_SESSION['lang']}/4-sorting.png") ?>">
				<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
			</li>
		</ul>

		<h5><a id="sorting_reg"></a><?= $HELPSTRINGS[6] ?></h5>
		<ul class="naked help">
			<li><?= $HELPSTRINGS[25] ?>.
			<li class="img">
				<img class="screen" src="<?= Asset::src("content/img/{$_SESSION['lang']}/5-sorting-reg.png") ?>">
				<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
			</li>
		</ul>

		<h4><a id="mobile"></a><?= $HELPSTRINGS[7] ?></h4>
		<ul class="help">
			<li><?= $HELPSTRINGS[26] ?>.
				<div><?= $HELPSTRINGS[31] ?>:
					<div id="mobile">
						<ul class="help">
							<li><img src="<?= Asset::src('img/arrival-grey-24x24.png') ?>"><?= $STRINGS['arrival'] ?></li>
							<li><img src="<?= Asset::src('img/departure-grey-24x24.png') ?>"><?= $STRINGS['departure'] ?></li>
							<li><img src="<?= Asset::src('img/help-grey-24x24.png') ?>"><?= $STRINGS['help'] ?></li>
							<li><img src="<?= Asset::src('img/register-grey-24x24.png') ?>"><?= $STRINGS['register'] ?></li>
							<li><img src="<?= Asset::src('img/login-grey-24x24.png') ?>"><?= $STRINGS['login'] ?></li>
							<li><img src="<?= Asset::src('img/logout-grey-24x24.png') ?>"><?= $STRINGS['logout'] ?></li>
							<li><img src="<?= Asset::src('img/profile-grey-24x24.png') ?>"><?= $STRINGS['profile'] ?></li>
							<li>&nbsp;</li>
							<li><img src="<?= Asset::src('img/dispinterval-grey-24x24.png') ?>"><?= $STRINGS['dispinterval'] ?></li>
							<li><img src="<?= Asset::src('img/notifinterval-grey-24x24.png') ?>"><?= $STRINGS['notifinterval'] ?></li>
							<li><img src="<?= Asset::src('img/changepw-grey-24x24.png') ?>"><?= $STRINGS['changepw'] ?></li>
						</ul>
					</div>
				</div>
			</li>
			<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
		</ul>

		<h4><a id="data-privacy"></a><?= $HELPSTRINGS[33] ?></h4>
		<ul class="naked help">
			<li>
				<div><?= $HELPSTRINGS[34] ?></div>
				<div><?= $HELPSTRINGS[35] ?></div>
			</li>
			<li>
				<div><?= $HELPSTRINGS[36] ?>
					<ul>
						<li><?= $HELPSTRINGS[37] ?></li>
						<li><?= $HELPSTRINGS[38] ?></li>
						<li><?= $HELPSTRINGS[39] ?></li>
						<li><?= $HELPSTRINGS[40] ?></li>
						<li><?= $HELPSTRINGS[41] ?></li>
					</ul>
				</div>
			</li>
			<li>
				<div><?= $HELPSTRINGS[42] ?>
					<ul>
						<li><?= $HELPSTRINGS[43] ?></li>
						<li><?= $HELPSTRINGS[44] ?>
							<ul>
								<li><?= $HELPSTRINGS[45] ?></li>
								<li><?= $HELPSTRINGS[46] ?></li>
								<li><?= $HELPSTRINGS[47] ?></li>
							</ul>
						</li>
					</ul>
				</div>
			</li>
			<li>
				<div><?= $HELPSTRINGS[48] ?></div>
			</li>
			<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
		</ul>

		<h4><a id="trouble"></a><?= $HELPSTRINGS[8] ?></h4>
		<ul class="naked help">
			<?= $HELPSTRINGS[27] ?>.
			<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
		</ul>

		<h4><a id="about"></a>About</h4>
		<ul class="naked help">
			<?= $HELPSTRINGS[28] ?>:
			<div>
				<ul class="help">
					<li><a href="https://jqueryui.com/">jQuery UI Library</a></li>
					<li><a href="https://www.kryogenix.org/code/browser/sorttable/">SortTable <?= $HELPSTRINGS[29] ?> Stuart Langridge</a></li>
					<li><a href="https://mobiledetect.net">Serban Ghita / Mobile_Detect PHP class</a></li>
				</ul>
			</div>
			<?= $HELPSTRINGS[30] ?>
			<div>
				<ul class="help">
					<li>
						<a href="content/emil.php?subject=<?= $HELPSTRINGS[50] ?>"
							target="_blank">
							<img id="address" class="emil" alt="email" src="content/mkpng.php?font=verdana&amp;size=10&amp;bg=white&amp;fg=%2300007f&amp;res=ADMIN_SNAILMAIL">
						</a>
					</li>
				</ul>
			</div>
			<a class="back" href="#"><?= $HELPSTRINGS[0] ?></a>
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
