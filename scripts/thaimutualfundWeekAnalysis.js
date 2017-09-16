(function () {
    if (window.thaiMuTualFundWeekAnalysis === undefined) {
        window.thaiMuTualFundWeekAnalysis = {};
    }
	
	const selectors = {
		ddlFund: "#ddlFund"
	}
	
	function initControl(){
		$(selectors.ddlFund).append("<option value=''>Please Select</option>");
		getAllFundHeader().then((results) => {
			if(!results || results.length === 0){
				return;
			}
			for(let i = 0; i < results.length; i++){
				const result = results[i];
				$(selectors.ddlFund).append("<option value='" + result.FundCode + "'>" + result.FundCode + " (" + result.FundName + ")</option>");
			}
		});
	}
	
	function handleControl(){
		$(selectors.ddlFund).change(function(){
			const fundCode = $(selectors.ddlFund).val();
			if(fundCode){
				getFundDetailByFundCode(fundCode).then((results) => {
					dataReturn(results);
				})
			}
			
		});
	}
	
	function calculateProfit(newNav, oldNav, digit = 2){
		return (((newNav - oldNav) / oldNav) * 100).toFixed(digit);
	}
	
	function dataReturn(NAVs){
		NAVs = NAVs || [];
		let currentNav = null;
		let currentNavDate = null;
		const day = 86400000;
		const data = {
			daily: 0,
			weekly: 0,
			monthly: 0,
			threeMonth: 0,
			sixMonth: 0,
			yearly: 0
		}
		let isWeekly = false;
		let isMonthly = false;
		let isThreeMonth = false;
		let isSixMonth = false;
		let isYearly = false;
		let preNav = null;
		for(let i = NAVs.length - 1; i > -1; i--){
			const nav = NAVs[i];
			const navDates = nav.NAVDate.split("-");
			const navDate = new Date(navDates[0], navDates[1] - 1, navDates[2]);
			if(i === NAVs.length - 1){
				currentNav = nav;
				currentNavDate = navDate;
			}else {	
				if(i === NAVs.length - 2){
					data.daily = calculateProfit(currentNav.NAV, nav.NAV);
				}
				if(!isWeekly && ((currentNavDate - navDate) / day) > 7){
					isWeekly = true;
					data.weekly= calculateProfit(currentNav.NAV, preNav.NAV);
				}
				if(!isMonthly && ((currentNavDate - navDate) / day) > 31){
					isMonthly = true;
					data.monthly= calculateProfit(currentNav.NAV, preNav.NAV);
				}
				if(!isThreeMonth && ((currentNavDate - navDate) / day) > 62){
					isThreeMonth = true;
					data.threeMonth= calculateProfit(currentNav.NAV, preNav.NAV);
				}
				if(!isSixMonth && ((currentNavDate - navDate) / day) > 183){
					isSixMonth = true;
					data.sixMonth= calculateProfit(currentNav.NAV, preNav.NAV);
				}
				if(!isYearly && ((currentNavDate - navDate) / day) > 365){
					isYearly = true;
					data.yearly= calculateProfit(currentNav.NAV, preNav.NAV);
				}
				
				if(isWeekly && isMonthly && isThreeMonth && isSixMonth && isYearly){
					i = 0;
				}
			}
			preNav = nav;
		}
		
		console.log(data);
	}
	
	function weekProfit(NAVs){
		NAVs = NAVs || [];
		let noDayOfWeek = 8;
		const navWeeks = [];
		for(let i = 0; i < NAVs.length; i++){
			const nav = NAVs[i];
			const navDates = nav.NAVDate.split("-");
			const navDate = new Date(navDates[0], navDates[1] - 1, navDates[2]);
			const navDayOfWeek = navDate.getDay();
			if(navDayOfWeek < noDayOfWeek){
				if(navWeeks.length > 0){
					const lastNav = navWeeks[navWeeks.length -1].NAV;
					nav.profit = calculateProfit(nav.NAV, lastNav);
				}else {
					nav.profit = 0;
				}
				navWeeks.push(nav);
			}
			noDayOfWeek = navDayOfWeek;
		}
		console.log(navWeeks);
	}
	
	function getFundDetailByFundCode(fundCode){
		const deferred = $.Deferred();
		const data = {
			type: "GetFundDetailByFundCode",
			fundCode: fundCode
		};
		$.ajax({
			async: true,
			cache: false,
			data: data,
			success: (result, textStatus, jqXhr) => {
				deferred.resolve(result);
			},
			type: "GET",
			url: "Api/ThaiMutualFundUtility.php"
		});
		return deferred.promise();
	}
	
	function getAllFundHeader(){
		const deferred = $.Deferred();
		const data = {
			type: "GetAllFundName"
		};
		$.ajax({
			async: true,
			cache: false,
			data: data,
			success: (result, textStatus, jqXhr) => {
				deferred.resolve(result);
			},
			type: "GET",
			url: "Api/ThaiMutualFundUtility.php"
		});
		return deferred.promise();
	}
	
	thaiMuTualFundWeekAnalysis.init = function(){
		initControl();
		handleControl();
	}
	
} ());


$(document).ready(function() {
	window.thaiMuTualFundWeekAnalysis.init();
});