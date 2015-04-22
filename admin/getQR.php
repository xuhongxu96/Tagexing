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

$weObj->checkAuth ();
$ret = $weObj->getQRCode(intval($_GET['id']), 1);
$url ="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ret['ticket']);
echo "<img src='$url' /><a href='$url'>View in new page</a>";
