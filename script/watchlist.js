function GetElementsByTag(parent, name, class_name)
{
	var elements = parent.querySelectorAll(name);
	var a = null;

	elements.forEach(function(element) {
		if (element.parentNode == parent)
		{
			if (!class_name || class_name == element.className)
			{
				if (!("none" == element.style.display))
				{
					if (null == a)
						a = new Array(0);

						a.push(element);
				}
			}
		}
	});

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

function AddRow(event)
{
	var table = getParentElementByTagName(event.target, "table");
	var tr = table.querySelector("tbody tr:first-of-type");
	var td;
	var row;

	/* Create new row to be inserted before this one, containing copies of col[0..n] */
	row = tr.cloneNode(true);
	row.dataset["submit"] = "add";

	td = row.querySelectorAll("td");

	if (td.length) {
		var div = td[0].querySelectorAll("div");

		if (div.length) {
			div[0].remove();
			td[0].insertAdjacentElement(
				"afterbegin", document.createElement("div")
			);
		}
	}

	// Clear and enable inputs
	var inputs = row.querySelectorAll("input");

	inputs.forEach(function(input) {
		input.value = "";
		input.disabled = false;
	});

	// Enable buttons
	row.querySelectorAll("button").
	forEach(function(button) {
		button.disabled = false;
	});

	setWatchlistButtonEvents(row);

	tr.parentNode.insertBefore(row, tr);

	// Set focus to the first input of the cloned row
	if (inputs.length) {
		inputs[0].focus();
	}

	return row;
}

function RemoveRow(event)
{
	var tr = getParentElementByTagName(event.target, "tr");
	var next;

	inp = tr.querySelectorAll("input,button");

	inp.forEach(function(elem) {
		elem.disabled = true;
	});

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
		next = AddRow(event);
	}

	if (next) {
		next.querySelector("input").focus();
	}
}

function setWatchlistButtonEvents(parent) {
	let buttons = parent.querySelectorAll("button");

	buttons.forEach(function(button) {
		if (button.classList.contains("del")) {
			button.onclick = RemoveRow;
		}
	});
}

function ToggleNotifications()
{
	var watchlist = document.getElementById("watchlist");
	var inputs = watchlist.querySelectorAll("input[type=checkbox]");
	var value = value = !inputs[0].checked;

	inputs.forEach(function(input) {
		input.checked = value;

		var tr = getParentElementByTagName(input, "tr");

		if (!tr.dataset["submit"])
			tr.dataset["submit"] = "upd";
	});
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

document.addEventListener("DOMContentLoaded", () => {
	var watchlist = document.getElementById("watchlist");
	var handle = document.getElementById("watchlist-handle");

	handle.onclick = function(e) {
		toggleWatchlist(watchlist);
		e.stopPropagation();
	}

	var form = watchlist.querySelectorAll("form")[0];
	var inputs = form.querySelectorAll("input");

	inputs.forEach(function(elem) {
		elem.addEventListener("change", (event) => {
			// ...unless it is a newly added row
			var tr = event.target.parentNode.parentNode;

			if (tr.dataset["submit"] != "add")
				tr.dataset["submit"] = "upd";
		});
	});

	var add = form.querySelectorAll("#watchlist button.add");

	add.forEach(function(elem) {
		elem.onclick = AddRow;
	});

	form.addEventListener("formdata", (event) => {
		var form = event.target;
		var keys = [];
		const submit = {
			"private": {
				"add": [],
				"del": [],
				"upd": []
			},
			"shared": {
				"add": [],
				"del": [],
				"upd": []
			}
		};

		["private", "shared"].forEach(owner => {
			// Get column keys from table header
			form.querySelectorAll(
				`div[data-owner='${owner}'] thead tr th`
			).forEach(col => {
				keys.push(col.dataset["key"]);
			});

			// For each private/shared div, loop through table rows,
			// check whether they are marked as to be added, updated or deleted
			// and build up a according arrays.
			form.querySelectorAll(`div[data-owner='${owner}'] tbody tr`).forEach(row => {
				var action = row.dataset["submit"];

				if (action)
				{
					var prev;
					var reg;
					var comment;
					var notify;

					row.querySelectorAll("input").forEach((input, idx) => {
						switch (keys[idx]) {
							case "reg":
								prev = input.defaultValue;
								reg = input.value;
								break;
							case "comment":
								comment = input.value;
								break;
							case "notify":
								notify = input.checked;
								break;
						}
					});

					if (reg.length > 0)
					{
						if (action === "del") {
							submit[owner].del.push(reg);
						}
						else {
							entry = {
								"reg": reg,
								"comment": comment,
								"notify": notify ?? false,
							};

							if (action === "add") {
								submit[owner].add.push(entry);
							}
							else if (action === "upd") {
								entry.prev = prev
								submit[owner].upd.push(entry);
							}
						}
					}
				}
			});
		});

		event.formData.set("watchlist", JSON.stringify(submit));
	});

	var tabs = document.querySelector("#watchlist .tabs");

	tabs.onclick = function(e) {
		e.stopPropagation();
	}

	form.onclick = function(e) {
		e.stopPropagation();
	}

	var body = document.querySelector("body");

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

	document.getElementById("toggle-notifications").
	addEventListener("click", (event) => {
		ToggleNotifications();
	});
});
