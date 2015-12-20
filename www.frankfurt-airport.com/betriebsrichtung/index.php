<html><head><script type="text/javascript" src="http://www.fraport.de/apps/dfra_corporate/docroot/frontend/js/lib/lib.min.js"></script><link rel="stylesheet" type="text/css" href="ch_mobile_styles.css" />
<script type="text/javascript">
/*!
 * webTicker 1.3
 * Examples and documentation at:
 * http://jonmifsud.com
 * 2011 Jonathan Mifsud
 * Version: 1.2 (26-JUNE-2011)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 * Requires:
 * jQuery v1.4.2 or later
 *
 */
(function( $ ){

  var globalSettings = new Array();

  var methods = {
    init : function( settings ) { // THIS
		settings = jQuery.extend({
			travelocity: 0.05,
			direction: 1,
			moving: true
		}, settings);
		globalSettings[jQuery(this).attr('id')] = settings;
		return this.each(function(){
				var $strip = jQuery(this);
				$strip.addClass("newsticker")
				var stripWidth = 0;
				var $mask = $strip.wrap("<div class='mask'></div>");
				$mask.after("<span class='tickeroverlay-left'>&nbsp;</span><span class='tickeroverlay-right'>&nbsp;</span>")
				var $tickercontainer = $strip.parent().wrap("<div class='tickercontainer'></div>");
				$strip.find("li").each(function(i){
					stripWidth += jQuery(this, i).outerWidth(true);
				});
				$strip.width(stripWidth+200);//20 used for ie9 fix
				function scrollnews(spazio, tempo){
					if (settings.direction == 1)
						$strip.animate({left: '-='+ spazio}, tempo, "linear", function(){
							$strip.children().last().after($strip.children().first());
							var first = $strip.children().first();
							var width = first.outerWidth(true);
							var defTiming = width/settings.travelocity;
						//$strip.css("left", left);
							$strip.css("left", '0');
							scrollnews(width, defTiming);
						});
					else
						$strip.animate({right: '-='+ spazio}, tempo, "linear", function(){
							$strip.children().last().after($strip.children().first());
							var first = $strip.children().first();
							var width = first.outerWidth(true);
							var defTiming = width/settings.travelocity;
							//$strip.css("left", left);
							$strip.css("right", '0');
							scrollnews(width, defTiming);
						});
				}

				var first = $strip.children().first();
				var travel = first.outerWidth(true);
				var timing = travel/settings.travelocity;
				scrollnews(travel, timing);
				$strip.hover(function(){
					jQuery(this).stop();
				},
				function(){
					if (globalSettings[jQuery(this).attr('id')].moving){
						var offset = jQuery(this).offset();
						var first = $strip.children().first();
						var width = first.outerWidth(true);
						var residualSpace;
						if (settings.direction == 1) residualSpace = parseInt(jQuery(this).css('left').replace('px',''))+ width;
						else residualSpace = parseInt(jQuery(this).css('right').replace('px',''))+ width;
						var residualTime = residualSpace/settings.travelocity;
						scrollnews(residualSpace, residualTime);
					}
				});
		});
	},
    stop : function( ) {
		if (globalSettings[jQuery(this).attr('id')].moving){
			globalSettings[jQuery(this).attr('id')].moving = false;
			return this.each(function(){
				jQuery(this).stop();
			});
		}
	},
    cont : function( ) { // GOOD
		if (!(globalSettings[jQuery(this).attr('id')].moving)){
			globalSettings[jQuery(this).attr('id')].moving = true;
			var settings = globalSettings[jQuery(this).attr('id')];
			return this.each(function(){
				var $strip = jQuery(this);
					function scrollnews(spazio, tempo){
							if (settings.direction == 1)
								$strip.animate({left: '-='+ spazio}, tempo, "linear", function(){
									$strip.children().last().after($strip.children().first());
									var first = $strip.children().first();
									var width = first.outerWidth(true);
									var defTiming = width/settings.travelocity;
								//$strip.css("left", left);
									$strip.css("left", '0');
									scrollnews(width, defTiming);
								});
							else
								$strip.animate({right: '-='+ spazio}, tempo, "linear", function(){
									$strip.children().last().after($strip.children().first());
									var first = $strip.children().first();
									var width = first.outerWidth(true);
									var defTiming = width/settings.travelocity;
									//$strip.css("left", left);
									$strip.css("right", '0');
									scrollnews(width, defTiming);
								});

					}
						var offset = jQuery(this).offset();
						var first = $strip.children().first();
						var width = first.outerWidth(true);
						var residualSpace;
						if (settings.direction == 1) residualSpace = parseInt(jQuery(this).css('left').replace('px',''))+ width;
						else residualSpace = parseInt(jQuery(this).css('right').replace('px',''))+ width;
						var residualTime = residualSpace/settings.travelocity;
						scrollnews(residualSpace, residualTime);

			});
		}
	}
  };

  $.fn.webTicker = function( method ) {

    // Method calling logic
    if ( methods[method] ) {
      return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' +  method + ' does not exist on jQuery.webTicker' );
    }

  };

})( jQuery );</script>
<style type='text/css'>
<style type='text/css'>h2, h3 {page-break-after: avoid;}h1, h2, h3, h4, p {
            margin: 0;
            padding: 0;
        }h1, h2, h3, .titel {
            font-style: italic;
            font-weight: bold;
            color: #666;
        }h1 {
            font-size: 28px;
            line-height: 30px;
            margin-bottom: 30px;
        }h2 {
            font-size: 22px;
            line-height: 24px;
            margin-bottom: 18px;
        }h3 {
            font-size: 17px;
            line-height: 19px;
        }body { background-color: white}
.tickercontainer { /* the outer div with the black border */
width: 1760px;
margin: 0;
padding: 0;
overflow: hidden;
}
.tickercontainer .mask { /* that serves as a mask. so you get a sort of padding both left and right */
position: relative;
top: 6px;
height: 70px;
/*width: 718px;*/
overflow: hidden;
}
ul.newsticker { /* that's your list */
position: relative;
/*left: 950px;*/
list-style-type: none;
margin: 0;
padding: 0;
}
ul.newsticker li {
float: left; /* important: display inline gives incorrect results when you check for elem's width */
margin: 0;
padding-right: 130px;
/*background: #fff;*/
}</style>
</head><body><script type="text/javascript">
jQuery(function(){
    jQuery("#webticker").webTicker({travelocity: 0.085});
});
</script><div class='dfra_module_block'><div class='dfra_textblock_content'><div class="titel" style="margin-bottom:0px;padding-right:8px;float:left;"><h3>Flugbetrieb Frankfurt</h3></div><div style="padding:3px;"><b>Stand: 08.12.2015, 11:30:11 Uhr</b></div>
<?php
	if (!isset($_GET['rwy']))
	{
		$rwy = "18,99";
	}
	else
	{
		$rwy = $_GET['rwy'];

		switch ($rwy)
		{
		case "07":
		case "07,18":
		case "25":
		case "25,18":
			;

		default:
			$rwy = "18,99";
		}
	}
?>
<ul id="webticker">
    <li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Betriebsrichtung +++ </div><div style="font-size:12px;"><b> <?php
	if (strstr($rwy, '07'))
		echo "07 (Ost-Betrieb)";
	else if (strstr($rwy, '25'))
		echo "25 (West-Betrieb)";
	else
		echo "99";
?></b></div><div style="font-size:12px;padding-right:100px;"> seit 29.11.2015, 02:34:15</div></div>   </li>
    <li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Startbahn +++</div> <div style="font-size:12px;"><b><?php
	if (strstr($rwy, '18'))
    	echo "18 West";
?></b></div><div style="font-size:12px;padding-right:130px;"> <?php
	if (strstr($rwy, '18'))
		echo "in Betrieb";
?></div></div></li>
    <li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Wetterkategorie +++ </div><div style="font-size:12px;"><b>CAT III</b></div><div style="font-size:12px;padding-right:100px;"> seit 29.11.2015, 02:34:15</div></div></div>   </li>
</ul>

</div></div></body></html>
