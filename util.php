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
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/ckinc/array.php");
require_once("$ADlib/common.php");
require_once("$ADlib/core.php");
require_once("$ADlib/account.php");


//#################################################################
//# 
//#################################################################
function AD_startTime_sort_fn($a, $b)
{
    $v1 = $a->startTimeInMillis;
    $v2 = $b->startTimeInMillis;
    if ($v1==$v2) return 0;
    return ($v1 < $v2) ? -1 : 1;
}


function AD_name_sort_fn($po1, $po2){
	return strcasecmp ($po1->name, $po2->name);
}

function sort_machine_agents( $po1, $po2){
	$sApp1 = ($po1->applicationIds == null? "none" : $po1->applicationIds[0]);
	$sApp2 = ($po2->applicationIds == null? "none" : $po2->applicationIds[0]);
		
	return strcasecmp ("$sApp1.".$po1->hostName, "$sApp1.".$po2->hostName);	
}
function sort_appserver_agents( $po1, $po2){
	return strcasecmp (
		"$po1->applicationName.$po1->applicationComponentName.$po1->hostName", 
		"$po2->applicationName.$po2->applicationComponentName.$po2->hostName"
	);	
}

function AD_title_sort_fn($po1, $po2){
	return strcasecmp ($po1->title, $po2->title);	
}

function get_BT_from_event($poEvent){
	$sBT = "";
	
	foreach ($poEvent->affectedEntities as $oItem){
		if ($oItem->entityType == "BUSINESS_TRANSACTION"){
			$sBT = $oItem->name;
			break;
		}
	}
	return $sBT;
}


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

class CAD_CorrelatedEvent{
	public $id;
	public $type;
	public $bt;
	public $eventTime;
	public $severity;
	public $action = null;
	public $deepLinkUrl;
}


//#################################################################
//# 
//#################################################################
class cADTransFlow{
	public $name = null;
	public $children = [];
	
	//*****************************************************************
	public function walk($poApp, $psTier, $psTrans){
		cDebug::enter();
		
		$sMetricPath = cADMetric::transExtNames($psTier, $psTrans);
		$this->walk_metric($poApp, $sMetricPath);
		$this->name = $psTrans;
		
		cDebug::leave();
	}

	//*****************************************************************
	protected function walk_metric($poApp, $psMetricPath){
		cDebug::enter();

		$aCalls = $poApp->GET_Metric_heirarchy($psMetricPath, false);
		cDebug::write($psMetricPath);
		
		foreach ($aCalls as $oCall)
			if ($oCall->type == "folder") {
				$sMetricPath = $psMetricPath . "|".$oCall->name."|".cADMetric::EXT_CALLS;
				
				$oChild = new cADTransFlow();
				$this->children[] = $oChild;
				$oChild->name = $oCall->name;
				$oChild->walk_metric($poApp, $sMetricPath);
				
			}
			
		cDebug::leave();
	}
	
	//*****************************************************************
	private function pr_add_children($psApp, $psMetric, $paCalls){
	}
}

//#################################################################
//# CLASSES
//#################################################################

class cADUtil {
	private static $maAppnodes = null;
	public static $SHOW_PROGRESS = true;
	
	//*****************************************************************
	public static function get_application_ids(){
		$aApps = cADController::GET_Applications();
		$aOutput = [];
		foreach ($aApps as $oApp){
			$aOutput[ $oApp->id] = $oApp->name;
		}
		return $aOutput;
	}
	
	//*****************************************************************
	public static function get_trans_assoc_array($poApp)
	{	
		$aData = [];
		$aTrans = $poApp->GET_Transactions();
		foreach ($aTrans as $oTrans)
			$aData[$oTrans->name] = $oTrans->id;
			
		return $aData;
	}
	//*****************************************************************
	public static function MergeMetricNodes($paData){
		if (count($paData) == 0)
			return null;
		elseif (count($paData) == 1)
			return array_pop($paData);
		else{
			$aNew = [];
			while (count($paData) >0)
			{
				$aPopped = array_pop($paData);
				if (count($aPopped) > 0){
					while (count($aPopped) > 0)
					{
						$aRow = array_pop($aPopped);
						$aNew[] = $aRow;
					}
				}
			}
			return $aNew;
		}
	}

	//*****************************************************************
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
	public static function Analyse_Metrics($poData)
	{
		$max = 0; 
		$count = 0;
		$items = 0;
		$min = -1;
		$sum=0;
		$avg=0;
		
		foreach( $poData as $oRow)
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
		
		return $oResult;
	}
	
	//*****************************************************************
	public static function Analyse_heatmap($poData){
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
	public static function get_event_policy($poEvent){
		$sPolicy = null;
		$sId = null;
		
		$aAffected = $poEvent->affectedEntities;
		foreach ($aAffected as $oEntity)
			if ($oEntity->entityType === "POLICY"){
				$sPolicy = $oEntity->name;
				$sId = $oEntity->entityId;
			}
		return ["policy"=>$sPolicy, "id"=>$sId];
	}
	
	//*****************************************************************
	public static function analyse_CorrelatedEvents($paEvents, $paCorrelated)
	{
		cDebug::enter();
		$aOutput=[];
		$iActionCount = 0;
		
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
			$oOut->type = $sType;
			$oOut->policy = $sPolicy;
			$oOut->deepLinkUrl = $oEvent->deepLinkUrl;
			$oOut->bt = get_BT_from_event($oEvent);
			if (array_key_exists($sID, $paCorrelated)){
				$oOut->action = $paCorrelated[$sID];
				$iActionCount ++;
			}
			
			
			$aOutput[] = $oOut;
		}
		if ($iActionCount  == 0) cDebug::write("no actions");
		
		cDebug::leave();
		return $aOutput;
	}
	
	//*****************************************************************
	public static function analyse_events($paEvents){
		cDebug::enter();
		$aAnalysed = [];
		$aTypes = [];
		
		foreach ($paEvents as $oEvent){
			//--find the policy name
			$aResult = self::get_event_policy($oEvent);
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
		
		ksort($aAnalysed);
		ksort($aTypes);
		$oOutput = new cEventAnalysisOutput;
		$oOutput->analysis = $aAnalysed;
		$oOutput->types = $aTypes;
		
		cDebug::leave();
		return $oOutput;
	}
	
	//*****************************************************************
	public static function extract_bt_name($psMetric, $psTier){
		$sLeft = cADMetric::tierTransactions($psTier);
		$sOut = substr($psMetric, strlen($sLeft)+1);
		$iPos = strpos($sOut, cADMetric::RESPONSE_TIME);
		$sOut = substr($sOut, 0, $iPos -1);
		return $sOut;
	}
	
	//*****************************************************************
	public static function extract_error_name($psTier, $psMetric){
		$sTier = preg_quote($psTier);
		$sPattern = "/\|$sTier\|(.*)\|Errors per Minute/";
		if (preg_match($sPattern, $psMetric, $aMatches))
			return $aMatches[1];
		else
			cDebug::error("no match $psMetric with $sPattern");
	}
	
	//*****************************************************************
	public static function extract_RUM_name($psType, $psMetric){
		$sType = preg_quote($psType);
		$sPattern = "/\|$sType\|([^\|]+)\|/";
		if (preg_match($sPattern, $psMetric, $aMatches))
			return $aMatches[1];
		else
			cDebug::error("no match $psMetric with $sPattern");
	}
	
	//*****************************************************************
	public static function extract_RUM_id($psType, $psMetricName){
		$sType="Base Page";
		if ($psType == cADMetric::AJAX_REQ) $sType="AJAX Request";
		$sPattern = "/\|$sType:(\d+)\|/";
		if (preg_match($sPattern, $psMetricName, $aMatches))
			return $aMatches[1];
		else
			cDebug::error("no match '$psMetricName' with '$sPattern'");
	}
	
	//*****************************************************************
	public static function extract_bt_id($psMetricName){
		if (preg_match("/\|BT:(\d+)\|/", $psMetricName, $aMatches))
			return $aMatches[1];
		else
			cDebug::error("no match");
	}

	
	//*****************************************************************
	public static function extract_agent_version($psInput){
		if (preg_match("/^[\d\.]+$/",$psInput))
			return $psInput;
		
		if (preg_match("/\s+(v[\d\.]+\s\w+)/",$psInput, $aMatches))
			return $aMatches[1];
		else	
			return "unknown $psInput";
	}
	
	//*****************************************************************
	public static function get_node_id($poApp, $psNodeName){
		$aMachines = $poApp->GET_Nodes();
		$sNodeID = null;
		
		foreach ($aMachines as $aNodes){
			foreach ($aNodes as $oNode)
				if ($oNode->name == $psNodeName){
					$sNodeID = $oNode->id;
					cDebug::write ("found $sNodeID");
					break;
				}
			if ($sNodeID) break;
		}
		
		return $sNodeID;
	}
	
	//*****************************************************************
	public static function get_node_name($poApp, $psNodeID){
		$aMachines = $poApp->GET_Nodes();
		$sNodeName = null;
		
		foreach ($aMachines as $aNodes){
			foreach ($aNodes as $oNode)
				if ($oNode->id == $psNodeID){
					$sNodeName = $oNode->name;
					cDebug::write ("found $sNodeName");
					break;
				}
			if ($sNodeName) break;
		}
		
		return $sNodeName;
	}
	
	//*****************************************************************
	public static function get_matching_extcall($poApp, $psExt){
		$aTiers = $poApp->GET_Tiers();
		foreach ($aTiers as $oTier){
			$aTierExt = $oTier->GET_ext_calls();
			foreach ($aTierExt as $oExt)
				if ( strpos($oExt->name, $psExt) !== false )
					return $oExt->name;
		}
		return null;
	}
	
	//*****************************************************************
	public static function ignore_exitCall($poExitCall){
		if ($poExitCall->detailString === "Get Pooled Connection From Datasource") return true;
		return false;
	}
	
	//*****************************************************************
	public static function count_flow_ext_calls($poFlow){
		cDebug::enter();
		$oExtCalls = new cAssocArray;
		$aNodes = $poFlow->nodes;
		
		foreach ($aNodes as $oNode){
			$aSegments = $oNode->requestSegmentDataItems;
			if (count($aSegments)==0) continue;
			
			foreach ($aSegments as $oSegment){
				$aExitCalls = $oSegment->exitCalls;
				if (count($aExitCalls)==0) continue;
				
				foreach ($aExitCalls as $oExitCall){
					$sExtName = $oExitCall->exitPointName.":".$oExitCall->toComponentId;
					if (self::ignore_exitCall($oExitCall)) continue;
					
					
					$iCount = 0;
					if ($oExtCalls->key_exists($sExtName)) 
						$oCounter = $oExtCalls->get($sExtName);
					else{
						$oCounter = new cExtCallsAnalysis;
						$oCounter->count = 0;
						$oCounter->exitPointName = $oExitCall->exitPointName;
						$oCounter->toComponentID = $oExitCall->toComponentId;
						$oExtCalls->set($sExtName, $oCounter);
					}
					
					$oCounter->count += $oExitCall->count;
					$oCounter->totalTime += $oExitCall->timeTakenInMillis;
				}
			}
		}
		
		cDebug::leave();		
		return $oExtCalls;
	}
	
	//*****************************************************************
	public static function count_snapshot_ext_calls($poShapshot){
		cDebug::enter();
		
		//---------------- get the flow
		try{
			$oFlow = cAD_RestUI::GET_snapshot_flow($poShapshot);
		}catch (Exception $e){
			return null;
		}
		
		//---------------- analyse the flow
		$oExtCalls = self::count_flow_ext_calls($oFlow);
		cDebug::leave();		
		return $oExtCalls;
	}

	public static function flushprint($psChar = cCommon::PROGRESS_CHAR){
		if (self::$SHOW_PROGRESS) cCommon::flushprint($psChar);
	}

	
}

?>
