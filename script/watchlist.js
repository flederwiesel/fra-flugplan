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

function getParentElementByTagName(elem, tag) {
	tag = tag.toLowerCase();

	while (elem.parentNode) {
		elem = elem.parentNode;

		if (elem.tagName.toLowerCase() === tag)
			return elem;
	}

	return null;
}

function CloneRow(event)
{
	var tr = getParentElementByTagName(event.target, "tr");
	var td;
	var row;

	/* Create new row to be inserted before this one, containing copies of col[0..n] */
	row = tr.cloneNode(true);
	row.dataset["submit"] = "add";

	td = row.getElementsByTagName("td");

	if (td.length) {
		var div = td[0].getElementsByTagName("div");

		if (div.length) {
			div[0].remove();
			td[0].insertAdjacentElement(
				"afterbegin", document.createElement("div")
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
	var tr = getParentElementByTagName(event.target, "tr");
	var next;
	var inp;

	tr.dataset["submit"] = "del";

	// Find next sibling which has not been queued to be deleted
	next = tr.nextElementSibling;

	while (next) {
		if (next.dataset["submit"] === "del")
			next = next.nextElementSibling;
		else
			break;
	}

	if (!next) {
		// Find any previous sibling which has not been queued to be deleted
		next = tr.previousElementSibling;

		while (next) {
			if (next.dataset["submit"] === "del")
				next = next.previousElementSibling;
			else
				break;
		}
	}

	if (!next) {
		// The deleted was the only active row, show a new empty row
		next = CloneRow(event);
	}

	if (next) {
		inp = next.getElementsByTagName("input");
		inp[0].focus();
	}
}

function setWatchlistButtonEvents(parent) {
	let buttons = parent.getElementsByTagName("button");

	for (let i = 0; i < buttons.length; i++) {
		if (buttons[i].classList.contains("add")) {
			buttons[i].onclick = CloneRow;
		}
		else if (buttons[i].classList.contains("del")) {
			buttons[i].onclick = RemoveRow;
		}
	}

	let toggle = document.getElementById("toggle-notifications");

	toggle.addEventListener("click", (event) => {
		ToggleNotifications();
	});
}

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

function toggleWatchlist(wl = null)
{
	let watchlist = wl;

	if (!watchlist)
		watchlist = document.getElementById("watchlist");

	if (watchlist.classList.contains("expanded")) {
		watchlist.classList.remove("expanded");
	}
	else {
		watchlist.classList.add("expanded");
	}
}

$(function()
{
	let form = document.querySelector("#watchlist form");
	let inputs = form.querySelectorAll("input[type='text']");

	// Whenever an input values changes, mark row as changed
	inputs.forEach(function(elem) {
		elem.addEventListener("change", (event) => {
			// ...unless it is a newly added row
			var tr = getParentElementByTagName(event.target, "tr");

			if (tr) {
				if (tr.dataset["submit"] != "add")
						tr.dataset["submit"] = "upd";
			}
		});
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

			if ($(this)[0].dataset["submit"] == "del")
			{
				// "$reg\n$reg"
				del = del ? del + "\n" : "";
				del += $(reg).val();
			}
			else
			{
				comment = $("input.comment", $(this))[0];
				notify  = $("input.notify",  $(this))[0];

				if ($(this)[0].dataset["submit"] == "add")
				{
					// "$reg\t$comment\t$notify\n..."
					add = add ? add + "\n" : "";
					add += $(reg).val() + "\t" +
						$(comment).val() + "\t" +
						($(notify).is(":checked") ? 1 : 0);
				}
				else if ($(this)[0].dataset["submit"] == "upd")
				{
					// "$reg\t$NewReg\t$comment\t$notify\n..."
					upd = upd ? upd + "\n" : "";
					upd += ($(reg).prop("defaultValue") ? $(reg).prop("defaultValue") : "") + "\t" +
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

	let watchlist = document.getElementById("watchlist");
	let handle = document.getElementById("watchlist-handle");

	handle.onclick = function(e) {
		toggleWatchlist(watchlist);
		e.stopPropagation();
	}

	form.onclick = function(e) {
		e.stopPropagation();
	}

	var body = document.getElementsByTagName("body")[0];

	body.onclick = function(e) {
		if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
			watchlist.classList.remove("expanded");
		}
	}

	body.onkeydown = function(e) {
		if (e.key === "Escape") {
			watchlist.classList.remove("expanded");
		}
	};

	setWatchlistButtonEvents(watchlist);
});
