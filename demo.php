<?php 
function test_auth($authkey, $db, $code)
{
	if (WX_DEBUG) return true;
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

		if ($ret == OTP_SUCCESS) {
			$db->setCache($authkey, $currdft, -1);
			return true;
		}
	}
	return false;
}
