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

class cAppCheckupMessage{
	public $message;
	public $is_bad;
	public $extra;
}
class cAppCheckupAnalysis{
	public $DCs = [];
	public $BTs = [];
	public $tiers = [];
	public $backends = [];
}


//# 
class cADApp{	
	//#################################################################
	public static function GET_Applications(){
		cDebug::enter();
		if ( cAD::is_demo()) return cADDemo::GET_Applications();
		
		$aData = cADCore::GET('?',true);
		if ($aData)	usort($aData,"AD_name_sort_fn");
		$aOut = [];
		foreach ($aData as $oItem)
			if ($oItem->name !== null)
				if (strtolower($oItem->name) !== "analytics"){
					$oApp = new cADApp($oItem->name, $oItem->id);
					$aOut[] = $oApp;
				}
		
		//if (cDebug::is_debugging()) cDebug::vardump($aOut);
		cDebug::leave();
		return $aOut;		
	}
	
	//#################################################################
	public static $server_app = null;
	public $name, $id;
	
	function __construct($psAppName, $psAppId=null) {	
		if (!$psAppName  && !$psAppId) cDebug::error("no app details provided");

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
   
	//*****************************************************************
	private function pr__get_id(){
		$aApps = cADApp::GET_Applications();
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
		$aApps = cADApp::GET_Applications();
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
	public function checkup(){
		cDebug::enter();

		$aTrans = $this->GET_Transactions();
		$oOut = new cAppCheckupAnalysis;
		
		//-------------BTs --------------------------------
		cDebug::extra_debug("analysing app $this->name");
		$iCount = count($aTrans);
		$sCaption  = "There are $iCount BTs.";
		$bBad = true;
		
		if ($iCount < 5)
			$sCaption .= " There are too few BTs - check BT detection configuration";
		elseif ($iCount >=250)
			$sCaption .= " This must be below 250. <b>Investigate configuration</b>";
		elseif ($iCount >=50)
			$sCaption .= " The number of transactions is very high";
		elseif ($iCount >=30)
			$sCaption .= " The number of transactions is on the high side. we recommend no more than about 30 BTs per application";
		else
			$bBad = false;
		
		$oMsg = new cAppCheckupMessage();
		$oMsg->message = $sCaption;
		$oMsg->is_bad = $bBad;
		$oOut->BTs[] = $oMsg;
		
		//-------------Data Collectors ----------------------
		cDebug::extra_debug("counting data collectors");
		$aDCs = $this->GET_data_collectors();
		$oMsg = new cAppCheckupMessage;
		$oMsg->extra = "data collectors";
		if (!$aDCs || count($aDCs) ==0){
			$oMsg->message = "no Data Collectors defined";
			$oMsg->is_bad = true;
		}elseif (count($aDCs)==1 && ($aDCs[0]->name === "Default HTTP Request Data Collector")){
			$oMsg->message = "only the Default HTTP Request DC defined: ";
			$oMsg->is_bad = true;
		}else{
			$oMsg->message = "number of DCs defined: ".count($aDCs);
			$oMsg->is_bad = false;
		}
		$oOut->DCs[] = $oMsg;
		
		
		//-------------tiers --------------------------------
		cDebug::extra_debug("counting tiers");
		$aTierCount = []; 	//counts the transactions per tier
		foreach ($aTrans as $oTrans){
			$sTier = $oTrans->tierName;
			if (! isset($aTierCount[$sTier])) $aTierCount[$sTier] = 0;
			$aTierCount[$sTier] = $aTierCount[$sTier] +1;
		}
		
		if (count($aTierCount) == 0){
			$oMsg = new cAppCheckupMessage;
			$oMsg->message = "no tiers defined";
			$oMsg->extra = "tiers";
			$oMsg->is_bad = true;
			$oOut->tiers[] = $oMsg;
		}else{
			foreach ($aTierCount as $sTier=>$iCount){
				
				$bBad = true;
				$sCaption = "There are $iCount BTs.";
				if ($iCount >=50)
					$sCaption .= " This must be below 50. <b>Investigate instrumentation</b>";
				elseif ($iCount >=10)
					$sCaption .= " The number of transactions is on the high side. we recommend around a max of 10 BTs per tier ";
				else
					$bBad = false;

				$oMsg = new cAppCheckupMessage;
				$oMsg->message = $sCaption;
				$oMsg->is_bad = $bBad;
				$oMsg->extra = $sTier;
				$oOut->tiers[] = $oMsg;
			}
		}
		
		//-------------backends --------------------------------
		$aBackends = $this->GET_Backends();
		$iCount = count($aBackends);
		if ($iCount ==0){
			$sCaption = "There no remote services detected.";
			$bBad = false;
		}else{
			$bBad = true;
			$sCaption = "There are $iCount remote services.";
			if ($iCount >=50)
				$sCaption .= " its a little on the high side";
			elseif ($iCount >=100)
				$sCaption .= " this doesnt look right, check the detection";
			else
				$bBad = false;
		}
		$oMsg = new cAppCheckupMessage;
		$oMsg->message = $sCaption;
		$oMsg->is_bad = $bBad;
		$oOut->backends[] = $oMsg;
		
		//-------------BTs --------------------------------
		cDebug::leave();
		return $oOut;
	}
	//*****************************************************************
	public function GET_Backends(){
		if ( cAD::is_demo()) return cADDemo::GET_Backends(null);
		$sMetricpath= cADMetric::backends();
		return $this->GET_Metric_heirarchy($sMetricpath, false); //dont cache
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
	public function GET_raw_tiers(){
		if ( cAD::is_demo()) return cADDemo::GET_Tiers($this);
		$sApp = rawurlencode($this->name);
		$aData = cADCore::GET("$sApp/tiers?",true );
		return $aData; 
	}
	
	public function GET_Tiers(){
		cDebug::enter();
		$aData = $this->GET_raw_tiers();
		if ($aData) usort($aData,"AD_name_sort_fn");
		
		$aOutTiers = [];

		//convert to tier objects and populate the app
		foreach ($aData as $oInTier){
			$oOutTier = new cADTier($this, $oInTier->name, $oInTier->id);
			$aOutTiers[] = $oOutTier;
		}
		
		cDebug::leave();
		return $aOutTiers;
	}


	//*****************************************************************
	public function GET_Transactions(){		
		cDebug::enter();
		$sApp = rawurlencode($this->name);
		$aData =cADCore::GET("$sApp/business-transactions?" );
		cDebug::leave();
		
		return $aData;
		
	}
	
	//*****************************************************************
	public function GET_Transaction_configs(){		
		cDebug::enter();
		$oData = cADRestUI::GET_transaction_configs($this);
		$aData = $oData->ruleScopeSummaryMappings;
		usort($aData,"bt_config_sort_function" );
		cDebug::leave();
		return $aData;
	}

}
function bt_config_sort_function($po1,$po2){
	$p1=str_pad($po1->rule->priority,3,"0", STR_PAD_LEFT);
	$p2=str_pad($po2->rule->priority,3,"0", STR_PAD_LEFT);
	$s1 = $p1." ".$po1->rule->summary->name;
	$s2 = $p2." ".$po2->rule->summary->name;
	return strcasecmp ($s2,$s1);
}
cADApp::$server_app = new cADApp(cADCore::SERVER_APPLICATION,cADCore::SERVER_APPLICATION);

?>
