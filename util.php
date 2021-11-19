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
function AD_appname_sort_fn($po1, $po2){
	return strcasecmp ($po1->app->name, $po2->app->name);
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



//#################################################################
//# 
//#################################################################
class cADTransFlow{
	public $name = null;
	public $children = [];
	
	//*****************************************************************
	public function walk($poApp, $psTier, $psTrans){
		cDebug::enter();
		
		$sMetricPath = cADMetricPaths::transExtNames($psTier, $psTrans);
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
				$sMetricPath = $psMetricPath . "|".$oCall->name."|".cADMetricPaths::EXT_CALLS;
				
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
		$aApps = cADController::GET_all_Applications();
		$aOutput = [];
		foreach ($aApps as $oApp){
			$aOutput[ $oApp->id] = $oApp->name;
		}
		return $aOutput;
	}
	
	//*****************************************************************
	public static function get_BT_from_event($poEvent){
		$sBT = "";
				
		foreach ($poEvent->affectedEntities as $oItem){
			if ($oItem->entityType == "BUSINESS_TRANSACTION"){
				$sBT = $oItem->name;
				break;
			}
		}
		return $sBT;
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
	
	//################################################################
	public static function extract_bt_name($psMetric, $psTier){
		$sLeft = cADMetricPaths::tierTransactions($psTier);
		$sOut = substr($psMetric, strlen($sLeft)+1);
		$iPos = strpos($sOut, cADMetricPaths::RESPONSE_TIME);
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
		if ($psType == cADMetricPaths::AJAX_REQ) $sType="AJAX Request";
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
			cDebug::error("no match in $psMetricName");
	}

	
	//*****************************************************************
	public static function extract_agent_version($psInput){
		if (preg_match("/^[\d\.]+$/",$psInput))
			return $psInput;
		
	if (preg_match("/\s+v([\d\.-]+)\s\w+/",$psInput, $aMatches))
			return $aMatches[1];

		if (preg_match("/([\d\.-]+)\scomp/",$psInput, $aMatches))
			return $aMatches[1];
		
		
		return "unknown - $psInput";
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
			if ($aSegments==null) continue;
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
	
	public static function flushprint($psChar = cCommon::PROGRESS_CHAR){
		if (self::$SHOW_PROGRESS) cCommon::flushprint($psChar);
	}

	
}

?>
