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
require_once("$phpinc/ckinc/cached_http.php");
require_once("$phpinc/pubsub/pub-sub.php");
require_once("$appdlib/objects.php");
require_once("$appdlib/demo.php");
require_once("$appdlib/common.php");
require_once("$appdlib/auth.php");
require_once("$appdlib/core.php");
require_once("$appdlib/account.php");
require_once("$appdlib/util.php");
require_once("$appdlib/metrics.php");
require_once("$appdlib/controllerui.php");
require_once("$appdlib/restui.php");
require_once("$appdlib/website.php");

require_once("$appdlib/controller.php");
require_once("$appdlib/app.php");
require_once("$appdlib/tier.php");
require_once("$appdlib/trans.php");

//#################################################################
//# 
//#################################################################

cAppDApp::$db_app = new cAppDApp(cAppDynCore::DATABASE_APPLICATION,cAppDynCore::DATABASE_APPLICATION);



//#################################################################
//# CLASSES
//#################################################################
class cAppDyn{
	const APPDYN_LOGO = 'adlogo.jpg';
	const APPDYN_OVERFLOWING_BT = "_APPDYNAMICS_DEFAULT_TX_";
	const ALL_EVENT_TYPES = "POLICY_OPEN_CRITICAL,POLICY_OPEN_WARNING,POLICY_CLOSE,POLICY_CLOSE_CRITICAL,POLICY_CLOSE_WARNING,POLICY_CONTINUES_CRITICAL";
	const ALL_SEVERITIES = "WARN,ERROR,INFO";
	
	private static $maAppNodes = null;
	
	
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* All
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	
	
	public static function is_demo(){
		$oCred = new cAppDynCredentials();
		$oCred->check();
		return $oCred->is_demo();
	}
	
		
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	//* Databases
	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
	
	public static function GET_Database_ServerStats($psDB){
		$sMetricPath= cAppDynMetric::databaseServerStats($psDB);
		return  cAppdynCore::GET_Metric_heirarchy(cAppDynCore::DATABASE_APPLICATION, $sMetricPath, false);
	}
				
}
?>
