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

class cADTier{
   public static $db_app = null;
   public $name = null, $id = null, $app = null;
	//##############################################################################
   function __construct($poApp, $psTierName, $psTierId) {	
		if ($poApp == null) cDebug::error("no App provided");
		$this->app = $poApp;
		
		if (!$psTierName  && !$psTierId) 
			cDebug::error("no tier details provided");
		
		if ($psTierName){
			$this->name = $psTierName; 
			$this->pr_get_tier_id();
		}else{
			$this->id = $psTierId;
			$this->pr_get_tier_name();
		}
   }
   
	//##############################################################################
	private function pr_get_tier_name(){
		cDebug::enter();
		$aTiers = $this->app->GET_raw_tiers(); 
		cDebug::leave();
	}
	
	private function pr_get_tier_id(){
		cDebug::enter();
		$aTiers = $this->app->GET_raw_tiers();
		$sID = null;
		$sName = strtolower($this->name);
		
		foreach ($aTiers as $oTier)
			if  (strtolower($oTier->name) == $sName)
				$sID = $oTier->id;
			
		if ($sID)
			$this->id = $sID;
		else	
			cDebug::error("tier name $sName not found");
		
		cDebug::leave();
		return $sID;
	}
	
	//##############################################################################
	public function GET_DiskMetrics(){
		cDebug::enter();
		$sMetricpath=cADMetric::InfrastructureNodeDisks($this->name, null);
		$aData = $this->app->GET_Metric_heirarchy($sMetricpath, true);
		
		$aOut = [];
		foreach ($aData as $oEntry)
			if ($oEntry->type === "leaf") $aOut[] = $oEntry;
		
		uasort($aOut, 'AD_name_sort_fn');
		cDebug::leave();
		return  $aOut;
	}
	
	
	//*****************************************************************
	public  function GET_errors($poTimes){
		$sMetricpath = cADMetric::Errors($this->name, "*");
		$aData = $this->app->GET_MetricData($sMetricpath, $poTimes,"true",false,true);
		return $aData;
	}
	
	//*****************************************************************
	public  function GET_ext_details($poTimes){
		global $aResults;
		$sApp = $this->app->name;
		$sTier = $this->name;
		
		cDebug::write("<h3>getting details for $sTier</h3>");
		//first get the metric heirarchy
		cADUtil::flushprint(".");
		$oHeirarchy = $this->GET_ext_calls();
			
		//get the transaction IDs TBD
		$trid=1;
		
		//for each row in the browser get external calls per minute
		$aResults = array();
		foreach ($oHeirarchy as $row){
			cADUtil::flushprint(".");
			$sOtherTier=$row->name;
			
			cDebug::write("<h4>other tier is $sOtherTier</h4>");
			cDebug::write("<b>Calls per min</b>");
			$oCalls = null;
			$oData = $this->GET_ExtCallsPerMin( $sOtherTier, $poTimes, "true");
			if ($oData)	$oCalls = cADUtil::Analyse_Metrics( $oData);
				
			cDebug::write("<b>response times</b>");
			$oTimes = null;
			$oData = $this->GET_ExtResponseTimes($sOtherTier, $poTimes, "true");
			if ($oData)	
				$oTimes = cADUtil::Analyse_Metrics( $oData);
			
			cDebug::write("<b>done</b>");
			
			$oDetails = new cADDetails($sOtherTier, $trid, $oCalls,  $oTimes);

			array_push($aResults, $oDetails);
		}
		
		return $aResults;
	}
	
	//*****************************************************************
   	public function GET_ext_calls(){
		$sTier = $this->name;
		cDebug::enter();
			$metricPath = "Overall Application Performance|$sTier|External Calls";
			$aData = $this->app->GET_Metric_heirarchy($metricPath, false);
			uasort ($aData, "AD_name_sort_fn");
		cDebug::leave();
		return $aData;
	}

	//*****************************************************************
	public  function GET_ExtCallsPerMin($psTier2, $poTimes, $psRollup){
		$sMetricpath= cADMetric::tierExtCallsPerMin($this->name, $psTier2);
		return $this->app->GET_MetricData($sMetricpath, $poTimes, $psRollup);
	}	

	//*****************************************************************
	public function GET_ExtResponseTimes($psTier2, $poTimes, $psRollup){
		$sMetricpath= cADMetric::tierExtResponseTimes($this->name, $psTier2);
		return $this->app->GET_MetricData($sMetricpath, $poTimes, $psRollup);
	}
	
	//*****************************************************************
	public function GET_JDBC_Pools($psNode=null){
		cDebug::enter();
		$sMetricpath=cADMetric::InfrastructureJDBCPools($this->name, $psNode);
		$oData = $this->app->GET_Metric_heirarchy($sMetricpath, false);
		cDebug::leave();
		return  $oData;
	}
	
	//*****************************************************************
	public function GET_Nodes(){
		cDebug::enter();
		$sMetricpath=cADMetric::InfrastructureNodes($this->name);
		$aData = $this->app->GET_Metric_heirarchy($sMetricpath, false);
		uasort($aData, 'AD_name_sort_fn');
		cDebug::leave();
		return  $aData;
	}
	
	//*****************************************************************
	public function GET_NodeDisks($psNode){
		cDebug::enter();
		$sMetricpath=cADMetric::InfrastructureNodeDisks($this->name, $psNode);
		$aData = $this->app->GET_Metric_heirarchy($sMetricpath, true);
		
		$aOut = [];
		foreach ($aData as $oEntry)
			if ($oEntry->type === "folder") $aOut[] = $oEntry;
		
		uasort($aOut, 'AD_name_sort_fn');
		cDebug::leave();
		return  $aOut;
	}

	//*****************************************************************
	public  function GET_ServiceEndPoints(){
		if ( cAD::is_demo()) return cADDemo::GET_TierServiceEndPoints(null,null);
		$sMetricpath= cADMetric::tierServiceEndPoints($this->name);
		$oData = $this->app->GET_Metric_heirarchy($sMetricpath, false);
		return $oData;
	}

	//*****************************************************************
	public function GET_transaction_names(){
		//find out the transactions in this tier - through metric heirarchy (but doesnt give the trans IDs)
		cDebug::enter();
		$aResults = []; 
		
		try{
			$metricPath = cADMetric::tierTransactions($this->name);
			$aTierTransactions = $this->app->GET_Metric_heirarchy($metricPath, false);	
			if (!$aTierTransactions) return null;
			
			//so get the transaction IDs
			$aAppTrans= cADUtil::get_trans_assoc_array($this->app);
			
			// and combine the two

			foreach ($aTierTransactions as $oTierTrans){
				if (!isset($aAppTrans[$oTierTrans->name])) continue;
				
				$sTransID = $aAppTrans[$oTierTrans->name];
				$oDetail = new cADDetails($oTierTrans->name, $sTransID, null, null);
				$aResults[] = $oDetail;
			}
			
			uasort($aResults, 'AD_name_sort_fn');
		}
		catch (Exception $e){
			$aResults = null;
		}
		cDebug::leave();
		return $aResults;
	}

	
}
?>
