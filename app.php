<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2018 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//see 
require_once("$appdlib/appdynamics.php");

//#################################################################
//# 
//#################################################################
class cAppDApp{
	public static $db_app = null;
	public $name, $id;
	function __construct($psAppName, $psAppId=null) {	
		if ($psAppName == null) cDebug::error("null app name");

		$this->name = $psAppName;
		if ($psAppId == null)
			$this->pr__get_id();
		else
			$this->id = $psAppId;
	}
   
	//*****************************************************************
	public function pr__get_id(){
		$aApps = cAppDynController::GET_Applications();
		$sID = null;
		
		foreach ($aApps as $oApp){
			if  ($oApp->name === $this->name){
				$sID = $oApp->id;
				break;
			}
		}
		
		if ($sID == null) cDebug::error("unable to find appid for $this->name");
		$this->id = $sID;
		return $sID;
	}
	
	//*****************************************************************
	public function GET_Backends(){
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_Backends(null);
		$sMetricpath= cAppDynMetric::backends();
		return cAppdynCore::GET_Metric_heirarchy($this->name, $sMetricpath, false); //dont cache
	}

	//*****************************************************************
	//see events reference at https://docs.appdynamics.com/display/PRO14S/Events+Reference
	public function GET_Events($poTimes, $psEventType = null){
		$sApp = rawurlencode($this->name);
		$sTimeQs = cAppdynTime::make($poTimes);
		if ($psEventType== null) $psEventType = cAppDyn::ALL_EVENT_TYPES;
		$sSeverities = cAppDyn::ALL_SEVERITIES;
		
		$sEventsUrl = cHttp::build_url("$sApp/events", "severities", $sSeverities);
		$sEventsUrl = cHttp::build_url($sEventsUrl, "Output", "JSON");
		$sEventsUrl = cHttp::build_url($sEventsUrl, "event-types", $psEventType);
		$sEventsUrl = cHttp::build_url($sEventsUrl, $sTimeQs);
		return cAppDynCore::GET($sEventsUrl );
	}

	//*****************************************************************
	public function GET_ExtTiers(){
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_AppExtTiers(null);
		cDebug::enter();
		$sMetricPath= cAppDynMetric::appBackends();
		$aMetrics = cAppdynCore::GET_Metric_heirarchy($this->name, $sMetricPath,false); //dont cache
		if ($aMetrics) uasort($aMetrics,"Appd_name_sort_fn");
		cDebug::leave();
		return $aMetrics;
	}

	//*****************************************************************
	public function GET_HealthRules(){
		cDebug::enter();
		$sUrl = "/alerting/rest/v1/applications/$this->id/health-rules";
		$aData = cAppDynCore::GET($sUrl,true,false,false);
		cDebug::leave();
		return $aData;
	}
	
	//*****************************************************************
	public function GET_InfoPoints($poTimes){
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_AppInfoPoints(null);
		return cAppdynCore::GET_Metric_heirarchy($this->name,cAppDynMetric::INFORMATION_POINTS, false, $poTimes);
	}

	//*****************************************************************
	public function GET_Nodes(){
		cDebug::enter();
		
		$sID = $this->id;
		
		$aResponse = cAppDynCore::GET("$sID/nodes?",true);

		$aOutput = [];
		foreach ($aResponse as $oNode){
			$iMachineID = $oNode->machineId;
			if (!isset($aOutput[(string)$iMachineID])) $aOutput[(string)$iMachineID] = [];
			$aOutput[(string)$iMachineID][] = $oNode;
		}
		ksort($aOutput );
		
		cDebug::leave();
		return $aOutput;
	}

	//*****************************************************************
	public function GET_snaphot_info($psTransID, $poTimes){
		/*should use instead
		eg https://xxx.saas.appdynamics.com/controller/restui/snapshot/snapshotListDataWithFilterHandle		{"firstInChain":false,"maxRows":600,"applicationIds":[1424],"businessTransactionIds":[],"applicationComponentIds":[4561],"applicationComponentNodeIds":[],"errorIDs":[],"errorOccured":null,"userExperience":[],"executionTimeInMilis":null,"endToEndLatency":null,"url":null,"sessionId":null,"userPrincipalId":null,"dataCollectorFilter":null,"archived":null,"guids":[],"diagnosticSnapshot":null,"badRequest":null,"deepDivePolicy":[],"rangeSpecifier":{"type":"BEFORE_NOW","durationInMinutes":15}}		
		*/
		
		$sApp = rawurlencode($this->name);
		$sUrl = cHttp::build_url("$sApp/request-snapshots", cAppdynTime::make($poTimes));
		$sUrl = cHttp::build_url($sUrl, "application_name", $sApp);
		//$sUrl = cHttp::build_url($sUrl, "application-component-ids", $psTierID);
		$sUrl = cHttp::build_url($sUrl, "business-transaction-ids", $psTransID);
		$sUrl = cHttp::build_url($sUrl, "output", "JSON");
		return cAppDynCore::GET($sUrl);
	}
	
	//*****************************************************************
	public function GET_Tiers(){
		if ( cAppDyn::is_demo()) return cAppDynDemo::GET_Tiers($this);
		$sApp = rawurlencode($this->name);
		$aData = cAppdynCore::GET("$sApp/tiers?" );
		if ($aData) uasort($aData,"Appd_name_sort_fn");
		
		$aOutTiers = [];

		//convert to tier objects and populate the app
		foreach ($aData as $oInTier){
			$oOutTier = new cAppDTier($this, $oInTier->name, $oInTier->id);
			$aOutTiers[] = $oOutTier;
		}
		
		return $aOutTiers;
	}


	//*****************************************************************
	public function GET_Transactions(){		
		$sApp = rawurlencode($this->name);
		return cAppDynCore::GET("$sApp/business-transactions?" );
	}

}

?>
