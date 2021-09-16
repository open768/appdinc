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
require_once("$ADlib/appdynamics.php");

//#################################################################
//# 
//#################################################################
class cADApp{
	public static $db_app = null;
	public static $server_app = null;
	
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
		$aApps = cADController::GET_Applications();
		$sID = null;
		
		foreach ($aApps as $oApp){
			if  ($oApp->name === $this->name){
				$sID = $oApp->id;
				break;
			}
		}
		
		if ($sID !== null)	$this->id = $sID;
		return $sID;
	}
	
	//*****************************************************************
	public function GET_Backends(){
		if ( cAD::is_demo()) return cADDemo::GET_Backends(null);
		$sMetricpath= cADMetric::backends();
		return $this->GET_Metric_heirarchy($sMetricpath, false); //dont cache
	}

	//*****************************************************************
	//see events reference at https://docs.appdynamics.com/display/PRO14S/Events+Reference
	/*
	but the UI uses restui https://xxxx.saas.appdynamics.com/controller/restui/events/query
	//with payload:	//{"queryCursor":{"timeRange":{"type":"BEFORE_NOW","durationInMinutes":60}},"eventStreamItemFilter":{"applicationIds":[354],"policyViolationStartedWarning":true,"policyViolationStartedCritical":true,"machineLearningStartedWarning":true,"machineLearningStartedCritical":true,"codeDeadlock":true,"resourcePoolLimit":true,"applicationDeployment":true,"appServerRestart":true,"appConfigChange":true,"applicationCrash":true,"clrCrash":true,"license":true,"controllerDiskSpaceLow":true,"agentVersionNewerThanController":true,"agentConfigurationError":true,"controllerMetricRegistrationLimitReached":true,"agentMetricRegistrationLimitReached":true,"devModeConfigUpdate":true,"syntheticAvailabilityHealthy":true,"syntheticAvailabilityWarning":true,"syntheticAvailabilityConfirmedWarning":true,"syntheticAvailabilityOngoingWarning":true,"syntheticAvailabilityError":true,"syntheticAvailabilityConfirmedError":true,"syntheticAvailabilityOngoingError":true,"syntheticPerformanceHealthy":true,"syntheticPerformanceWarning":true,"syntheticPerformanceConfirmedWarning":true,"syntheticPerformanceOngoingWarning":true,"syntheticPerformanceCritical":true,"syntheticPerformanceConfirmedCritical":true,"syntheticPerformanceOngoingCritical":true,"mobileNewCrash":true,"customEventFilters":[],"networkIncluded":true,"clusterEvents":false,"businessTransactionIds":[],"applicationComponentIds":[],"applicationComponentNodeIds":[],"timeRange":{"type":"BEFORE_NOW","durationInMinutes":60}}}
	*/
	public function GET_Events($poTimes, $psEventType = null){
		$sApp = rawurlencode($this->name);
		$sTimeQs = cADTime::make($poTimes);
		if ($psEventType== null) $psEventType = cADCore::ALL_EVENT_TYPES;
		$sSeverities = cADCore::ALL_SEVERITIES;
		
		$sEventsUrl = cHttp::build_url("$sApp/events", "severities", $sSeverities);
		$sEventsUrl = cHttp::build_url($sEventsUrl, "Output", "JSON");
		$sEventsUrl = cHttp::build_url($sEventsUrl, "event-types", $psEventType);
		$sEventsUrl = cHttp::build_url($sEventsUrl, $sTimeQs);
		return cADCore::GET($sEventsUrl );
	}
	

	//*****************************************************************
	public function GET_ExtTiers(){
		if ( cAD::is_demo()) return cADDemo::GET_AppExtTiers(null);
		cDebug::enter();
		$sMetricPath= cADMetric::appBackends();
		$aMetrics = $this->GET_Metric_heirarchy($sMetricPath,false); //dont cache
		if ($aMetrics) uasort($aMetrics,"AD_name_sort_fn");
		cDebug::leave();
		return $aMetrics;
	}

	//*****************************************************************
	public function GET_HealthRules(){
		cDebug::enter();
		$sUrl = "/alerting/rest/v1/applications/$this->id/health-rules";
		$aData = cADCore::GET($sUrl,true,false,false);
		cDebug::leave();
		return $aData;
	}
	
	//*****************************************************************
	public function GET_InfoPoints($poTimes){
		if ( cAD::is_demo()) return cADDemo::GET_AppInfoPoints(null);
		return $this->GET_Metric_heirarchy(cADMetric::INFORMATION_POINTS, false, $poTimes);
	}

	//*****************************************************************
	public function GET_MetricData($psMetricPath, $poTimes , $psRollup="false", $pbCacheable=false, $pbMulti = false)
	{
		cDebug::enter();
		if ($poTimes == null) cDebug::error("times are missing");
		$sApp = $this->name;
		
		$sRangeType = "";
		$sTimeCmd=cADTime::make($poTimes);
		
		$encoded = rawurlencode($psMetricPath);
		$encoded = str_replace(rawurlencode("*"),"*",$encoded);
		
		if ($sApp === cADCore::SERVER_APPLICATION)
			$sApp = cADCore::ENCODED_SERVER_APPLICATION;		//special case
		else
			$sApp = rawurlencode($sApp);
		
		$url = "$sApp/metric-data?metric-path=$encoded&$sTimeCmd&rollup=$psRollup";
		$oData = cADCore::GET( $url ,$pbCacheable);
		
		$aOutput = $oData;
		if (!$pbMulti && (count($oData) >0)) $aOutput = $oData[0]->metricValues; //watch out this will knobble the data
		
		cDebug::leave();
		return $aOutput;		
	}
	
	//*****************************************************************
	public function GET_Metric_heirarchy($psMetricPath, $pbCached=true, $poTimes = null)
	{
		cDebug::enter();
		cDebug::extra_debug("get Heirarchy: $psMetricPath");
		$encoded=rawurlencode($psMetricPath);	
		$encoded = str_replace("%2A","*",$encoded);			//decode wildcards
		
		if ($this->name === cADCore::SERVER_APPLICATION)
			$sApp = cADCore::ENCODED_SERVER_APPLICATION;		//special case
		else
			$sApp = rawurlencode($this->name);

		$sCommand = "$sApp/metrics?metric-path=$encoded";
		if ($poTimes !== null){
			if ( $poTimes === cADCore::BEFORE_NOW_TIME)
				$sTimeCmd=cADTime::beforenow();
			else
				$sTimeCmd=cADTime::make($poTimes);
			$sCommand .= "&$sTimeCmd";
		}
		
		$oData = cADCore::GET($sCommand, $pbCached);
		cDebug::extra_debug("count of rows: ".count($oData));
		cDebug::leave();
		return $oData;
	}
	
	//*****************************************************************
	public function GET_Nodes(){
		cDebug::enter();
		
		$sID = $this->id;
		
		$aResponse = cADCore::GET("$sID/nodes?",true);

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
		$sUrl = cHttp::build_url("$sApp/request-snapshots", cADTime::make($poTimes));
		$sUrl = cHttp::build_url($sUrl, "application_name", $sApp);
		//$sUrl = cHttp::build_url($sUrl, "application-component-ids", $psTierID);
		$sUrl = cHttp::build_url($sUrl, "business-transaction-ids", $psTransID);
		$sUrl = cHttp::build_url($sUrl, "output", "JSON");
		return cADCore::GET($sUrl);
	}
	
	//*****************************************************************
	public function GET_Tiers(){
		if ( cAD::is_demo()) return cADDemo::GET_Tiers($this);
		$sApp = rawurlencode($this->name);
		$aData = cADCore::GET("$sApp/tiers?" );
		if ($aData) uasort($aData,"AD_name_sort_fn");
		
		$aOutTiers = [];

		//convert to tier objects and populate the app
		foreach ($aData as $oInTier){
			$oOutTier = new cADTier($this, $oInTier->name, $oInTier->id);
			$aOutTiers[] = $oOutTier;
		}
		
		return $aOutTiers;
	}


	//*****************************************************************
	public function GET_Transactions(){		
		$sApp = rawurlencode($this->name);
		return cADCore::GET("$sApp/business-transactions?" );
	}

}

cADApp::$db_app = new cADApp(cADCore::DATABASE_APPLICATION,cADCore::DATABASE_APPLICATION);
cADApp::$server_app = new cADApp(cADCore::SERVER_APPLICATION,cADCore::SERVER_APPLICATION);

?>
