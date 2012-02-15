#!/usr/local/bin/php
<?php
////////////////////////////////////////////////////////////////////////////////
// RBL.JP Auto Register Script Version.0.3
///////////////////////////////////////////////////////////////////////////////

// このスクリプトはアラートメールとして以下のようなメールを想定しています。
//
// ・ヘッダ部分
//
//   Subject: Virus Check Alert ([ウイルス名称])
//   Message-ID: <[Amavis メッセージID]@[メールサーバ名]>
//
// ・本文
//
//   The mail originated from: <?@[送信元ホスト名]>
//
//   ～省略～
//
//   ------------------------- BEGIN HEADERS -----------------------------
//   [元メールヘッダー]
//   -------------------------- END HEADERS ------------------------------
//
// なお、正規表現で判断をしているためこれ以外のメールパターンにも容易に対応する事ができます。

//*********************** 変数宣言 ***********************//
// Basic認証 - ユーザID
$c_bid		= "";
// Basic認証 - パスワード
$c_bpass	= "";

// Virus RBL - ユーザID
$c_id		= "";
// Virus RBL - メールアドレス
$c_email	= "";
// Virus RBL - IPアドレス
$c_ip		= "";
// POST先のURL
$c_post 	= "http://www.rbl.jp/vreport/vrecord.php";		       // 本番用

// 通知先メールアドレス(空白時は通知なし)
$c_mail 	= "";

// ホワイトリスト(正規表現で記述)
$white = array(
	// BIGLOBEのバウンスメール判定
	"^rcpt-expgw\.biglobe\.ne\.jp$",
	"^rcpt-expgw2\.biglobe\.ne\.jp$",
	// AOLはよく分からないのでとりあえず非登録
	"\.ipt\.aol\.com$",
	// ODN(Content-Typeが一般的ではないため)
	"^mfep9\.odn\.ne\.jp$",
// Exim
	// USEN
	"^mx1\.usen\.ad\.jp$",
	// DNS Live
	"^server[0-9]+\.dnslive\.net$",
// CATV系のNAT
	// LeopalaceBB
	"^gk[0-9]\.leo-net\.jp$",
	// Second Vision
	"^nat\.secondvision\.ne\.jp$",
	// あさがおネット
	"^msq\.asagaotv\.ne\.jp$",
	// Aitai net
	"^andromeda\.aitai\.ne\.jp$"
);
//*********************** 変数宣言 ***********************//

////////////////////////////////////////////////////////////////////////////////
// 変更履歴
//  Ver.0.1
//    ・とりあえず動作するように作成。
//    ・バウンスメールの判断をすることにより誤判定を防ぐ
//  Ver.0.2
//    ・Moperaのバウンスメール判定のために正規表現の大文字小文字の区別をなくした。
//  Ver.0.3
//    ・Eximのバウンスメールに対応
////////////////////////////////////////////////////////////////////////////////

// HTTP_Request(Pear Library)
require_once "HTTP/Request.php";

$reg  = 1;
$row  = 0;
$flag = 0;

// 標準入力からメールを取得
$file = file("php://stdin");

// メールの解析処理
foreach($file as $line){
	// メッセージIDから固有IDの割り出し
	if(ereg("^Message-ID: <(.+)\@ns\.s-lines\.net>", $line, $res) && !$flag){
		$mess_id = $res[1];
	}
	// ウイルス名称の割り出し
	if(ereg("^Subject: Virus Check Alert \((.+)\)", $line, $res) && !$flag){
		$memo = $res[1];
	}
	// 送信元ホストの割り出し
	if(ereg("^The mail originated from: <\?\@(.+)>", $line, $res) && !$flag){
		$host = $res[1];
	}
	// ヘッダー判別(END)
	if(ereg("^-------------------------- END HEADERS ------------------------------", $line)){
		$flag = 0;
	}
	// ヘッダー抽出
	if($flag){
		$header[$row] = $line;
		$row++;
		$rawmail.= $line;
	}
	// ヘッダー判別(START)
	if(ereg("^------------------------- BEGIN HEADERS -----------------------------", $line)){
		$flag = 1;
	}
}

// バウンスメールなどの判定
foreach($header as $line){
	if(eregi("^Content-Type: multipart/report; report-type=delivery-status", $line)){	// Delivery-status
		$reg = 0;
		$response = "Content-Type : Delivery-status";
	}elseif(eregi("^Received: \(qmail [0-9]+ invoked for bounce\)", $line)){// qmailからのバウンスメール
		$reg = 0;
		$response = "MTA : qmail";
	}elseif(eregi("^Received: by .+\.ocn\.ne\.jp \(Postfix\) via BOUNCE", $line)){			// OCNからのバウンスメール
		$reg = 0;
		$response = "Bounce from OCN";
	}elseif(eregi("^Received: from .+ by .+ with local \(Exim .+\)", $line)){					// Exim
		$reg = 0;
		$response = "MTA : Exim";
	}
}

// ホワイトリストとの照らし合わせ
foreach($white as $line){
	if(ereg($line, $host)){
		$reg = 0;
	}
}

// ホスト名をIPアドレスに変換
$ip = gethostbyname($host);

// 登録判断
if($reg){
	// HTTP_Request の設定
	$req =& new HTTP_Request($c_post);
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->setBasicAuth($c_bid, $c_bpass);

	// POSTするデータの指定
	$req->addPostData("ID", $c_id);
	$req->addPostData("email", $c_email);
	$req->addPostData("posted", $c_ip);
	$req->addPostData("vhost", $ip);
	$req->addPostData("memo", $memo);
	$req->addPostData("rawmail", $rawmail);

	// リクエスト送信(エラー処理)
	if(!PEAR::isError($req->sendRequest())){
		if(ereg("completed", $req->getResponseBody())){
			$response = "Successed";
		}
	}else{
		$response = "Error";
	}
}else{
	$response.= "\n              No Regist";
}

// ログファイル
$log = sprintf("ID=%s,VIRUS=%s,HOST=%s[%s],RESULT=%s\n", $mess_id, $memo, $host, $ip , $response);

// syslog 初期化
define_syslog_variables();
// syslog
openlog("rbl", LOG_PID, LOG_MAIL);
syslog(LOG_INFO, $log);
closelog();

// メール通知判断
if($c_mail != ""){

$mail = <<<MAIL
----------------- RBL.JP AutoRegister Script Result -----------------

     Result : {$response}

 Virus name : {$memo}
       Host : {$host}[{$ip}]

---------------------------------------------------------------------
MAIL;

	mail($c_mail, "RBL.JP Auto Register Script Result", $mail, "From: RBL.JP Auto Register Script <{$c_mail}>");
}

exit(0);

?>
