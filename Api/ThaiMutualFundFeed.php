<?php 
	header('Content-Type: application/json');
	include('../Config/webconfig.php');
	include('../Utility/Connection.php');
	include('../Utility/Guid.php');
	
	function convertFormatDateString($inputDate , $isThaiYear = false){
		if(empty(trim($inputDate))){
			return "";
		}
		$dates = explode("/", $inputDate);
		if($isThaiYear){
			$dates[2] = intval($dates[2]) - 543;
		}
		return $dates[2] . '-' . $dates[1] . '-' . $dates[0];
	}
	
	function getSQLInsertFundHeader($fundHeader){
		return "('" . $fundHeader['FundId'] . "', '" . $fundHeader['FundCode'] . "', '" . $fundHeader['FundName'] . "'
				, '" . $fundHeader['FundNameEN'] . "', '" . $fundHeader['FundType'] . "', '" . $fundHeader['FundFlag'] . "'
				, '" . $fundHeader['FundCompany'] . "', 'admin', '" . $fundHeader['FundDate'] . "', 'admin', '" . $fundHeader['FundDate'] . "', 1)";
	}
	
	function getSQLInsertNAV($fundNAV){
		return "('" . $fundNAV['NAVDate'] . "', '" . $fundNAV['FundCode'] . "', " . $fundNAV['FundAsset'] . ", " . $fundNAV['NAV'] . "
				, " . $fundNAV['NAVBuy'] . ", " . $fundNAV['NAVSell'] . ", " . $fundNAV['XD'] . ", '" . $fundNAV['XDDate'] . "'
				, 'admin', '" . $fundNAV['NAVDate'] . "', 'admin', '" . $fundNAV['NAVDate'] . "', 1)";
	}
	
	function UpdateFundHeader($fundHeaders, $output){
		global $con;
		$fundCodes = array_keys($fundHeaders);
		$fundCodeWhere = "'" . join("','", $fundCodes) . "'";
		$fundInsert = [];
		$fundUpdate = [];
		$sqlSelect = "SELECT FundId, FundCode, FundName, FundNameEN, FundType, FundFlag, FundCompany 
			FROM fundheader WHERE IsActive = 1 AND FundCode IN (" . $fundCodeWhere . ")";
		$result = mysql_query($sqlSelect, $con);
		if ($result) {
			while ($row = mysql_fetch_object($result)) {
				$fundDataId = trim($row->FundId);
				$fundDataCode = trim($row->FundCode);
				$fundDataName = trim($row->FundName);
				$fundDataNameEN = trim($row->FundNameEN);
				$fundDataType = trim($row->FundType);			
				$fundDataFlag = trim($row->FundFlag);
				$fundDataCompany = trim($row->FundCompany);
				
				$fundHeaderName = $fundHeaders[$fundDataCode]['FundName'];
				$fundHeaderNameEN = $fundHeaders[$fundDataCode]['FundNameEN'];
				$fundHeaderType = $fundHeaders[$fundDataCode]['FundType'];
				$fundHeaderFlag = $fundHeaders[$fundDataCode]['FundFlag'];
				$fundHeaderCompany = $fundHeaders[$fundDataCode]['FundCompany'];
				
				$fundCodeIndex = array_search($fundDataCode, $fundCodes);
				array_splice($fundCodes, $fundCodeIndex, 1);
				if($fundDataName != $fundHeaderName || $fundDataNameEN != $fundHeaderNameEN || $fundDataType != $fundHeaderType 
					|| $fundDataFlag != $fundHeaderFlag || $fundDataCompany != $fundHeaderCompany ){
					array_push($fundInsert, getSQLInsertFundHeader($fundHeaders[$fundDataCode]));
					array_push($fundUpdate, $fundDataId);
				}else {
					$fundHeaders[$fundDataCode]['FundId'] = $fundDataId;
				}
			}
		}
		foreach($fundCodes as $value){
			array_push($fundInsert, getSQLInsertFundHeader($fundHeaders[$value]));
		}
		if(count($fundUpdate) > 0){		
			$fundUpdateIds = "'" . join("','", $fundUpdate) . "'";
			$sqlUpdate= "UPDATE fundheader SET IsActive = 0, UpdatedDate = '". convertFormatDateString($_GET["date2"], true) ."' WHERE FundId IN (" . $fundUpdateIds . ")";
			$resultUpdate = mysql_query($sqlUpdate, $con);
			if($resultUpdate){
				$output["UpdatedRecordFunds"] = count($fundUpdate);
			}else {
				$output["IsSuccess"] = false;
			}
		}
		if($output["IsSuccess"] == true && count($fundInsert) > 0){
			$sqlInsert = "INSERT INTO fundheader(FundId, FundCode, FundName, FundNameEN, FundType, FundFlag, FundCompany, CreatedBy, 
				CreatedDate, UpdatedBy, UpdatedDate, IsActive) VALUES " . join(', ', $fundInsert);
			$resultInsert = mysql_query($sqlInsert, $con);
			if($resultInsert){
				$output["InsertdRecordFunds"] = count($fundInsert);
			}else {
				$output["IsSuccess"] = false;
			}
		}
		return $output;
	}
	
	function UpdateFundNAV($fundNAVs, $fundHeaders, $navDates, $output, $start){
		global $con;
		$fundInserts = [];
		if($start == 0){
			$fundCodes = "'" . join("','", array_keys($fundHeaders)) . "'";
			$fundNAVDates = "'" . join("','", $navDates) . "'";
			$sqlDelete = "DELETE FROM `fundnav` where FundCode in (" . $fundCodes . ") AND `NAVDate` in (" . $fundNAVDates . ");";
			$resultDelete = mysql_query($sqlDelete, $con);
			if(!$resultDelete){
				$output["IsSuccess"] = false;
			}
		}	
		if($output["IsSuccess"] && count($fundNAVs) > 0 && count($fundNAVs) > $start){
			$end = $start + 5000;
			for($i = $start; $i < $end && $i < count($fundNAVs); $i++){
				$nav = $fundNAVs[$i];
				$fundCode = $nav["FundCode"];
				array_push($fundInserts, getSQLInsertNAV($nav));
			}
			$sqlInsert = "INSERT INTO `fundnav` (`NAVDate`, `FundCode`, `FundAsset`, `NAV`, `NAVBuy`, `NAVSell`, 
				`XD`, `XDDate`, `CreatedBy`, `CreatedDate`, `UpdatedBy`, `UpdatedDate`, `IsActive`) VALUES " . join(', ', $fundInserts) . ";";
			$resultInsert = mysql_query($sqlInsert, $con);
			if(!$resultInsert){
				$output["IsSuccess"] = false;
			}else {
				$output["InsertdRecordNAVs"] += count($fundInserts);
			}
		}
		
		if(!$output["IsSuccess"]){
			return $output;
		}
		
		if($output["IsSuccess"] && $i >= count($fundNAVs)){
			return $output;
		}else {
			return UpdateFundNAV($fundNAVs, $fundHeaders, $navDates, $output, $i);
		}
	}
	
	function convertToNAV($nav){
		if(empty(trim($nav))){
			return 0;
		}
		return floatval(trim($nav));
	}
	
	function convertToAsset($nav){
		if(empty(trim($nav))){
			return 0;
		}
		return floatval(trim($nav));
	}
	
	$date1 = $_GET["date1"];
	$date2 = $_GET["date2"];
	$url = "https://www.thaimutualfund.com/AIMC/aimc_navCenterDownloadRepRep.jsp?date1=".$date1."&date2=".$date2;
	$data = file_get_contents($url);
	$data = str_replace('"', '', $data);
	$navDates = [];
	$fundHeader = [];
	$fundNAV = [];
	$results = [];
	if(!empty(trim($data))){
		$datas = explode("\n", $data);
		foreach($datas as $value){
			if(!empty(trim($value))){
				$informations = explode(",", $value);
				if(count($informations) > 13){
					$id = getGuid();
					$date = convertFormatDateString(trim($informations[0]));
					$OECE = trim($informations[1]);
					$type = trim($informations[2]);
					$com = trim($informations[3]);
					$com2 = trim($informations[4]);
					$nameTH = trim($informations[5]);
					$nameTH = iconv('tis-620', 'utf-8//IGNORE', $nameTH);
					$nameEN = trim($informations[6]);
					$nameEN = iconv('tis-620', 'utf-8//IGNORE', $nameEN);
					$code = trim($informations[7]);
					$asset = convertToAsset($informations[8]);
					$NAV = convertToNAV($informations[9]);
					$XDValue = convertToNAV($informations[10]);
					$XDDate = convertFormatDateString(trim($informations[11]));
					$NAVBuy = convertToNAV($informations[12]);
					$NAVSell = convertToNAV($informations[13]);
					
					if($NAV > 0){
						if(!isset($fundHeader[$code])) {
							$fundHeader[$code] = array(
								'FundDate' => $date,
								'FundId' => $id,
								'FundCode' => $code,
								'FundName' => $nameTH,
								'FundNameEN' => $nameEN,
								'FundType' => $type,
								'FundFlag' => $OECE,
								'FundCompany' => $com,
								'IsActive' => 1
							);
						}
						$currentNAV = array(
							'NAVDate' => $date,
							'FundCode' => $code,
							'FundAsset' => $asset,
							'NAV' => $NAV,
							'NAVBuy' => $NAVBuy,
							'NAVSell' => $NAVSell,
							'XD' => $XDValue,
							'XDDate' => $XDDate,
							'IsActive' => 1
						);
						array_push($fundNAV, $currentNAV);
						
						if(!in_array($date, $navDates)){
							array_push($navDates, $date);
						}
					}
				}
			}
		}
	}
	$output = array(
		'IsSuccess' => true,
		'InsertdRecordFunds' => 0,
		'UpdatedRecordFunds' => 0,
		'InsertdRecordNAVs' => 0
	);
	connect_db();
	mysql_query("START TRANSACTION");
	if(count($fundHeader) > 0){
		$output = UpdateFundHeader($fundHeader, $output);
	}
	if($output["IsSuccess"] == true && count($fundNAV) > 0){
		$output = UpdateFundNAV($fundNAV, $fundHeader, $navDates, $output, 0);
	}
	if ($output["IsSuccess"]) {
		mysql_query("COMMIT");
	} else {        
		mysql_query("ROLLBACK");
	}
	close_db();
	echo json_encode($output);
	/*echo "<pre>";
	print_r($output);
	echo "</pre>";*/
	
	/*echo "<pre>";
	print_r($fundHeader);
	echo "</pre>";*/
?>
