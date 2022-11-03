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
require_once("$ADlib/common.php");


//#################################################################
//#
//#################################################################
class cADAnalytics{
	
	const PORT=443;
	const CONTENT_TYPE="application/vnd.appd.events+json;v=2";
	
	//*************************************************************
	static private function pr_GET($psFragment, $psPayload=null){
		cDebug::enter();
		
		//get and check credentials
		$oCred = new cADCredentials();
		if (!$oCred->analytics_api_key) cDebug::error("no Analytics key");
		$oCred->check();
		
		//build the url
		$sHost = $oCred->analytics_host;
		$sUrl = "https://$sHost:".self::PORT."/$psFragment";
		$aHeaders= [
			"Accept"=> self::CONTENT_TYPE,
			"X-Events-API-AccountName" => $oCred->global_account_name,
			"X-Events-API-Key" => $oCred->analytics_api_key
		];
		
		if ($psPayload)	$aHeaders["Content-Type"] = self::CONTENT_TYPE;
		
		//perform the http request
		$oHttp = new cHttp();
		//$oHttp->debug = true;
		$oHttp->USE_CURL = false;
		$oHttp->extra_headers = $aHeaders;
		if ($psPayload){
			cDebug::extra_debug("Payload:$psPayload");
			$oHttp->request_payload= $psPayload;
		}
		
		cDebug::write("making request");
		$oData = $oHttp->getJson($sUrl);
		cDebug::leave();
		
		return $oData;
	}
	
	//*************************************************************
	static function list_schemas(){
		return  cADRestUI::GET_analytics_schemas();
	}

	//*************************************************************
	static function get_metric($psMetricName){
		$oOutMetric = null;
		$aMetrics = cADRestUI::GET_analytics_metrics();
		foreach ($aMetrics as $oMetric){
			if ($oMetric->queryName === $psMetricName){
				$oOutMetric = $oMetric;
				break;
			}
		}
		return $oOutMetric;
	}
	
	//*************************************************************
	static function list_metrics(){
		return  cADRestUI::GET_analytics_metrics();
	}
	
	//*************************************************************
	static function schema_fields($psSchemaName){
		return  cADRestUI::GET_analytics_schema_fields($psSchemaName);
	}
	
	//*************************************************************
	static function create_schema($psSchemaName, $psDetails){
		cDebug::enter();

		$sPayload  = "{ 'schema':{ $psDetails } }";
		// http request
		$oData = self::pr_GET("events/schema/$psSchemaName", $sPayload);
		
		cDebug::leave();
		return $oData;
	}
	
	//*************************************************************
	static function query($poTimes, $psQuery){
		cDebug::enter();
		cDebug::extra_debug( $poTimes->toString());
		
		$sStart = $poTimes->start;
		$sEnd = $poTimes->end;
		$sFragment = "events/query?start=$sStart&end=$sEnd";
		$sPayload =   "[{\"query\": \"$psQuery\"}]";
		$oData = self::pr_GET($sFragment, $sPayload);
		$oData = self::pr__parse_query_results($oData);
		cDebug::leave();
		return $oData;
	}
	
	//*************************************************************
	private static function pr__parse_query_results($poData){
		cDebug::enter();
		
		$aFields = $poData[0]->fields;
		$aResults = $poData[0]->results;
		$aOut = [];
		
		foreach ($aResults as $aItem){
			$aList = [];
			
			for ($i=0; $i<count($aItem); $i++)
				$aList[ $aFields[$i]->label] = $aItem[$i];
				
			$aOut[] = (object)$aList;
		}
		
		cDebug::leave();
		return $aOut;
	}
}
?>