<?php
	header('Content-Type: application/json');
	include('../Config/webconfig.php');
	include('../Utility/Connection.php');
	
	$type = $_GET["type"];
	if($type == "GetAllFundName"){
		global $con;
		$output = [];
		connect_db();
		$sqlSelect = "SELECT FundCode, FundName FROM fundheader WHERE IsActive = 1 AND LastUpdatedNavDate > DATE_ADD(now(), INTERVAL -1 MONTH) 
			ORDER BY FundCode, FundName";
		$result = mysql_query($sqlSelect, $con);
		if ($result) {
			while ($row = mysql_fetch_object($result)) {
				$data = array(
					'FundCode' => trim($row->FundCode),
					'FundName' => trim($row->FundName)
				);
				array_push($output, $data);
			}
		}
		close_db();
		echo json_encode($output);
	}
	
	if($type == "GetFundDetailByFundCode"){
		global $con;
		$fundCode = $_GET["fundCode"];
		$output = [];
		connect_db();
		$sqlSelect = "SELECT NAVDate, NAV FROM fundnav WHERE IsActive = 1 AND FundCode = '" . $fundCode . "' ORDER BY NAVDate";
		$result = mysql_query($sqlSelect, $con);
		if ($result) {
			while ($row = mysql_fetch_object($result)) {
				$data = array(
					'NAVDate' => trim($row->NAVDate),
					'NAV' => floatval($row->NAV)
				);
				array_push($output, $data);
			}
		}
		close_db();
		echo json_encode($output);
	}
?>