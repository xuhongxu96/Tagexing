<?php
require_once 'config.php';
require_once 'db.php';
require_once 'wechat.class.php';
require_once 'function.php';
include 'emoji.php';

$options = array (
	'token' => WX_TOKEN, // 填写应用接口的Token
	'appid' => WX_APPID, // 填写高级调用功能的appid
	'encodingaeskey' => WX_KEY,
	'appsecret' => WX_SECRET,
	//'logcallback' => logdebug,
	//'debug' => true
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
	$userInfo = $db->getInfo ( $weObj->getRevFrom () );
	if ($userInfo['state'] != 0) {
		$weObj->text ( "你已经通过认证！" )->reply ();
	} else {
		$db->postImg ( $weObj->getRevFrom (), $pic ['mediaid'] );
		if ($userInfo['pic']) {
			$weObj->text ( "成功修改证件照片！" )->reply ();
		} else {
			$weObj->text ( "成功录入证件照片！\n再次提交即可修改" )->reply ();
		}
	}
	$db->disconnect ();
	break;
case Wechat::MSGTYPE_TEXT :
	$weObj->text ( "你好，欢迎来到imall 公益电商平台 和" . WX_TITLE . " 校园公共自行车服务！\n\n<a href='http://www.imall365.org'>点此开始imall公益电商之旅！</a>\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
	break;
case Wechat::MSGTYPE_EVENT :
	$e = $weObj->getRevEvent ();
	switch ($e ['event']) {
	case Wechat::EVENT_SUBSCRIBE :
		$scan = $weObj->getRevSceneId ();
		if (ereg("^[0-9]+$", $scan)) {
			$weObj->text ( "欢迎新用户来到imall 公益电商平台 和" . WX_TITLE . " 校园公共自行车服务！\n\n在借车前请先提交认证信息，等我们确认您的身份后即可享受校园公共自行车服务了~\n\n<a href='http://www.imall365.org'>点此开始imall公益电商之旅！</a>\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
		} else if (substr($scan, 0, 5) == 'prizea') {
			$redirectURI3 = urlencode ( "http://" . WX_URL . "/main.php?a=prize" );
			$authURI3 = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI3 . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
			$weObj->text ("欢迎新用户来到imall 公益电商平台 和" . WX_TITLE . " 校园公共自行车服务！\n\n在借车前请先提交认证信息，等我们确认您的身份后即可享受校园公共自行车服务了~\n\n<a href='http://www.imall365.org'>点此开始imall公益电商之旅！</a>\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>\n\n"."<a href='$authURI3'>点此抽奖~</a>")->reply();
		} else {
			$weObj->text ("欢迎新用户来到imall 公益电商平台 和" . WX_TITLE . " 校园公共自行车服务！\n\n在借车前请先提交认证信息，等我们确认您的身份后即可享受校园公共自行车服务了~\n\n<a href='http://www.imall365.org'>点此开始imall公益电商之旅！</a>\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>")->reply();
			//$weObj->text ( "你好，欢迎来到imall 公益电商平台 和" . WX_TITLE . " 校园公共自行车服务！\n\n<a href='http://www.imall365.org'>点此开始imall公益电商之旅！</a>\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
		}
		break;
	case Wechat::EVENT_SCAN :
		$scan = $weObj->getRevSceneId ();
		if (ereg("^[0-9]+$", $scan)) {
			$redirectURI2 = urlencode ( "http://" . WX_URL . "/main.php?a=confirmRent&back=back&s=$scan" );
			$authURI2 = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI2 . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
			$db = new DB ();
			$db->connect ();
			$userInfo = $db->getInfo ( $weObj->getRevFrom () );
			$db->disconnect ();
			switch ($userInfo ['state']) {
			case 0 :
				$weObj->text ( "您目前处于未认证状态，请先认证！\n\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
				break;
			case 1 :
				$weObj->text ( "<a href='$authURI2'>确认借车 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 　 </a>" )->reply ();
				break;
			case 2 :
				$weObj->text ( "您目前处于已借车状态，不能再次借车！\n\n<a href='" . $authURI . "'>点此开始你的" . WX_TITLE . "！</a>" )->reply ();
				break;
			case 3 :
				$weObj->text ( "您目前处于被禁用状态！\n\n<a href='" . $authURI . "'>点此查看原因</a>" )->reply ();
				break;
			}
		} else if (substr($scan, 0, 5) == "prizea") {
			// Prize
			$redirectURI3 = urlencode ( "http://" . WX_URL . "/main.php?a=prize" );
			$authURI3 = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . $redirectURI3 . "&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
			$weObj->text ("<a href='$authURI3'>点此抽奖~</a>")->reply();
		}
		break;
	}
	break;
}
$weObj->checkAuth ();
$weObj->createMenu ( array (
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
));
