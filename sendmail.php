function send_mail($from, $to, $subject, $body, $from_name = ""){

	$from_name = mb_convert_encoding($from_name,"JIS","EUC-JP");
	$subject = mb_convert_encoding($subject,"JIS","EUC-JP");
	$body = mb_convert_encoding($body,"JIS","EUC-JP");
	if($subject != ""){
		$subject = mime_enc($subject);
	}
	$body = str_replace("\r\n", "\n", $body);
	$body = str_replace("\r" , "\n", $body);
	if($from_name){
		$head .= "From: ".mime_enc($from_name)." <".$from.">\n";
	}else{
		$head .= 'From: "'.$from.'" <'.$from.'>'."\n";
	}
	$head.= "X-Originating-IP: [{$_SERVER['SERVER_ADDR']}]\n";
	$head.= "X-Originating-Email: [{$from}]\n";
	$head.= "X-Sender: {$from}\n";
	$head.= "Mime-Version: 1.0\n";
	$head.= "Content-Type: text/plain;charset=ISO-2022-JP\n";
	$head.= "X-Mailer: PHP/".phpversion();
	if(!mail($to, $subject, $body, $head)){
		return FALSE;
	}else{
		return TRUE;
	}

}

function mime_enc($str){

	$encode = "=?iso-2022-jp?B?".base64_encode($str)."?=";
	return $encode;

}