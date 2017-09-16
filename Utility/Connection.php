<?php
$con;
function connect_db(){
	global $host, $user, $pass, $db, $con;
	if (!$con) {
		$con = mysql_connect(constant('MYSQLHOST'),constant('MYSQLUSER'),constant('MYSQLPASS')) ; 
		mysql_select_db(constant('MYSQLDB')) ;
		mysql_query("SET NAMES utf8",$con);
	}
}
function close_db(){
	global $con;
	mysql_close($con);
}
?>