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


//#################################################################
//# CLASSES
//#################################################################
class cADMetricPaths{
	const METRIC_TYPE_QS ="mt";
	const METRIC_TYPE_RUMCALLS = "mrc";
	const METRIC_TYPE_RUMRESPONSE = "mrr";
	const METRIC_TYPE_TRANSRESPONSE = "mtr";
	const METRIC_TYPE_DATABASE_TIME = "mdt";
	const METRIC_TYPE_ACTIVITY = "mac";
	const METRIC_TYPE_ERRORS = "mer";
	const METRIC_TYPE_RESPONSE_TIMES = "mrt";
	const METRIC_TYPE_BACKEND_RESPONSE = "mbr";
	const METRIC_TYPE_BACKEND_ACTIVITY = "mba";
	const METRIC_TYPE_JMX_DBPOOLS = "mtjdbp";
	
	const ALL_OTHER = "_APPDYNAMICS_DEFAULT_TX_";
	const CALLS_PER_MIN = "Calls per Minute";
	const ERRS_PER_MIN = "Errors per Minute";
	const EXCEP_PER_MIN = "Exceptions per Minute";
	const RESPONSE_TIME = "Average Response Time (ms)";
	const SLOW_CALLS = "Number of Slow Calls";
	const STALL_COUNT = "Stall Count";
	const USAGE_METRIC = "moduleusage";
	const VSLOW_CALLS = "Number of Very Slow Calls";

	const AJAX_REQ = "AJAX Requests";
	const APPLICATION = "Overall Application Performance";
	const BACKENDS = "Backends";
	const BASE_PAGES = "Base Pages";
	const DATABASES = "Databases";
	const END_USER = "End User Experience";
	const ERRORS = "Errors";
	const EXT_CALLS = "External Calls";
	const INFORMATION_POINTS = "Information Points";
	const INFRASTRUCTURE = "Application Infrastructure Performance";
	const SERVICE_END_POINTS = "Service Endpoints";
	const TRANSACTIONS = "Business Transaction Performance";

	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Module Usage
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function moduleUsage($psModule, $piMonths){
		return self::USAGE_METRIC."/$psModule/$piMonths";
	}
	

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Service End  Points
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function endPointResponseTimes($psTier, $psName){
		return self::SERVICE_END_POINTS."|$psTier|$psName|".self::RESPONSE_TIME;
	}
	
	public static function endPointCallsPerMin($psTier, $psName){
		return self::SERVICE_END_POINTS."|$psTier|$psName|".self::CALLS_PER_MIN;
	}

	public static function endPointErrorsPerMin($psTier, $psName){
		return self::SERVICE_END_POINTS."|$psTier|$psName|".self::ERRS_PER_MIN;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* backends
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function backends(){
		return self::BACKENDS;
	}
	
	public static function backendResponseTimes($psBackend){
		return self::BACKENDS."|$psBackend|".self::RESPONSE_TIME;
	}

	public static function backendCallsPerMin($psBackend){
		return self::BACKENDS."|$psBackend|".self::CALLS_PER_MIN;
	}
	public static function backendErrorsPerMin($psBackend){
		return self::BACKENDS."|$psBackend|".self::ERRS_PER_MIN;
	}

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Database
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function databases(){
		return self::DATABASES;
	}
	
	public static function databaseKPI($psDB){
		return self::DATABASES."|$psDB|KPI";
	}
	
	public static function databaseTimeSpent($psDB){
		return self::databaseKPI($psDB)."|Time Spent in Executions (s)";
	}
	public static function databaseConnections($psDB){
		return self::databaseKPI($psDB)."|Number of Connections";
	}
	public static function databaseCalls($psDB){
		return self::databaseKPI($psDB)."|".self::CALLS_PER_MIN;
	}

	public static function databaseServerStats($psDB){
		return self::DATABASES."|$psDB|Server Statistic";
	}
	
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Errors
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function Errors($psTier, $psError){
		return self::ERRORS."|$psTier|$psError|".self::ERRS_PER_MIN;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* information Points
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function infoPointResponseTimes($psName){
		return self::INFORMATION_POINTS."|$psName|".self::RESPONSE_TIME;
	}
	
	public static function infoPointCallsPerMin($psName){
		return self::INFORMATION_POINTS."|$psName|".self::CALLS_PER_MIN;
	}

	public static function infoPointErrorsPerMin($psName){
		return self::INFORMATION_POINTS."|$psName|".self::ERRS_PER_MIN;
	}
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* infrastructure
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function InfrastructureNodes($psTier){
		return self::INFRASTRUCTURE."|$psTier|Individual Nodes";
	}
	
	public static function InfrastructureNode($psTier, $psNode= null){
		$sMetric = self::INFRASTRUCTURE."|$psTier";
		if ($psNode) $sMetric .= "|Individual Nodes|$psNode";
		
		return $sMetric;
	}
	
	public static function InfrastructureJDBCPools($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JMX|JDBC Connection Pools";
	}

	public static function InfrastructureJDBCPoolActive($psTier, $psNode=null, $psPool){
		return self::InfrastructureJDBCPools($psTier, $psNode)."|$psPool|Active Connections";
	}
	public static function InfrastructureJDBCPoolMax($psTier, $psNode=null, $psPool){
		return self::InfrastructureJDBCPools($psTier, $psNode)."|$psPool|Maximum Connections";
	}

	public static function InfrastructureNodeDisks($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Disks";
	}
	public static function InfrastructureNodeDiskFree($psTier, $psNode, $psDisk){
		return self::InfrastructureNodeDisks($psTier, $psNode)."|$psDisk|Space Available";
	}
	public static function InfrastructureNodeDiskUsed($psTier, $psNode, $psDisk){
		return self::InfrastructureNodeDisks($psTier, $psNode)."|$psDisk|Space Used";
	}
	
	public static function InfrastructureAppAgentAvailability($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Agent|App|Availability";
	}
	public static function InfrastructureAgentAvailability($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Agent|Machine|Availability";
	}

	public static function InfrastructureAgentMetricsUploaded($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Agent|Metric Upload|Metrics uploaded";
	}

	public static function InfrastructureAgentMetricsLicenseErrors($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."Agent|Metric Upload|Requests License Errors";
	}

	public static function InfrastructureAgentInvalidMetrics($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."Agent|Metric Upload|Invalid Metrics";
	}

	public static function InfrastructureMachineAvailability($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Machine|Availability";
	}

	public static function InfrastructureCpuBusy($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|CPU|%Busy";
	}

	public static function InfrastructureMemoryFree($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Memory|Free (MB)";
	}

	public static function InfrastructureDiskFree($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Disks|MB Free";
	}
	
	public static function InfrastructureDiskWrites($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Disks|KB written/sec";
	}

	public static function InfrastructureNetworkIncoming($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Network|Incoming KB/sec";
	}
	public static function InfrastructureNetworkOutgoing($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|Hardware Resources|Network|Outgoing KB/sec";
	}
	
	public static function InfrastructureJavaHeapUsed($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JVM|Memory|Heap|Current Usage (MB)";
	}
	public static function InfrastructureJavaHeapUsedPct($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JVM|Memory|Heap|Used %";
	}

	public static function InfrastructureJavaGCTime($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JVM|Garbage Collection|GC Time Spent Per Min (ms)";
	}
	public static function InfrastructureJavaCPUUsage($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|JVM|Process CPU Usage %";
	}
	public static function InfrastructureDotnetHeapUsed($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|CLR|Memory|Heap|Current Usage (bytes)";
	}
	public static function InfrastructureDotnetGCTime($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|CLR|Garbage Collection|GC Time Spent (%)";
	}
	public static function InfrastructureDotnetAnonRequests($psTier, $psNode=null){
		return self::InfrastructureNode($psTier, $psNode)."|ASP.NET Applications|Anonymous Requests";
	}
	
	public static function InfrastructureMetric($psTier, $psNode, $psMetric){
		return self::InfrastructureNode($psTier, $psNode)."|$psMetric";
	}
	
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Servers
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function serverRoot(){
		return self::INFRASTRUCTURE."|Root";
	}
	public static function serverIndividualNodes($psNodeName=null){
		$sMetricPath = self::serverRoot()."|Individual Nodes";
		if ($psNodeName) $sMetricPath.= "|$psNodeName";
		return $sMetricPath;
	}
	
	public static function serverNodesWithMQ(){
		return self::serverIndividualNodes()."|*|Custom Metrics|WebsphereMQ|Metrics Uploaded";
	}
	
	public static function serverMQManagers($psNode){
		return self::serverIndividualNodes($psNode)."|Custom Metrics|WebsphereMQ";
	}
	
	public static function serverMQQueues($psNode, $psManager){
		return self::serverMQManagers($psNode)."|$psManager|Queues";
	}

	public static function serverMQQueue($psNode, $psManager, $psQueue){
		return self::serverMQQueues($psNode, $psManager)."|$psQueue";
	}
	public static function serverMQQueueCurrent($psNode, $psManager, $psQueue){
		return self::serverMQQueue($psNode, $psManager, $psQueue)."|Current Queue Depth";
	}
	public static function serverMQQueueMax($psNode, $psManager, $psQueue){
		return self::serverMQQueue($psNode, $psManager, $psQueue)."|Max Queue Depth";
	}
	

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* transactions
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function Transaction($psTier, $psTrans, $psNode=null){
		$sMetric = cADTierMetricPaths::tierTransactions($psTier)."|$psTrans";
		if ($psNode) $sMetric .= "|Individual Nodes|$psNode";
		return $sMetric;
	}
	
	public static function transResponseTimes($poTrans, $psNode=null){
		return self::Transaction($poTrans->tier->name, $poTrans->name, $psNode)."|".self::RESPONSE_TIME;
	}

	public static function transErrors($poTrans, $psNode=null){
		return self::Transaction($poTrans->tier->name, $poTrans->name, $psNode)."|".self::ERRS_PER_MIN;
	}

	public static function transCpuUsed($poTrans, $psNode=null){
		return self::Transaction($poTrans->tier->name, $poTrans->name, $psNode)."|Average CPU Used (ms)";
	}
	
	public static function transCallsPerMin($poTrans, $psNode=null){
		return self::Transaction($poTrans->tier->name, $poTrans->name, $psNode)."|".self::CALLS_PER_MIN;
	}
	
	public static function transExtNames($poTrans, $psNode=null){
		return self::Transaction($poTrans->tier->name, $poTrans->name, $psNode)."|".self::EXT_CALLS;
	}
	
	public static function transExtCalls($poTrans, $psExt){
		return self::transExtNames($poTrans)."|$psExt|".self::CALLS_PER_MIN;
	}
		
	public static function transExtResponseTimes($poTrans, $psExt){
		return self::transExtNames($poTrans)."|$psExt|".self::RESPONSE_TIME;
	}
	public static function transExtErrors($poTrans, $psExt){
		return self::transExtNames($poTrans)."|$psExt|".self::ERRORS;
	}
}

//######################################################################
//#
//######################################################################
class cADAppMetricPaths{
	public static function app(){
		return cADMetricPaths::APPLICATION;
	}
	
	public static function appResponseTimes(){
		return self::app()."|".cADMetricPaths::RESPONSE_TIME;
	}
	
	public static function appCallsPerMin(){
		return self::app()."|".cADMetricPaths::CALLS_PER_MIN;
	}

	public static function appSlowCalls(){
		return self::app()."|".cADMetricPaths::SLOW_CALLS;
	}

	public static function appVerySlowCalls(){
		return self::app()."|".cADMetricPaths::VSLOW_CALLS;
	}

	public static function appStalledCount(){
		return self::app()."|".cADMetricPaths::STALL_COUNT;
	}
	public static function appErrorsPerMin(){
		return self::app()."|".cADMetricPaths::ERRS_PER_MIN;
	}
	public static function appExceptionsPerMin(){
		return self::app()."|".cADMetricPaths::EXCEP_PER_MIN;
	}

	public static function appBackends(){
		return cADMetricPaths::backends();
	}
	public static function appExtCalls(){
		return self::app()."|*|".cADMetricPaths::EXT_CALLS;
	}
}

//######################################################################
class cADTierMetricPaths{
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* tiers
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function tier($psTier){
		return cADMetricPaths::APPLICATION."|$psTier";
	}
	
	public static function tierCallsPerMin($psTier){
		return self::tier($psTier)."|".cADMetricPaths::CALLS_PER_MIN;
	}
	public static function tierResponseTimes($psTier){
		return self::tier($psTier)."|".cADMetricPaths::RESPONSE_TIME;
	}
	public static function tierErrorsPerMin($psTier){
		return self::tier($psTier)."|".cADMetricPaths::ERRS_PER_MIN;
	}
	public static function tierExceptionsPerMin($psTier){
		return self::tier($psTier).cADMetricPaths::EXCEP_PER_MIN;
	}
	public static function tierSlowCalls($psTier){
		return self::tier($psTier)."|".cADMetricPaths::SLOW_CALLS;
	}
	
	public static function tierNodes($psTier){
		return self::tier($psTier)."|Individual Nodes";
	}

	public static function tierVerySlowCalls($psTier){
		return self::tier($psTier)."|".cADMetricPaths::VSLOW_CALLS;
	}
	public static function tierTransactions($psTier){
		$sMetric = cADMetricPaths::TRANSACTIONS."|Business Transactions|$psTier";
		return $sMetric;
	}
	public static function tierTransaction($psTier, $psName){
		$sMetric = cADMetricPaths::TRANSACTIONS."|Business Transactions|$psTier|$psName";
		return $sMetric;
	}
	
	//----------------------------------------------------------------------------------
	public static function toTier($psTier1,$psTier2){
		if (strstr($psTier2, "|"))
			$sMetric = $psTier2;
		else
			$sMetric = self::tier($psTier1)."|".cADMetricPaths::EXT_CALLS."|$psTier2";
		return $sMetric;
	}
	
	public static function toTierCallsPerMin($psTier1,$psTier2){
		$sMetric = self::toTier($psTier1,$psTier2)."|".cADMetricPaths::CALLS_PER_MIN;
		return $sMetric;
	}

	public static function toTierResponseTimes($psTier1,$psTier2){
		$sMetric = self::toTier($psTier1,$psTier2)."|".cADMetricPaths::RESPONSE_TIME;
		return $sMetric;
	}

	public static function toTierErrorsPerMin($psTier1,$psTier2){
		$sMetric = self::toTier($psTier1,$psTier2)."|".cADMetricPaths::ERRS_PER_MIN;
		return $sMetric;
	}
	
	public static function extCalls($psTier){
		$sMetric = self::tier($psTier)."|". cADMetricPaths::EXT_CALLS;
		return $sMetric;
	}
	
	public static function threadTasks($psTier){ //4.5
		$sMetric = self::tier($psTier)."|Thread Tasks";
		return $sMetric;
	}
	
	public static function threadTaskExtCallNames($psTier, $psTask){ //4.5
		$sMetric = self::threadTasks($psTier)."|$psTask|".cADMetricPaths::EXT_CALLS;
		return $sMetric;
	}
	
	public static function threadTaskExtCallName($psTier, $psTask, $psExt){ //4.5
		$sMetric = self::threadTaskExtCallNames($psTier,$psTask)."|$psExt";
		return $sMetric;
	}
	
	public static function threadTasksAsyncExtCalls($psTier){
		$sMetric = self::threadTasks($psTier)."|AsyncRun|".cADMetricPaths::EXT_CALLS;
		return $sMetric;
	}
	
	public static function extCallsPerMin($psTier, $psExt){
		$sMetric = self::tier($psTier)."|Thread Tasks|$psExt|".cADMetricPaths::EXT_CALLS."|*|".cADMetricPaths::CALLS_PER_MIN;
		return $sMetric;
	}

	//----------------------------------------------------------------------------------
	public static function tierNodeCallsPerMin($psTier, $psNode=null){
		if ($psNode)
			return self::tierNodes($psTier)."|$psNode|".cADMetricPaths::CALLS_PER_MIN;
		else
			return self::tierCallsPerMin($psTier);
	}
	
	public static function tierNodeResponseTimes($psTier, $psNode=null){
		if ($psNode)
			return self::tierNodes($psTier)."|$psNode|".cADMetricPaths::RESPONSE_TIME;
		else
			return self::tierResponseTimes($psTier);
	}
	
	public static function tierServiceEndPoints($psTier){
		return cADMetricPaths::SERVICE_END_POINTS. "|$psTier";
	}
}


//#####################################################################################
//#
//#####################################################################################
class cADWebRumMetricPaths{
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* webrum
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function jobs(){
		return cADAppMetricPaths::END_USER."|Synthetic Jobs";
	}
	public static function App(){
		return cADAppMetricPaths::END_USER."|App";
	}
	public static function CallsPerMin(){
		return cADAppMetricPaths::app()."|Page Requests per Minute";
	}
	public static function ResponseTimes(){
		return cADAppMetricPaths::app()."|End User Response Time (ms)";
	}
	public static function JavaScriptErrors(){
		return cADAppMetricPaths::app()."|Page views with JavaScript Errors per Minute";
	}
	public static function FirstByte(){
		return cADAppMetricPaths::app()."|First Byte Time (ms)";
	}
	public static function ServerTime(){
		return cADAppMetricPaths::app()."|Application Server Time (ms)";
	}
	public static function TCPTime(){
		return cADAppMetricPaths::app()."|TCP Connect Time (ms)";
	}

	public static function Ajax(){
		return cADAppMetricPaths::END_USER."|AJAX Requests";
	}
	public static function Pages(){
		return cADAppMetricPaths::END_USER."|Base Pages";
	}
	
	public Static function Metric($psKind, $psPage, $psMetric)
	{
		switch ($psKind){
			case cADAppMetricPaths::BASE_PAGES:
			case cADAppMetricPaths::AJAX_REQ:
				break;
			default:
				cDebug::error("unknown kind");
		}
		return cADAppMetricPaths::END_USER."|$psKind|$psPage|$psMetric";
	}
	
	
	public static function PageCallsPerMin($psType, $psPage){
		return self::Metric($psType, $psPage, "Requests per Minute");
	}
	public static function PageResponseTimes($psType, $psPage){
		return self::Metric($psType, $psPage, "End User Response Time (ms)");
	}
	public static function PageFirstByte($psType, $psPage){
		return self::Metric($psType, $psPage, "First Byte Time (ms)");
	}
	public static function PageServerTime($psType, $psPage){
		return self::Metric($psType, $psPage, "Application Server Time (ms)");
	}
	public static function PageTCPTime($psType, $psPage){
		return self::Metric($psType, $psPage, "TCP Connect Time (ms)");
	}
	public static function PageJavaScriptErrors($psType, $psPage){
		return self::Metric($psType, $psPage, "Page views with JavaScript Errors per Minute");
	}

}
?>
