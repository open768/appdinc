<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//see 
require_once("$ADlib/AD.php");

//#################################################################
//# 
//#################################################################
class cADTrans{
	public $name, $id, $tier;

   function __construct($poTier, $psName, $psID) {	
		if (!$poTier ) cDebug::error("must provide a tier");
		if (!$psName ) cDebug::error("must provide a name");
		if (!$psID ) cDebug::error("must provide an id");
		$this->tier = $poTier;
		$this->name = $psName; 
		$this->id = $psID;
   }
	
	//*****************************************************************
	public function GET_ExtTiers(){
		$sMetricPath= cADMetric::transExtNames($this->tier->name,$this->name);
		return $this->tier->app->GET_Metric_heirarchy( $sMetricPath, false);
	}
	
	//*****************************************************************
	public function GET_snapshots($poTimes){
		/*should use instead
		eg https://xxx.saas.appdynamics.com/controller/restui/snapshot/snapshotListDataWithFilterHandle		{"firstInChain":false,"maxRows":600,"applicationIds":[1424],"businessTransactionIds":[],"applicationComponentIds":[4561],"applicationComponentNodeIds":[],"errorIDs":[],"errorOccured":null,"userExperience":[],"executionTimeInMilis":null,"endToEndLatency":null,"url":null,"sessionId":null,"userPrincipalId":null,"dataCollectorFilter":null,"archived":null,"guids":[],"diagnosticSnapshot":null,"badRequest":null,"deepDivePolicy":[],"rangeSpecifier":{"type":"BEFORE_NOW","durationInMinutes":15}}		
		*/
		
		$oApp = $this->tier->app;
		$sApp = rawurlencode($oApp->name);
		$sUrl = cHttp::build_url("$sApp/request-snapshots", cADTime::make($poTimes));
		$sUrl = cHttp::build_url($sUrl, "application_name", $sApp);
		//$sUrl = cHttp::build_url($sUrl, "application-component-ids", $psTierID);
		$sUrl = cHttp::build_url($sUrl, "business-transaction-ids", $this->id);
		//$sUrl = cHttp::build_url($sUrl, "output", "JSON");
		return cADCore::GET($sUrl);
	}
	
	//*****************************************************************
	public function GET_snapshot_segments($sSnapGUID, $sSnapTime){
		return cADRestUI::GET_snapshot_segments($sSnapGUID, $sSnapTime);	
	}
}
?>
