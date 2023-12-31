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

document.onmousedown = function(e)
{
	if (!e)
		e = window.event;

	if (e.target)
		target = e.target;
	else if (e.srcElement)
		target = e.srcElement;
	else
		target = NULL;

	if (target)
	{
		do
		{
			if ("wl_handle" == target.id)
			{
				watchlist("toggle");
				break;
			}
			if ("wl_cont" == target.id)
			{
				break;
			}

			target = target.parentNode;
		}
		while (target);

		if (!target)
			watchlist("hide");
	}

	return true;
}

var initial = 0;

function watchlist(action)
{
	wlc = document.getElementById("wl_cont");
	wl  = document.getElementById("wl_div");
	wlh = document.getElementById("wl_handle");
	div = document.getElementById("expandable");
	img = document.getElementById("wl_img");

	if (0 == initial)
		initial = div.style.width;

	if ("toggle" == action && initial == div.style.width ||
		"show"   == action)
	{
		div.style.visibility = "visible";
		div.style.width = "auto";
		img.src = wl_img_close;	// defined in main html

		wlc.style.margin = "0 " + (wl.clientWidth - (wlh.clientWidth - 12)) + "px 0 0";
	}
	else
	{
		div.style.visibility = "hidden";
		div.style.width = initial;
		img.src = wl_img_open;	// defined in main html

		wlc.style.marginRight = "12px";
	}

	document.getElementById("list").style.height = "auto";
}

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

function CloneRow(input)
{
	var tr = input.parentNode.parentNode;
	var row;
	var inp;
	var a;
	var img;

	/* Create new row to be inserted before this one, containing copies of col[0..n] */
	row = tr.cloneNode(true);
	row.setAttribute("add", "true");
	tr.parentNode.insertBefore(row, tr.nextSibling);

	inp = row.getElementsByTagName("input");

	for (var i = 0; i < inp.length; i++)
	{
		inp[i].name = "";
		inp[i].value = "";

		a = row.getElementsByTagName("a");

		if (a[0])
		{
			img = a[0].getElementsByTagName("img");

			img[0].src = "img/photodb-ina.png";

			if (a[0])
			{
				a[0].parentNode.appendChild(img[0]);
				a[0].parentNode.removeChild(a[0]);
			}
		}
	}

	inp[0].focus();

	return row;
}

function RemoveRow(input)
{
	var tr = input.parentNode.parentNode;
	var rows = GetElementsByTag(/*<tbody>*/tr.parentNode, "tr", "");
	var next;
	var inp;

	if (1 == rows.length)
	{
		next = CloneRow(input);

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

$(function()	// PreparePostData()
{
	$("#watch").on("focusin", "input.reg", function()
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

	$("#watch").on("change", "input", function()
	{
		// Whenever an input values changes, mark row as changed
		// ...unless it is a newly added row
		if (!$($(this).parents("tr")[0]).attr("add"))
			$($(this).parents("tr")[0]).attr("upd", "true");
	});

	$("#watch").submit(function(event) {

		var add = null;
		var del = null;
		var upd = null;

		$("input:submit", $(this)).attr("disabled", "disabled");

		// Loop through table rows, check whether they are marked as to be added,
		// updated or deleted and build up three strings, being separated with
		// newline from each other.
		// Within these lines, multiple input values are separated using tabs.
		$("#watch tbody tr").each(function()
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
					if ($(this).attr("upd") == "true")
					{
						// "$reg\t$NewReg\t$comment\t$notify\n..."
						upd = upd ? upd + "\n" : "";
						upd += ($(reg).data("reg") ? $(reg).data("reg") : "") + "\t" +
							$(reg).val() + "\t" +
							$(comment).val() + "\t" +
							($(notify).is(":checked") ? 1 : 0);
					}
				}
			}
		});


		if (add)
			$("#watch").append($("<input>").attr("type", "hidden").attr("name", "add").val(add));

		if (del)
			$("#watch").append($("<input>").attr("type", "hidden").attr("name", "del").val(del));

		if (upd)
			$("#watch").append($("<input>").attr("type", "hidden").attr("name", "upd").val(upd));

		event.preventDefault();
		this.submit();
	});
});

function ToggleNotifications()
{
	var form = document.getElementById("watch");
	var inp = form.getElementsByTagName("input");
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
