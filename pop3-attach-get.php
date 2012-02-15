#!/usr/local/bin/php
<?

$path			= "/path/to/";
$pop3user	= "";
$pop3pass = "";
$pop3server = "";

// 接続開始
$sock		= fsockopen($pop3server,110, $err, $errno, 10) or die("サーバーに接続できません");
$buf		= fgets($sock, 512);

if(substr($buf, 0, 3) != '+OK') die($buf);

$buf = _sendcmd("USER $pop3user");
$buf = _sendcmd("PASS $pop3pass");
$data = _sendcmd("STAT");
sscanf($data, '+OK %d %d', $num, $size);

// 件数が 0 件の場合は disconnection
if($num == 0){
	$buf = _sendcmd('QUIT');
	fclose($sock);
	exit;
}
// メール取得
for($i=1;$i<=$num;$i++){
	$line = _sendcmd("RETR $i");
	while (!ereg("^\.\r\n",$line)) {
		$line = fgets($sock,512);
		$dat[$i].= $line;
	}
	$data = _sendcmd("DELE $i");
}
$buf = _sendcmd("QUIT");
fclose($sock);

for($j=1;$j<=$num;$j++){
	list($head, $body) = mime_split($dat[$j]);

	// 日付取得
	eregi("Date:[ \t]*([^\r\n]+)", $head, $date);
	$file = date("Ymd.His",strtotime($date[1]));

	// サブジェクト取得
	eregi("Subject:[ \t]*([^\r\n]+)", $head, $subject);
	while (eregi("(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)",$subject[1],$regs)) {	// MIME Bデコード
		$subject = $regs[1].base64_decode($regs[2]).$regs[3];
	}

	if (eregi("Content-type:.*multipart/",$head)) {
		eregi('boundary="([^"]+)"', $head, $boureg);
		$body = str_replace($boureg[1], urlencode($boureg[1]), $body);
		$part = split("\r\n--".urlencode($boureg[1])."-?-?",$body);
	}

	foreach ($part as $multi){
		list($m_head, $m_body) = mime_split($multi);
		$m_body = ereg_replace("\r\n\.\r\n$", "", $m_body);
		if (!eregi("Content-type: *([^;\n]+)", $m_head, $type)) continue;
		list($main, $sub) = explode("/", $type[1]);
		// 本文をデコード
		if (strtolower($main) == "text") {
			$tmp = split("\r\n",mb_convert_encoding($m_body, "EUC-JP","JIS"));
			$fp = fopen("{$path}/{$file}.txt", "w");
			fputs($fp,$tmp[0]."\n".date("Y/m/d H:i:s",strtotime($date[1])));
			fclose($fp);
		}
		// 添付データをデコードして保存
		if (eregi("Content-Transfer-Encoding:.*base64", $m_head)) {
			$tmp = base64_decode($m_body);
			$fp = fopen("{$path}/{$file}.jpg", "wb");
			fputs($fp, $tmp);
			fclose($fp);
		}
	}
	$handle = fopen ("{$path}/list.txt", "a");
		fwrite($handle,"{$file}\n");
	fclose($handle);
	copy("{$path}/{$file}.txt","{$path}/new.txt");
	copy("{$path}/{$file}.jpg","{$path}/new.jpg");
}

//////////////////////////////
// コマンド送信
function _sendcmd($cmd) {
	global $sock;
	fputs($sock, $cmd."\r\n");
	$buf = fgets($sock, 512);
	if(substr($buf, 0, 3) == '+OK') {
		return $buf;
	} else {
		die($buf);
	}
	return false;
}
// MIME分割
function mime_split($data) {
	$part = split("\r\n\r\n", $data, 2);
	$part[1] = ereg_replace("\r\n[\t ]+", " ", $part[1]);

	return $part;
}
?>