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

/******************************************************************************
 *
 * <form> layout:
 *

  - registration -----

  (error|message)

  emailaddress    [ email ]
  username        [ user ]             hintnumchars
  password        [ passwd ]
  confirmpassword [ passwd-confirm ]   hintpassword
  language        [ lang ]

          [ sumbit ]
 *
 ******************************************************************************/

?>
<form class="stretched" method="post" action="?req=register"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $lang['registration']; ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="error">
			<?php echo $error; ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="success">
			<?php echo $message; ?>
		</div>
<?php } else { ?>
		<div id="notification" style="display: none;"></div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo $lang['emailaddress']; ?></div>
				<div class="cell">
					<input type="text" id="email" name="email"
					 value="<?php Input_SetValue('email', INP_POST | INP_GET, 'hausmeister@flederwiesel.com'); ?>" autofocus>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['username']; ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?php Input_SetValue('user', INP_POST | INP_GET, 'flederwiesel'); ?>">
					<div class="hint">
						<?php echo sprintf($lang['hintnumchars'], USERNAME_MIN, USERNAME_MAX); ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="cell">&nbsp;</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['password']; ?></div>
				<div class="cell">
					<input type="password" id="passwd" name="passwd"
					 value="<?php Input_SetValue(null, 0, 'elvizzz'); ?>">
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['confirmpassword']; ?></div>
				<div class="cell">
					<input type="password" id="passwd-confirm" name="passwd-confirm"
					 value="<?php Input_SetValue(null, 0, 'elvizzz'); ?>">
					<div class="hint"><?php echo $lang['hintpassword']; ?></div>
				</div>
			</div>
			<div class="row">
				<div class="cell">&nbsp;</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['timezone']; ?></div>
				<div class="cell">
					<select class="stretched" id="timezone" name="timezone">
						<option value="-43200" >GMT -12 Eniwetok, Kwajalein
						<option value="-39600" >GMT -11 Midway Island, Samoa
						<option value="-36000" >GMT -10 Hawaii
						<option value="-32400" >GMT -09 Alaska
						<option value="-28800" >GMT -08 Pacific Time (USA); Tijuana
						<option value="-25200" >GMT -07 Mountain Time (USA)
						<option value="-25200" >GMT -07 Arizona
						<option value="-21600" >GMT -06 Central Time (USA)
						<option value="-21600" >GMT -06 Saskatchewan
						<option value="-21600" >GMT -06 Mexico City, Tegucigalpa
						<option value="-18000" >GMT -05 Eastern Time (USA)
						<option value="-18000" >GMT -05 Indiana East
						<option value="-18000" >GMT -05 Bogota, Lima, Quito
						<option value="-14400" >GMT -04 Atlantic Time (Canada)
						<option value="-14400" >GMT -04 Caracas, La Paz
						<option value="-14400" >GMT -04 Santiago
						<option value="-10800" >GMT -03 Brasilia
						<option value="-10800" >GMT -03 Buenos Aires, Georgetown
						<option value="-7200" >GMT -02 Mid-Atlantic
						<option value="-3600" >GMT -01 Azores, Cape Verde Is.
						<option value="0" >GMT Dublin, Edinburgh, Lisbon, London
						<option value="0" >GMT Casablanca, Monrovia
						<option value="3600" >GMT +01 Belgrade, Bratislava, Budapest, Ljubljana, Prague
						<option value="3600" >GMT +01 Sarajevo, Skopje, Sofija, Warsaw, Zagreb
						<option value="3600" >GMT +01 Brussels, Copenhagen, Madrid, Paris, Vilnius
						<option value="3600" selected>GMT +01 Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna
						<option value="7200" >GMT +02 Bucharest
						<option value="7200" >GMT +02 Cairo
						<option value="7200" >GMT +02 Helsinki, Riga, Tallinn
						<option value="7200" >GMT +02 Athens, Istanbul, Minsk
						<option value="7200" >GMT +02 Jerusalem
						<option value="7200" >GMT +02 Harare, Pretoria
						<option value="10800" >GMT +03 Moscow, St. Petersburg, Volgograd
						<option value="10800" >GMT +03 Baghdad, Kuwait, Riyadh
						<option value="10800" >GMT +03 Nairobi
						<option value="14400" >GMT +04 Abu Dhabi, Muscat
						<option value="14400" >GMT +04 Baku, Tbilisi
						<option value="18000" >GMT +05 Ekaterinburg
						<option value="18000" >GMT +05 Islamabad, Karachi, Tashkent
						<option value="21600" >GMT +06 Astana, Almaty, Dhaka
						<option value="21600" >GMT +06 Colombo
						<option value="25200" >GMT +07 Bangkok, Hanoi, Jakarta
						<option value="28800" >GMT +08 Beijing, Chongqing, Hong Kong, Urumqi
						<option value="28800" >GMT +08 Singapore
						<option value="28800" >GMT +08 Taipei
						<option value="28800" >GMT +08 Perth
						<option value="32400" >GMT +09 Seoul
						<option value="32400" >GMT +09 Osaka, Sapporo, Tokyo
						<option value="32400" >GMT +09 Yakutsk
						<option value="36000" >GMT +10 Canberra, Melbourne, Sydney
						<option value="36000" >GMT +10 Brisbane
						<option value="36000" >GMT +10 Hobart
						<option value="36000" >GMT +10 Vladivostok
						<option value="36000" >GMT +10 Guam, Port Moresby
						<option value="39600" >GMT +11 Magadan, Solomon Is., New Caledonia
						<option value="43200" >GMT +12 Fiji, Kamchatka, Marshall Is.
						<option value="43200" >GMT +12 Auckland, Wellington
					</select>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['language']; ?></div>
				<div class="cell">
					<select class="stretched" id="lang" name="lang">
<?php
						/* Sort languages by local denomination */
						$language = array(
							'de' => $lang['de'],
							'en' => $lang['en']
							);

						asort($language);

						foreach ($language as $key => $value)
							echo "<option value=\"$key\" ".
								($key == $_SESSION['lang'] ? " selected" : "").">$value</option>";
?>
					</select>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="submit" id="submit" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
