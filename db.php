<?php
class DB {
	private $mysqli;
	public function connect() {
		$this->mysqli = new mysqli ( WX_DBHOST, "root", WX_DBPSW, WX_DBNAME );
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
		return $ret;
	}
	public function setCache ($name, $val, $expired) {
		$ret = $this->update("DELETE * FROM tcache WHERE name = '$name'");
		return $ret + $this->update("INSERT INTO tcache (name, value, expired, time) VALUES ('$name', '$val', $expired, NOW())");
	}
	public function getCache ($name) {
		$this->update("DELETE * FROM tcache WHERE TIMEDIFF(NOW(), time) >= expired");
		return $this->query("SELECT * FROM tcache WHERE name = '$name'");
	}
	public function removeCache ($name) {
		return $this->update("DELETE * FROM tcache WHERE name = '$name'");
	}
	public function register($ID, $name, $mobile, $info, $inviterName, $inviterMobile) {
		$name = $this->mysqli->real_escape_string ( $name );
		$mobile = $this->mysqli->real_escape_string ( $mobile );
		$info = $this->mysqli->real_escape_string ( $info );
		$inviterName = $this->mysqli->real_escape_string ( $inviterName );
		$inviterMobile = $this->mysqli->real_escape_string ( $inviterMobile );
		$vip = array(
			"17888829772" => "1"
		);
		$inviter = -1;
		if (!array_key_exists($mobile, $vip)) {
			$inviter = $this->query ( "SELECT ID FROM tuser WHERE mobile='$inviterMobile' AND name='$inviterName'" );
			if ($inviter == 0) {
				return "邀请人信息错误！";
			}	
			$inviter = $inviter["ID"];
		}
		if ($this->query ( "SELECT ID FROM tuser WHERE mobile='$mobile' AND ID != $ID" )) {
			return "该手机号码已经存在！";
		}
		$this->update ( "UPDATE tuser SET name='$name', mobile='$mobile', confirmInfo='$info', inviterID=$inviter WHERE ID = $ID" );
	}
	public function postImg($openid, $img) {
		$this->update ( "UPDATE tuser SET pic='$img' WHERE openid = '$openid'" );
	}
	public function getRank ($score) {
		return $this->query("SELECT * FROM trank WHERE minScore <= $score ORDER BY minScore DESC LIMIT 0, 1");
	}
	public function getStopInfo () {
		return $this->queryAll("SELECT * FROM tstop");
	}
}

