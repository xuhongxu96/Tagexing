<?php
require_once 'config.php';
require_once 'db.php';
/*
 * Smarty plugin
* -------------------------------------------------------------
* File:     modifier.getImg.php
* Type:     modifier
* Name:     getImg
* Purpose:  ID获取图片URL
* -------------------------------------------------------------
*/
function smarty_modifier_getImg($string)
{
	$db = new DB ();
	$db->connect ();
	$url = $db->getImg($string);
	$db->disconnect ();
	return $url;
}
?>