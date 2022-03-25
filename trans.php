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
require_once("$ADlib/AD.php");

//#####################################################################
function sort_timetaken($a, $b){
	return (($a->timeTakenInMilliSecs<$b->timeTakenInMilliSecs?1:-1));
}

//#################################################################
//# 
//#################################################################
class cADTrans{
	public $name, $id, $tier;

   function __construct($poTier, $psName, $psID, $pbIDCanBeNull = false) {	
		if (!$poTier ) cDebug::error("must provide a tier");
		if (!$psName ) cDebug::error("must provide a name");
		if (!$psID && !$pbIDCanBeNull) cDebug::error("must provide an id");
		$this->tier = $poTier;
		$this->name = $psName; 
		$this->id = $psID;
   }
	
	//*****************************************************************
	public function GET_ExtTiers(){
		cDebug::enter();
		$sMetricPath= cADMetricPaths::transExtNames($this);
		$oApp = $this->tier->app;
		$aMetrics = $oApp->GET_Metric_heirarchy( $sMetricPath, false);
		cDebug::leave();
		return $aMetrics;
	}
	
	//*****************************************************************
	public function GET_snapshots($poTimes){
		/*should use instead
		eg https://xxx.saas.appdynamics.com/controller/restui/snapshot/snapshotListDataWithFilterHandle		{"firstInChain":false,"maxRows":600,"applicationIds":[xxx],"businessTransactionIds":[],"applicationComponentIds":[4561],"applicationComponentNodeIds":[],"errorIDs":[],"errorOccured":null,"userExperience":[],"executionTimeInMilis":null,"endToEndLatency":null,"url":null,"sessionId":null,"userPrincipalId":null,"dataCollectorFilter":null,"archived":null,"guids":[],"diagnosticSnapshot":null,"badRequest":null,"deepDivePolicy":[],"rangeSpecifier":{"type":"BEFORE_NOW","durationInMinutes":15}}		
		*/
		
		cDebug::enter();
		
		$oApp = $this->tier->app;
		$sApp = rawurlencode($oApp->name);
		$sUrl = cHttp::build_url("$sApp/request-snapshots", cADTime::make($poTimes));
		$sUrl = cHttp::build_url($sUrl, "application_name", $sApp);
		$sUrl = cHttp::build_url($sUrl, "business-transaction-ids", $this->id);
		$aOut = [];
		$aResults = cADCore::GET($sUrl);
		foreach ($aResults as $oItem){
			$oSnap = new cADSnapshot($this, $oItem->requestGUID, $oItem->serverStartTime);
			$oSnap->summary = $oItem->summary;
			$oSnap->applicationComponentNodeId = $oItem->applicationComponentNodeId;
			$oSnap->timeTakenInMilliSecs = $oItem->timeTakenInMilliSecs;
			$oSnap->url = ($oItem->URL?$oItem->URL:$this->name);
			
			$aOut[] = $oSnap;
		}
		cDebug::leave();
		return $aOut;
	}
	
	//*****************************************************************
	public function GET_top_10_snapshots($poTimes){
		cDebug::enter();
		
		$aSnapshots = $this->GET_snapshots($poTimes);
		usort($aSnapshots , "sort_timetaken");	
		$aTopTen = [];
		foreach ($aSnapshots as $oSnapshot){
			if (count($aTopTen) >=10) break;				
			$aTopTen[] = $oSnapshot;
		}
		cDebug::leave();
		return $aTopTen ;
	}
	
	
	//*****************************************************************
	public function GET_response_times($poTimes){
		cDebug::enter();
		
		$bOK = true;
		$oOut = null;
		
		//get all the times and filter out the one we want
		$sMetricpath = cADMetricPaths::transResponseTimes($this);
		try{
			$oApp = $this->tier->app;
			$aStats = $oApp->GET_MetricData($sMetricpath, $poTimes,true,false,true);
		}catch (Exception $e){
			cDebug::extra_debug("unable to retrieve transactions");
			$bOK = false;
			return null;
		}
		
		if ($bOK){
			$bFound = false;
			foreach ($aStats as $oTrans){
				$sName = cADUtil::extract_bt_name($oTrans->metricPath, $this->tier->name);
				if ($sName === $this->name){
					$bFound = true;
					$oOut = $oTrans;
					break;
				}
			}
			if (!$bFound){
				$bOK = false;
				cDebug::extra_debug("unable to locate transaction named: $this->name");
			}
		}
		
		cDebug::leave();
		return  $oOut;
	}
	
	//*****************************************************************
	public function populate_ID( $poTimes){
		cDebug::enter();
		
		$sID = null;
		if ($this->id) 
			cDebug::extra_debug("there is allready an ID");
		else{
			$oTrans = new cADTrans($this->tier, $this->name, null, true);
			$oResponse = $oTrans->GET_response_times($poTimes);
			if ($oResponse)
				if (count($oResponse->metricValues) >0){
					$sID = cADUtil::extract_bt_id($oResponse->metricName);
					cDebug::extra_debug("got transaction id: $sID");
					$this->id = $sID;
				}
				
			if (! $this->id){
				cDebug::extra_debug("unable to get transaction id");
				cDebug::vardump($oResponse);
			}
		}
		cDebug::leave();
	}
	

}
?>
