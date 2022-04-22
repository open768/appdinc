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
require_once("$ADlib/AD.php");



//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//%
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function bt_config_sort_function($po1,$po2){
	$p1=str_pad($po1->rule->priority,3,"0", STR_PAD_LEFT);
	$p2=str_pad($po2->rule->priority,3,"0", STR_PAD_LEFT);
	$s1 = $p1." ".$po1->rule->summary->name;
	$s2 = $p2." ".$po2->rule->summary->name;
	return strcasecmp ($s2,$s1);
}

//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
//%
//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
class cADApp{	
	public static $server_app = null;
	public $name = null, $id = null;
	const NO_APP = "*noapp";
	const CALLS_DATA = "calls";
	
	function __construct($psAppName, $psAppId=null) {	
		if (!$psAppName  && !$psAppId) cDebug::error("no app details provided");
		if ($psAppName === self::NO_APP) return;

		if ($psAppName){
			$this->name = $psAppName;
			if (!$psAppId)
				$this->pr__get_id();
			else
				$this->id = $psAppId;
		}else{
			$this->id = $psAppId;
			$this->name = $this->pr__get_name();
		}
	}
   
	//#################################################################
	private function pr__get_id(){
		$aApps = cADController::GET_all_Applications();
		$sID = null;
		
		switch($this->name){
			case cADCore::DATABASE_APPLICATION:
			case cADCore::SERVER_APPLICATION:
				$sID = $this->name;
				break;
			default:
				$sLower = strtolower($this->name);
				foreach ($aApps as $oApp)
					if  (strtolower($oApp->name) === $sLower){
						$sID = $oApp->id;
						break;
					}
		}
		
		if ($sID == null)
			cDebug::error("unable to find application id with name: $sLower");
		else
		$this->id = $sID;
		
		return $sID;
	}
	//*****************************************************************
	private function pr__get_name(){
		$aApps = cADController::GET_all_Applications();
		$sName = null;
		
		foreach ($aApps as $oApp){
			if  ($oApp->id == $this->id){
				$sName = $oApp->name;
				break;
			}
		}
		
		if ($sName == null)	
			cDebug::error("unable to find application name with ID: $this->id");
		else
			$this->name = $sName;
	
		return $sName;
	}
	
	//#################################################################
	//#################################################################
	public function checkup($poTimes, $psCheckOnly=null){
		cDebug::enter();
		$oOut =  cADAppCheckup::checkup($this, $poTimes, $psCheckOnly);
		cDebug::leave();
		return $oOut;
	}
	
	//*****************************************************************
	public function is_active(){
		$oTimes = new cADTimes();
		$oTimes->set_duration_hrs(6);
		$aCallsPerMin = $this->GET_CallsPerMin($oTimes);
		return (count($aCallsPerMin)>0);
	}
	
	
	//*****************************************************************
	public function GET_Backends(){
		cDebug::enter();
		if ( cAD::is_demo()) return cADDemo::GET_Backends(null);
		$oAuth = new cADCredentials;
		if ($oAuth->is_logged_in){
			$sMetricpath= cADMetricPaths::backends();
			$aData = cADMetricData::GET_Metric_heirarchy($this,$sMetricpath, false); //dont cache
		}else
			$aData = cADRestUI::get_app_backends($this);
		usort( $aData, "AD_name_sort_fn");
		cDebug::leave();
		return $aData;
	}

	//*****************************************************************
	public function GET_CallsPerMin($poTimes){
		cDebug::enter();
		$oCred = new cADCredentials;
		if ($oCred->is_logged_in){
			$sMetric = cADAppMetrics::appCallsPerMin();
			$aData = cADMetricData::GET_MetricData($this,$sMetric, $poTimes,true);
		}else
			$aData = cADRestUI::pr__do_get_applications_from_ids([$this->id]);
		cDebug::leave();
		return $aData;
	}
	
	//*****************************************************************
	public function GET_data_collectors(){
		cDebug::enter();
		$aData = cADRestUI::get_app_data_collectors($this);
		usort($aData, "AD_name_sort_fn");
		cDebug::leave();
		return $aData;
	}
	
	//*****************************************************************
	public function GET_diagnostic_stats(){
		cDebug::enter();
		$aData = cADRestUI::get_app_diagnostic_stats($this);
		$aOut = cADAnalysis::analyse_app_diagnostic_stats($aData);
		cDebug::leave();
		return $aOut;
	}

	//*****************************************************************
	public function GET_Events($poTimes, $psEventType = cADCore::ALL_EVENT_TYPES){
		$sApp = rawurlencode($this->name);
		$sTimeQs = cADTime::make($poTimes);
		
		$sEventsUrl = cHttp::build_url("$sApp/events", "severities", cADCore::ALL_SEVERITIES);
		$sEventsUrl = cHttp::build_url($sEventsUrl, "Output", "JSON");
		$sEventsUrl = cHttp::build_url($sEventsUrl, "event-types", $psEventType);
		$sEventsUrl = cHttp::build_url($sEventsUrl, $sTimeQs);
		return cADCore::GET($sEventsUrl );
	}
	

	//*****************************************************************
	public function GET_ExtTiers(){
		if ( cAD::is_demo()) return cADDemo::GET_AppExtTiers(null);
		cDebug::enter();
		$sMetricPath= cADAppMetrics::appBackends();
		$aMetrics = cADMetricData::GET_Metric_heirarchy($this,$sMetricPath,false); //dont cache
		if ($aMetrics) usort($aMetrics,"AD_name_sort_fn");
		cDebug::leave();
		return $aMetrics;
	}

	//*****************************************************************
	public function GET_flowmap(){
		cDebug::enter();
		$oData = cADRestUI::GET_app_flowmap($this);
		cDebug::leave();
		
		return $oData;
	}
	
	//*****************************************************************
	public function GET_HealthRules(){
		cDebug::enter();

		$sUrl = "/alerting/rest/v1/applications/$this->id/health-rules";
		$aData = cADCore::GET($sUrl,true,false,false);
		if ($aData) usort($aData, "AD_name_sort_fn");
		cDebug::leave();
		
		return $aData;
	}
	
	//*****************************************************************
	public function GET_HealthRuleDetail($piRuleID){
		cDebug::enter();

		$sUrl = "/alerting/rest/v1/applications/$this->id/health-rules/$piRuleID";	
		$oData = cADCore::GET($sUrl,true,false,false);
		cDebug::leave();
		
		return $oData;
	}

	
	//*****************************************************************
	public function GET_InfoPoints($poTimes){
		if ( cAD::is_demo()) return cADDemo::GET_AppInfoPoints(null);
		return cADMetricData::GET_Metric_heirarchy($this, cADMetricPaths::INFORMATION_POINTS, false, $poTimes);
	}

	//*****************************************************************
	//this function belongs elsewhere as it is common to AD objects
	public function GET_MetricData($psMetricPath, $poTimes , $pbRollup=false, $pbCacheable=false, $pbMulti = false)
	{
		cDebug::enter();
		$aOutput = cADMetricData::GET_MetricData($this,$psMetricPath, $poTimes , $pbRollup, $pbCacheable, $pbMulti);
		cDebug::leave();
		return $aOutput;		
	}
	
	//*****************************************************************
	//this function belongs elsewhere as it is common to AD objects
	public function GET_Metric_heirarchy($psMetricPath, $pbCached=true, $poTimes = null)
	{
		cDebug::enter();
		$oData = cADMetricData::GET_Metric_heirarchy($this,$psMetricPath, $pbCached, $poTimes );
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
	public function GET_ServiceEndPoints(){	
		cDebug::enter();
		$oData = cADRestUI::GET_service_end_points($this);
		cDebug::leave();
		return $oData;
	}

	//*****************************************************************
	//add this function as otherwise exponential circular calls  when instaiating applications
	public function GET_raw_tiers(){
		if ( cAD::is_demo()) return cADDemo::GET_Tiers($this);
		$sApp = rawurlencode($this->name);
		try{
			$aData = cADCore::GET("$sApp/tiers?",true );
		}catch (Exception $e){
			$aData = cADCore::GET("$this->id/tiers?",true );
		}
		return $aData; 
	}
	
	//*****************************************************************
	public function GET_Tiers(){
		cDebug::enter();
		$aData = $this->GET_raw_tiers();
		if ($aData) usort($aData,"AD_name_sort_fn");
		
		$aOutTiers = [];

		//convert to tier objects and populate the app
		foreach ($aData as $oInTier)
			if (strtolower($oInTier->name) !== "machine agent"){
				$oOutTier = new cADTier($this, $oInTier->name, $oInTier->id);
				$aOutTiers[] = $oOutTier;
			}
				
		cDebug::leave();
		return $aOutTiers;
	}


	//*****************************************************************
	//* transactions
	//*****************************************************************
	public function GET_BTs(){		
		cDebug::enter();
		$oAuth = new cADCredentials();
		if ($oAuth->is_logged_in){
			$sApp = rawurlencode($this->name);
			$aData =cADCore::GET("$sApp/business-transactions?" );
		}else{
			$oTimes = new cADTimes;
			$aData = $this->GET_BT_Calls($oTimes);
		}
		cDebug::leave();
		
		return $aData;
		
	}
	//*****************************************************************
	public function GET_BT_Calls($poTimes){
		cDebug::enter();
		$aData = cADRestUI::get_app_BT_Summary($this, $poTimes);
		//cDebug::vardump($aData);
		$aOut = [];
		foreach ($aData as $oItem){
			if ($oItem->componentId){
				$oTier = new cAdTier($this, $oItem->applicationComponentName, $oItem->componentId);
				$oTrans = new cADBT($oTier, $oItem->name, $oItem->id);
				$oTrans->data[ self::CALLS_DATA] = $oItem->numberOfCalls;
				$aOut[] = $oTrans;
			}
		}
		cDebug::leave();
		return $aOut;
	}
	
	//*****************************************************************
	public function GET_AppLevel_BT_Detection_Config(){
		cDebug::enter();
		$oData = cADRestUI::GET_appLevel_BT_Config($this);
		cDebug::leave();
		return $oData;
	}
	
	//*****************************************************************
	public function GET_app_BT_configs(){		
		cDebug::enter();
		$oData = cADRestUI::GET_app_BT_configs($this);
		$aData = [];
		if ($oData && property_exists($oData,"ruleScopeSummaryMappings")){
			$aData = $oData->ruleScopeSummaryMappings;
			usort($aData,"bt_config_sort_function" );
		}else
			cDebug::extra_debug("unable to get BT configs");
		cDebug::leave();
		return $aData;
	}

}
cADApp::$server_app = new cADApp(cADCore::SERVER_APPLICATION,cADCore::SERVER_APPLICATION);

?>
