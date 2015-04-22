<?php
require_once '../config.php';
require_once '../db.php';
require_once '../wechat.class.php';
include '../emoji.php';

function utf8_bytes($cp){

	if ($cp > 0x10000){
		# 4 bytes
		return	chr(0xF0 | (($cp & 0x1C0000) >> 18)).
			chr(0x80 | (($cp & 0x3F000) >> 12)).
			chr(0x80 | (($cp & 0xFC0) >> 6)).
			chr(0x80 | ($cp & 0x3F));
	}else if ($cp > 0x800){
		# 3 bytes
		return	chr(0xE0 | (($cp & 0xF000) >> 12)).
			chr(0x80 | (($cp & 0xFC0) >> 6)).
			chr(0x80 | ($cp & 0x3F));
	}else if ($cp > 0x80){
		# 2 bytes
		return	chr(0xC0 | (($cp & 0x7C0) >> 6)).
			chr(0x80 | ($cp & 0x3F));
	}else{
		# 1 byte
		return chr($cp);
	}
}
$options = array (
	'token' => WX_TOKEN, // 填写应用接口的Token
	'appid' => WX_APPID, // 填写高级调用功能的appid
	'encodingaeskey' => '6pnP7qHyqJ1kFXMjuO4Z3QrpOa9WfapsgkPOtXoZKC2',
	'appsecret' => WX_SECRET,
);

$weObj = new Wechat ( $options );

$redirectURI = urlencode ( "http://" . WX_URL . "/main.php" );
$authURI = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";

$weObj->checkAuth ();
echo ($weObj->createMenu ( array (
	'button' => array (
		0 => array (
			'name' => '慈善商店',
			'type' => 'view',
			'url' => 'http://www.imall365.org/'
		),
		1 => array (
			'name' => WX_TITLE . emoji_docomo_to_unified(utf8_bytes(0x1F6B2)),
			'type' => 'view',
			'url' => $authURI
		)
	)
) ));
