function GetElementsByTag(parent, name, class_name)
{
	var elements = parent.getElementsByTagName(name);
	var a = null;

	for (var i = 0; i < elements.length; i++)
	{
		if (elements[i].parentNode == parent)
		{
			if (!class_name || class_name == elements[i].className)
			{
				if (!("none" == elements[i].style.display))
				{
					if (null == a)
						a = new Array(0);

					a.push(elements[i]);
				}
			}
		}
	}

	return a;
}

function CloneRow(event)
{
	var tr = event.target.parentNode.parentNode;
	var td;
	var row;

	/* Create new row to be inserted before this one, containing copies of col[0..n] */
	row = tr.cloneNode(true);
	row.setAttribute("add", "true");

	td = row.getElementsByTagName("td");

	if (td.length) {
		var div = td[0].getElementsByTagName("div");

		if (div.length) {
			div[0].remove();
			td[0].insertAdjacentElement(
				'afterbegin', document.createElement("div")
			);
		}
	}

	// Clear inputs
	var inp = row.getElementsByTagName("input");

	if (inp.length) {
		for (let i = 0; i < inp.length; i++)
			inp[i].value = "";
	}

	setWatchlistButtonEvents(row);

	tr.parentNode.insertBefore(row, tr.nextSibling);

	// Set focus to the first input of the cloned row
	if (inp.length) {
		inp[0].focus();
	}

	return row;
}

function RemoveRow(event)
{
	var tr = event.target.parentNode.parentNode;
	var rows = GetElementsByTag(/*<tbody>*/tr.parentNode, "tr", "");
	var next;
	var inp;

	if (1 == rows.length)
	{
		next = CloneRow(event);

		// Don't remove, as its values are still required for submit
		tr.style.display = "none";
		tr.setAttribute("del", "true");
	}
	else
	{
		if (rows.length > 1)
		{
			for (i = 0; i < rows.length; i++)
			{
				if (rows[i] == tr)
				{
					// Don't remove, as its values are still required for submit
					tr.style.display = "none";
					tr.setAttribute("del", "true");

					if (i < rows.length - 1)
						next = rows[i + 1];
					else
						next = rows[i - 1];

					break;
				}
			}
		}
	}

	inp = next.getElementsByTagName("input");
	inp[0].focus();
}

function setWatchlistButtonEvents(parent) {
	buttons = parent.getElementsByTagName("button");

	for (let i = 0; i < buttons.length; i++) {
		if (buttons[i].classList.contains("add")) {
			buttons[i].onclick = CloneRow;
		}
		else if (buttons[i].classList.contains("del")) {
			buttons[i].onclick = RemoveRow;
		}
}
}

$(function()
{
	$("#watchlist form").on("focusin", "input.reg", function()
	{
		// For a newly added row, we don't need to remember reg
		// When updating a reg, remember original value for
		// SQL UPDATE statement in attr("reg")
		if (!$($(this).parents("tr")[0]).attr("add"))
		{
			if (!$(this).data("reg"))
				$(this).data("reg", $(this).val());
		}
	});

	$("#watchlist form").on("change", "input", function()
	{
		// Whenever an input values changes, mark row as changed
		// ...unless it is a newly added row
		if (!$($(this).parents("tr")[0]).attr("add"))
			$($(this).parents("tr")[0]).attr("upd", "true");
	});

	$("#watchlist form").submit(function(event) {

		var add = null;
		var del = null;
		var upd = null;

		$("input:submit", $(this)).attr("disabled", "disabled");

		// Loop through table rows, check whether they are marked as to be added,
		// updated or deleted and build up three strings, being separated with
		// newline from each other.
		// Within these lines, multiple input values are separated using tabs.
		$("#watchlist form tbody tr").each(function()
		{
			reg = $("input.reg", $(this))[0];

			if ($(this).attr("del") == "true")
			{
				// "$reg\n$reg"
				del = del ? del + "\n" : "";
				del += $(reg).val();
			}
			else
			{
				comment = $("input.comment", $(this))[0];
				notify  = $("input.notify",  $(this))[0];

				if ($(this).attr("add") == "true")
				{
					// "$reg\t$comment\t$notify\n..."
					add = add ? add + "\n" : "";
					add += $(reg).val() + "\t" +
						$(comment).val() + "\t" +
						($(notify).is(":checked") ? 1 : 0);
				}
				else
				{
					// assume `$(this).attr("upd") == "true"`
					// "$reg\t$NewReg\t$comment\t$notify\n..."
					upd = upd ? upd + "\n" : "";
					upd += ($(reg).data("reg") ? $(reg).data("reg") : "") + "\t" +
						$(reg).val() + "\t" +
						$(comment).val() + "\t" +
						($(notify).is(":checked") ? 1 : 0);
				}
			}
		});

		if (add)
			$("#watchlist form").append($("<input>").attr("type", "hidden").attr("name", "add").val(add));

		if (del)
			$("#watchlist form").append($("<input>").attr("type", "hidden").attr("name", "del").val(del));

		if (upd)
			$("#watchlist form").append($("<input>").attr("type", "hidden").attr("name", "upd").val(upd));

		event.preventDefault();
		this.submit();
	});
});

function ToggleNotifications()
{
	var watchlist = document.getElementById("watchlist");
	var inp = watchlist.getElementsByTagName("input");
	var value = true;

	for (i = 0; i < inp.length; i++)
	{
		if ("checkbox" == inp[i].type)
		{
			value = inp[i].checked;
			break;
		}
	}

	for (i = 0; i < inp.length; i++)
	{
		if ("checkbox" == inp[i].type)
		{
			inp[i].checked = !value;
		}
	}
}

$(function() {
	var watchlist = document.getElementById("watchlist");
	var handle = document.getElementById("watchlist-handle");

	handle.onclick = function(e) {
		if (watchlist.classList.contains("expanded")) {
			watchlist.classList.remove("expanded");
		}
		else {
			watchlist.classList.add("expanded");
		}

		e.stopPropagation();
	}

	var form = watchlist.getElementsByTagName("form")[0];

	form.onclick = function(e) {
		e.stopPropagation();
	}

	var body = document.getElementsByTagName("body")[0];

	body.onclick = function(e) {
		watchlist.classList.remove("expanded");
	}

	body.onkeydown = function(e) {
		if (e.key === "Escape") {
			watchlist.classList.remove("expanded");
		}
	};

	setWatchlistButtonEvents(watchlist);
});
