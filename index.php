<?php
require_once 'config.php';
require_once 'db.php';
require_once 'wechat.class.php';
function logdebug($txt) {
	file_put_contents ( "./1.txt", $txt . "\n", FILE_APPEND );
}
$options = array (
		'token' => WX_TOKEN, // 填写应用接口的Token
		'appid' => WX_APPID, // 填写高级调用功能的appid
		'appsecret' => WX_SECRET,
		'debug' => false,
		'logcallback' => 'logdebug' 
);

$weObj = new Wechat ( $options );
$weObj->valid ();

$redirectURI = urlencode ( "http://" . WX_URL . "/main.php" );
$authURI = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";

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
		$weObj->text ( "你好，欢迎来到" . WX_TITLE . " 校园公共自行车服务！\n\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
		break;
	case Wechat::MSGTYPE_EVENT :
		$e = $weObj->getRevEvent ();
		switch ($e ['event']) {
			case Wechat::EVENT_SUBSCRIBE :
				$scan = $weObj->getRevSceneId ();
				if ($scan) {
					$weObj->text ( "欢迎新用户来到" . WX_TITLE . " 校园公共自行车服务！\n\n在借车前请先提交认证信息，等我们确认您的身份后即可享受校园公共自行车服务了~\n\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
				} else {
					$weObj->text ( "你好，欢迎来到" . WX_TITLE . " 校园公共自行车服务！\n\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
				}
				break;
			case Wechat::EVENT_SCAN :
				$scan = $weObj->getRevSceneId ();
				if ($scan) {
					$redirectURI = urlencode ( "http://" . WX_URL . "/main.php?a=confirmRent&s=$scan" );
					$authURI = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
					$db = new DB ();
					$db->connect ();
					$userInfo = $db->getInfo ( $weObj->getRevFrom () );
					$db->disconnect ();
					switch ($userInfo ['state']) {
						case 0 :
							$weObj->text ( "您目前处于未认证状态，请先认证！\n\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
							break;
						case 1 :
							$weObj->text ( "<a href='$authURI'>确认借车 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 </a>" )->reply ();
							break;
						case 2 :
							$weObj->text ( "您目前处于已借车状态，不能再次借车！\n\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
							break;
						case 3 :
							$weObj->text ( "您目前处于被禁用状态！\n\n<a href='" . $authURI . "'>点此查看原因</a>" )->reply ();
							break;
					}
				}
				break;
		}
		break;
}
