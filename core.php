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
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/common.php");
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/ckinc/http.php");
require_once("$ADlib/common.php");
require_once("$ADlib/time.php");


//#################################################################
//# 
//#################################################################

class cADMetricRow{
	public $value;
	public $max;
	public $startTimeInMillis;
}

//#################################################################
//# 
//#################################################################
class cADCore{
	public static $CONTROLLER_PREFIX="controller";
	public static $SUFFIX = "&output=JSON";
	private static $bOutputController = false;
	const USUAL_METRIC_PREFIX = "/rest/applications/";
	const CONFIG_METRIC_PREFIX = "/rest/configuration";
	const DB_METRIC_PREFIX = "/rest/applications/Database%20Monitoring/metric-data?metric-path=";
	const RESTUI_PREFIX = "/restui/";
	const DBUI_PREFIX = "/databasesui/";
	const DATABASE_APPLICATION = "Database Monitoring";
	const SERVER_APPLICATION = "Server & Infrastructure Monitoring";
	const ENCODED_SERVER_APPLICATION = "Server%20&%20Infrastructure%20Monitoring";
	const LOGIN_URL = "/auth?action=login";
	const DEMO_HOST = "demo";
	const BEFORE_NOW_TIME = "bn";
	const METRIC_NOT_FOUND = "METRIC DATA NOT FOUND";
	const ALL_EVENT_TYPES = "POLICY_OPEN_CRITICAL,POLICY_OPEN_WARNING,POLICY_CLOSE,POLICY_CLOSE_CRITICAL,POLICY_CLOSE_WARNING,POLICY_CONTINUES_CRITICAL";
	const ALL_SEVERITIES = "WARN,ERROR,INFO";
	const APPDYN_OVERFLOWING_BT = "_APPDYNAMICS_DEFAULT_TX_";
	
	public static $URL_PREFIX = self::USUAL_METRIC_PREFIX;
	
	const DATE_FORMAT="Y-m-d\TG:i:s\Z";

	public static function GET_controller(){
		$oCred = new cADCredentials();
		$sController = ($oCred->use_https?"https":"http")."://$oCred->host";
		
		if (self::$CONTROLLER_PREFIX)	$sController.= "/".self::$CONTROLLER_PREFIX;
			
		//cDebug::extra_debug("controller URL: $sController");
		return $sController;
	}
	
	//*****************************************************************
	public static function login(){
		cDebug::enter();
		//TBD "controller/auth?action=login"		
		//-------------- get authentication info
		$oCred = new cADCredentials();
		$oCred->check();
		$sCred=$oCred->encode();
		
		if ($oCred->host === self::DEMO_HOST){
			cDebug::write("demo host detected");
			cDebug::leave();
			return "demo";
		}
		
		$oHttp = new cHttp();
		$oHttp->USE_CURL = false;
		$oHttp->set_credentials($sCred,$oCred->get_password());
		$sUrl = self::GET_controller(). self::LOGIN_URL;

		$oHttp->fetch_url($sUrl);	//will throw an error if unauthorised	
		$oCred->save_restui_auth($oHttp);
		cDebug::leave();
	}
	
	//*****************************************************************
	private static function pr__get_extra_header(){
		//-------------- get authentication info
		$oCred = new cADCredentials();
		$oCred->check();

		$sExtraHeader= "Content-Type: application/json";
		$sExtraHeader.= "\r\nAccept: application/json, text/plain, */*";
		$sExtraHeader.= "\r\nUser-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36";
		$sExtraHeader = "\r\nX-CSRF-TOKEN: $oCred->csrftoken";
		$sExtraHeader.= "\r\nCookie: JSESSIONID=$oCred->jsessionid; X-CSRF-TOKEN: $oCred->csrftoken;";	
		
		return $sExtraHeader;
	}
	
	//*****************************************************************
	public static function  GET_restUI_with_payload($psCmd,  $psPayload, $pbCacheable = false, $psUIPrefix=self::RESTUI_PREFIX ){
		global $oData;

		cDebug::enter();
		
		//-------------- get authentication info
		$oCred = new cADCredentials();
		$oCred->check();
		
		//-------------- convert object
		if (is_object($psPayload) || is_array($psPayload))
		$psPayload = json_encode($psPayload);
		
		//-------------- check the cache
		cDebug::write("getting $psCmd with payload");
		$sCacheCmd = $oCred->host.$oCred->account.$psCmd.$psPayload;
		
		if ($pbCacheable && (!cDebug::$IGNORE_CACHE) && cHash::exists($sCacheCmd)){
			cDebug::extra_debug("getting cached response");
			$oData = cHash::get($sCacheCmd);
			cDebug::leave();
			return $oData;
		}
		$sExtraHeader = self::pr__get_extra_header();

		
		//----- actually do it
		$sAD_REST = self::GET_controller().$psUIPrefix;
		$url = $sAD_REST.$psCmd;
		//cDebug::extra_debug("Url: $url");
		//cDebug::extra_debug("header: $sExtraHeader");
		
		$oHttp = new cHttp();
		$oHttp->USE_CURL = false;
		$oHttp->extra_header = $sExtraHeader;
		$oHttp->request_payload= $psPayload;
		try{
			$oData = $oHttp->getjson($url);
		}catch (Exception $e){
			if (strpos($e->getMessage(), "401")){
				cDebug::write("unauthorised - logging in again");
				self::login();
				cDebug::write("finished logging in, trying again");
				$oData = $oHttp->getjson($url);
			}elseif (strpos($e->getMessage(), "204")){
				cDebug::error("no data returned");
			}else{
				cDebug::write("unknown error: ".$e->getMessage());
				throw($e);
			}
		}
		
		//----- 
		if ($pbCacheable)	
			cHash::put($sCacheCmd, $oData,true);

		cDebug::leave();
		return $oData;
	}
	
	//*****************************************************************
	public static function  GET_restUI($psCmd, $pbCacheable = false, $psUIPrefix=self::RESTUI_PREFIX){
		cDebug::enter();
		$oData = self::GET_restUI_with_payload($psCmd, null, $pbCacheable, $psUIPrefix);
		cDebug::leave();
		return $oData;
	}

	//*****************************************************************
	public static function  GET($psCmd, $pbCacheable = false, $pbPrefix=true, $pbSuffix=true){
		global $oData;

		//cDebug::enter();
		//-------------- get authentication info
		$oCred = new cADCredentials();
		$oCred->check();
		
		//-------------- check the cache
		$sCacheCmd = $oCred->host.$oCred->account.$psCmd;
		
		if ($pbCacheable && (!cDebug::$IGNORE_CACHE) && cHash::exists($sCacheCmd)){
			cDebug::extra_debug("$psCmd cached");
			$iOld = cHash::$CACHE_EXPIRY;		//TBD to replace with cache instance
			cHash::$CACHE_EXPIRY = 600; //10 mins
			$oData = cHash::get($sCacheCmd); 
			cHash::$CACHE_EXPIRY = $iOld; //whatever it was
			//cDebug::leave();
			return $oData;
		}
		
		//-------------- build the url
		$sCred=$oCred->encode();
		$sAD_REST = self::GET_controller();
		if ($pbPrefix) $sAD_REST.=self::$URL_PREFIX;
		
		
		//----- actually do it
		$sUrl = $sAD_REST.$psCmd;
		if ($pbSuffix) $sUrl.=self::$SUFFIX;
		//cDebug::extra_debug("Url: $sUrl");
		
		$oHttp = new cHttp();
		//$sExtraHeader = self::pr__get_extra_header();
		//$oHttp->extra_header = $sExtraHeader;
		$oHttp->USE_CURL = false;
		
		$oHttp->set_credentials($sCred,$oCred->get_password());
		$oData = $oHttp->getjson($sUrl);
		
		//----- 
		if ($pbCacheable)	cHash::put($sCacheCmd, $oData,true);

		//cDebug::leave();
		return $oData;
	}
}

?>
