<?php
session_start ();
require_once './config.php';
require_once './db.php';
require_once './wechat.class.php';
require_once './smarty/Smarty.class.php';
require_once './function.php';

$openid = '';
$smarty = new Smarty ();

if (WX_DEBUG == '1') {
	$openid = "xuhongxu96justforfun";
} else {
	if (! isset ( $_GET ['code'] ) && ! isset ( $_SESSION ['openid'] )) { // 未进行微信OAuth2.0认证
		$smarty->assign ( 'error', '请通过微信公众号进入踏鸽行操作页面！' );
		$smarty->display ( 'error.html' );
		exit ();
	}
	if (isset ( $_GET ['code'] )) { // 新的授权
		$result = http_get ( 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . WX_APPID . '&secret=' . WX_SECRET . '&code=' . $_GET ['code'] . '&grant_type=authorization_code' );
		$json = json_decode ( $result, true );
		if (! isset ( $json ['openid'] )) {
			$smarty->assign ( 'error', '认证错误，请重新从微信公众号进入页面！' );
			$smarty->display ( 'templates/error.html' );
			exit ();
		}
		$openid = $json ['openid'];
		$_SESSION ['openid'] = $openid;
	} else { // SESSION 当前授权用户
		$openid = $_SESSION ['openid'];
	}
}

$db = new DB ();
$db->connect ();
function showRegister($smarty, $userInfo, $db) {
	$smarty->assign ( 'name', $userInfo ['name'] );
	$smarty->assign ( 'pic', $userInfo ['pic'] );
	$smarty->assign ( 'info', $userInfo ['confirmInfo'] );
	$smarty->assign ( 'mobile', $userInfo ['mobile'] );
	if (! $userInfo ['inviterID'])
		$userInfo ['inviterID'] = - 1;
	$inviter = $db->getAllByID ( "tuser", $userInfo ['inviterID'] );
	if ($inviter) {
		$smarty->assign ( 'inviterName', $inviter ['name'] );
		$smarty->assign ( 'inviterMobile', $inviter ['mobile'] );
	} else {
		$smarty->assign ( 'inviterName', "" );
		$smarty->assign ( 'inviterMobile', "" );
	}
	$smarty->display ( 'templates/register.html' );
}
function showIndex($smarty, $userInfo, $db) {
	$smarty->assign('userInfo', $userInfo);
	$smarty->assign('rank', $db->getRank ($userInfo['score']));
	switch ($userInfo['state']) {
		case 0:
			$smarty->assign("state", "未认证");
			break;
		case 1:
			$smarty->assign("state", "待借车");
			break;
		case 2:
			$smarty->assign("state", "已借车");
			break;
		case 3:
			$smarty->assign("state", "被禁用");
			break;
	}
	$smarty->assign("stop", $db->getStopInfo());
	$smarty->display ( 'templates/index.html' );
}
$userInfo = $db->getInfo ( $openid );

if (! isset ( $_GET ["a"] )) {
	$_GET ["a"] = 'index';
}

switch ($_GET ["a"]) {
	case 'index' :
		switch ($userInfo ["state"]) {
			case 0 : // not registered
				showRegister ( $smarty, $userInfo, $db );
				$db->disconnect ();
				die ();
				break;
			case 1 : // normal
				showIndex ( $smarty, $userInfo, $db );
				break;
			case 2 : // rented
				break;
			case 3 : // disabled
				break;
		}
		break;
	case 'register' :
		$msg = "";
		if (isset ( $_POST ['name'] ) && isset ( $_POST ['mobile'] ) && isset ( $_POST ['info'] ) && isset ( $_POST ['inviterName'] ) && isset ( $_POST ["inviterMobile"] )) {
			$msg = $db->register ( $userInfo ['ID'], $_POST ['name'], $_POST ['mobile'], $_POST ['info'], $_POST ['inviterName'], $_POST ['inviterMobile'] );
		}
		$smarty->assign ( "error", $msg );
		$userInfo = $db->getInfo ( $openid );
		showRegister ( $smarty, $userInfo, $db );
		break;
}

$db->disconnect ();
?>
