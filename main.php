<?php
session_start ();
require_once './config.php';
require_once './function.php';
require_once './db.php';
require_once './wechat.class.php';
require_once './smarty/Smarty.class.php';


$openid = '';
$smarty = new Smarty ();
$db = new DB ();
$db->connect ();
$smarty->assign ( "random", rand ( 0, 10000000 ) );
function showError($smarty, $error, $db) {
	$smarty->assign ( 'error', $error );
	$smarty->display ( 'templates/error.html' );
	$db->disconnect ();
	die ();
}
function showTip($smarty, $tip, $db) {
	$smarty->assign ( 'tip', $tip );
	$smarty->display ( 'templates/tip.html' );
	$db->disconnect ();
	die ();
}
if (WX_DEBUG == '2') {
	//$openid = "xuhongxu96justforfun";
	$openid = "new";
	 $openid = "hahahahahaha";
} else {
	if (! isset ( $_GET ['code'] ) && ! isset ( $_SESSION ['openid'] )) { // 未进行微信OAuth2.0认证
		showError ( $smarty, '请通过微信公众号进入踏鸽行操作页面！', $db );
	}
	if (isset ( $_GET ['code'] ) && $_GET ['code'] != $_SESSION ['code']) { // 新的授权
		$result = http_get ( 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . WX_APPID . '&secret=' . WX_SECRET . '&code=' . $_GET ['code'] . '&grant_type=authorization_code' );
		$json = json_decode ( $result, true );
		if (! isset ( $json ['openid'] )) {
			showError ( $smarty, '认证错误，请重新从微信公众号进入页面！\n错误信息： ' . $result, $db );
		}
		$openid = $json ['openid'];
		$_SESSION ['openid'] = $openid;
		$_SESSION ['code'] = $_GET ['code'];
	} else { // SESSION 当前授权用户
		$openid = $_SESSION ['openid'];
	}
}

$smarty->assign ( "error", "" );
function showRegister($smarty, $userInfo, $db) {
	$smarty->assign ( 'name', $userInfo ['name'] );
	$smarty->assign ( 'pic', $userInfo ['pic'] );
	$smarty->assign ( 'info', $userInfo ['confirmInfo'] );
	$smarty->assign ( 'mobile', $userInfo ['mobile'] );
	$smarty->assign ( 'comment', $userInfo ['comment'] );
	if (! $userInfo ['inviterID'])
		$userInfo ['inviterID'] = -1;
	$inviter = $db->getAllByID ( "tuser", $userInfo ['inviterID'] );
	if ($inviter) {
		$smarty->assign ( 'inviterName', $inviter ['name'] );
		$smarty->assign ( 'inviterMobile', $inviter ['mobile'] );
	} else {
		$smarty->assign ( 'inviterName', "" );
		$smarty->assign ( 'inviterMobile', "" );
	}
	$smarty->display ( 'templates/register.html' );
	$db->disconnect ();
	die ();
}
function showIndex($smarty, $userInfo, $db) {
	if (!$userInfo ['timeAmount']) $userInfo ['timeAmount'] = 0;
	$userInfo ['timeAmount'] = new DateInterval ( "PT" . $userInfo ['timeAmount'] . "M" );
	$userInfo ['timeAmount'] = $userInfo ['timeAmount']->format ( '%d天 %h时 %i分' );
	$smarty->assign ( 'userInfo', $userInfo );
	$rank = $db->getRank ( $userInfo ['score'] );
	$ret = $db->getCache("LongTimeEnabled");
	if ($ret['value']){
		$rank['maxTime'] = $rank['maxTime2'];
	}
	$smarty->assign ( 'rank', $rank );
	switch ($userInfo ['state']) {
		case 0 :
			$smarty->assign ( "state", "未认证" );
			break;
		case 1 :
			$rent = $db->getAllByID ( "trent", $userInfo ['rentID'] );
			$smarty->assign ( "pwd", "" );
			if ($rent) {
				$now = new DateTime ( "NOW" );
				$returnTime = new DateTime ( $rent ['returnTime'] );
				if (interval_to_seconds(date_diff ( $now, $returnTime )) / 60 <= CONFIG_PWD_EXPIRED) {
					$smarty->assign ( "pwd", $rent ['lockPWD'] );
				}
			}
			$smarty->assign ( "state", "待借车" );
			break;
		case 2 :
			$rent = $db->getAllByID ( "trent", $userInfo ['rentID'] );
			$smarty->assign ( "rentInfo", $rent );
			$smarty->assign("bikeInfo", $db->getAllByID("tbike", $rent['bikeID']));
			$time = date_create ( $rent ['rentTime'] );
			$now = new DateTime ( "NOW" );
			$rentTime= new DateTime ( $rent ['rentTime'] );
			$returnTime = new DateTime ( $rent ['rentTime'] );
			$returnTime->add ( new DateInterval ( 'PT' . $rank ['maxTime'] . 'H' ) );
			$smarty->assign ( "returnTime", $returnTime );
			if ($now > $returnTime)
				$smarty->assign ( "over", true );
			else
				$smarty->assign ( "over", false );
			$past = date_diff($now, $rentTime);
			if (interval_to_seconds($past) / 60 < 4) {
				$smarty->assign("report", true);
			} else {
				$smarty->assign("report", false);
			}
			$smarty->assign ( "now", $now );
			$smarty->assign ( "restTime", date_diff ( $now, $returnTime )->format ( '%d天 %h时 %i分' ) );
			$smarty->assign ( "state", "已借车" );
			break;
		case 3 :
			$smarty->assign ( "state", "被禁用" );
			break;
	}
	$smarty->assign ( "stop", $db->getStopInfo () );
	$smarty->display ( 'templates/index.html' );
	$db->disconnect ();
	die ();
}
function showRent($smarty, $userInfo, $db) {
	$smarty->assign ( "stop", $db->getStopInfo () );
	$smarty->display ( 'templates/rent.html' );
	$db->disconnect ();
	die ();
}
function showRentBike($smarty, $userInfo, $db) {
	$smarty->assign ( "bikes", $db->getBikeInfo ( $_GET ['s'] ) );
	$smarty->display ( 'templates/rentBike.html' );
	$db->disconnect ();
	die ();
}
function showConfirmRent($smarty, $userInfo, $db) {
	$smarty->assign ( "bike", $db->getAllByID ( "tbike", $_GET ['s'] ) );
	$smarty->display ( 'templates/confirmRent.html' );
	$db->disconnect ();
	die ();
}
function showRented($smarty, $userInfo, $db) {
	$pwd = $db->rentIt ( $userInfo ['ID'], $_GET ['s'] );
	if ($pwd == "no") {
		showError ( $smarty, "真不巧，该车刚被借走或在修~", $db );
	} else if ($pwd == "quick") {
		showError ( $smarty, "你借车太频繁了~歇一会再借", $db );
	}
	$smarty->assign ( "pwd", $pwd );
	$smarty->assign ( "s", $_GET ['s'] );
	$smarty->display ( 'templates/rented.html' );
	$db->disconnect ();
	die ();
}
function showReport($smarty, $userInfo, $db) {
	$smarty->display ( 'templates/report.html' );
	$db->disconnect ();
	die ();
}
function showReturn($smarty, $userInfo, $db) {
	$smarty->assign ( "stop", $db->getStopInfo () );
	$smarty->display ( 'templates/return.html' );
	$db->disconnect ();
	die ();
}
function showConfirmReturn($smarty, $userInfo, $db) {
	$smarty->assign ( "stop", $db->getAllByID ( "tstop", $_GET ['s'] ) );
	$smarty->display ( 'templates/confirmReturn.html' );
	$db->disconnect ();
	die ();
}
function showReturned($smarty, $userInfo, $db) {
	$pwd = $db->returnIt ( $userInfo ['ID'], $_GET ['s'] );
	if ($pwd == "no") {
		showError ( $smarty, "真不巧，该车站刚刚没有空位了~", $db );
	}
	$smarty->assign ( "pwd", $pwd );
	$smarty->display ( 'templates/returned.html' );
	$db->disconnect ();
	die ();
}
define("OTP_SUCCESS",0x00000000); //操作成功
define("OTP_ERR_INVALID_PARAMETER",0x00000001);//参数无效
define("OTP_ERR_CHECK_PWD",0x00000002);//认证失败
define("OTP_ERR_SYN_PWD",0x00000003);//同步失败
define("OTP_ERR_REPLAY",0x00000004);//动态口令被重放

function test_auth($authkey, $db, $code)
{
	if (function_exists('et_checkpwdz201'))
	{
		$t = time();
		$t0 = 0;
		$x = 60;
		$drift = intval($db->getCache($authkey));
		$authwnd = 10;  //认证窗口
		$lastsucc = 0;
		$otp = $code;
		$otplen = 6;    //otp长度，6位或8位
		$currsucc = 0;
		$currdft = 0;
		$ret = OTP_ERR_CHECK_PWD;
		$ret = et_checkpwdz201($authkey, $t, $t0, $x,
				$drift, $authwnd, $lastsucc,
				$otp, $otplen,
				$currsucc, $currdft);
				
		if (WX_DEBUG) return true;
		if ($ret == OTP_SUCCESS) {
			$db->setCache($authkey, $currdft, -1);
			return true;
		}
	}
	return false;
}



$userInfo = $db->getInfo ( $openid );

if (! isset ( $_GET ["a"] )) {
	$_GET ["a"] = 'index';
}

switch ($userInfo ["state"]) {
	case 0 : // not registered
		switch ($_GET ['a']) {
			case 'index' :
			default :
				showRegister ( $smarty, $userInfo, $db );
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
		break;
	case 1 : // normal
		switch ($_GET ['a']) {
			case 'index' :
				showIndex ( $smarty, $userInfo, $db );
				break;
			case 'rent' :
				showRent ( $smarty, $userInfo, $db );
				break;
			case 'rentBike' :
				if (isset ( $_GET ['s'] ))
					showRentBike ( $smarty, $userInfo, $db );
				else
					showError ( $smarty, '请正确借用车辆！', $db );
				break;
			case 'confirmRent' :
				if (isset ( $_GET ['s'] ))
					showConfirmRent ( $smarty, $userInfo, $db );
				else
					showError ( $smarty, '请正确借用车辆！', $db );
				break;
			case 'rentIt' :
				if (isset ( $_GET ['s'] ) && isset ( $_POST ['code'] )) {
					$bike = $db->getAllByID ( "tbike", $_GET ['s'] );
					$stop = $db->getAllByID ( "tstop", $bike ['stopID'] );
					if (test_auth( $stop ['code'], $db, $_POST['code'])) {
						showRented ( $smarty, $userInfo, $db );
					} else {
						showError ( $smarty, '车站口令错误，请重试！', $db );
					}
				} else {
					showError ( $smarty, '请正确借用车辆！', $db );
				}
				break;
			default :
				$userInfo = $db->getInfo ( $openid );
				showIndex ( $smarty, $userInfo, $db );
				break;
		}
		break;
	case 2 : // rented
		switch ($_GET ['a']) {
			case 'index' :
			default :
				showIndex ( $smarty, $userInfo, $db );
				break;
			case 'report' :
				showReport ( $smarty, $userInfo, $db );
				break;
			case 'submitReport' :
				if (isset ( $_POST ['brokenType'] ) && isset ( $_POST ['desciption'] )) {
					$db->report ( $userInfo ['ID'], $_POST ['brokenType'], $_POST ['desciption'] );
					showTip ( $smarty, "报告成功，感谢您的配合！", $db );
				} else {
					showError ( $smarty, '请正确报告问题！', $db );
				}
				break;
			case 'return' :
				showReturn ( $smarty, $userInfo, $db );
				break;
			case 'returnTo' :
				if (isset ( $_GET ['s'] ))
					showConfirmReturn ( $smarty, $userInfo, $db );
				else
					showError ( $smarty, '请正确归还车辆！', $db );
				break;
			case 'returnIt' :
				if (isset ( $_GET ['s'] ) && isset ( $_POST ['code'] )) {
					$stop = $db->getAllByID ( "tstop", $_GET ['s'] );
					if (test_auth( $stop ['code'], $db, $_POST['code'])) {
						showReturned ( $smarty, $userInfo, $db );
					} else {
						showError ( $smarty, '车站口令错误，请重试！', $db );
					}
				} else {
					showError ( $smarty, '请正确归还车辆！', $db );
				}
				break;
			case 'accident' :
				showTip ( $smarty, "<p>请联系志愿者：</p><p>17888829772-许宏旭</p><p>联系后，请仍然将车子归还到任意车站！</p><p>确定上报后，车辆会被标记为需要维修！</p><a class='ui-btn' href='main.php?a=confirmAccident&q=" . rand ( 1, 10000000 ) . "'>确定上报</a>", $db );
				break;
			case 'confirmAccident' :
				$db->setAccident ( $userInfo ['ID'] );
				showTip ( $smarty, "<p>已经上报，请到车站归还车辆！</p><p>谢谢您的配合！</p><p>我们的志愿者会尽快联系到您！</p>", $db );
				break;
		}
		break;
	case 3 : // disabled
		showIndex ( $smarty, $userInfo, $db );
		break;
}

$db->disconnect ();
?>
