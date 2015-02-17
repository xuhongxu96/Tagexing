<?php
require_once '../config.php';
require_once '../function.php';
require_once '../db.php';
require_once '../smarty/Smarty.class.php';
session_start ();
$smarty = new Smarty ();
$db = new DB ();
$db->connect ();

if (! (isset ( $_POST ['username'] ) && isset ( $_POST ['password'] ))) {
	$_POST ['username'] = '';
	$_POST ['password'] = '';
	if (isset ( $_SESSION ['username'] ) && isset ( $_SESSION ['password'] )) {
		$_POST ['username'] = $_SESSION ['username'];
		$_POST ['password'] = $_SESSION ['password'];
	}
}
if (! isset ( $_GET ['a'] ))
	$_GET ['a'] = 'index';
$admin = 0;
if ($admin = $db->adminLogin ( $_POST ['username'], $_POST ['password'] )) {
	$_SESSION ['username'] = $_POST ['username'];
	$_SESSION ['password'] = $_POST ['password'];
	$smarty->assign ( "name", $admin ['name'] );
	$smarty->assign ( "bikeMgr", $admin ['limit'] & 1 );
	$smarty->assign ( "rankMgr", $admin ['limit'] & 2 );
	$smarty->assign ( "rentMgr", $admin ['limit'] & 4 );
	$smarty->assign ( "stopMgr", $admin ['limit'] & 8 );
	$smarty->assign ( "userMgr", $admin ['limit'] & 16 );
	$smarty->assign ( "adminMgr", $admin ['limit'] & 32 );
	$smarty->assign ( "systemMgr", $admin ['limit'] & 64 );
	$smarty->assign ( "mgrtype", $_GET ['a'] );
	switch ($_GET ['a']) {
		case 'index' :
		default :
			$smarty->display ( "index.html" );
			break;
		case 'adminMgr' :
			if (! ($admin ['limit'] & 32)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['i'] ))
				$_GET ['i'] = 0;
			$all = $db->query ( "SELECT COUNT(*) FROM tadmin" );
			$smarty->assign ( "index", $_GET ['i'] );
			$smarty->assign ( "page", ceil ( $all ['COUNT(*)'] / 20 ) );
			$smarty->assign ( "admins", $db->queryAll ( "SELECT * FROM tadmin LIMIT " . $_GET ['i'] * 20 . ", 20" ) );
			$smarty->display ( "adminMgr.html" );
			break;
		case 'editAdmin' :
			if (! ($admin ['limit'] & 32)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "mgrtype", "adminMgr" );
			if (isset ( $_GET ['id'] )) {
				$smarty->assign ( "adminInfo", $db->getAllByID ( "tadmin", $_GET ['id'] ) );
			}
			$smarty->display ( "editAdmin.html" );
			break;
		case 'addAdmin' :
			if (! ($admin ['limit'] & 32)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "mgrtype", "adminMgr" );
			$smarty->assign ( "adminInfo", array (
					"ID" => "-1",
					"name" => "",
					"pwd" => "",
					"limit" => 0 
			) );
			$smarty->display ( "editAdmin.html" );
			break;
		case 'submitEditAdmin' :
			if (! ($admin ['limit'] & 32)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_POST ['id'] )) {
				header ( "location: index.php?a=index" );
				break;
			}
			$limit = 0;
			foreach ( $_POST ['limit'] as $lmt ) {
				$limit |= $lmt;
			}
			$ret = 0;
			if ($_POST ['id'] == - 1) {
				$ret = $db->addAdmin ( $_POST ['name'], $_POST ['pwd'], $limit );
			} else {
				$ret = $db->editAdmin ( $_POST ['id'], $_POST ['name'], $_POST ['pwd'], $limit );
			}
			if ($ret == -1) {
				echo "Name has been used!";
			} else {
				header ( "location: index.php?a=adminMgr" );
			}
			break;
		case 'delAdmin' :
			if (! ($admin ['limit'] & 32)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['id'] )) {
				header ( "location: index.php?a=index" );
				break;
			}
			$db->update ( "DELETE FROM tadmin WHERE ID = " . $_GET ['id'] );
			header ( "location: index.php?a=adminMgr" );
			break;
		case 'bikeMgr' :
			if (! ($admin ['limit'] & 1)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['i'] ))
				$_GET ['i'] = 0;
			$all = $db->query ( "SELECT COUNT(*) FROM tbike" );
			$smarty->assign ( "index", $_GET ['i'] );
			$smarty->assign ( "page", ceil ( $all ['COUNT(*)'] / 20 ) );
			$smarty->assign ( "bikes", $db->queryAll ( "SELECT *,(SELECT tstop.name FROM tstop WHERE ID = tbike.stopID) AS stopName FROM tbike LIMIT " . $_GET ['i'] * 20 . ", 20" ) );
			$smarty->display ( "bikeMgr.html" );
			break;
		case 'editBike' :
			if (! ($admin ['limit'] & 1)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "mgrtype", "bikeMgr" );
			$smarty->assign ( "stops", $db->getStopInfo () );
			if (isset ( $_GET ['id'] )) {
				$smarty->assign ( "bikeInfo", $db->getAllByID ( "tbike", $_GET ['id'] ) );
			}
			$smarty->display ( "editBike.html" );
			break;
		case 'addBike' :
			if (! ($admin ['limit'] & 1)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "mgrtype", "bikeMgr" );
			$smarty->assign ( "stops", $db->getStopInfo () );
			$smarty->assign ( "bikeInfo", array (
					"ID" => "-1",
					"name" => "",
					"rentID" => "",
					"state" => "",
					"stopID" => "",
					"pwd" => "" 
			) );
			$smarty->display ( "editBike.html" );
			break;
		case 'submitEditBike' :
			if (! ($admin ['limit'] & 1)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_POST ['id'] )) {
				header ( "location: index.php?a=index" );
				break;
			}
			$ret = 0;
			if ($_POST ['id'] == - 1) {
				$ret = $db->addBike ( $_POST ['name'], $_POST ['state'], $_POST ['stop'], $_POST ['pwd'] );
			} else {
				$ret = $db->editBike ( $_POST ['id'], $_POST ['name'], $_POST ['state'], $_POST ['stop'], $_POST ['pwd'] );
			}
			$db->refreshStopInfo ();
			if ($ret == -1) {
				echo "Name has been used!";
			} else {
				header ( "location: index.php?a=bikeMgr" );
			}
			break;
		case 'delBike' :
			if (! ($admin ['limit'] & 1)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['id'] )) {
				header ( "location: index.php?a=index" );
				break;
			}
			$db->update ( "DELETE FROM tbike WHERE ID = " . $_GET ['id'] );
			$db->refreshStopInfo ();
			header ( "location: index.php?a=bikeMgr" );
			break;
		case 'rankMgr' :
			if (! ($admin ['limit'] & 2)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['i'] ))
				$_GET ['i'] = 0;
			$all = $db->query ( "SELECT COUNT(*) FROM trank" );
			$smarty->assign ( "index", $_GET ['i'] );
			$smarty->assign ( "page", ceil ( $all ['COUNT(*)'] / 20 ) );
			$smarty->assign ( "ranks", $db->queryAll ( "SELECT * FROM trank LIMIT " . $_GET ['i'] * 20 . ", 20" ) );
			$smarty->display ( "rankMgr.html" );
			break;
		case 'editRank' :
			if (! ($admin ['limit'] & 2)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "mgrtype", "rankMgr" );
			if (isset ( $_GET ['id'] )) {
				$smarty->assign ( "rankInfo", $db->getAllByID ( "trank", $_GET ['id'] ) );
			}
			$smarty->display ( "editRank.html" );
			break;
		case 'addRank' :
			if (! ($admin ['limit'] & 2)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "mgrtype", "rankMgr" );
			$smarty->assign ( "rankInfo", array (
					"ID" => "-1",
					"minScore" => "",
					"name" => "",
					"maxTime" => "",
					"maxTime2" => "" 
			) );
			$smarty->display ( "editRank.html" );
			break;
		case 'submitEditRank' :
			if (! ($admin ['limit'] & 2)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_POST ['id'] )) {
				header ( "location: index.php?a=index" );
				break;
			}
			if ($_POST ['id'] == - 1) {
				$db->addRank ( $_POST ['name'], $_POST ['minScore'], $_POST ['maxTime'], $_POST ['maxTime2'] );
			} else {
				$db->editRank ( $_POST ['id'], $_POST ['name'], $_POST ['minScore'], $_POST ['maxTime'], $_POST ['maxTime2'] );
			}
			header ( "location: index.php?a=rankMgr" );
			break;
		case 'delRank' :
			if (! ($admin ['limit'] & 2)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['id'] )) {
				header ( "location: index.php?a=index" );
				break;
			}
			$db->update ( "DELETE FROM trank WHERE ID = " . $_GET ['id'] );
			$db->refreshStopInfo ();
			header ( "location: index.php?a=rankMgr" );
			break;
		case 'rentMgr' :
			if (! ($admin ['limit'] & 4)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['i'] ))
				$_GET ['i'] = 0;
			if (isset ( $_GET ['index'] )) {
				$smarty->assign ( "page", - 1 );
				$rent = $db->queryAll ( "SELECT *,TIMEDIFF(returnTime, rentTime) AS timeDiff,(SELECT tbike.name FROM tbike WHERE ID = trent.bikeID) AS bikeName" . ", (SELECT CONCAT(tuser.name, tuser.mobile) FROM tuser WHERE ID = trent.userID) AS userName " . ", (SELECT tstop.name FROM tstop WHERE ID = trent.stop1) AS stopName1,(SELECT tstop.name FROM tstop WHERE ID = trent.stop2) AS stopName2 FROM trent WHERE ID = " . $_GET ['index'] . " ORDER BY ID DESC, returnTime, rentTime" );
				$smarty->assign ( "rent", $rent );
			} else {
				$all = $db->query ( "SELECT COUNT(*) FROM trent" );
				$smarty->assign ( "index", $_GET ['i'] );
				$smarty->assign ( "page", ceil ( $all ['COUNT(*)'] / 20 ) );
				$rent = $db->queryAll ( "SELECT *,TIMEDIFF(returnTime, rentTime) AS timeDiff,(SELECT tbike.name FROM tbike WHERE ID = trent.bikeID) AS bikeName" . ", (SELECT CONCAT(tuser.name, tuser.mobile) FROM tuser WHERE ID = trent.userID) AS userName " . ", (SELECT tstop.name FROM tstop WHERE ID = trent.stop1) AS stopName1,(SELECT tstop.name FROM tstop WHERE ID = trent.stop2) AS stopName2 FROM trent ORDER BY ID DESC, returnTime, rentTime LIMIT " . $_GET ['i'] * 20 . ", 20" );
				$smarty->assign ( "rent", $rent );
			}
			$smarty->display ( "rentMgr.html" );
			break;
		case 'editRent' :
			if (! ($admin ['limit'] & 4)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_POST ['id'] ) && ! isset ( $_POST ['cmt'] )) {
				echo "Please submit properly!";
				break;
			}
			$db->editRent ( $_POST ['id'], $_POST ['cmt'] );
			break;
		case 'stopMgr' :
			if (! ($admin ['limit'] & 8)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['i'] ))
				$_GET ['i'] = 0;
			$all = $db->query ( "SELECT COUNT(*) FROM tstop" );
			$smarty->assign ( "index", $_GET ['i'] );
			$smarty->assign ( "page", ceil ( $all ['COUNT(*)'] / 20 ) );
			$smarty->assign ( "stops", $db->queryAll ( "SELECT * FROM tstop LIMIT " . $_GET ['i'] * 20 . ", 20" ) );
			$smarty->display ( "stopMgr.html" );
			break;
		case 'editStop' :
			if (! ($admin ['limit'] & 8)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "mgrtype", "stopMgr" );
			if (isset ( $_GET ['id'] )) {
				$smarty->assign ( "stopInfo", $db->getAllByID ( "tstop", $_GET ['id'] ) );
			}
			$smarty->display ( "editStop.html" );
			break;
		case 'addStop' :
			if (! ($admin ['limit'] & 8)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "mgrtype", "stopMgr" );
			$smarty->assign ( "stopInfo", array (
					"ID" => "-1",
					"name" => "",
					"stopCount" => "",
					"code" => "" 
			) );
			$smarty->display ( "editStop.html" );
			break;
		case 'submitEditStop' :
			if (! ($admin ['limit'] & 8)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_POST ['id'] )) {
				header ( "location: index.php?a=index" );
				break;
			}
			$ret = 0;
			if ($_POST ['id'] == - 1) {
				$ret = $db->addStop ( $_POST ['name'], $_POST ['stopCount'], $_POST ['code'] );
			} else {
				$ret = $db->editStop ( $_POST ['id'], $_POST ['name'], $_POST ['stopCount'], $_POST ['code'] );
			}
			if ($ret == -1) {
				echo "Name has been used!";
			} else {
				header ( "location: index.php?a=stopMgr" );
			}
			break;
		case 'delStop' :
			if (! ($admin ['limit'] & 8)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['id'] )) {
				header ( "location: index.php?a=index" );
				break;
			}
			$db->update ( "DELETE FROM tstop WHERE ID = " . $_GET ['id'] );
			$db->refreshStopInfo ();
			header ( "location: index.php?a=stopMgr" );
			break;
		case 'userMgr' :
			if (! ($admin ['limit'] & 16)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_GET ['i'] ))
				$_GET ['i'] = 0;
			if (isset ( $_GET ['index'] )) {
				$smarty->assign ( "page", - 1 );
				$user = $db->queryAll ( "SELECT *, (SELECT CONCAT(t.name,t.mobile) FROM tuser AS t WHERE t.ID = t1.inviterID) AS inviter FROM tuser AS t1 WHERE ID = " . $_GET ['index'] );
				$smarty->assign ( "users", $user );
			} else {
				$all = $db->query ( "SELECT COUNT(*) FROM tuser" );
				$smarty->assign ( "index", $_GET ['i'] );
				$smarty->assign ( "page", ceil ( $all ['COUNT(*)'] / 20 ) );
				$user = $db->queryAll ( "SELECT *, (SELECT CONCAT(t.name,t.mobile) FROM tuser AS t WHERE t.ID = t1.inviterID) AS inviter FROM tuser AS t1 LIMIT " . $_GET ['i'] * 20 . ", 20" );
				$smarty->assign ( "users", $user );
			}
			
			$smarty->display ( "userMgr.html" );
			break;
		case 'editUser' :
			if (! ($admin ['limit'] & 16)) {
				echo "Permission denied!";
				break;
			}
			if (! isset ( $_POST ['id'] ) && ! isset ( $_POST ['score'] ) && ! isset ( $_POST ['state'] ) && ! isset ( $_POST ['mobile'] )) {
				echo "Please submit properly!";
				break;
			}
			if ($db->editUser ( $_POST ['id'], $_POST ['score'], $_POST ['state'], $_POST ['freeTime'], $_POST ['mobile'], $_POST ['cmt'] ) == "no") {
				echo "Mobile has been used!";
				break;
			}
			break;
		case 'systemMgr' :
			if (! ($admin ['limit'] & 64)) {
				echo "Permission denied!";
				break;
			}
			$smarty->assign ( "longtime", $db->getCache ( "LongTimeEnabled" )['value'] );
			$smarty->display ( "systemMgr.html" );
			break;
		case 'setLong' :
			if (! ($admin ['limit'] & 64)) {
				echo "Permission denied!";
				break;
			}
			if (!isset($_GET['o'])) {
				echo "Please set properly!";
				break;
			}
			$db->setCache("LongTimeEnabled", $_GET['o'], -1);
			header("location: index.php?a=systemMgr");
			break;
		case 'newTerm':
			if (! ($admin ['limit'] & 64)) {
				echo "Permission denied!";
				break;
			}
			$db->update("UPDATE tuser SET score = 40 WHERE score <= 0 AND state != 0");
			header("location: index.php?a=systemMgr");
			break;
	}
} else {
	header ( "location: admin.html" );
}
$db->disconnect ();
?>
