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
require_once("$appdlib/appdynamics.php");

//#################################################################
//# CLASSES
//#################################################################
class cAppDynController{		
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
		$old_prefix = cAppDynCore::$URL_PREFIX;
		cAppDynCore::$URL_PREFIX = cAppDynCore::CONFIG_METRIC_PREFIX ;
		$oData = cAppDynCore::GET("?");
		cAppDynCore::$URL_PREFIX = $old_prefix ;
		cDebug::leave();
		return $oData;
	}
	
	//*****************************************************************
	public static function GET_Applications(){
		cDebug::enter();
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_Applications();
		
		$aData = cAppDynCore::GET('?',true);
		if ($aData)	uasort($aData,"Appd_name_sort_fn");
		$aOut = [];
		foreach ($aData as $oItem){
			if ($oItem->name !== null){
				$oApp = new cAppDApp($oItem->name, $oItem->id);
				$aOut[] = $oApp;
			}
		}
		
		//if (cDebug::is_debugging()) cDebug::vardump($aOut);
		cDebug::leave();
		return $aOut;		
	}
	
	//*****************************************************************
	public static function GET_Databases(){
		cDebug::enter();
		$sMetricPath= cAppDynMetric::databases();
		$oData = cAppdynCore::GET_Metric_heirarchy(cAppDynCore::DATABASE_APPLICATION, $sMetricPath, false);
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
				$aServices[$sBName][] = new cAppDApp($oApp->name, $oApp->id);
			}
		}
		ksort($aServices);
		cDebug::leave();
		return $aServices;
	}
	
	//*****************************************************************
	public static function GET_server_nodes_with_MQ(){
		cDebug::enter();
		
		$oTime = new cAppDynTimes();
		$oTime->time_type = cAppDynTimes::BEFORE_NOW;
		$oTime->duration = 5;
		
		//fetch the data
		$sMetricPath= cAppDynMetric::serverNodesWithMQ();  
		$oApp = new cAppdApp(cAppDynCore::SERVER_APPLICATION, cAppDynCore::SERVER_APPLICATION);
		$oData = cAppDynCore::GET_MetricData($oApp, $sMetricPath, $oTime, false,true,true);
		if (gettype($oData) === "NULL")		cDebug::error("no data returned");
		cDebug::extra_debug("type of data returned is :". gettype($oData));
		cDebug::vardump($oData);
		
		//parse the data
		$aOut = [];
		foreach ($oData as $oItem){
			
			if ($oItem->metricName !== cAppDynCore::METRIC_NOT_FOUND){
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
