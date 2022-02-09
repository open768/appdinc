<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//#################################################################
//# 
//#################################################################
class cCallsAnalysis{
    public $max, $min, $avg, $sum, $count, $extCalls;
}
class cExtCallsAnalysis{
    public $count=0, $totalTime=0, $exitPointName, $toComponentID;
}
class cEventAnalysis{
	public $typeCount = null;
	public $name = "";
	public $id = -1;
	public $link = "";
	
	function __construct() {
		$this->typeCount = [];
	}
	public function add($psType){
		if (!array_key_exists($psType, $this->typeCount)) 
			$this->typeCount[$psType] = 0;
		$this->typeCount[$psType]++;
	}
}

class cEventAnalysisOutput{
	public $types = null;
	public $analysis = null;
}

class cAgentAnalysis{
	public $machineAgents = null;
	public $appAgents = null;
}

class cAgentCounts{
	public $type;
	public $count;
}

class cAgentLine{
	public $tier = null;
	public $app = null;
	public $node = null;
	public $type;
	public $version;
	public $raw_version;
	public $hostname;
	public $runtime;
	public $id;
	public $installDir;
}


class CAD_CorrelatedEvent{
	public $id;
	public $type;
	public $bt;
	public $eventTime;
	public $severity;
	public $action = null;
	public $deepLinkUrl;
}

class cTierErrorsAnalysis{
	public $name;
	public $count;
	public $average;
}

class cAccountFlowmapAnalysis{
	public $type="", $app=null, $response="", $errors="", $calls="", $normal="", $warning="", $critical="", $has_activity=false;
}

class cDashDetailAnalysis{
	public $id, $type,$x,$y,$height,$width,$text,$issues=[],$raw;
}



//#################################################################
//# CLASSES
//#################################################################

class cADAnalysis {

	//*****************************************************************
	public static function analyse_account_flowmap($paData){
		$aOutput = [];
		$aNodes = $paData->nodes;
		//cDebug::vardump($aNodes[0]);
		
		foreach ($aNodes as $oNode){
			$bActive = false;
			$oItem = new cAccountFlowmapAnalysis;
			$oApp = new cADApp($oNode->name,  $oNode->idNum);
			$oItem->app = $oApp;
			$oItem->type = $oNode->entityType;
			if ($oItem->type !== "APPLICATION") continue;
			if ( strtolower($oNode->name) === "analytics") continue;

			$oStats = $oNode->stats;
			if ($oStats->averageResponseTime->metricValue > 0){
				$oItem->response = $oStats->averageResponseTime->metricValue;
				$bActive = true;
			}
			if ($oStats->callsPerMinute->metricValue > 0){
				$oItem->calls = $oStats->callsPerMinute->metricValue ;
				$bActive = true;
			}
			if ($oStats->errorsPerMinute->metricValue > 0){
				$oItem->errors = $oStats->errorsPerMinute->metricValue;
				$bActive = true;
			}
			
			$oHealth = $oNode->componentHealthStats;
			if ($oHealth){
				if ($oHealth->normalNodes >0){
					$oItem->normal = $oHealth->normalNodes;
					$bActive = true;
				}
				if ($oHealth->warningNodes >0){
					$oItem->warning = $oHealth->warningNodes;
					$bActive = true;
				}
				if ($oHealth->criticalNodes >0){
					$oItem->critical = $oHealth->criticalNodes;
					$bActive = true;
				}
			}
			$oItem->has_activity = $bActive;
			$aOutput[] = $oItem;
		}
		
		usort ($aOutput, "AD_appname_sort_fn");
		//cDebug::vardump($aOutput);
		return $aOutput;
	}
	
	//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
	//# Agents
	//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&	
	public static function analyse_agents($paAgents, $psType){
		$aOut = [];
		$sLowerApp = null;
		
		foreach ($paAgents as $oAgent){
			$sRaw = null;
			if (property_exists($oAgent,"agentDetails")){
				$oDetails = $oAgent->agentDetails;
				if ($oDetails->disable || $oDetails->disableMonitoring)
					continue;
				
				if (property_exists($oAgent->agentDetails,"agentVersion"))
					$sRaw = $oAgent->agentDetails->agentVersion;
			}
			
			if (!$sRaw)		$sRaw = $oAgent->version;
			$sVer = cADUtil::extract_agent_version($sRaw);
			
			$oObj = new cAgentLine;
			$oObj->version = $sVer;
			$oObj->raw_version = $sRaw;
			$oObj->hostname = $oAgent->hostName;
			
			if (property_exists($oAgent,"agentDetails")){
				$oDetails = $oAgent->agentDetails;
				$oObj->id = $oDetails->id;
				$oObj->installDir = $oDetails->installDir;
				
				try{
					if (property_exists($oAgent, "applicationId"))
						$oObj->app = new cAdApp($oAgent->applicationName, $oAgent->applicationId);
					elseif ($oAgent->applicationIds)
						$oObj->app = new cAdApp(null,$oAgent->applicationIds[0]);
				}catch (Exception $e){
					cDebug::extra_debug_warning("unable to create app object: ".$e->getMessage());
				}

				if (property_exists($oAgent,"applicationComponentNodeName"))
					$oObj->node = $oAgent->applicationComponentNodeName;
				
				
				$oObj->type = $oDetails->type;
				$oObj->runtime = $oDetails->latestAgentRuntime;
			}elseif ($psType === "db"){
				$oObj->type = "DB_AGENT";
				$oObj->id = $oAgent->id;
			}
			
			$oObj->tier = @$oAgent->applicationComponentName;
			
			$aOut[] = $oObj;
		}
		
		return $aOut;	
	}
	
	//*****************************************************************
	public static function count_all_agent_types($paNodes){
		cDebug::enter();
		$aTypes = [];
		foreach ($paNodes as $aNodes)
			foreach ($aNodes as $oNode)
				cArrayUtil::add_count_to_array($aTypes, $oNode->agentType);
		
		
		cDebug::leave();
		return $aTypes;
	}
	//*****************************************************************
	public static function count_agent_types($paNodes){
		cDebug::enter();
		$aTypes = [];
		foreach ($paNodes as $oNode)
			cArrayUtil::add_count_to_array($aTypes, $oNode->agentType);
			
		$aOut = [];
		foreach ($aTypes as $sType=>$iCount){
			$oItem = new cAgentCounts;
			$oItem->type = $sType;
			$oItem->count = $iCount;
			$aOut[] = $oItem;
		}
		
		cDebug::leave();
		return $aOut;
	}
	
	//*****************************************************************
	public static function analyse_agent_versions($paNodes){
		cDebug::enter();
		$aMachineAgentCounts = [];
		$aAppAgentCounts = [];

		foreach ($paNodes as $aNodes)
			foreach ($aNodes as $oNode){
				if ($oNode->machineAgentPresent){
					$sAgent = cADUtil::extract_agent_version($oNode->machineAgentVersion);
					if (array_key_exists($sAgent, $aMachineAgentCounts))
						$aMachineAgentCounts[$sAgent] ++;
					else
						$aMachineAgentCounts[$sAgent] = 1;
				}
				if ($oNode->appAgentPresent){
					$sAgent = cADUtil::extract_agent_version($oNode->appAgentVersion);
					if (array_key_exists($sAgent, $aAppAgentCounts))
						$aAppAgentCounts[$sAgent] ++;
					else
						$aAppAgentCounts[$sAgent] = 1;
				}
			}
			
		
		$oOutput = new cAgentAnalysis;
		$oOutput->machineAgents = $aMachineAgentCounts;
		$oOutput->appAgents = $aAppAgentCounts;
		
		cDebug::vardump($oOutput);
		
		cDebug::leave();
		return $oOutput;
	}
	
	
	//#######################################################################
	//# 
	//#######################################################################
	public static function analyse_app_diagnostic_stats($paData){
		cDebug::enter();
		$aOut= [];
		$aData = $paData->children;
		foreach ($aData as $oItem){
			$aProperties = get_object_vars($oItem);
			foreach ( $aProperties as $sKey=>$oMetric)
				if (is_object($oMetric))
					if (property_exists($oMetric, "value")){
						if (!array_key_exists( $sKey, $aOut	))	$aOut[$sKey] = 0;
						if ($oMetric->value >= 0)				$aOut[$sKey] += $oMetric->value;
					}	
		}
		//cDebug::vardump($aOut);
		
		cDebug::leave();
		return $aOut;
	}
	
	//*****************************************************************
	public static function analyse_dash_detail($paDetail){
		$aOut = [];
		$aWidgets = $paDetail->widgets;
		foreach ($aWidgets as $oWidget){
			if ($oWidget->type === "LABEL") continue;
			$oOutput = new cDashDetailAnalysis;
			$oOutput->id = $oWidget->id;
			$oOutput->type = $oWidget->type;
			$oOutput->x = $oWidget->x;
			$oOutput->y = $oWidget->y;
			$oOutput->height = $oWidget->height;
			$oOutput->width = $oWidget->width;
			
			switch ($oWidget->type){
				case "METRIC_LABEL":
					$oOutput->text = $oWidget->label;
					break;
				case "HEALTH_LIST":
					break;
				case "TIMESERIES_GRAPH":
					if (!$oWidget->title){
						$oOutput->issues[] = "no widget title";
						$oOutput->text = "unknown";
					}else
						$oOutput->text = $oWidget->title;
					
					
			}	
			if (property_exists($oWidget, "widgetsMetricMatchCriterias"))
				if ($oWidget->widgetsMetricMatchCriterias == null)
					$oOutput->issues[] = "no metric matches configured";
				
			if (property_exists($oWidget, "entitySelectionType"))
				if ($oWidget->entitySelectionType === "SPECIFIED"){
					$aIds = $oWidget->entityIds;
					if ($aIds == null || count($aIds) == 0)
						$oOutput->issues[] = "no entities specified";
				}
			
			//$oOutput->raw = $oWidget;
			$aOut[] = $oOutput;
		}
		return $aOut;
	}
	
	//*****************************************************************
	public static function analyse_events($paEvents){
		cDebug::enter();
		$aAnalysed = [];
		$aTypes = [];
		
		foreach ($paEvents as $oEvent){
			//--find the policy name
			$aResult = cADUtil::get_event_policy($oEvent);
			$sPolicy = $aResult["policy"];
			$sId = $aResult["id"];
			
			//add the event to the analysis	
			if (array_key_exists($sPolicy, $aAnalysed))
				$oItem = $aAnalysed[$sPolicy];
			else{
				$oItem = new cEventAnalysis;
				$oItem->name = $sPolicy;
				$oItem->id = $sId;
				$aAnalysed[$sPolicy] = $oItem;
			}
			$oItem->add($oEvent->type);
			
			//add the type to the array
			if (!array_key_exists($oEvent->type, $aTypes)) 
				$aTypes[$oEvent->type] = 1;
		}
		
		//---------------------------------------------------
		ksort($aAnalysed);
		ksort($aTypes);
		$oOutput = new cEventAnalysisOutput;
		$oOutput->analysis = $aAnalysed;
		$oOutput->types = $aTypes;
		
		cDebug::leave();
		return $oOutput;
	}

	//*****************************************************************
	//Events
	public static function analyse_CorrelatedEventActions($paEvents){
		cDebug::enter();
		$aAnalysed = [];
		$aTypes = [];
		
		//---------------------------------------------------
		foreach ($paEvents as $oEvent){
			if ($oEvent->action) {
				$sPolicy = $oEvent->policy;
				$sActionType = $oEvent->action->type;
				
				if (array_key_exists($sPolicy, $aAnalysed))
					$oItem = $aAnalysed[$sPolicy];
				else{
					$oItem = new cEventAnalysis;
					$oItem->name = $sPolicy;
					$aAnalysed[$sPolicy] = $oItem;
				}
				$oItem->add($sActionType);
			}
		}
		
		//add the type to the array
		if (!array_key_exists($sActionType, $aTypes)) 
			$aTypes[$sActionType] = 1;
		
		//---------------------------------------------------
		ksort($aAnalysed);
		ksort($aTypes);
		$oOutput = new cEventAnalysisOutput;
		$oOutput->analysis = $aAnalysed;
		$oOutput->types = $aTypes;
		
		cDebug::leave();
		return $oOutput;
	}
	
	//*****************************************************************
	//Events
	public static function analyse_CorrelatedEvents($paEvents, $paCorrelated)
	{
		cDebug::enter();
		$aOutput=[];
		$aEventIds = [];
		$iActionCount = 0;
		
		//---------------------------------------------------
		foreach ($paEvents as $oEvent){
			$aPolicy = cADUtil::get_event_policy($oEvent);
			$sPolicy  = $aPolicy["policy"];
			$sType = $oEvent->type;
			$sType = str_replace("_"," ",$sType);
			$sID = strval($oEvent->id);
			
			$oOut = new CAD_CorrelatedEvent;
			$oOut->severity = $oEvent->severity;
			$oOut->eventTime = $oEvent->eventTime;
			$oOut->id = $sID;
			$aEventIds[] = $sID;
			$oOut->type = $sType;
			$oOut->policy = $sPolicy;
			$oOut->deepLinkUrl = $oEvent->deepLinkUrl;
			$oOut->bt = cADUtil::get_BT_from_event($oEvent);
			if (array_key_exists($sID, $paCorrelated)){
				$oOut->action = $paCorrelated[$sID];
				$iActionCount ++;
			}
			
			
			$aOutput[] = $oOut;
		}
		if ($iActionCount  == 0) {
			cDebug::extra_debug("vardump Events");
			cDebug::vardump($aEventIds);
			
			cDebug::extra_debug("vardump correlated");
			cDebug::vardump($paCorrelated);
			cCommon::messagebox("no actions");
		}
		
		cDebug::leave();
		return $aOutput;
	}	
	

	//*****************************************************************
	public static function analyse_heatmap($poData){
		$aDays = [];
		$aHours = [];

		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		function pr__add_to_array(&$paArray, $psCol, $psRow, $psValue){
			if (!isset($paArray[$psCol])) $paArray[$psCol]=[];
			if (!isset($paArray[$psCol][$psRow])) $paArray[$psCol][$psRow]=0;
			$paArray[$psCol][$psRow] += $psValue;
		};
		
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		function pr__normalise_array(&$paArray){
			$iMax=0;
			foreach ($paArray as $sCol=>$aRows)
				foreach ($aRows as $sRow =>$iValue)
					if ($iValue > $iMax) $iMax = $iValue;
				
			foreach ($paArray as $sCol=>$aRows)
				foreach ($aRows as $sRow =>$iValue)
					$paArray[$sCol][$sRow] = $iValue/$iMax;
		}
		
		//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		foreach( $poData as $oRow){
			$milli = $oRow->startTimeInMillis;
			$hour = date("H", $milli/1000);
			$min = date("i", $milli/1000); 
			$day = date("w", $milli/1000); 
			$value = $oRow->value;

			pr__add_to_array($aDays,$day,$hour,$value);
			pr__add_to_array($aHours,$hour,$min, $value);
		}
		
		pr__normalise_array($aDays);
		pr__normalise_array($aHours);
		
		return ["days"=>$aDays, "hours"=>$aHours];
	}
	
	//*****************************************************************
	public static function analyse_license_usage( $poData){
		cDebug::enter();
		
		$aUsageData = get_object_vars($poData);	
		$aKeys = array_keys($aUsageData);
		cDebug::extra_debug("number of entries:". count($aKeys));
		
		//build up the typedata
		$aTypeData = [];
		foreach ($aKeys  as $sKey){
			$aItem = $aUsageData[$sKey];
			foreach ($aItem as $oEntry){
				$sType = $oEntry->agentType;
				$sHost = $oEntry->hostId;
				if (!array_key_exists($sType, $aTypeData)) $aTypeData[$sType] = [];
				$aTypeData[$sType][] = $sHost;
			}
		}
		cDebug::leave();
		return $aTypeData;
	}
	
	
	//*****************************************************************
	public static function analyse_metrics($paData)
	{
		$max = 0; 
		$count = 0;
		$items = 0;
		$min = -1;
		$sum=0;
		$avg=0;
		
		cDebug::enter();
		foreach( $paData as $oRow)
		{
				
			$value = $oRow->value;	
		
			$max = max($max, $value, $oRow->max);
			if ($value >0){
				if ($min==-1)
					$min=$value;
				else
					$min = min($min, $value);
			}
				
			if ($value>0){
				$count+=$oRow->count;
				$sum+=$value;
				$items++;
			}
		}
		
		if ($min==-1) $min = 0;
		if ($count>0)
			$avg = $sum/$items;
		
		$oResult = new cCallsAnalysis();
		$oResult->max = $max;
		$oResult->min = $min;
		$oResult->sum = $sum;
		$oResult->avg = round($avg,2);
		$oResult->count= $count;
		
		cDebug::leave();
		return $oResult;
	}
	
	//*****************************************************************
	public static function analyse_tier_errors($poTier, $paData){
		$aOutput = [];
		
		foreach ($paData as $oItem){
			if ($oItem == null ) continue;
			if ($oItem->metricValues == null ) continue;
			$oValues = $oItem->metricValues[0];
			if ($oValues->count == 0 ) continue;
			
			$oEntry = new cTierErrorsAnalysis;
			$oEntry->name = cADUtil::extract_error_name($poTier->name, $oItem->metricPath);
			$oEntry->count = $oValues->count;
			$oEntry->average = $oValues->value;
			$aOutput[] = $oEntry;
		}
		return $aOutput;
	}
	
	//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
	//# nodes
	//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
	public static function analyse_app_nodes($paNodes){
		cDebug::enter();
		$aTierData = [];
		
		foreach ($paNodes as $aNodes)
			foreach ($aNodes as $oNode){
				$sTier = $oNode ->tierName;
				
				if (!isset($aTierData[$sTier]))	$aTierData[$sTier] = new cAgentTotals();
				$aTierData[$sTier]->total ++;
				if ($oNode->machineAgentPresent) $aTierData[$sTier]->machine ++;
				if ($oNode->appAgentPresent) $aTierData[$sTier]->appserver++;
			}
		cDebug::leave();
		return $aTierData;
	}
	
	//*****************************************************************
	public static function group_nodes_by_tier($paNodes){
		$aTiers = [];
		
		foreach ($paNodes as $aNodes)
			foreach ($aNodes as $oNode){
				$sTier = $oNode->tierName;
				if (!isset($aTiers[$sTier])) $aTiers[$sTier] = [];
				$aTiers[$sTier][] = $oNode;
			}
		uksort($aTiers,"strcasecmp");
		return $aTiers;
	}
}

?>
