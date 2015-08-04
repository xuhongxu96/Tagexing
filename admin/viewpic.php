<?php
require_once '../config.php';
require_once '../function.php';
require_once '../db.php';
require_once '../wechat.class.php';
if ($_GET['p'][4] != ':') {
	$db = new DB ();
	$db->connect ();

	$weObj = new Wechat ();
	$weObj->checkAuth (WX_APPID, WX_SECRET);

	echo "<img src='" . $weObj->getMediaURL($_GET['p']) . "'>";
	$db->disconnect();
} else {
	echo "<a href='".$_GET['p']. "'>" . $_GET['p'] . "</a><br>请复制链接查看原图，或打开链接后在地址栏回车重新打开。(刷新无用)";
}
