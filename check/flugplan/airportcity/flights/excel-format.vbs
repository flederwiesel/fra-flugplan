'http://stackoverflow.com/questions/2050505/way-to-run-excel-macros-from-command-line-or-batch-file
'http://support.microsoft.com/kb/219151
'http://support.microsoft.com/kb/291308
'http://www.cerrotorre.de/faq-script/faq-script-excel.htm
'http://www.datapigtechnologies.com/downloads/Excel_Enumerations.txt

Option Explicit

Const xlAddIn = 18
Const xlAddIn8 = 18
Const xlCSV = 6
Const xlCSVMac = 22
Const xlCSVMSDOS = 24
Const xlCSVWindows = 23
Const xlCurrentPlatformText = -4158
Const xlDBF2 = 7
Const xlDBF3 = 8
Const xlDBF4 = 11
Const xlDIF = 9
Const xlExcel12 = 50
Const xlExcel2 = 16
Const xlExcel2FarEast = 27
Const xlExcel3 = 29
Const xlExcel4 = 33
Const xlExcel4Workbook = 35
Const xlExcel5 = 39
Const xlExcel7 = 39
Const xlExcel8 = 56
Const xlExcel9795 = 43
Const xlHtml = 44
Const xlIntlAddIn = 26
Const xlIntlMacro = 25
Const xlOpenDocumentSpreadsheet = 60
Const xlOpenXMLAddIn = 55
Const xlOpenXMLStrictWorkbook = 61
Const xlOpenXMLTemplate = 54
Const xlOpenXMLTemplateMacroEnabled = 53
Const xlOpenXMLWorkbook = 51
Const xlOpenXMLWorkbookMacroEnabled = 52
Const xlSYLK = 2
Const xlTemplate = 17
Const xlTemplate8 = 17
Const xlTextMac = 19
Const xlTextMSDOS = 21
Const xlTextPrinter = 36
Const xlTextWindows = 20
Const xlUnicodeText = 42
Const xlWebArchive = 45
Const xlWJ2WD1 = 14
Const xlWJ3 = 40
Const xlWJ3FJ3 = 41
Const xlWK1 = 5
Const xlWK1ALL = 31
Const xlWK1FMT = 30
Const xlWK3 = 15
Const xlWK3FM3 = 32
Const xlWK4 = 38
Const xlWKS = 4
Const xlWorkbookDefault = 51
Const xlWorkbookNormal = -4143
Const xlWorks2FarEast = 28
Const xlWQ1 = 34
Const xlXMLSpreadsheet = 46

Const xlDelimited                =  1
Const xlTextQualifierDoubleQuote =  1

Const xlLocalSessionChanges = 2

Const xlWhole = 1
Const xlPart = 2

Const xlDown = -4121
Const xlToLeft = -4159
Const xlToRight = -4161
Const xlUp = -4162

Const xlDiagonalDown = 5
Const xlDiagonalUp = 6
Const xlEdgeBottom = 9
Const xlEdgeLeft = 7
Const xlEdgeRight = 10
Const xlEdgeTop = 8
Const xlInsideHorizontal = 12
Const xlInsideVertical = 11

Const xlContinuous = 1
Const xlDash = -4115
Const xlDashDot = 4
Const xlDashDotDot = 5
Const xlDot = -4118
Const xlDouble = -4119
Const xlLineStyleNone = -4142
Const xlSlantDashDot = 13

Const xlHairline = 1
Const xlMedium = -4138
Const xlThick = 4
Const xlThin = 2

Const xlLandscape = 2
Const xlPortrait = 1

Const xlPaper10x14 = 16
Const xlPaper11x17 = 17
Const xlPaperA3 = 8
Const xlPaperA4 = 9
Const xlPaperA4Small = 10
Const xlPaperA5 = 11
Const xlPaperB4 = 12
Const xlPaperB5 = 13
Const xlPaperCsheet = 24
Const xlPaperDsheet = 25
Const xlPaperEnvelope10 = 20
Const xlPaperEnvelope11 = 21
Const xlPaperEnvelope12 = 22
Const xlPaperEnvelope14 = 23
Const xlPaperEnvelope9 = 19
Const xlPaperEnvelopeB4 = 33
Const xlPaperEnvelopeB5 = 34
Const xlPaperEnvelopeB6 = 35
Const xlPaperEnvelopeC3 = 29
Const xlPaperEnvelopeC4 = 30
Const xlPaperEnvelopeC5 = 28
Const xlPaperEnvelopeC6 = 31
Const xlPaperEnvelopeC65 = 32
Const xlPaperEnvelopeDL = 27
Const xlPaperEnvelopeItaly = 36
Const xlPaperEnvelopeMonarch = 37
Const xlPaperEnvelopePersonal = 38
Const xlPaperEsheet = 26
Const xlPaperExecutive = 7
Const xlPaperFanfoldLegalGerman = 41
Const xlPaperFanfoldStdGerman = 40
Const xlPaperFanfoldUS = 39
Const xlPaperFolio = 14
Const xlPaperLedger = 4
Const xlPaperLegal = 5
Const xlPaperLetter = 1
Const xlPaperLetterSmall = 2
Const xlPaperNote = 18
Const xlPaperQuarto = 15
Const xlPaperStatement = 6
Const xlPaperTabloid = 3
Const xlPaperUser = 256

Dim directions
Dim dir
Dim fso
Dim filext
Dim filename
Dim excel
Dim book
Dim sheet
Dim region
Dim row
Dim bottom
Dim range
Dim match
Dim re
Dim comment

'On Error GoTo HandleError
'On Error Resume Next

directions = Array("arrival", "departure")

Set re = CreateObject("VBScript.RegExp")
Set fso = CreateObject("Scripting.FileSystemObject")

Set excel = CreateObject("Excel.Application")
excel.Visible = True
'excel.Visible = False

For Each dir In directions

	filext = ".xlsx"
	filename = fso.GetAbsolutePathName(dir & ".csv")

	If (fso.FileExists(filename + filext)) Then
		fso.DeleteFile (filename + filext), True
	End If

	excel.Workbooks.Open filename, False, True, 2, , , , , , True, , , False, True

	Set book = excel.ActiveWorkbook
	Set sheet = book.Worksheets(1)

	' Replace C-Comments, change commented cell colour to red
	Set range = sheet.Columns("A:M")
	Set match = range.Find("/*")

	If Not match Is Nothing Then
		Do

			re.Global = True
			re.IgnoreCase = True
			' C-Comment with spaces around and in between
			re.Pattern = "[ \t]*/\*([ \t]*[^/]*|[^*]/[ \t]*)\*/"

			Set comment = re.Execute(match.Value)

			If Not comment Is Nothing Then
				match.Value = re.Replace(match.Value, "$1")
				match.Characters(comment(0).FirstIndex + 1, 0).Font.Color = Rgb(255,0 , 0)
				match.Characters(comment(0).FirstIndex + 1, 0).Font.Bold = True
			End If

			Set match = range.FindNext(match)
		Loop While Not match Is Nothing
	End If

	' Set font of all sheet's cells
	With sheet.Cells.Font
		.Name = "Arial"
		.Size = 6
	End With

	' Autofit all columns
	With book.Worksheets(1).Columns
		.Autofit
	End With

	' Select regions of cells in rows A:M for contiguous rows
	' Similar to Ctrl+Shift+Right + Ctrl+Shift+Down, except
	' for empty cells, which we jump over horizontally, if empty
	row = 1

	While (row > 0) And (sheet.Range("A" & row) <> "")
		Set region = sheet.Range("A" & row).CurrentRegion

		bottom = region.Cells.Row + UBound(region.Cells) - 1

		With excel.Range("A" & bottom & ":M" & bottom).Cells.Borders(xlEdgeBottom)
			.LineStyle = xlContinuous
			.ColorIndex = 0
			.TintAndShade = 0
			.Weight = xlThin
		End With

		row = row + UBound(region.Cells) + 1
		Set region = Nothing
	Wend

	With sheet.PageSetup
		.LeftMargin = excel.InchesToPoints(0.5)
		.RightMargin = excel.InchesToPoints(0.5)
		.TopMargin = excel.InchesToPoints(0.5)
		.BottomMargin = excel.InchesToPoints(0.5)
		.HeaderMargin = excel.InchesToPoints(0.2)
		.FooterMargin = excel.InchesToPoints(0.2)
		.PaperSize = xlPaperA4
		.Orientation = xlPortrait 'xlLandscape
	End With

	If ".xlsx" = filext Then
		book.SaveAs (filename + filext), xlOpenXMLWorkbook, , , , False, , xlLocalSessionChanges
	Else
		If ".xls" = filext Then
			book.SaveAs (filename + filext), xlExcel7, , , , False, , xlLocalSessionChanges
		Else
			MsgBox "`" + fileext + "` not supported."
		End If
	End If

	Set sheet = Nothing

	book.Close
	Set book = Nothing

Next

'HandleError:

excel.Quit

Set excel = Nothing
Set fso = Nothing
