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
require_once("$ADlib/util.php");
class cADTierTransResult{
	public $name, $max, $id, $url, $avg, $count;
}
function trans_name_sort_fn($po1, $po2){
	return strcasecmp ($po1->name, $po2->name);
}
class cADOverflowTraffic{
	public $name;
	public $count;
}

class cADTier{
   public static $db_app = null;
   public $name = null, $id = null, $app = null;
	//##############################################################################
   function __construct($poApp, $psTierName, $psTierId) {	
		if ($poApp == null) cDebug::error("no App provided");
		$this->app = $poApp;
		
		if ($psTierName && $psTierId){
			$this->name = $psTierName; 
			$this->id = $psTierId;
		}elseif ($psTierName){
			$this->name = $psTierName; 
			$this->pr_get_tier_id();
		}elseif ($psTierId){
			$this->id = $psTierId;
			$this->pr_get_tier_name();
		}else
			cDebug::error("no tier details provided");
   }
   
	//##############################################################################
	private function pr_get_tier_name(){
		cDebug::enter();
		$aTiers = $this->app->GET_raw_tiers(); 
		foreach ($aTiers as $oTier)
			if ($oTier->id == $this->id){
				$this->name = $oTier->name;
				return;
			}
			
		cDebug::error("Tier ID doesnt match");
		cDebug::leave();
	}
	
	private function pr_get_tier_id(){
		//cDebug::enter();
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
		
		//cDebug::leave();
		return $sID;
	}
	
	//##############################################################################
	public function GET_All_App_Agent_availability($poTimes, $psNode, $pbRollup=true){
		$sMetricpath=cADInfraMetric::InfrastructureAppAgentAvailability($this->name, $psNode);
		$aData = $this->app->GET_MetricData($sMetricpath, $poTimes,$pbRollup,true,true);
		return  $aData;
	}
	
	//*****************************************************************
	public function GET_Inactive_App_Agents($poTimes){
		cDebug::enter();
		
		$aNodeNames = [];
		$aAvailData = $this->GET_All_App_Agent_availability($poTimes, "*"); //get availability for all nodes in the tier
		if (!cArrayUtil::array_is_empty($aAvailData)){
			cDebug::extra_debug("total app agents found for: $this->name = ". count($aAvailData));
			foreach ($aAvailData as $oItem){ //loop through nodes
				$bInactive=true;
				if (count($oItem->metricValues) > 0){
					$iSum = $oItem->metricValues[0]->sum;
					$bInactive = ($iSum == 0); //only historical if the sum is 0
				}
				
				if ($bInactive){
					$sNode = cAdUtil::extract_node_name($oItem->metricPath);
					$aNodeNames[] = $sNode;
				}
			}
		}
		
		$iCount = count($aNodeNames);
		$aNodes = [];
		if ($iCount == 0)
			cDebug::extra_debug("no inactive nodes found");
		else{
			cDebug::extra_debug(" $iCount inactive app agents found");
			$aNodes = cADUtil::get_nodes_from_names($this->app, $aNodeNames);
		}
		
		cDebug::leave();
		return $aNodes;
	}
		
	//*****************************************************************
	public function GET_DiskMetrics(){
		cDebug::enter();
		$sMetricpath=cADInfraMetric::InfrastructureNodeDisks($this->name, null);
		$aData = $this->app->GET_Metric_heirarchy($sMetricpath, true);
		
		$aOut = [];
		foreach ($aData as $oEntry)
			if ($oEntry->type === "leaf") $aOut[] = $oEntry;
		
		usort($aOut, "AD_name_sort_fn");
		cDebug::leave();
		return  $aOut;
	}
	
	
	//*****************************************************************
	public  function GET_errors($poTimes){
		$sMetricpath = cADMetricPaths::Errors($this->name, "*");
		$aData = $this->app->GET_MetricData($sMetricpath, $poTimes,true,false,true);
		return $aData;
	}
	
	//*****************************************************************
	//TODO this function shoud be removed as it takes a very long time to execute
	//caller has to be asynchronous
	public  function GET_ext_details($poTimes){
		global $aResults;
		$sApp = $this->app->name;
		$sTier = $this->name;
		
		cDebug::write("<h3>getting details for $sTier</h3>");
		//first get the metric heirarchy
		cADUtil::flushprint(".");
		$oHeirarchy = $this->GET_ext_calls();
			
		//get the transaction IDs TODO
		$trid=1;
		
		//for each row in the browser get external calls per minute
		$aResults = array();
		foreach ($oHeirarchy as $row){
			cADUtil::flushprint(".");
			$sOtherTier=$row->name;
			
			cDebug::write("<h4>other tier is $sOtherTier</h4>");
			cDebug::write("<b>Calls per min</b>");
			$oCalls = null;
			$oData = $this->GET_ExtCallsPerMin( $sOtherTier, $poTimes, true);
			if ($oData)	$oCalls = cADAnalysis::analyse_metrics( $oData);
				
			cDebug::write("<b>response times</b>");
			$oTimes = null;
			$oData = $this->GET_ExtResponseTimes($sOtherTier, $poTimes, true);
			if ($oData)	
				$oTimes = cADAnalysis::analyse_metrics( $oData);
			
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
			cDebug::extra_debug("Getting external calls for tier $this->name");
			$sMetricPath = cADTierMetricPaths::extCalls($this->name); //doesntwork for 4.5!!
			$aData = $this->app->GET_Metric_heirarchy($sMetricPath, true);
			if (count($aData) > 0)
				usort ($aData, "AD_name_sort_fn");
			else{	
				$aData  =[];
				cDebug::extra_debug("nothing found - trying a different approach"); //for 4.5
				$sMetricPath = cADTierMetricPaths::threadTasks($this->name); //old!!
				$aTasks = $this->app->GET_Metric_heirarchy($sMetricPath, true);
				foreach ($aTasks as $oTask)
					if ( preg_match("/_Exit$/", $oTask->name) || ($oTask->name === "AsyncRun")){
						$sMetricPath = cADTierMetricPaths::threadTaskExtCallNames($this->name,$oTask->name); 
						$aExt = $this->app->GET_Metric_heirarchy($sMetricPath, true);
						foreach ($aExt as $oExt){
							$oExt->name = cADTierMetricPaths::threadTaskExtCallName($this->name,$oTask->name,$oExt->name);
							$aData[] = $oExt;
						}
					}
			}
		//cDebug::vardump($aData);
		cDebug::leave();
		return $aData;
	}

	//*****************************************************************
	public  function GET_ExtCallsPerMin($psTier2, $poTimes, $pbRollup){
		$sMetricpath= cADTierMetricPaths::toTierCallsPerMin($this->name, $psTier2);
		return $this->app->GET_MetricData($sMetricpath, $poTimes, $pbRollup);
	}	

	//*****************************************************************
	public function GET_ExtResponseTimes($psTier2, $poTimes, $pbRollup){
		$sMetricpath= cADTierMetricPaths::toTierResponseTimes($this->name, $psTier2);
		return $this->app->GET_MetricData($sMetricpath, $poTimes, $pbRollup);
	}
	
	//*****************************************************************
	public function GET_JDBC_Pools($psNode=null){
		cDebug::enter();
		$sMetricpath=cADInfraMetric::InfrastructureJDBCPools($this->name, $psNode);
		$oData = $this->app->GET_Metric_heirarchy($sMetricpath, false);
		cDebug::leave();
		return  $oData;
	}
	
	//*****************************************************************
	public function GET_Nodes(){
		cDebug::enter();
		$sMetricpath=cADInfraMetric::InfrastructureNodes($this->name);
		$aData = $this->app->GET_Metric_heirarchy($sMetricpath, false);
		usort($aData, "AD_name_sort_fn");
		cDebug::leave();
		return  $aData;
	}
	
	//*****************************************************************
	public function GET_NodeDisks($psNode){
		cDebug::enter();
		$sMetricpath=cADInfraMetric::InfrastructureNodeDisks($this->name, $psNode);
		$aData = $this->app->GET_Metric_heirarchy($sMetricpath, true);
		
		$aOut = [];
		foreach ($aData as $oEntry)
			if ($oEntry->type === "folder") $aOut[] = $oEntry;
		
		usort($aOut, "AD_name_sort_fn");
		cDebug::leave();
		return  $aOut;
	}


	//*****************************************************************
	public  function GET_ServiceEndPoints(){
		return cADRestUI::GET_Tier_service_end_points($this);
	}


	
	//*****************************************************************
	public function GET_all_transaction_names(){
		//find out the transactions in this tier - through metric heirarchy (but doesnt give the trans IDs)
		cDebug::enter();
		$aResults = []; 
		
		try{
			$sMetricPath = cADTierMetricPaths::tierTransactions($this->name);
			$aTierTransactions = $this->app->GET_Metric_heirarchy($sMetricPath, false);	
			if (!$aTierTransactions) return null;
			
			//so get the transaction IDs
			$aAppTrans= cADUtil::get_trans_assoc_array($this->app);
			
			// and combine the two

			foreach ($aTierTransactions as $oTierTrans){
				if (!isset($aAppTrans[$oTierTrans->name])) continue;
				
				$sTransID = $aAppTrans[$oTierTrans->name];
				$oDetail = new cADDetails($oTierTrans->name, $sTransID, null, null);
				$oDetail->tier = $this;
				$aResults[] = $oDetail;
			}
			
			usort($aResults, "AD_name_sort_fn");
		}
		catch (Exception $e){
			$aResults = null;
		}
		cDebug::leave();
		return $aResults;
	}

	//*****************************************************************
	public function GET_all_transaction_times($poTimes){
		$aActive = [];
		$aInActive = [];
		$bContinue = true;
		cDebug::enter();
		
		$oTrans = new cADBT( $this, "*", null, true);
		$sMetricpath = cADMetricPaths::transResponseTimes($oTrans);
		cDebug::extra_debug($sMetricpath);
		
		try{
			$aStats = $this->app->GET_MetricData($sMetricpath, $poTimes,true,false,true);
		}catch (Exception $e){
			$bContinue	=false	;
		}
		//cDebug::vardump($aStats);
		if ($bContinue)
			foreach ($aStats as $oMetrics){
				$sName = cADUtil::extract_bt_name($oMetrics->metricPath, $this->name);
				if (count($oMetrics->metricValues) > 0){
					$oStats =  cADAnalysis::analyse_metrics($oMetrics->metricValues);
					try {
						$sID = cADUtil::extract_bt_id($oMetrics->metricName);
					}catch (Exception $e){
						//cDebug::vardump($oTrans);
						continue;
					}
					
					$oItem = new cADTierTransResult;
					$oItem->name = $sName;
					$oTrans = new cADBT($this,$oItem->name,$sID);
					$oItem->url= cADControllerUI::transaction($oTrans);
					$oItem->id = $sID;
					$oItem->max = $oStats->max;
					$oItem->avg = $oStats->avg;
					$oItem->count = $oStats->count;
					$aActive[] = $oItem;
				}else
					$aInActive[] = $sName;
			}
		sort($aInActive);
		if (count($aActive)>0){
			cDebug::extra_debug("size was ".count($aActive));
			cDebug::vardump($aActive);
			usort($aActive, "trans_name_sort_fn");
		}
		
		cDebug::leave();
		return (object)["active"=>$aActive, "inactive"=>$aInActive];
	}
	
	//*****************************************************************
	public function GET_dropped_overflow_traffic($poTimes){
		cDebug::enter();
		
		$oData = cADRestUI::GET_dropped_overflow_transaction_traffic($this);
		//cDebug::vardump($oData);
		
		$aOut = [];
		foreach ($oData->droppedTransactionItemList as $oItem){
			$oTraffic = new cADOverflowTraffic;
			$oTraffic->name = $oItem->name;
			$oTraffic->count = $oItem->count;
			$aOut[] = $oTraffic;
		}
		uasort($aOut, "AD_name_sort_fn");
		$aOut = array_values($aOut);
		
		cDebug::leave();
		return $aOut;
	}
	
	//*****************************************************************
	public function register_overflow_traffic($psName){

	}
	
}
?>
