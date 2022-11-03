<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 - 2022

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/
require_once("$ADlib/core.php");
require_once("$ADlib/flowmap.php");

class cADRestUISynthList{
	public $applicationId= -1;
	public $timeRangeString ="";	
}
class cADUsageRequest{
	public $allocationId = null;
    public $packageId = null;
	public $hostIds = null;
}

class cADRestUITime{
	public $type="BETWEEN_TIMES";
	public $durationInMinutes = 60;
	public $endTime = -1;
	public $startTime = -1;
	public $timeRange=null;
	public $timeRangeAdjusted=false;
}


class cADRestUISnapshotFilter{
	public $applicationIds = [];
	public $applicationComponentIds = [];
	public $sepIds = [];
	public $rangeSpecifier = null;
	
	function __construct() {
		$this->rangeSpecifier = new cADRestUITime;
	}
}
class cADRestUIRequest{
	public $applicationIds = [];
	public $guids = [];
	public $rangeSpecifier = null;
	public $needExitCalls = true;
	
	function __construct(){
		$this->rangeSpecifier = new cADRestUITime;
	}
}

class cADSynthResponse{
	public $id;
	public $name;
	public $app;
	public $rate;
	public $executions;
	public $durations;
	public $config;
	public $raw_data;
}

class cADCorrelatedEventRequest{
	public $type = "EVENT";
	public $id = "";

	function __construct($psID) {
		$this->id = $psID;
	}
}

class cADCorrelatedEvent{
	public $type= "";
	public $summary= "";
	public $id = "";
	public $parentid = "";
}

class cADLogDetailRequest{
	public $id;
	public $version;
}


//#####################################################################################
//#
//#####################################################################################
class cADRestUI{
	private static $iAccount = null;
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* init data
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_init_data(){
		cDebug::enter();
		$sUrl = "user/initData";
		$oData = cADCore::GET_restUI($sUrl);
		cDebug::leave();
		return $oData;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* account
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_account(){
		$oAccount = cADController::GET_account();
		return $oAccount->id;
	}
	
	//**********************************************************************
	public static function GET_account_flowmap(){
		cDebug::enter();

		$iAccount = self::GET_account();
		$sTime = cADTime::last_hour();
		$sUrl = "accountFlowMapUiService/account/$iAccount?$sTime&mapId=-1&baselineId=-1";
		$oData = cADCore::GET_restUI($sUrl);

		cDebug::leave();
		return $oData;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* analyTICS
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_analytics_schemas(){
		cDebug::enter();
		$sUrl = "analytics/schema";
		$oData = cADCore::GET_restUI($sUrl, true);
		$aData = json_decode($oData->rawResponse);
		sort ($aData);
		
		cDebug::leave();
		return $aData;
	}
	
	public static function GET_analytics_schema_fields($psSchemaName){
		cDebug::enter();
		$sUrl = "analytics/v1/store/metadata/getFieldDefinitions?eventType=$psSchemaName";
		$oData = cADCore::GET_restUI($sUrl, true);
		cDebug::leave();
		return $oData;
	}
	
	public static function GET_log_analytics_sources(){
		cDebug::enter();
		$sUrl = "analytics/logsources";
		$aData = cADCore::GET_restUI($sUrl, true);
		usort($aData,"AD_name_sort_fn");

		cDebug::leave();
		return $aData;
	}
	
	public static function GET_analytics_metrics(){
		cDebug::enter();
		$sUrl = "analyticsMetric/getAnalyticsScheduledQueryReports";
		$aData = cADCore::GET_restUI($sUrl, true);
		cDebug::leave();
		return $aData;
	}
	//*******************************************************************
	public static function GET_log_analytics_details($psID){
		cDebug::enter();
		$aData = null;
		$oFound = null;
		
		//find the details from GET_log_analytics_sources
		cDebug::extra_debug("looking for ID $psID");
		$aSources = self::GET_log_analytics_sources();
		foreach ($aSources as $oSource)
			if ($oFound == null)
				if ($oSource->id == $psID)
					$oFound = $oSource;
				
		
		if ($oFound==null)
			cDebug::error("didnt find log source with id $psID");
		
		//---------make the request to get more information
		$sUrl = "analytics/logsources/extract-fields";
		$sEncoded =  json_encode($oFound);
		cDebug::extra_debug("Request payload: $sEncoded");
		$aData = cADCore::GET_restUI_with_payload($sUrl,$sEncoded);

		cDebug::leave();
		return $aData;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Application
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_application_ids($paColumns=["NAME"]){
		cDebug::enter();
		$oTimes = new cADTimes;
		$oPayload = (object)[
			"requestFilter" => (object)[
				"filters" => [
					(object)[
						"field" => "TYPE",
						"criteria" => "APM",
						"operator" => "EQUAL_TO"
					]
				],
				"queryParams" => null
			],
			"searchFilters" => [],
			"timeRangeStart" => $oTimes->start,
			"timeRangeEnd" => $oTimes->end,
			"columnSorts" => [],
			"resultColumns" => $paColumns,
			"offset"=> 0,
			"limit"=> -1
		];
		$oData = cADCore::GET_restUI_with_payload("v1/app/list/all", $oPayload); //cant be cached as time is set
		cDebug::leave();
		return $oData->data;
	}
	
	//********************************************************************************************
	static function get_applications_status_from_ids($paIDs, $paCols=["NAME", "CALLS_PER_MINUTE"]){
		cDebug::enter();
		$oTimes = new cADTimes;
		
		$oPayload = (object)[
			"requestFilter" => $paIDs,
			"timeRangeStart"=> $oTimes->start,
			"timeRangeEnd" => $oTimes->end,
			"searchFilters"=> null,
			"columnSorts" => null,
			"resultColumns" => $paCols,
			"offset" => 0,
			"limit" => -1
		];
		$oData = cADCore::GET_restUI_with_payload("v1/app/list/ids", $oPayload); //cant be cached as time is set
		cDebug::leave();
		
		return $oData->data;
	}
	
	//********************************************************************************************
	private static function pr__get_app_objs_from_ids($paIDs){
		cDebug::enter();
		$aData = self::get_applications_status_from_ids($paIDs);
		$aOut = [];
		foreach ($aData as $oItem){
			$oApp = new cADApp( $oItem->name, $oItem->id);
			$aOut[] = $oApp;
		}
		cDebug::leave();
		return $aOut;
	}

	//********************************************************************************************
	public static function GET_applications(){
		cDebug::enter();
		
		$aIDs = self::GET_application_ids();
		$aApps = self::pr__get_app_objs_from_ids($aIDs);
		
		cDebug::leave();
		return $aApps;
	}
	
	//********************************************************************************************
	public static function GET_app_flowmap($poApp){
		cDebug::enter();
		$sTime = cADTime::last_hour();
		$sUrl = "applicationFlowMapUiService/application/$poApp->id?$sTime&mapId=-1&baselineId=-1";
		$oData = cADCore::GET_restUI($sUrl);
		
		$oFlowMap = new cADFlowMap;
		$oFlowMap->parse($oData);
		
	}
	
	//****************************************************************
	public static function get_app_backends($poApp){
		cDebug::enter();
		$oTimes = new cADTimes;
		$oPayload = (object)[
			"requestFilter"=> (object)[
				"queryParams"=> (object)[
					"applicationId"=> $poApp->id
				],
				"filters"=> []
			],
			"resultColumns"=> ["ID", "NAME"],
			"offset"=> 0,
			"limit"=> -1,
			"searchFilters"=> [],
			"columnSorts"=> [],
			"timeRangeStart"=> $oTimes->start,
			"timeRangeEnd"=> $oTimes->end
		];
		$oData = cADCore::GET_restUI_with_payload("backend/list/remoteService",$oPayload);
		cDebug::leave();
		return $oData->data;
	}
	
	//****************************************************************
	public static function get_app_data_collectors($poApp){
		cDebug::enter();
		$aData = cADCore::GET_restUI("MidcUiService/getAllDataGathererConfigs/$poApp->id",true);
		cDebug::leave();
		return $aData;
	}
	
	//****************************************************************
	public static function get_app_diagnostic_stats($poApp){
		cDebug::enter();
		$sUrl = "agent/setting/agentDiagnosticStats?entityType=APPLICATION&entityId=$poApp->id&timeRange=last_1_hour.BEFORE_NOW.-1.-1.60";
		$aData = cADCore::GET_restUI($sUrl,true);
		cDebug::leave();
		return $aData;
	}
	
	//****************************************************************
	public static function get_app_BT_Summary($poApp, $poTimes){
		cDebug::enter();
		
		$sUrl = "v1/bt/listViewDataByColumns";
		$oPayload = (object)[
			"requestFilter"=>[$poApp->id],
			"searchFilters"=>null,
			"timeRangeStart"=>$poTimes->start,
			"timeRangeEnd"=>$poTimes->end,
			"columnSorts"=>null,
			"resultColumns"=>["NAME","BT_HEALTH","CALL_PER_MIN"],
			"offset"=>0,
			"limit"=>-1
		];
		
		$oData = cADCore::GET_restUI_with_payload($sUrl,$oPayload);
		
		cDebug::leave();
		return $oData->btListEntries;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Agents 
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_database_agents(){
		cDebug::enter();	
		$sURL = "agent/setting/getDBAgents";
		$aAgents = cADCore::GET_restUI($sURL,true);
		cDebug::leave();	
		return  $aAgents;
	}

	//****************************************************************
	public static function GET_machine_agents(){
		cDebug::enter();	
		$aAgents = cADCore::GET_restUI("agent/setting/allMachineAgents",true);
		cDebug::extra_debug("sorting");
		usort($aAgents,"sort_machine_agents");
		cDebug::extra_debug("finished sorting");
		cDebug::leave();	
		return  $aAgents;
	}
	//****************************************************************
	public static function GET_appserver_agents(){
		cDebug::enter();	
		$aAgents = cADCore::GET_restUI("agent/setting/getAppServerAgents",true);
		usort($aAgents,"sort_appserver_agents");
		cDebug::leave();	
		return  $aAgents;
	}

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* backends  
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_tier_backends($poTier){
		cDebug::enter();
		$sUrl = "backendUiService/resolvedBackendsForTier/$poTier->id";
		$aAgents = cADCore::GET_restUI($sUrl);
		cDebug::leave();
		return $aAgents;
	}
	
	public static function DELETE_backend($piID){
		cDebug::enter();
		$sUrl = "backendUiService/deleteBackends";
		$sPayload = json_encode([$piID]);
		cADCore::GET_restUI_with_payload($sUrl,$sPayload);
		cDebug::leave();
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Dashboards
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_dashboards(){
		cDebug::enter();
		
		$sUrl = "/dashboards/getAllDashboardsByType/false";
		$aDashboards = cADCore::GET_restUI($sUrl);
		usort($aDashboards, "AD_name_sort_fn");
		cDebug::leave();
		return $aDashboards;
	}

	public static function GET_dashboard_detail($piDashID){
		cDebug::enter();
		$sUrl = "/dashboards/dashboardIfUpdated/$piDashID/-1";
		$aData = cADCore::GET_restUI($sUrl, true);
		cDebug::leave();
		return $aData;
	}
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Events
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_correlatedEvents($paEvents){
		cDebug::enter();
		
		$aRequest = [];
		foreach ($paEvents as $oEvent){
			$aRequest[] = new cADCorrelatedEventRequest($oEvent->id);
		}
		//cDebug::vardump($aRequest);
		
		//$sTime = cADTime::beforenow(60);
		$sTime = "timeRangeString=last_1_hour.BEFORE_NOW.-1.-1.60";
		$sUrl = "events/correlatedEvents?$sTime";
		$aResponse = cADCore::GET_restUI_with_payload($sUrl,$aRequest);
		//cDebug::vardump($aResponse);
		
		//
		$aOutput = [];
		foreach ( $aResponse as $aItem)
			foreach ($aItem as $oItem)
			{
				$oObj = new cADCorrelatedEvent;
				$oObj->type = $oItem->eventType;
				$oObj->summary = $oItem->summary;
				
				$sID = strval($oItem->id);
				$oObj->id = $sID;
				
				$sCorrelatedID = "";
				$aCorrelated = $oItem->correlationKeys;
				foreach ($aCorrelated as $oCorrelated){
					if ($oCorrelated->correlationType === "PARENT_EVENT_ID")
						$sCorrelatedID = $oCorrelated->correlationValue;
				}

				if ($sCorrelatedID === "") 
					cDebug::extra_debug("no correlation ID found for $sID");
				else{
					$oObj->parentid = $sCorrelatedID;
					$aOutput[$sCorrelatedID] = $oObj;
				}
			}
		//cDebug::vardump($aOutput);
		
		cDebug::leave();
		return $aOutput;
	}

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* health rules
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function get_app_healthrules($poApp){
		cDebug::enter();
		$sUrl = "policy2/policies/$poApp->id";
		$aData = cADCore::GET_restUI($sUrl);
		cDebug::leave();
		return $aData;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Licenses
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_allocationRules(){
		cDebug::enter();
		$oPayload = (object)[
			"durationInMinutes" => 60,
			"endTime" => null,
			"startTime" => null,
			"timeRange" => null,
			"timeRangeAdjusted" => false,
			"type" => "BEFORE_NOW"
		];
		
		$sUrl ="licenseRule/getAllRulesSummary";
		$oData = cADCore::GET_restUI_with_payload($sUrl, $oPayload);
		cDebug::leave();
		
		return($oData);
	}
	
	public static function GET_allocationID($piRuleID){
		cDebug::enter();
		$aRules = self::GET_allocationRules();
		$iAllocationID = $aRules[$piRuleID]->allocationId;
		cDebug::leave();
		return $iAllocationID ;
	}
	public static function GET_allocationHosts($psAllocationID){
		cDebug::enter();
		$iAccount = self::GET_account();
		$sUrl = "license/accounts/$iAccount/allocations/$psAllocationID/hosts?offset=0&max=1000";
		$aData = cADCore::GET_restUI($sUrl);
		cDebug::extra_debug("count of hosts:". count($aData));
		cDebug::leave();
		return $aData;
	}

	public static function GET_license_usage($psAllocationID, $paHostIds){
		cDebug::enter();
		
		$iAccount = self::GET_account();
		
		$sUrl = "license/accounts/$iAccount/activeLicenseEntities";
		$oReq = new cADUsageRequest;
		$oReq->allocationId = $psAllocationID;
		$oReq->hostIds = $paHostIds;
		
		$oData = cADCore::GET_restUI_with_payload($sUrl, $oReq);
		
		cDebug::leave();
		return $oData;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Nodes  
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_Node_details($piAppID, $piNodeID){
		$sURL = "dashboardNodeViewData/$piAppID/$piNodeID";
		return  cADCore::GET_restUI($sURL);
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* RBAC Role based Authentication
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function get_rbac_all_users(){
		cDebug::enter();
		$sUrl = "userAdministrationUiService/users";
		$aData = cAdCore::GET_restui($sUrl, true);
		cDebug::leave();
		return $aData;		
	}
	
	//************************************************************************************
	public static function get_rbac_ldap_group_users($psGroup){
		cDebug::enter();
		$oPayload = (object)[
			"offset" =>1,
			"resultCount"=>25,
			"cookie"=>null,
			"queryString"=>"",
			"groupName"=>"cn=$psGroup,ou=group,ou=AppDynamics,o=t2,dc=com"
		];
		$sUrl = "ldapAdministrationUiService/users/ldapquery";
		$oData = cADCore::GET_restUI_with_payload($sUrl, $oPayload, true);
		cDebug::leave();
		return $oData;		
	}
	//************************************************************************************
	public static function get_rbac_internal_group_users($psGroupID){
		cDebug::enter();
		$sUrl = "groupAdministrationUiService/groups/userids/$psGroupID";
		$oData = cADCore::GET_restUI($sUrl, true);
		cDebug::leave();
		return $oData;		
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Snapshots (warning this uses an undocumented API)
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_snapshot_segments($psGUID, $piSnapTime){
		cDebug::enter();
			$oTime = new cADTimes($piSnapTime);
			$sTimeUrl = cADTime::make_short( $oTime);
			$sURL = "snapshot/getRequestSegmentData?requestGUID=$psGUID&$sTimeUrl";
			$aResult = cADCore::GET_restUI($sURL);
		cDebug::leave();
		return  $aResult;
	}
	
	//************************************************************************************
	public static function GET_snapshot_problems($poApp,$psGUID, $piSnapTime){
		cDebug::enter();
			$oTime = new cADTimes($piSnapTime);
			$sTimeUrl = cADTime::make_short( $oTime, "time-range");
			$sURL = "snapshot/potentialProblems?request-guid=$psGUID&applicationId=$poApp->id&$sTimeUrl&max-problems=50&max-rsds=30&exe-time-threshold=5";
			$aResult = cADCore::GET_restUI($sURL);
		cDebug::leave();
		return  $aResult;
	}
	
	//************************************************************************************
	public static function GET_snapshot_flow($poSnapShot){
		cDebug::enter();
			$oTime = new cADTimes($poSnapShot->serverStartTime);
			$sAid = $poSnapShot->applicationId;
			$sBtID = $poSnapShot->businessTransactionId;
			$sGUID = $poSnapShot->requestGUID;
			$sTimeUrl = cADTime::make_short( $oTime);
			$sURL = "snapshotFlowmap/distributedSnapshotFlow?applicationId=$sAid&businessTransactionId=$sBtID&requestGUID=$sGUID&eventType=&$sTimeUrl&mapId=-1";
			$oResult = cADCore::GET_restUI($sURL);
		cDebug::leave();
		
		return $oResult;
	}

	//************************************************************************************
	public static function GET_snapshot_expensive_methods($psGUID, $piSnapTime){
		cDebug::enter();
			$oTime = new cADTimes($piSnapTime);
			$sTimeUrl = cADTime::make_short( $oTime);
			$sURL = "snapshot/getMostExpensiveMethods?limit=30&max-rsds=30&$sTimeUrl&mapId=-1";
			$oResult = cADCore::GET_restUI_with_payload($sURL,$psGUID);
		cDebug::leave();
		
		return $oResult;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* service end points
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_service_end_points($poApp){
		cDebug::enter();
		$sURL = "serviceEndpoint/list";
		$oTimes = new cADTimes;
		$oPayload = (object)[
			"requestFilter" => (object)[
				"queryParams" => (object)[
					"applicationId" => $poApp->id,
					"mode" => "FILTER_EXCLUDED"
				],
				"searchText"=> "",
				"filters" => (object)[
					"type" => [],
					"sepName" => []
				]
			],
			"columnSorts" => [
				(object)[
					"column" => "NAME",
					"direction" => "ASC"
				]
			],
			"timeRangeStart" => $oTimes->start,
			"timeRangeEnd"=> $oTimes->end
		];
		$oResult = cADCore::GET_restUI_with_payload($sURL,$oPayload);
		cDebug::leave();
		return $oResult;
	}
	
	//************************************************************************************
	public static function GET_Tier_service_end_points($poTier){ //this can use metric heirarchy
		cDebug::enter();
		$oResult = self::GET_service_end_points($poTier->app);
		$aData = $oResult->data;
		
		//now filter the results for the tier id
		$aEndPoints = [];
		foreach( $aData as $oService){
			if ($oService->applicationComponentId == $poTier->id){
				$oItem = new cADDetails($oService->name, $oService->id, null,null);
				$oItem->type = $oService->type;
				$aEndPoints[] = $oItem;
			} 
		}
		usort($aEndPoints,"AD_name_sort_fn");
		cDebug::leave();
		return $aEndPoints;
	}
	
	//************************************************************************************
	public static function GET_Service_end_point_snapshots($poTier, $piServiceEndPointID, $oTime){
		cDebug::enter();
		//{"applicationIds":[1424],"applicationComponentIds":[4561],"sepIds":[6553581],"rangeSpecifier":{"type":"BEFORE_NOW","durationInMinutes":60},"maxRows":600}
		
		$sURL = "snapshot/snapshotListDataWithFilterHandle";
		$oFilter = new cADRestUISnapshotFilter;
		$oFilter->applicationIds[] = intval($poTier->app->id);
		$oFilter->applicationComponentIds[] = intval($poTier->id);
		$oFilter->sepIds[] = intval($piServiceEndPointID);
		$oFilter->maxRows = 600;
		$oFilter->rangeSpecifier->startTime = $oTime->start;
		$oFilter->rangeSpecifier->endTime = $oTime->end;

		$sPayload = json_encode($oFilter);
		cDebug::extra_debug($sPayload);
		$oResult = cADCore::GET_restUI_with_payload($sURL,$sPayload);
		
		cDebug::leave();
		return $oResult;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* synthetics
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_Synthetic_jobs($poApp, $oTime, $pbDetails){
		cDebug::enter();
		$oRequest = new cADRestUISynthList;
		$oRequest->applicationId = (int)$poApp->id;
		$oRequest->timeRangeString = cADTime::make_short( $oTime,null);
		$sURL = "synthetic/schedule/getJobList";
		$sPayload = json_encode($oRequest);
		
		try{
			$oResult = cADCore::GET_restUI_with_payload($sURL,$sPayload,true);
		}catch (Exception $e){
			$oResult = null;
		}
		
		$aSyth = [];
		foreach ($oResult->jobListDatas as $oJob){
			$oSummary = new cADSynthResponse;
			$oSummary->app = $poApp;
			$oSummary->id = $oJob->config->id;
			$oSummary->rate = $oJob->config->rate;
			$oSummary->name = $oJob->config->description;
			
			if ($pbDetails){
				if ($oJob->metrics->sessionDuration->count >0)
					$oSummary->durations = $oJob->metrics->sessionDuration;	
				if ($oJob->metrics->jobExecutions->count >0)
					$oSummary->executions = $oJob->metrics->jobExecutions;	
				if (cDebug::is_debugging() )
					$oSummary->raw_data = $oJob;	
				$oSummary->config = $oJob->config->performanceCriteria;
			}
			$aSyth[] = $oSummary;
		}
		cDebug::leave();
		return $aSyth;		
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* BT config
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function GET_app_BT_configs($poApp){
		cDebug::enter();
		$sURL = "transactionConfigProto/getRules/$poApp->id";
		$oData = cADCore::GET_restUI($sURL,true);
		cDebug::leave();
		return $oData;
	}

	//***************************************************************
	public static function GET_appLevel_BT_Config($poApp){
		cDebug::enter();
		$sURL = "transactionConfig/getAppLevelBTConfig/$poApp->id";
		$oData = cADCore::GET_restUI($sURL,true);
		cDebug::leave();
		return $oData;
	}
	
	//***************************************************************
	public static function GET_dropped_overflow_transaction_traffic($poTier){
		cDebug::enter();
		$sPayload = '{"componentId":'.$poTier->id.',"timeRangeSpecifier":{"type":"BEFORE_NOW","durationInMinutes":60,"endTime":null,"startTime":null,"timeRange":null,"timeRangeAdjusted":false}}';
		$sURL = "overflowtraffic/event";
		$oResult = cADCore::GET_restUI_with_payload($sURL,$sPayload,true);
		
		cDebug::leave();
		return $oResult;
	}
}
	
	
	


