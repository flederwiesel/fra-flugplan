/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author$
 *         $Date$
 *          $Rev$
 *
 ******************************************************************************
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

/*
 *  Retrieve data using the XMLHttpRequest object
 *
 */
function AjaxCallServer(url)
{
	var xml = null;
	var response = null;

	try
	{
		// Mozilla, Opera, Safari sowie Internet Explorer >= v7
		xml = new XMLHttpRequest();
	}
	catch (exception)
	{
		try
		{
			// MS Internet Explorer >= v6
			xml = new ActiveXObject("Microsoft.XMLHTTP");
		}
		catch (exception)
		{
			try
			{
				// MS Internet Explorer >= v5
				xml = new ActiveXObject("Msxml2.XMLHTTP");
			}
			catch (exception)
			{
				//alert("Your browser does not support XMLHTTP.");
				xml = null;
			}
		}
	}

	if (null == xml)
	{
		//response = "Your browser does not support XMLHTTP.";
	}
	else
	{
		xml.open("GET", url, false);
		xml.send(null);

		if (404 == xml.status)
			alert("Error 404: Incorrect URL: GET " + url);

		response = xml.responseText;
	}

	return response;
}
