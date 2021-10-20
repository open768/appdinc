<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2018 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

require_once("$ADlib/AD.php");
//see 

//#################################################################
//# 
//#################################################################
class cDBMetric{
	public $db, $name, $query;
}

class cADDB{
	public static $db_app = null;

	//*****************************************************************
	public static function GET_Databases(){
		cDebug::enter();
		$sMetricPath= cADMetric::databases();
		$oData = (self::$db_app)->GET_Metric_heirarchy($sMetricPath, false);
		cDebug::leave();
		return $oData;
	}

	//*****************************************************************
	public static function GET_Database_ServerStats($psDB){
		$sMetricPath= cADMetric::databaseServerStats($psDB);
		return  (self::$db_app)->GET_Metric_heirarchy($sMetricPath, false);
	}
	
	//*****************************************************************
	public static function GET_all_custom_metrics(){
		cDebug::enter();
		$aData = cADCore::GET_restUI("/dbCustomQueryMetrics/getAll", true, $psUIPrefix=cADCore::DBUI_PREFIX);
		
		//cDebug::vardump($aData[0]);
		$aOut = [];
		foreach ($aData as $oItem){
			if ($oItem->configNames){
				$sDB = $oItem->configNames[0];
				if (!array_key_exists($sDB, $aOut))
					$aOut[$sDB] = [];
				$oEntry = new cDBMetric;
				$oEntry->db = $sDB;
				$oEntry->name = $oItem->name;
				$oEntry->query = $oItem->queryText;
				$aOut[$sDB][] = $oEntry;
			}
		}
		ksort($aOut);
		
		cDebug::vardump($aOut);
		cDebug::leave();
		return $aOut;
	}
}
cADDB::$db_app = new cADApp(cADCore::DATABASE_APPLICATION,cADCore::DATABASE_APPLICATION);

?>
