<?php
require_once 'config.php';
require_once 'db.php';
require_once 'wechat.class.php';
function logdebug($txt){
	file_put_contents("./1.txt", $txt."\n", FILE_APPEND);
}
$options = array (
		'token' => WX_TOKEN, // 填写应用接口的Token
		'appid' => WX_APPID, // 填写高级调用功能的appid
		'appsecret' => WX_SECRET,
		'debug' =>true,
		'logcallback' => 'logdebug'
);

$weObj = new Wechat ( $options );
$weObj->valid ();
$weObj->checkAuth ();

$redirectURI = urlencode ( "http://" . WX_URL . "/main.php" );

$authURI = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";

$weObj->createMenu ( array (
		'button' => array (
				0 => array (
						'name' => WX_TITLE . ' 校园公共自行车服务',
						'type' => 'view',
						'url' => $authURI 
				) 
		) 
) );
$type = $weObj->getRev ()->getRevType ();
switch ($type) {
	case Wechat::MSGTYPE_IMAGE :
		$pic = $weObj->getRevPic ();
		$db = new DB ();
		$db->connect ();
		$db->postImg ( $weObj->getRevFrom (), $pic ['picurl'] );
		$db->disconnect ();
		$weObj->text ( "成功录入证件照片！" )->reply ();
		break;
	case Wechat::MSGTYPE_TEXT :
		$weObj->text ( "你好，欢迎来到" . WX_TITLE . " 校园公共自行车服务！\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
		break;
}