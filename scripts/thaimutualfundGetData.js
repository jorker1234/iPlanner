(function () {
    if (window.thaiMuTualFundGetData === undefined) {
        window.thaiMuTualFundGetData = {};
    }
	
	//let dataMonth = 6;
	//let dataYear = 2535; //2535
	//const maxMonth = new Date().getMonth() + 1;
	//const maxYear = new Date().getFullYear() + 543;
	let dataMonth = 9;
	let dataYear = 2560;
	const maxMonth = 9;
	const maxYear = 2560;
	 
	function isFuture(){
		return ((dataYear * 100) + dataMonth) >= ((maxYear * 100) + maxMonth);
	}
	
	function nextMonth() {
		dataMonth ++;
		if(dataMonth > 12){
			dataMonth = 1;
			dataYear ++;
		}
	}
	
	function lefPad(data){
		const result = data.toString();
		return result.length === 1 ? "0" + result : result;
	}
	
	function process(){
		const dayTime = 1000 * 60 * 60 * 24;
		const endTime = new Date(dataYear - 543, dataMonth, 1).getTime() - dayTime;
		const endDate = new Date(endTime).getDate();
		const date1 = lefPad(1) + "/" + lefPad(dataMonth) + "/" + dataYear;
		const date2 = lefPad(endDate) + "/" + lefPad(dataMonth) + "/" + dataYear;
		getData(date1, date2).then(function(result){
			if(result.IsSuccess){
				$("body").append("<div style='color:green'>" + date1 + " - " + date2 + " (Fund Insert: <b>" + result.InsertdRecordFunds +
					"</b>, Fund Update: <b>" + result.UpdatedRecordFunds + "</b>, NAVs: <b>" + result.InsertdRecordNAVs + "</b>)</div>");
				if(!isFuture()){
					nextMonth();
					process();
				}else {
					$("body").append("<div style='color:blue'>Done</div>");
				}
			}else {
				$("body").append("<div style='color:red'>>" + date1 + " - " + date2 + ": false" + "</div>");
			}
		})
		
	}
	
	function getData(date1, date2){
		const deferred = $.Deferred();
		const data = {
			date1: date1,
			date2: date2
		};
		$.ajax({
			async: true,
			cache: false,
			data: data,
			success: (result, textStatus, jqXhr) => {
				deferred.resolve(result);
			},
			type: "GET",
			url: "Api/ThaiMutualFundFeed.php"
		});
		return deferred.promise();
	}
	
	thaiMuTualFundGetData.init = function(){
		process();
	}
	
} ());


$(document).ready(function() {
	window.thaiMuTualFundGetData.init();
});