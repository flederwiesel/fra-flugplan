(function() {
	var tab = document.getElementById("profile-tab");
	var active = tab.getAttribute("data-active");

	if (active === 'dispinterval')
	{
		$.each(["phone", "tablet"], function(index, value)
		{
			var min = $("#" + value + "-min");
			var max = $("#" + value + "-max");
			var divider = $("option", min).length;
			var ticks = $("option", min).length + $("option", max).length;
			var slider = $("<div id=\"" + value + "-slider\"></div>").insertAfter($(max)).slider({
				min: 1,
				max: ticks,
				range: true,

				values: [$(min)[0].selectedIndex + 1,
						$(max)[0].selectedIndex + 1 + $("option", min).length],

				slide: function(event, ui)
				{
					/* Don't let min and max overlap! */
					if (ui.values[0] > divider)
						return false;

					if (ui.values[1] < divider + 1)
						return false;

					$(min)[0].selectedIndex = ui.values[0] - 1;
					$(max)[0].selectedIndex = ui.values[1] - 1 - $("option", min).length;
				}
			});
		});

		$("#phone-min").change(function()
		{
			$("#phone-slider").slider("values", 0, this.selectedIndex + 1);
		});

		$("#phone-max").change(function()
		{
			$("#phone-slider").slider("values", 1,
				$("#phone-min option").length + this.selectedIndex + 1);
		});

		$("#tablet-min").change(function()
		{
			$("#tablet-slider").slider("values", 0, this.selectedIndex + 1);
		});

		$("#tablet-max").change(function()
		{
			$("#tablet-slider").slider("values", 1,
				$("#tablet-min option").length + this.selectedIndex + 1);
		});
	}

	if (active === 'notifinterval')
	{
		$.each(["notification"], function(index, value)
		{
			var min = $("#" + value + "-from");
			var max = $("#" + value + "-until");
			var divider = $("option", min).lentgh;
			var slider = $("<div id=\"" + value + "-slider\"></div>").insertAfter($(max)).slider({
				min: 1,
				max: 25,
				range: true,

				values: [$(min)[0].selectedIndex + 1,
						$(max)[0].selectedIndex + 1],

				slide: function(event, ui)
				{
					$(min)[0].selectedIndex = ui.values[0] - 1;
					$(max)[0].selectedIndex = ui.values[1] - 1;
				}
			});
		});

		$("#notification-from").change(function()
		{
			if ($("#notification-until").prop("selectedIndex") <= this.selectedIndex)
				this.selectedIndex = $("#notification-until").prop("selectedIndex");

			$("#notification-slider").slider("values", 0, this.selectedIndex + 1);
		});

		$("#notification-until").change(function()
		{
			if ($("#notification-from").prop("selectedIndex") >= this.selectedIndex)
				this.selectedIndex = $("#notification-from").prop("selectedIndex");

			$("#notification-slider").slider("values", 1, this.selectedIndex + 1);
		});
	}
})();
