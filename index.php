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
		'debug' =>false,
		'logcallback' => 'logdebug'
);

$weObj = new Wechat ( $options );
$weObj->valid ();
/*$weObj->checkAuth ();
$weObj->createMenu ( array (
		'button' => array (
				0 => array (
						'name' => WX_TITLE,
						'type' => 'view',
						'url' => $authURI
				)
		)
) );
*/
$redirectURI = urlencode ( "http://" . WX_URL . "/main.php" );

$authURI = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";

$type = $weObj->getRev ()->getRevType ();
$news = "已完成：\n1、认证页面和主界面\n2、借车功能以及借车时的问题报告\n3、*NEW*添加还车功能\n4、*NEW*添加禁用状态\n\n另外，车站码分别为1、2和3。\n\n";
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
		$weObj->text ( "你好，欢迎来到" . WX_TITLE . " 校园公共自行车服务！\n\n$news\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
		break;
	case Wechat::MSGTYPE_EVENT :
		$e = $weObj->getRevEvent();
		switch ($e['event']) {
			case Wechat::EVENT_SUBSCRIBE:
				$weObj->text ( "你好，欢迎来到" . WX_TITLE . " 校园公共自行车服务！\n\n$news\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>\n\n冲我说点什么获取最新动态~" )->reply ();
				break;
		}
		break;
}
