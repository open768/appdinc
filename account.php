<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2022

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//see 
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/ckinc/common.php");
require_once("$ADlib/common.php");
require_once("$ADlib/core.php");


//#################################################################
//# 
//#################################################################
class cADAccountData{
	public $value;
	public $date;
	
	function  __construct($psDate, $psValue){
		$this->date = $psDate;
		$this->value = $psValue;
	}
}

class cADAccount{
	public static $account_id = null;
	
	//*****************************************************************
	public static function GET_account_id(){
		cDebug::enter();

		if (!self::$account_id){
			cADCore::$URL_PREFIX="/api/accounts";
			cADCore::$CONTROLLER_PREFIX=null;
			
			$oJson = cADCore::GET("/myaccount?");
			self::$account_id = $oJson->id;
			
			cDebug::write("accountID is ".self::$account_id);
		}

		cDebug::leave();
		return self::$account_id;
	}

	//*****************************************************************
	public static function GET_license_modules(){
		cDebug::enter();

		$oJson = self::pr__get("/licensemodules?");

		cDebug::leave();
		return $oJson;
	}
	

	//*****************************************************************
	//dates must be of format 2015-12-25T00:00:00Z
	public static function GET_license_usage($psModule, $piMonths=1){
		cDebug::enter();
		
		cDebug::write("looking for usage of $psModule for $piMonths months");
		
		$dStart = date(cADCore::DATE_FORMAT, time()-($piMonths*cCommon::SECONDS_IN_MONTH));
		$dEnd = date(cADCore::DATE_FORMAT, time());

		$oJson = self::pr__get("/licensemodules/$psModule/usages?startdate=$dStart&enddate=$dEnd");
		
		$aUsages = [];
		if ($oJson && property_exists($oJson,"usages")){
			//cDebug::vardump($oJson);
			foreach ($oJson->usages as $oData)
				$aUsages[] = new cADAccountData($oData->createdOnIsoDate, $oData->maxUnitsUsed);
		}
		
		cDebug::leave();
		return $aUsages;
	}
	
	//*****************************************************************
	public static function GET_LicenseRules(){
		cDebug::enter();
		
		$aRules=cADRestUI::GET_allocationRules();
		
		cDebug::leave();
		return $aRules;
	}
	
	//*****************************************************************
	private static function pr__get($psURL){
		cDebug::enter();
		$sID = self::GET_account_id();
		
		cADCore::$URL_PREFIX="/api/accounts/$sID";
		cADCore::$CONTROLLER_PREFIX=null;
		$oJson = cADCore::GET($psURL);

		
		cDebug::leave();
		return $oJson;
	}
	
}

?>
