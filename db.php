<?php
class DB {
	private $mysqli;
	public function connect() {
		$this->mysqli = new mysqli ( WX_DBHOST, WX_DBUSR, WX_DBPSW, WX_DBNAME );
		if ($this->mysqli->connect_errno) {
			echo "Failed to connect to the database!";
		}
		$this->mysqli->set_charset ( 'utf8' );
		$this->mysqli->query ( "SET NAMES 'utf8'" );
	}
	public function disconnect() {
		$this->mysqli->close ();
	}
	public function query($sql) {
		if ($res = $this->mysqli->query ( $sql )) {
			if ($res->num_rows == 0)
				return 0;
			$row = $res->fetch_assoc ();
			$res->free ();
			return $row;
		} else {
			echo "Failed to query" . $sql;
			return 0;
		}
	}
	public function queryAll($sql) {
		if ($res = $this->mysqli->query ( $sql )) {
			$ret = array ();
			while ( $row = $res->fetch_assoc () ) {
				$ret [] = $row;
			}
			$res->free ();
			return $ret;
		} else {
			echo "Failed to query" . $sql;
			return 0;
		}
	}
	public function update($sql) {
		$this->mysqli->query ( $sql );
		if ($this->mysqli->affected_rows != - 1)
			return $this->mysqli->affected_rows;
		else {
			echo $sql;
			return $sql;
		}
	}
	public function getAllByID($table, $ID) {
		if (! $ID)
			$ID = - 1;
		return $this->query ( "SELECT * FROM $table WHERE ID = $ID" );
	}
	public function getInfo($openid) {
		$openid = $this->mysqli->real_escape_string ( $openid );
		$ret;
		if (! $ret = $this->query ( "SELECT * FROM tuser WHERE openid = '$openid'" )) {
			echo $this->update ( "INSERT INTO tuser (openID, score) VALUES ('$openid', 60)" );
			return $this->query ( "SELECT * FROM tuser WHERE openid = '$openid'" );
		}
		if ($ret ['state'] == 3) {
			$free = new DateTime ( $ret ['freeTime'] );
			$now = new DateTime ( "now" );
			if ($free < $now) {
				$this->update ( "UPDATE tuser SET state = 1, freeTime = 0, comment='' WHERE openid = '$openid'" );
			}
		}
		if ($ret ['state'] != 0 && $ret ['score'] <= 0) {
			$this->update("UPDATE tuser SET score = 0 WHERE openid = '$openid'");
			$ret ['score'] = 0;
			$ret ['state'] = 3;
			$ret ['comment'] = "信用值已为0";
		}
		return $ret;
	}
	public function setCache($name, $val, $expired) {
		$ret = $this->update ( "DELETE FROM tcache WHERE name = '$name'" );
		return $ret + $this->update ( "INSERT INTO tcache (name, value, expired, time) VALUES ('$name', '$val', $expired, NOW())" );
	}
	public function getCache($name) {
		$this->update ( "DELETE FROM tcache WHERE TIME_TO_SEC(TIMEDIFF(NOW(), time)) >= expired AND expired != -1" );
		return $this->query ( "SELECT * FROM tcache WHERE name = '$name'" );
	}
	public function removeCache($name) {
		return $this->update ( "DELETE FROM tcache WHERE name = '$name'" );
	}
	public function register($ID, $name, $mobile, $info, $inviterName, $inviterMobile) {
		$name = $this->mysqli->real_escape_string ( $name );
		$mobile = $this->mysqli->real_escape_string ( $mobile );
		$info = $this->mysqli->real_escape_string ( $info );
		$inviterName = $this->mysqli->real_escape_string ( $inviterName );
		$inviterMobile = $this->mysqli->real_escape_string ( $inviterMobile );
		$vip = array (
			"17888829772" => "1" 
		);
		$inviter = - 1;
		if (! array_key_exists ( $mobile, $vip )) {
			$inviter = $this->query ( "SELECT ID FROM tuser WHERE mobile='$inviterMobile' AND name='$inviterName' AND state != 0 AND score >= 40" );
			if ($inviter == 0) {
				return "邀请人信息错误！";
			}
			$inviter = $inviter ["ID"];
		}
		if ($this->query ( "SELECT ID FROM tuser WHERE mobile='$mobile' AND ID != $ID" )) {
			return "该手机号码已经存在！";
		}
		$ret = $this->query("SELECT name FROM tuser WHERE ID = $ID");
		if ($ret['name']) {
			return "您已注册！";
		}
		$this->update ( "UPDATE tuser SET score = score + 1 WHERE mobile='$inviterMobile'" );
		$this->update ( "INSERT INTO tscore (userID, score, reason, time) VALUES ($inviter, 1, '成功邀请！', NOW())" );
		$this->update ( "UPDATE tuser SET name='$name', mobile='$mobile', confirmInfo='$info', inviterID=$inviter, state=0,timeAmount=0 WHERE ID = $ID" );
	}
	public function postImg($openid, $img) {
		$img = $this->mysqli->real_escape_string ( $img );
		$this->update ( "UPDATE tuser SET pic='$img' WHERE openid = '$openid'" );
	}
	public function getRank($score) {
		return $this->query ( "SELECT * FROM trank WHERE minScore <= $score ORDER BY minScore DESC LIMIT 0, 1" );
	}
	public function getStopInfo() {
		return $this->queryAll ( "SELECT * FROM tstop" );
	}
	public function getStopByCode($code) {
		$code = $this->mysqli->real_escape_string ( $code );
		return $this->query ( "SELECT * FROM tstop WHERE code = '$code'" );
	}
	public function getBikeInfo($stop) {
		$stop = $this->mysqli->real_escape_string ( $stop );
		return $this->queryAll ( "SELECT * FROM tbike WHERE stopID = $stop" );
	}
	public function rentIt($userID, $bikeID) {
		$bikeID = $this->mysqli->real_escape_string ( $bikeID );
		$bike = $this->getAllByID ( "tbike", $bikeID );
		if ($bike ['state'] != 0) {
			return "no";
		}
		$user = $this->getAllByID ( "tuser", $userID );
		$rent = $this->getAllByID ( "trent", $user ['rentID'] );
		if ($rent && $rent['brokenType'] == 0) {
			$rentTime = new DateTime ( $rent ['rentTime'] );
			$now = new DateTime ( "NOW" );
			$past = date_diff ( $now, $rentTime );
			if (interval_to_seconds ( $past ) / 60 < 5) { // 两次借车间隔时间
				return "quick" . (5 - interval_to_seconds ( $past ) / 60);
			}
		}
		$rank = $this->getRank ( $user ['score'] );
		$ret = $this->getCache ( "LongTimeEnabled" );
		if ($ret ['value']) {
			$rank ['maxTime'] = $rank ['maxTime2'];
		}
		$ret = $this->query ( "SELECT pwd FROM tbike WHERE ID = $bikeID" );
		$this->update ( "INSERT INTO trent (bikeID, userID, rentTime, maxTime, unlockPWD, stop1) VALUES ($bikeID, $userID, NOW()," . $rank ['maxTime'] . ", '" . $bike ['pwd'] . "', " . $bike ['stopID'] . ")" );
		$rentID = $this->mysqli->insert_id;
		$this->update ( "UPDATE tuser SET state = 2, rentID = " . $this->mysqli->insert_id . " WHERE ID = $userID" );
		$this->update ( "UPDATE tstop SET bikeCount = bikeCount - 1 WHERE ID = " . $bike ['stopID'] );
		$this->update ( "UPDATE tbike SET state = 1, stopID = -1, rentID = " . $rentID . " WHERE ID = $bikeID" );
		return $ret ['pwd'];
	}
	public function report($userID, $type, $info) {
		$type = $this->mysqli->real_escape_string ( $type );
		$info = $this->mysqli->real_escape_string ( $info );
		$rentID = $this->query ( "SELECT rentID FROM tuser WHERE ID = $userID" );
		$rentID = $rentID ["rentID"];
		$rentInfo = $this->getAllByID ( "trent", $rentID );
		$this->update ( "UPDATE trent SET brokenType = $type, brokenInfo = '$info', returnTime = NOW(), lockPWD = unlockPWD, stop2 = stop1 WHERE ID = $rentID" );
		$bikeID = $rentInfo ['bikeID'];
		$stopID = $rentInfo ['stop1'];
		$this->update ( "UPDATE tbike SET state = 2, stopID = $stopID WHERE ID = $bikeID" );
		$this->update ( "UPDATE tuser SET state = 1 WHERE ID = $userID" );
	}
	public function returnIt($userID, $stopID) {
		$stopID = $this->mysqli->real_escape_string ( $stopID );
		$stop = $this->getAllByID ( "tstop", $stopID );
		if ($stop ['stopCount'] - $stop ['bikeCount'] <= 0) {
			return "no";
		}
		$user = $this->getAllByID ( "tuser", $userID );
		$rentID = $user ['rentID'];
		$rent = $this->getAllByID ( "trent", $rentID );
		$bikeID = $rent ['bikeID'];
		$bike = $this->getAllByID ( "tbike", $bikeID );
		$pwd = "";
		for($i = 0; $i < 5; $i ++)
			$pwd .= rand ( 0, 9 );
		if ($bike ['state'] == 1) {
			$this->update ( "UPDATE tbike SET state = 0, stopID = $stopID, pwd='$pwd' WHERE ID = $bikeID" );
			$this->update ( "UPDATE tstop SET bikeCount = bikeCount + 1 WHERE ID = $stopID" );
		} else {
			$this->update ( "UPDATE tbike SET stopID = $stopID, pwd='$pwd' WHERE ID = $bikeID" );
		}
		$rentTime = new DateTime ( $rent ['rentTime'] );
		$this->update ( "UPDATE trent SET returnTime = NOW(), lockPWD = '$pwd', stop2 = $stopID WHERE ID = $rentID" );
		$now = new DateTime ( "NOW" );
		$returnTime = new DateTime ( $rent ['rentTime'] );
		$ret = $this->getCache ( "LongTimeEnabled" );
		$rank = $this->getRank ( $user ['score'] );
		if ($ret ['value']) {
			$rank ['maxTime'] = $rank ['maxTime2'];
		}
		$returnTime->add ( new DateInterval ( 'PT' . $rank ['maxTime'] . 'H' ) );
		$diff = date_diff ( $now, $returnTime );
		$overH = interval_to_seconds ($diff) / 3600;
		if ($diff->invert == 0) {
			$this->update ( "UPDATE tuser SET state = 1, score = score + 2, timeAmount = timeAmount + " . intval ( interval_to_seconds ( date_diff ( $now, $rentTime ) ) / 60 ) . " WHERE ID = $userID" );
			$this->update ( "INSERT INTO tscore (userID, score, reason, time) VALUES ($userID, 2, '及时还车！', NOW())" );
		}
		else if ($overH <= 3) {
			$this->update ( "UPDATE tuser SET state = 1, score = score - 5, timeAmount = timeAmount + " . intval ( interval_to_seconds ( date_diff ( $now, $rentTime ) ) / 60 ) . " WHERE ID = $userID" );
			$this->update ( "INSERT INTO tscore (userID, score, reason, time) VALUES ($userID, -5, '还车超时(<3h)', NOW())" );
		}
		else if ($overH <= 24){
			$this->update ( "UPDATE tuser SET state = 1, score = score - 10, timeAmount = timeAmount + " . intval ( interval_to_seconds ( date_diff ( $now, $rentTime ) ) / 60 ) . " WHERE ID = $userID" );
			$this->update ( "INSERT INTO tscore (userID, score, reason, time) VALUES ($userID, -10, '还车超时(<24h)', NOW())" );
		} else {
			$cut = 10 * intval(($overH + 23) / 24);
			$this->update ( "UPDATE tuser SET state = 1, score = score - $cut, timeAmount = timeAmount + " . intval ( interval_to_seconds ( date_diff ( $now, $rentTime ) ) / 60 ) . " WHERE ID = $userID" );
			$this->update ( "INSERT INTO tscore (userID, score, reason, time) VALUES ($userID, -$cut, '还车超时(>24h)', NOW())" );
		}
		return $pwd;
	}
	public function setAccident($userID) {
		$user = $this->getAllByID ( "tuser", $userID );
		$rentID = $user ['rentID'];
		$rent = $this->getAllByID ( "trent", $rentID );
		$bikeID = $rent ['bikeID'];
		return $this->update ( "UPDATE tbike SET state = 2 WHERE ID = $bikeID" );
	}

	// admin
	public function adminLogin($username, $password) {
		$username = $this->mysqli->real_escape_string ( $username );
		$password = $this->mysqli->real_escape_string ( $password );
		return $this->query ( "SELECT * FROM tadmin WHERE name = '$username' AND pwd = '$password'" );
	}
	public function addAdmin($name, $pwd, $lmt) {
		$name = $this->mysqli->real_escape_string ( $name );
		$pwd = $this->mysqli->real_escape_string ( $pwd );
		$ret = $this->query ( "SELECT * FROM tadmin WHERE name = '$name'" );
		if ($ret)
			return -1;
		return $this->update ( "INSERT INTO tadmin (name, pwd, `limit`) VALUES ('$name', '$pwd', $lmt)" );
	}
	public function editAdmin($id, $name, $pwd, $lmt) {
		$name = $this->mysqli->real_escape_string ( $name );
		$pwd = $this->mysqli->real_escape_string ( $pwd );
		$ret = $this->query ( "SELECT * FROM tadmin WHERE name = '$name' AND ID != $id" );
		if ($ret)
			return -1;
		return $this->update ( "UPDATE tadmin SET name = '$name', pwd = '$pwd', `limit` = $lmt WHERE ID = $id" );
	}
	public function addBike($name, $state, $stopID, $pwd) {
		$name = $this->mysqli->real_escape_string ( $name );
		$pwd = $this->mysqli->real_escape_string ( $pwd );
		$stopID = $this->mysqli->real_escape_string ( $stopID );
		$state = $this->mysqli->real_escape_string ( $state );
		$ret = $this->query ( "SELECT * FROM tbike WHERE name = '$name'" );
		if ($ret)
			return -1;
		return $this->update ( "INSERT INTO tbike (name, state, stopID, pwd) VALUES ('$name', $state, $stopID, '$pwd')" );
	}
	public function editBike($id, $name, $state, $stopID, $pwd) {
		$name = $this->mysqli->real_escape_string ( $name );
		$pwd = $this->mysqli->real_escape_string ( $pwd );
		$stopID = $this->mysqli->real_escape_string ( $stopID );
		$state = $this->mysqli->real_escape_string ( $state );
		$ret = $this->query ( "SELECT * FROM tbike WHERE name = '$name' AND ID != $id" );
		if ($ret)
			return -1;
		return $this->update ( "UPDATE tbike SET name = '$name', pwd = '$pwd', state = $state, stopID = $stopID WHERE ID = $id" );
	}
	public function refreshStopInfo() {
		return $this->update ( "UPDATE tstop SET tstop.bikeCount = (SELECT COUNT(*) FROM tbike WHERE tbike.stopID = tstop.ID AND tbike.state = 0)" );
	}
	public function addRank($name, $minScore, $maxTime, $maxTime2) {
		$name = $this->mysqli->real_escape_string ( $name );
		$minScore = $this->mysqli->real_escape_string ( $minScore );
		$maxTime = $this->mysqli->real_escape_string ( $maxTime );
		$maxTime2 = $this->mysqli->real_escape_string ( $maxTime2 );
		return $this->update ( "INSERT INTO trank (name, minScore, maxTime, maxTime2) VALUES ('$name', $minScore , $maxTime, $maxTime2)" );
	}
	public function editRank($id, $name, $minScore, $maxTime, $maxTime2) {
		$name = $this->mysqli->real_escape_string ( $name );
		$minScore = $this->mysqli->real_escape_string ( $minScore );
		$maxTime = $this->mysqli->real_escape_string ( $maxTime );
		$maxTime2 = $this->mysqli->real_escape_string ( $maxTime2 );
		return $this->update ( "UPDATE trank SET name = '$name', minScore = $minScore, maxTime = $maxTime, maxTime2 = $maxTime2 WHERE ID = $id" );
	}
	public function editRent($id, $cmt) {
		$id = $this->mysqli->real_escape_string ( $id );
		$cmt = $this->mysqli->real_escape_string ( $cmt );
		return $this->update ( "UPDATE trent SET comment = '$cmt' WHERE ID = $id" );
	}
	public function addStop($name, $stopCount, $code) {
		$name = $this->mysqli->real_escape_string ( $name );
		$stopCount = $this->mysqli->real_escape_string ( $stopCount );
		$code = $this->mysqli->real_escape_string ( $code );
		$ret = $this->query ( "SELECT * FROM tstop WHERE name = '$name'" );
		if ($ret)
			return -1;
		return $this->update ( "INSERT INTO tstop (name, stopCount, code, bikeCount) VALUES ('$name', $stopCount, '$code', 0)" );
	}
	public function editStop($id, $name, $stopCount, $code) {
		$name = $this->mysqli->real_escape_string ( $name );
		$stopCount = $this->mysqli->real_escape_string ( $stopCount );
		$code = $this->mysqli->real_escape_string ( $code );
		$ret = $this->query ( "SELECT * FROM tstop WHERE name = '$name' AND ID != $id" );
		if ($ret)
			return -1;
		return $this->update ( "UPDATE tstop SET name = '$name', stopCount = $stopCount, code = '$code' WHERE ID = $id" );
	}
	public function editUser($id, $score, $state, $freeTime, $mobile, $cmt, $editScore) {
		$id = $this->mysqli->real_escape_string ( $id );
		$score = $this->mysqli->real_escape_string ( $score );
		$state = $this->mysqli->real_escape_string ( $state );
		$freeTime = $this->mysqli->real_escape_string ( $freeTime );
		$mobile = $this->mysqli->real_escape_string ( $mobile );
		$cmt = $this->mysqli->real_escape_string ( $cmt );
		$user = $this->query("SELECT * FROM tuser WHERE ID = $id");
		if (!$editScore) {
			$score = $user['score'];
		}
		if ($score != $user['score'])
			$this->update ( "INSERT INTO tscore (userID, score, reason, time) VALUES ($id, " . ($score - $user['score']) . ", '$cmt', NOW())" );
		if ($this->query ( "SELECT * FROM tuser WHERE mobile = '$mobile' AND id != $id" )) {
			return "no";
		}
		if ($freeTime)
			return $this->update ( "UPDATE tuser SET score= $score, state=$state, freeTime = '$freeTime', mobile = '$mobile', comment = '$cmt' WHERE ID = $id" );
		else
			return $this->update ( "UPDATE tuser SET score= $score, state=$state, freeTime = null, mobile = '$mobile', comment = '$cmt' WHERE ID = $id" );
	}
}
