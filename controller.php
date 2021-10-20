<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013-2018 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//see 
require_once("$ADlib/AD.php");

//#################################################################
//# CLASSES
//#################################################################
class cADController{		
	
	public static function GET_account(){
		cDebug::enter();
		$aData = cADCore::GET('/api/accounts/myaccount',true,false,false);
		cDebug::leave();
		return $aData;
	}
	
	//****************************************************************
	public static function GET_Controller_version(){
		cDebug::enter();
		$aConfig = self::GET_configuration();
		foreach ($aConfig as $oItem)
			if ($oItem->name === "schema.version"){
				$sVersion = preg_replace("/^0*/","",$oItem->value);
				$sVersion = preg_replace("/-0+(\d+)/",'.$1',$sVersion);
				$sVersion = preg_replace("/-0+/",'.0',$sVersion);
				cDebug::leave();
				return $sVersion;
			}
		cDebug::leave();
	}

	//****************************************************************
	public static function GET_configuration(){
		cDebug::enter();
		$old_prefix = cADCore::$URL_PREFIX;
		cADCore::$URL_PREFIX = cADCore::CONFIG_METRIC_PREFIX ;
		$oData = cADCore::GET("?");
		cADCore::$URL_PREFIX = $old_prefix ;
		cDebug::leave();
		return $oData;
	}
	
	

	//*****************************************************************
	public static function GET_allBackends(){
		cDebug::enter();
		$aServices = [];
		
		$oApps = self::GET_Applications();
		foreach ($oApps as $oApp){
			$aBackends = self::GET_Backends($oApp->name);
			foreach ($aBackends as $oBackend){
				$sBName = $oBackend->name;
				if (!isset($aServices[$sBName])) $aServices[$sBName] = [];
				$aServices[$sBName][] = new cADApp($oApp->name, $oApp->id);
			}
		}
		ksort($aServices);
		cDebug::leave();
		return $aServices;
	}
	
	//*****************************************************************
	public static function GET_server_nodes_with_MQ(){
		cDebug::enter();
		
		$oTime = new cADTimes();
		$oTime->time_type = cADTimes::BEFORE_NOW;
		$oTime->duration = 5;
		
		//fetch the data
		$sMetricPath= cADMetric::serverNodesWithMQ();  
		$oApp = new cADApp(cADCore::SERVER_APPLICATION, cADCore::SERVER_APPLICATION);
		$oData = $oApp->GET_MetricData($sMetricPath, $oTime, false,true,true);
		if (gettype($oData) === "NULL")		cDebug::error("no data returned");
		cDebug::extra_debug("type of data returned is :". gettype($oData));
		cDebug::vardump($oData);
		
		//parse the data
		$aOut = [];
		foreach ($oData as $oItem){
			
			if ($oItem->metricName !== cADCore::METRIC_NOT_FOUND){
				$sMetric = $oItem->metricPath;
				$sMetric = preg_replace("/^.*Nodes\|(.*)\|Custom.*$/", "$1", $sMetric);
				$aOut[] = $sMetric;
			}
		}
		asort($aOut, SORT_FLAG_CASE  | SORT_STRING);
		cDebug::leave();
		return $aOut;
	}
	
}
?>
