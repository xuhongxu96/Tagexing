<?php
require_once '../config.php';
require_once '../db.php';
require_once '../wechat.class.php';

$options = array (
		'token' => WX_TOKEN, // 填写应用接口的Token
		'appid' => WX_APPID, // 填写高级调用功能的appid
		'appsecret' => WX_SECRET,
		'debug' =>false,
		'logcallback' => 'logdebug'
);

$weObj = new Wechat ( $options );

$redirectURI = urlencode ( "http://" . WX_URL . "/main.php" );
$authURI = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";

$weObj->checkAuth ();
echo ($weObj->createMenu ( array (
		'button' => array (
				0 => array (
						'name' => WX_TITLE,
						'type' => 'view',
						'url' => $authURI
				)
		)
) ));