$(function()
{
	$('#date-from').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: new Date(2012, 6 - 1, 6),
		maxDate: 0,
		changeMonth: true,
		changeYear: true
	});

	$('#date-until').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: new Date(2012, 6 - 1, 6),
		maxDate: '0',
		changeMonth: true,
		changeYear: true,
		onSelect: function(selectedDate) { $('#from').datepicker('option', 'maxDate', selectedDate);  }
	});

	$('#form').submit(function() {
		$('#submit').attr('disabled', 'disabled');
	});
});
