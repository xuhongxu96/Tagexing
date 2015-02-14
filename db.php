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
		return $this->mysqli->affected_rows;
	}
	public function getAllByID($table, $ID) {
		return $this->query ( "SELECT * FROM $table WHERE ID = $ID" );
	}
	public function getInfo($openid) {
		$openid = $this->mysqli->real_escape_string ( $openid );
		$ret;
		if (! $ret = $this->query ( "SELECT * FROM tuser WHERE openid = '$openid'" )) {
			$this->update ( "INSERT INTO tuser (openID) VALUES ('$openid')" );
			return $this->query ( "SELECT * FROM tuser WHERE openid = '$openid'" );
		}
		if ($ret['state'] == 3) {
			$free = new DateTime($ret['freeTime']);
			$now = new DateTime("now");
			if ($free < $now) {
				$this->update("UPDATE tuser SET state = 1, freeTime = 0, comment='' WHERE openid = '$openid'");
			}
		}
		return $ret;
	}
	public function setCache($name, $val, $expired) {
		$ret = $this->update ( "DELETE FROM tcache WHERE name = '$name'" );
		return $ret + $this->update ( "INSERT INTO tcache (name, value, expired, time) VALUES ('$name', '$val', $expired, NOW())" );
	}
	public function getCache($name) {
		$this->update ( "DELETE FROM tcache WHERE TIME_TO_SEC(TIMEDIFF(NOW(), time)) >= expired" );
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
			$inviter = $this->query ( "SELECT ID FROM tuser WHERE mobile='$inviterMobile' AND name='$inviterName' AND state != 0" );
			if ($inviter == 0) {
				return "邀请人信息错误！";
			}
			$inviter = $inviter ["ID"];
		}
		if ($this->query ( "SELECT ID FROM tuser WHERE mobile='$mobile' AND ID != $ID" )) {
			return "该手机号码已经存在！";
		}
		$this->update ( "UPDATE tuser SET name='$name', mobile='$mobile', confirmInfo='$info', inviterID=$inviter, state=1 WHERE ID = $ID" );
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
		$rankID = $this->getRank ( $user ['score'] );
		$rankID = $rankID ["ID"];
		$ret = $this->query ( "SELECT pwd FROM tbike WHERE ID = $bikeID" );
		$this->update ( "INSERT INTO trent (bikeID, userID, rentTime, rankID, unlockPWD, stop1) VALUES ($bikeID, $userID, NOW(), $rankID, '" . $bike ['pwd'] . "', " . $bike ['stopID'] . ")" );
		$this->update ( "UPDATE tuser SET state = 2, rentID = " . $this->mysqli->insert_id . " WHERE ID = $userID" );
		$this->update ( "UPDATE tstop SET bikeCount = bikeCount - 1 WHERE ID = " . $bike ['stopID'] );
		$this->update ( "UPDATE tbike SET state = 1, stopID = -1, rentID = " . $this->mysqli->insert_id . " WHERE ID = $bikeID" );
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
		$returnTime = new DateTime ( $rent ['returnTime'] );
		$this->update ( "UPDATE trent SET returnTime = NOW(), lockPWD = '$pwd', stop2 = $stopID WHERE ID = $rentID" );
		$this->update ( "UPDATE tuser SET state = 1, timeAmount = timeAmount + " . date_diff ( $returnTime, $rentTime )->i . " WHERE ID = $userID" );
		return $pwd;
	}
	public function setAccident($userID) {
		$user = $this->getAllByID ( "tuser", $userID );
		$rentID = $user ['rentID'];
		$rent = $this->getAllByID ( "trent", $rentID );
		$bikeID = $rent ['bikeID'];
		return $this->update ( "UPDATE tbike SET state = 2 WHERE ID = $bikeID" );
	}
}

