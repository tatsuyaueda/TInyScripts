	Try
	    dbCmd.ExecuteNonQuery()
	Catch err As SqlClient.SqlException
	    For i = 0 To err.Errors.Count - 1
		Global.DBErr("データベースシステムエラー" & vbNewLine & _
			       "Index #" & i.ToString() & vbNewLine & _
			       "Message: " & err.Errors(i).Message & vbNewLine & _
			       "LineNumber: " & err.Errors(i).LineNumber & vbNewLine & _
			       "Source: " & err.Errors(i).Source & vbNewLine & _
			       "Procedure: " & err.Errors(i).Procedure & vbNewLine)
	    Next i
	    Return
	End Try