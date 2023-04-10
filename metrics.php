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
require_once("$ADlib/metricpaths.php");

//#################################################################
//# sort functions
//#################################################################
function ad_sort_by_metric_short($po1, $po2){
	return strcasecmp ($po1->metric->short, $po2->metric->short);
}

//#################################################################
//# CLASSES
//#################################################################
class cMetricItem{
	public $value;
	public $max;
	public $date;
}

//######################################################################
class cMetricOutput{
	public $div;
	public $metric;
	public $app;
	public $data = [];
	public $epoch_start;
	public $epoch_end;
	
	public function add($psDate, $piValue, $piMax = null){
		$oItem = new cMetricItem;
		$oItem->value = $piValue;
		$oItem->max = $piMax;
		$oItem->date = $psDate;
		
		$this->data[] = $oItem;
	}
}

//######################################################################
class cADInfraMetricTypeDetails{
	public $type;
	public $metric;
}

//######################################################################
class cADInfraMetric{
	const METRIC_TYPE_INFR_AVAIL = "mtia";
	const METRIC_TYPE_INFR_AGENT_METRICS = "mtiam";
	const METRIC_TYPE_INFR_AGENT_INVALID_METRICS = "mtiaim";
	const METRIC_TYPE_INFR_AGENT_LICENSE_ERRORS = "mtiale";
	const METRIC_TYPE_INFR_CPU_BUSY = "mticb";
	const METRIC_TYPE_INFR_MEM_FREE = "mtimf";
	const METRIC_TYPE_INFR_NETWORK_IN = "mtini";
	const METRIC_TYPE_INFR_NETWORK_OUT = "mtino";
	const METRIC_TYPE_INFR_JAVA_HEAP_USED = "mtijhu";
	const METRIC_TYPE_INFR_JAVA_HEAP_USEDPCT = "mtijup";
	const METRIC_TYPE_INFR_JAVA_GC_TIME = "mtijgt";
	const METRIC_TYPE_INFR_JAVA_CPU_USAGE = "mtijcpu";
	const METRIC_TYPE_INFR_DOTNET_HEAP_USED = "mtidhu";
	const METRIC_TYPE_INFR_DOTNET_GC_PCT = "mtidgp";
	const METRIC_TYPE_INFR_DOTNET_ANON_REQ = "mtidar";
	
	//**************************************************************************
	public static function getInfrastructureMetricDetails($poTier){
		$aTypes = self::getInfrastructureMetricTypes();
		$aOut = [];
		foreach ( $aTypes as $sType){
			$oMetric = self::getInfrastructureMetric($poTier->name,null,$sType);
			$oDetails = new cADInfraMetricTypeDetails;
			$oDetails->type = $sType;
			$oDetails->metric = $oMetric;
			$aOut[] = $oDetails;
		}
		usort($aOut,"ad_sort_by_metric_short");
		return $aOut;
	}
		
	//**************************************************************************
	public static function getInfrastructureMetricTypes(){
		$aMetricTypes = [cADMetricPaths::METRIC_TYPE_ACTIVITY, cADMetricPaths::METRIC_TYPE_RESPONSE_TIMES];
		$aMiscInfraMetricTypes = self::getInfrastructureMiscMetricTypes();
		$aAgentMetricTypes = self::getInfrastructureAgentMetricTypes();
		$aMemoryMetricTypes = self::getInfrastructureMemoryMetricTypes();
		$aMetricTypes = array_merge($aMetricTypes, $aMiscInfraMetricTypes,$aAgentMetricTypes, $aMemoryMetricTypes);
		return $aMetricTypes;
	}
	
	//**************************************************************************
	public static function getInfrastructureAgentMetricTypes(){
		$aTypes = 
		 [
			self::METRIC_TYPE_INFR_AVAIL,
			self::METRIC_TYPE_INFR_AGENT_METRICS,
			self::METRIC_TYPE_INFR_AGENT_INVALID_METRICS,
			self::METRIC_TYPE_INFR_AGENT_LICENSE_ERRORS,
		];
		
		//sort the list
		$aSortedList = [];
		foreach ($aTypes as $sMetricType){
			$oDetails = self::getInfrastructureMetric(null,null,$sMetricType);
			$aSortedList[$oDetails->caption] = $oDetails;
		}
		uksort($aSortedList, "strnatcasecmp");
		$aTypes = [];
		foreach ($aSortedList as $oDetails)
			$aTypes[] = $oDetails->type;
		
		return $aTypes;
	}

	//**************************************************************************
	public static function getInfrastructureMemoryMetricTypes(){
		$aTypes = 
		 [
			self::METRIC_TYPE_INFR_MEM_FREE,
			self::METRIC_TYPE_INFR_JAVA_HEAP_USEDPCT,
			self::METRIC_TYPE_INFR_JAVA_HEAP_USED,
			self::METRIC_TYPE_INFR_JAVA_GC_TIME,
			self::METRIC_TYPE_INFR_DOTNET_HEAP_USED,
			self::METRIC_TYPE_INFR_DOTNET_GC_PCT
		];
		
		//sort the list
		$aSortedList = [];
		foreach ($aTypes as $sMetricType){
			$oDetails = self::getInfrastructureMetric(null,null,$sMetricType);
			$aSortedList[$oDetails->caption] = $oDetails;
		}
		uksort($aSortedList, "strnatcasecmp");
		$aTypes = [];
		foreach ($aSortedList as $oDetails)
			$aTypes[] = $oDetails->type;
		
		return $aTypes;
	}
	
	//**************************************************************************
	public static function getInfrastructureMiscMetricTypes(){
		$aTypes = 
		 [
			self::METRIC_TYPE_INFR_CPU_BUSY,
			self::METRIC_TYPE_INFR_JAVA_CPU_USAGE,
			self::METRIC_TYPE_INFR_DOTNET_ANON_REQ,
			self::METRIC_TYPE_INFR_NETWORK_IN,
			self::METRIC_TYPE_INFR_NETWORK_OUT
		];
		
		//sort the list
		$aSortedList = [];
		foreach ($aTypes as $sMetricType){
			$oDetails = self::getInfrastructureMetric(null,null,$sMetricType);
			$aSortedList[$oDetails->caption] = $oDetails;
		}
		uksort($aSortedList, "strnatcasecmp");
		$aTypes = [];
		foreach ($aSortedList as $oDetails)
			$aTypes[] = $oDetails->type;
		
		return $aTypes;
	}
	
	//**************************************************************************
	public static function getInfrastructureMetric($psTier, $psNode=null, $psMetricType){			
			switch($psMetricType){
				case cADMetricPaths::METRIC_TYPE_ERRORS:
					if ($psTier)
						$sMetricUrl = cADTierMetricPaths::tierErrorsPerMin($psTier,$psNode);
					else
						$sMetricUrl = cADAppMetricPaths::appErrorsPerMin();
					$sCaption = "Errors per min";
					$sShortCaption = "Errors";
					break;
				case cADMetricPaths::METRIC_TYPE_ACTIVITY:
					if ($psTier)
						$sMetricUrl = cADTierMetricPaths::tierNodeCallsPerMin($psTier,$psNode);
					else
						$sMetricUrl = cADAppMetricPaths::appCallsPerMin();
					$sCaption = "Calls per min";
					$sShortCaption = "Activity";
					break;
				case cADMetricPaths::METRIC_TYPE_RESPONSE_TIMES:
					if ($psTier)
						$sMetricUrl = cADTierMetricPaths::tierNodeResponseTimes($psTier,$psNode);
					else
						$sMetricUrl = cADAppMetricPaths::appResponseTimes();
					$sCaption = "response times in ms";
					$sShortCaption = "Response";
					break;
				case self::METRIC_TYPE_INFR_AVAIL:
					$sMetricUrl = self::InfrastructureAgentAvailability($psTier,$psNode);
					$sCaption = "Agent Availailability";
					$sShortCaption = "Availability";
					break;
				case self::METRIC_TYPE_INFR_CPU_BUSY:
				$sMetricUrl = self::InfrastructureCpuBusy($psTier,$psNode);
					$sCaption = "CPU Busy";
					$sShortCaption = "CPU Busy";
					break;
				case self::METRIC_TYPE_INFR_MEM_FREE:
					$sMetricUrl = self::InfrastructureMemoryFree($psTier,$psNode);
					$sCaption = "Server memory free in MB";
					$sShortCaption = "Server Memory free (MB)";
					break;
				case self::METRIC_TYPE_INFR_NETWORK_IN:
					$sMetricUrl = self::InfrastructureNetworkIncoming($psTier,$psNode);
					$sCaption = "incoming network traffic in KB/sec ";
					$sShortCaption = "Network-in";
					break;
				case self::METRIC_TYPE_INFR_NETWORK_OUT:
					$sMetricUrl = self::InfrastructureNetworkOutgoing($psTier,$psNode);
					$sCaption = "outgoing network traffic in KB/sec ";
					$sShortCaption = "Network-out";
					break;
				case self::METRIC_TYPE_INFR_JAVA_HEAP_USED:
					$sMetricUrl = self::InfrastructureJavaHeapUsed($psTier,$psNode);
					$sCaption = "memory - Java Heap used ";
					$sShortCaption = "Java Heap used";
					break;
				case self::METRIC_TYPE_INFR_JAVA_HEAP_USEDPCT:
					$sMetricUrl = self::InfrastructureJavaHeapUsedPct($psTier,$psNode);
					$sCaption = "memory - Java Heap %Used ";
					$sShortCaption = "Java Heap %Used";
					break;
				case self::METRIC_TYPE_INFR_JAVA_GC_TIME:
					$sMetricUrl = self::InfrastructureJavaGCTime($psTier,$psNode);
					$sCaption = "Java GC Time ";
					$sShortCaption = "Java GC Time";
					break;
				case self::METRIC_TYPE_INFR_JAVA_CPU_USAGE:
					$sMetricUrl = self::InfrastructureJavaCPUUsage($psTier,$psNode);
					$sCaption = "CPU - Java Usage ";
					$sShortCaption = "Java CPU";
					break;
				case self::METRIC_TYPE_INFR_DOTNET_HEAP_USED:
					$sMetricUrl = self::InfrastructureDotnetHeapUsed($psTier,$psNode);
					$sCaption = "memory - dotNet heap used ";
					$sShortCaption = ".Net heap used";
					break;
				case self::METRIC_TYPE_INFR_DOTNET_GC_PCT:
					$sMetricUrl = self::InfrastructureDotnetGCTime($psTier,$psNode);
					$sCaption = "percent DotNet GC time  ";
					$sShortCaption = ".Net-GC";
					break;
				case self::METRIC_TYPE_INFR_DOTNET_ANON_REQ:
					$sMetricUrl = self::InfrastructureDotnetAnonRequests($psTier,$psNode);
					$sCaption = "DotNet Anonymous Requests  ";
					$sShortCaption = ".Net-Anonymous";
					break;
				case self::METRIC_TYPE_INFR_AGENT_METRICS:
					$sMetricUrl = self::InfrastructureAgentMetricsUploaded($psTier,$psNode);
					$sCaption = "Agent - Metrics uploaded  ";
					$sShortCaption = "Agent-Metrics";
					break;
				case self::METRIC_TYPE_INFR_AGENT_INVALID_METRICS:
					$sMetricUrl = self::InfrastructureAgentInvalidMetrics($psTier,$psNode);
					$sCaption = "Agent - Invalid Metrics  ";
					$sShortCaption = "Agent-Invalid Metrics";
					break;
				case self::METRIC_TYPE_INFR_AGENT_LICENSE_ERRORS:
					$sMetricUrl = self::InfrastructureAgentMetricsLicenseErrors($psTier,$psNode);
					$sCaption = "Agent - License Errors ";
					$sShortCaption = "Agent-License errors";
					break;
				default:
					cDebug::error("unknown Metric type");
			}	

			return (object)["metric"=>$sMetricUrl, "caption"=>$sCaption, "short"=>$sShortCaption , "type"=>$psMetricType];
	}
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* infrastructure
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	public static function InfrastructureNodes($psTier){
		return cADMetricPaths::INFRASTRUCTURE."|$psTier|Individual Nodes";
	}
	
	public static function InfrastructureNode($psTier, $psNode= null){
		$sMetric = cADMetricPaths::INFRASTRUCTURE."|$psTier";
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
	}}

//######################################################################
class cADMetricData{
	//*****************************************************************
	public static function GET_MetricData($poApp, $psMetricPath, $poTimes , $pbRollup=false, $pbCacheable=false, $pbMulti = false)
	{
		//cDebug::enter();
		if ($poTimes == null) cDebug::error("times are missing");
		$sApp = $poApp->name;
		
		$sRangeType = "";
		$sTimeCmd=cADTime::make($poTimes);
		
		$encoded = rawurlencode($psMetricPath);
		$encoded = str_replace(rawurlencode("*"),"*",$encoded);
		
		if ($sApp === cADCore::SERVER_APPLICATION)
			$sApp = cADCore::ENCODED_SERVER_APPLICATION;		//special case
		else
			$sApp = rawurlencode($sApp);
		
		$url = "$sApp/metric-data?metric-path=$encoded&$sTimeCmd";
		if (!$pbRollup) $url .= "&rollup=false";
		$oData = cADCore::GET( $url ,$pbCacheable);
		
		$aOutput = $oData;
		if (!$pbMulti && (count($oData) >0)) $aOutput = $oData[0]->metricValues; //watch out this will knobble the data
		
		//cDebug::leave();
		return $aOutput;		
	}
	
	//*****************************************************************
	public static function GET_Metric_heirarchy($poApp, $psMetricPath, $pbCached=true, $poTimes = null)
	{
		//cDebug::enter();
		cDebug::extra_debug("get Heirarchy: $psMetricPath");
		$sApp = $poApp->name;
		$encoded=rawurlencode($psMetricPath);	
		$encoded = str_replace("%2A","*",$encoded);			//decode wildcards
		
		if ($sApp === cADCore::SERVER_APPLICATION)
			$sApp = cADCore::ENCODED_SERVER_APPLICATION;		//special case
		else
			$sApp = rawurlencode($sApp);

		$sCommand = "$sApp/metrics?metric-path=$encoded";
		if ($poTimes == null || ( $poTimes === cADCore::BEFORE_NOW_TIME))
			$sTimeCmd=cADTime::beforenow();
		else
			$sTimeCmd=cADTime::make($poTimes);
		$sCommand .= "&$sTimeCmd";
		
		$oData = cADCore::GET($sCommand, $pbCached);
		cDebug::extra_debug("count of rows: ".count($oData));
		//cDebug::leave();
		return $oData;
	}
}
?>
