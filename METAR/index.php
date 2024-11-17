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

?>
<html>
<body>
<div style="font-size: 8pt;">
<div><img src="img/BCFG.png"><br>BCFG - patches of fog</div>
<div><img src="img/BLDU.png"><br>BLDU - blowing dust</div>
<div><img src="img/BLPY.png"><br>BLPY - spray</div>
<div><img src="img/BLSA.png"><br>BLSA - blowing sand</div>
<div><img src="img/BLSN.png"><br>BLSN - blowing snow</div>
<div><img src="img/BR.png"><br>BR - mist</div>
<div><img src="img/DZ.png"><br>DZ - drizzle</div>
<div><img src="img/DZRA.png"><br>DZRA - drizzling rain</div>
<div><img src="img/DZSN.png"><br>DZSN - drizzle and snow</div>
<div><img src="img/FG.png"><br>FG - fog</div>
<div><img src="img/FU.png"><br>FU - smoke</div>
<div><img src="img/HZ.png"><br>HZ - haze</div>
<div><img src="img/RA.png"><br>RA - rain</div>
<div><img src="img/RADZ.png"><br>RADZ - drizzling rain</div>
<div><img src="img/RASN.png"><br>RASN - rain and snow</div>
<div><img src="img/SH.png"><br>SH - showers</div>
<div><img src="img/SN.png"><br>SN - snow</div>
<div><img src="img/NSW.png"><br>NSW - nil significant weather</div>
</div>
<title>METAR.php</title>
<pre>
<?php

	class Metar
	{
		public $icao;
		public $day;
		public $hour;
		public $minute;
		public $vis;
	}

	class Wind
	{
		public $direction;
		public $speed;
		public $unit;
	}

	class Visibility
	{
		public $range;
		public $unit;
		public $dir;
	}

	class Weather
	{
		public $intensity;
		public $desc;
		public $precipitation;
		public $haze;
		public $other;
	}

	function humidity($T, $TD)
	{
		/*
			SDD(T) = 6.1078 * 10^((a*T)/(b+T))
			DD(r,T) = r/100 * SDD(T)
			r(T,TD) = 100 * SDD(TD) / SDD(T)
			TD(r,T) = b*v/(a-v) mit v(r,T) = log10(DD(r,T)/6.1078)
			AF(r,TK) = 10^5 * mw/R* * DD(r,T)/TK; AF(TD,TK) = 10^5 * mw/R* * SDD(TD)/TK

			Bezeichnungen:
			r = relative Luftfeuchte
			T = Temperatur in °C
			TK = Temperatur in Kelvin (TK = T + 273.15)
			TD = Taupunkttemperatur in °C
			DD = Dampfdruck in hPa
			SDD = Sättigungsdampfdruck in hPa

			Parameter:
			a = 7.5, b = 237.3 für T >= 0
			a = 7.6, b = 240.7 für T < 0 über Wasser (Taupunkt)
			a = 9.5, b = 265.5 für T < 0 über Eis (Frostpunkt)

			R* = 8314.3 J/(kmol*K) (universelle Gaskonstante)
			mw = 18.016 kg (Molekulargewicht des Wasserdampfes)
			AF = absolute Feuchte in g Wasserdampf pro m3 Luft
		*/
		$a = $T >= 0 ? 7.5 : 7.6;
		$b = $T >= 0 ? 237.3 : 240.7;
		$T = 6.1078 * exp($a * $T / ($b + $T) / log10(2.71828183));

		$a = $T >= 0 ? 7.5 : 7.6;
		$b = $T >= 0 ? 237.3 : 240.7;
		$TD = 6.1078 * exp($a * $TD / ($b + $TD) / log10(2.71828183));

		return $TD / $T;
	}

	//echo humidity(7,6)."\n";

	function chunk(&$line, &$pos, $regex, &$match)
	{
		$found = 0;
		$match = [];

		do
		{
			//echo "<span style=\"color:gray;\">".$regex."\n\t".substr($line, $pos)."</span>\n";
			$result = preg_match($regex, $line, $m, PREG_OFFSET_CAPTURE, $pos);

			if ($result < 0)
			{
				$found = -1;
			}
			else
			{
				if (0 == $result)
				{
					break;
				}
				else
				{
					if ($m[0][1] > $pos)
					{
						break;
					}
					else
					{
						$len = strlen($m[0][0]);
						//echo "@$pos:[$len]:<span style=\"color: green;\">".substr($line, $pos, $len)."</span>\n";
						$pos += $len;

						while (' ' == substr($line, $pos, 1))
							$pos++;

						$match[$found] = [$m[0][0], $m];
						$found++;
					}
				}
			}
		}
		while ($found > 0);

		return $found;
	}

	$weather = [
		['MI', 'shallow'],
		['BC', 'patches'],
		['PR', 'partial'],
		['DR', 'low drifting'],
		['BL', 'blowing'],
		['SH', 'shower'],
		['TS', 'thunderstorm'],
		['FZ', 'freezing'],
		['DZ', 'drizzle'],
		['RA', 'rain'],
		['SN', 'snow'],
		['SG', 'snow grains'],
		['IC', 'ice crystals'],
		['PL', 'ice pellets'],
		['GR', 'hail'],
		['GS', 'small hail/snow pellets'],
		['UP', 'unknown precipitation'],
		['BR', 'mist'],
		['FG', 'fog'],
		['FU', 'smoke'],
		['VA', 'volcanic ash'],
		['DU', 'widespread dust'],
		['SA', 'sand'],
		['HZ', 'haze'],
		['PY', 'spray'],
		['PO', 'dust/sand whirls'],
		['SQ', 'squalls'],
		['FC', 'tornado'],
		['SS', 'sandstorm'],
		['DS', 'duststorm'],
	];

	$weathers = [
		['BCFG',     'patches of fog'],
		['BLDU',     'blowing dust'],
		['BLPY',     'spray'],
		['BLSA',     'blowing sand'],
		['BLSN',     'blowing snow'],
		['BR',       'mist'],
		['DRSA',     'low drifting sand'],//img?
		['DRSN',     'low drifting snow'],//img?
		['DZ',       'drizzle'],
		['DZRA',     'drizzling rain'],
		['FG',       'fog'],
		['FU',       'smoke'],
		['FZDZ',     'freezing drizzle'],//img?
		['FZFG',     'freezing fog'],//img?
		['FZRA',     'freezing rain'],//img?
		['HZ',       'haze'],
		['IC',       'ice crystals'],//img?
		['MIFG',     'shallow fog'],//img?
		['NSW',      'nil significant weather'],
		['PRFG',     'partial fog'],//img?
		['RA',       'rain'],
		['RADZ',     'drizzling rain'],
		['RASN',     'rain and snow'],
		['SA',       'sand'],//img?
		['SG',       'snow grains'],//img?
		['SGSN',     'snow grains /w snow'],//img?
		['SH',       'showers'],
		['SHPL',     'ice pellet showers'],//img?
		['SHRA',     'rain showers'],//img?
		['SHSN',     'snow showers'],//img?
		['SHSNRA',   'snow showers and rain'],//img?
		['SN',       'snow'],//img?
		['SNSH',     'snow showers'],//img?
		['TS',       'thunderstorm'],//img?
		['TSGRRASN', 'thunderstorm /w snow grains and rain'],//img?
		['TSRA',     'thunderstorm and rain'],//img?
		['TSSHRA',   'thunderstorm and rain showers'],//img?
		['UP',       'unknown'],//img?
	];

	$clouds = [
		['CLR', 'clear'],
		['NCD', 'no clouds detected'],
		['NSC', 'nil significant cloud'],
		['FEW', 'few'],
		['SCT', 'scattered'],
		['BKN', 'broken'],
		['OVC', 'overcast'],
		['VV',  'vertical visual range'],
	];

	$lines = 0;
	$metars = file("metars.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	foreach ($metars as $line)
	{
		$lines++;
		$line = strtoupper($line);
		$len = strlen($line);
		echo "#{$lines}\t";

		$pos = 0;
		$metar = NULL;

		// Airport
		$n = chunk($line, $pos, '/[A-Z][A-Z0-9]{3}/', $m);

		if ($n > 0)
		{
			$metar = new Metar;
			$metar->icao = $m[0][0];

			// Date/Time
			$n = chunk($line, $pos, '/([0-9]{2})([0-9]{2})([0-9]{2})Z/', $m);

			if ($n > 0)
			{
				$metar->day    = $m[0][1][1];
				$metar->hour   = $m[0][1][2];
				$metar->minute = $m[0][1][3];

				$n = chunk($line, $pos, '/AUTO/', $m);
			}
		}

		// Wind
		$n = chunk($line, $pos, '/((VRB|([0-9]{3}))([0-9]{2,3})(G[0-9]{2,3})?|XXXXX)?(KT|MPS|KMH)( ([0-9]{3})V([0-9]{3}))?/', $m);

		if ($n > -1)
		{
			// CAVOK
			$n = chunk($line, $pos, '/CAVOK/', $m);

			if (0 == $n)
			{
				// Visibility
				//$n = chunk($line, $pos, '/[0-9]{4,5}(NE|NW|SE|SW|N|S|E|W)?M?|[0-9]{1,2}KM|([0-9]([0-9]|\/[0-9])?)SM/', $m);
				$n = chunk($line, $pos, '/[0-9]{4,5}(NE|NW|SE|SW|N|S|E|W)?M?|[0-9]{1,2}KM|([0-9]([0-9]|\/[0-9]|[ ][0-9]{2})?)SM/', $m);

				if ($n > -1)
				{
					while ($n--)
						;//&&echo $m[$n][0].($n ? ' ' : '');

					// Runway Visual Range
					$n = chunk($line, $pos, '/R([0-9]{2}[LCR]?)\/?[MP]?[0-9]{4}(V[MP]?[0-9]{4})?(FT)?[UDN]?/', $m);

					if ($n > -1)
					{
						// Weather
						$n = chunk($line, $pos, '/NSW|(\+|-|)?(VC|BC|BL|BR|DR|DS|DU|DZ|FC|FG|FU|FZ|GR|GS|HZ|IC|MI|NSW|PL|PO|PR|RA|SA|SG|SH|SN|SQ|SS|TS|UP|VA)+/', $m);

						for ($i = 0; $i < $n; $i++)
						{
							$idx = 0;
							$str = preg_replace('/\+|-|VC/', '', $m[$i][0]);
/*
$f=fopen('weather.txt', 'a+');
fputs($f, "$str\n\n");
fclose($f);
*/
							foreach ($weathers as $w)
								if ($w[0] == $str)
									echo ($i ? ', ' : '').$w[1];
						}

						// Clouds
						$n = chunk($line, $pos, '/(NSC|NCD|CLR|SKC|FEW|SCT|BKN|OVC)([0-9]{2,3}(TCU|CB)*)*|TCU|CB|VV(\/\/\/|[0-9]{3})?/', $m);

						while ($n--)
							;//&&echo $m[$n][0].($n ? ' ' : '');
					}
				}
			}

			// Temperature
			$n = chunk($line, $pos, '/[MP]?[0-9]{2}(\/?[MP]?[0-9]{2})?/', $m);

			if ($n > -1)
			{
				// Air Pressure
				$n = chunk($line, $pos, '/(A|Q(FF|NH\ )?)([0-9]{4})?/', $m);

				if ($n > -1)
				{
					// Runway conditions
					$n = chunk($line, $pos, '/R[0-9]{2}[LCR]?\/?[0-9]{6}/', $m);

					if ($n > -1)
						;
				}

				$n = chunk($line, $pos, '/RE(BC|BL|BR|DR|DS|DU|DZ|FC|FG|FU|FZ|GR|GS|HZ|IC|MI|NSW|PL|PO|PR|RA|SA|SG|SH|SN|SQ|SS|TS|UP|VA)+/', $m);
			}
		}

		// Remark
		$n = chunk($line, $pos, '/RMK.*$/', $m);

		if ($n > -1)
			;

		// Forecast
		$n = chunk($line, $pos, '/(NOSIG|BECMG|TEMPO).*$/', $m);

		if ($n > -1)
			;

		echo "\t".'<span style="color:#008000">'.substr($line, 0, $pos).'</span>';
		echo '<span style="color:#ff0000">'.substr($line, $pos).'</span>';
		echo "\n";
	}

	echo "\n\n$metar->icao $metar->day $metar->hour $metar->minute\n";

?>
</pre>
</body>
</html>
