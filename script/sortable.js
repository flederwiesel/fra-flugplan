var idxof_model = -1;
var idxof_reg = -1;

schedule = document.getElementById("schedule");

if (schedule)
{
	header = schedule.getElementsByTagName("thead");

	if (header)
	{
		tr = schedule.getElementsByTagName("tr");

		if (tr)
		{
			cols = tr[0].getElementsByTagName("th");

			for (i = 0; i < cols.length; i++)
			{
				if (cols[i].classList.contains("sorttable_model"))
					idxof_model = i;

				if (cols[i].classList.contains("sorttable_reg"))
					idxof_reg = i;
			}
		}
	}
}

function sort_model(tr1, tr2)	/* [0]=td.value [1]=tr */
{
	/* Cargo has higher prio */
	l = tr1[1].cells[idxof_model].className.split(" ").indexOf("cargo") < 0 ? 1 : 0;
	r = tr2[1].cells[idxof_model].className.split(" ").indexOf("cargo") < 0 ? 1 : 0;

	if (l == r)
	{
		/* Always sort empty string towards bottom */
		if (0 == tr1[0].length)
		{
			if (tr2[0].length > 0)
				return 1;
		}
		else
		{
			if (0 == tr2[0].length)
				return -1;
		}

		l = tr1[0];
		r = tr2[0];
	}

	return l < r ? -1 : (l > r ? 1 : 0);
}

function sort_reg(tr1, tr2)	/* [0]=td.value [1]=tr */
{
	/* Prio in descending order:
	 * - watchlist
	 * - rare
	 * - normal
	 * - empty
	 */
	l = "watch" == tr1[1].cells[idxof_reg].className ? 0 :
		("rare" == tr1[1].cells[idxof_reg].className ? 1 : 2);

	r = "watch" == tr2[1].cells[idxof_reg].className ? 0 :
		("rare" == tr2[1].cells[idxof_reg].className ? 1 : 2);

	if (l == r)
	{
		/* Always sort empty string towards bottom */
		if (0 == tr1[0].length)
		{
			if (tr2[0].length > 0)
				return 1;
		}
		else
		{
			if (0 == tr2[0].length)
				return -1;
		}

		l = tr1[0];
		r = tr2[0];
	}

	return l < r ? -1 : (l > r ? 1 : 0);
}
