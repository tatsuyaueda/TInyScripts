'====================================================================================
''' �R���{���X�g�̕`��
'------------------------------------------------------------------------------------
Private Sub comboFontList_DrawItem(ByVal sender As Object, ByVal e As System.Windows.Forms.DrawItemEventArgs) Handles comboFontList.DrawItem

	Dim ff As FontFamily
	Dim f As Font
	Dim fs As FontStyle
	Dim b As Brush

	ff = New FontFamily(Me.comboFontList.Items.Item(e.Index).ToString)

	If ff.IsStyleAvailable(FontStyle.Regular) Then
		fs = FontStyle.Regular
	ElseIf ff.IsStyleAvailable(FontStyle.Italic) Then
		fs = FontStyle.Italic
	Else
		fs = FontStyle.Bold
	End If

	f = New Font(ff, 12, fs)

	e.DrawBackground()	  '�w�i�̓h��Ԃ���VB�ɂ��C�����Ă��܂��B

	If e.State = DrawItemState.Selected Then
		b = SystemBrushes.HighlightText
	Else
		b = SystemBrushes.WindowText
	End If

	e.Graphics.DrawString(Me.comboFontList.Items.Item(e.Index).ToString, f, b, e.Bounds.X, e.Bounds.Y)

	'-- �t�H���g�̔j��
	f.Dispose()
	ff.Dispose()

End Sub
'====================================================================================

'====================================================================================
''' �R���{���X�g�̕`��
'------------------------------------------------------------------------------------
Private Sub comboFontList_MeasureItem(ByVal sender As Object, ByVal e As System.Windows.Forms.MeasureItemEventArgs) Handles comboFontList.MeasureItem
	Dim ff As FontFamily
	Dim f As Font
	Dim fs As FontStyle

	ff = New FontFamily(Me.comboFontList.Items.Item(e.Index).ToString)

	If ff.IsStyleAvailable(FontStyle.Regular) Then
		fs = FontStyle.Regular
	ElseIf ff.IsStyleAvailable(FontStyle.Italic) Then
		fs = FontStyle.Italic
	Else
		fs = FontStyle.Bold
	End If

	f = New Font(ff, 12, fs)

	e.ItemHeight = f.Height + 2

	f.Dispose()
	ff.Dispose()

End Sub
'====================================================================================