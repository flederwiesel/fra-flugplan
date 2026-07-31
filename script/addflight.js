$(function()
{
	$('#from').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: 0,
		maxDate: '+1Y',
		changeMonth: true,
		changeYear: true,
		onSelect: function(selectedDate) { $('#until').datepicker('option', 'minDate', selectedDate);  }
	});

	$('#until').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: 0,
		maxDate: '+1Y',
		changeMonth: true,
		changeYear: true,
	});

	$(document).ready(function()
	{
		$('#reg').focus();
	});

	$('#form').submit(function() {
		$('#submit').attr('disabled', 'disabled');
	});

	/* Input event handlers */
	$('#once').click(function()	{
		$('#until').attr('disabled', '');
		days_enable(0);
	});

	$('#daily').click(function() {
		$('#until').removeAttr('disabled');
		$('#all').attr('checked', '');
		days_check(1);
		days_enable(0);
	});

	$('#each').click(function()	{
		$('#until').removeAttr('disabled');
		$('#all').removeAttr('disabled');
		days_enable(1);
	});

	$('#all').click(function() {
		days_check($(this).prop('checked'));
	});

	var days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

	function days_check(b) {

		jQuery.each(days, function() {
			$('#' + this).prop('checked', b);
		});
	}

	function days_enable(b) {

		if (b)
		{
			$('#all').removeAttr('disabled');

			jQuery.each(days, function() {
				$('#' + this).removeAttr('disabled', '');
			});
		}
		else
		{
			$('#all').attr('disabled', '');

			jQuery.each(days, function() {
				$('#' + this).attr('disabled', '');
			});
		}
	}
});
