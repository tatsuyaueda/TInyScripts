#!/usr/local/bin/php
<?php
////////////////////////////////////////////////////////////////////////////////
// RBL.JP Auto Register Script Version.0.3
///////////////////////////////////////////////////////////////////////////////

// ���̃X�N���v�g�̓A���[�g���[���Ƃ��Ĉȉ��̂悤�ȃ��[����z�肵�Ă��܂��B
//
// �E�w�b�_����
//
//   Subject: Virus Check Alert ([�E�C���X����])
//   Message-ID: <[Amavis ���b�Z�[�WID]@[���[���T�[�o��]>
//
// �E�{��
//
//   The mail originated from: <?@[���M���z�X�g��]>
//
//   �`�ȗ��`
//
//   ------------------------- BEGIN HEADERS -----------------------------
//   [�����[���w�b�_�[]
//   -------------------------- END HEADERS ------------------------------
//
// �Ȃ��A���K�\���Ŕ��f�����Ă��邽�߂���ȊO�̃��[���p�^�[���ɂ��e�ՂɑΉ����鎖���ł��܂��B

//*********************** �ϐ��錾 ***********************//
// Basic�F�� - ���[�UID
$c_bid		= "";
// Basic�F�� - �p�X���[�h
$c_bpass	= "";

// Virus RBL - ���[�UID
$c_id		= "";
// Virus RBL - ���[���A�h���X
$c_email	= "";
// Virus RBL - IP�A�h���X
$c_ip		= "";
// POST���URL
$c_post 	= "http://www.rbl.jp/vreport/vrecord.php";		       // �{�ԗp

// �ʒm�惁�[���A�h���X(�󔒎��͒ʒm�Ȃ�)
$c_mail 	= "";

// �z���C�g���X�g(���K�\���ŋL�q)
$white = array(
	// BIGLOBE�̃o�E���X���[������
	"^rcpt-expgw\.biglobe\.ne\.jp$",
	"^rcpt-expgw2\.biglobe\.ne\.jp$",
	// AOL�͂悭������Ȃ��̂łƂ肠������o�^
	"\.ipt\.aol\.com$",
	// ODN(Content-Type����ʓI�ł͂Ȃ�����)
	"^mfep9\.odn\.ne\.jp$",
// Exim
	// USEN
	"^mx1\.usen\.ad\.jp$",
	// DNS Live
	"^server[0-9]+\.dnslive\.net$",
// CATV�n��NAT
	// LeopalaceBB
	"^gk[0-9]\.leo-net\.jp$",
	// Second Vision
	"^nat\.secondvision\.ne\.jp$",
	// ���������l�b�g
	"^msq\.asagaotv\.ne\.jp$",
	// Aitai net
	"^andromeda\.aitai\.ne\.jp$"
);
//*********************** �ϐ��錾 ***********************//

////////////////////////////////////////////////////////////////////////////////
// �ύX����
//  Ver.0.1
//    �E�Ƃ肠�������삷��悤�ɍ쐬�B
//    �E�o�E���X���[���̔��f�����邱�Ƃɂ��딻���h��
//  Ver.0.2
//    �EMopera�̃o�E���X���[������̂��߂ɐ��K�\���̑啶���������̋�ʂ��Ȃ������B
//  Ver.0.3
//    �EExim�̃o�E���X���[���ɑΉ�
////////////////////////////////////////////////////////////////////////////////

// HTTP_Request(Pear Library)
require_once "HTTP/Request.php";

$reg  = 1;
$row  = 0;
$flag = 0;

// �W�����͂��烁�[�����擾
$file = file("php://stdin");

// ���[���̉�͏���
foreach($file as $line){
	// ���b�Z�[�WID����ŗLID�̊���o��
	if(ereg("^Message-ID: <(.+)\@ns\.s-lines\.net>", $line, $res) && !$flag){
		$mess_id = $res[1];
	}
	// �E�C���X���̂̊���o��
	if(ereg("^Subject: Virus Check Alert \((.+)\)", $line, $res) && !$flag){
		$memo = $res[1];
	}
	// ���M���z�X�g�̊���o��
	if(ereg("^The mail originated from: <\?\@(.+)>", $line, $res) && !$flag){
		$host = $res[1];
	}
	// �w�b�_�[����(END)
	if(ereg("^-------------------------- END HEADERS ------------------------------", $line)){
		$flag = 0;
	}
	// �w�b�_�[���o
	if($flag){
		$header[$row] = $line;
		$row++;
		$rawmail.= $line;
	}
	// �w�b�_�[����(START)
	if(ereg("^------------------------- BEGIN HEADERS -----------------------------", $line)){
		$flag = 1;
	}
}

// �o�E���X���[���Ȃǂ̔���
foreach($header as $line){
	if(eregi("^Content-Type: multipart/report; report-type=delivery-status", $line)){	// Delivery-status
		$reg = 0;
		$response = "Content-Type : Delivery-status";
	}elseif(eregi("^Received: \(qmail [0-9]+ invoked for bounce\)", $line)){// qmail����̃o�E���X���[��
		$reg = 0;
		$response = "MTA : qmail";
	}elseif(eregi("^Received: by .+\.ocn\.ne\.jp \(Postfix\) via BOUNCE", $line)){			// OCN����̃o�E���X���[��
		$reg = 0;
		$response = "Bounce from OCN";
	}elseif(eregi("^Received: from .+ by .+ with local \(Exim .+\)", $line)){					// Exim
		$reg = 0;
		$response = "MTA : Exim";
	}
}

// �z���C�g���X�g�Ƃ̏Ƃ炵���킹
foreach($white as $line){
	if(ereg($line, $host)){
		$reg = 0;
	}
}

// �z�X�g����IP�A�h���X�ɕϊ�
$ip = gethostbyname($host);

// �o�^���f
if($reg){
	// HTTP_Request �̐ݒ�
	$req =& new HTTP_Request($c_post);
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->setBasicAuth($c_bid, $c_bpass);

	// POST����f�[�^�̎w��
	$req->addPostData("ID", $c_id);
	$req->addPostData("email", $c_email);
	$req->addPostData("posted", $c_ip);
	$req->addPostData("vhost", $ip);
	$req->addPostData("memo", $memo);
	$req->addPostData("rawmail", $rawmail);

	// ���N�G�X�g���M(�G���[����)
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

// ���O�t�@�C��
$log = sprintf("ID=%s,VIRUS=%s,HOST=%s[%s],RESULT=%s\n", $mess_id, $memo, $host, $ip , $response);

// syslog ������
define_syslog_variables();
// syslog
openlog("rbl", LOG_PID, LOG_MAIL);
syslog(LOG_INFO, $log);
closelog();

// ���[���ʒm���f
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
