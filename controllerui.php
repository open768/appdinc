<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2016 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//#################################################################
//# CLASSES
//#################################################################
class cADControllerUI{
	//###############################################################################################
	//privates
	private static function pr__time_command($poTimes, $psKey="timeRange"){
		return  "$psKey=Custom_Time_Range.BETWEEN_TIMES.".$poTimes->end.".".$poTimes->start.".0";
	}
	
	private static function pr__get_location($psLocation){
		$sBaseUrl = cADCore::GET_controller();
		return $sBaseUrl."/#/location=$psLocation";		
	}
	
	private static function pr__get_app_location($poApp, $psLocation){
		$sBaseUrl = self::pr__get_location($psLocation);
		return $sBaseUrl."&application=$poApp->id";		
	}

	private static function pr__get_tier_location($poTier, $psLocation){
		$sBaseUrl = self::pr__get_app_location($poTier->app, $psLocation);
		return $sBaseUrl."&component=$poTier->id";		
	}

	//###############################################################################################
	public static function account_groups(){
		$sURL = self::pr__get_location("ACCOUNT_ADMIN_GROUPS");
		return $sURL;
	}
	public static function account_users(){
		$sURL = self::pr__get_location("ACCOUNT_ADMIN_USERS");
		return $sURL;
	}
	
	//###############################################################################################
	public static function agents(){
		$sURL = self::pr__get_location("SETTINGS_AGENTS");
		return $sURL;

	}
	public static function licenses(){
		$sURL = cADCore::GET_controller()."#/licensing/summary";
		return $sURL;
	}
	
	//###############################################################################################
	//# analytics
	public static function analytics_home(){
		return self::pr__get_location("ANALYTICS_HOME");
	}
	public static function analytics_config(){
		return self::pr__get_location("ANALYTICS_CONFIG_TXN_ANALYTICS_CONFIGURATION");
	}
	public static function log_analytics_config(){
		return self::pr__get_location("ANALYTICS_SOURCE_CONFIG");
	}
	
	//###############################################################################################
	//# apps
	public static function apps_home(){
		return self::pr__get_location("APPS_ALL_DASHBOARD");
	}
	public static function application($poApp){
		return self::pr__get_app_location($poApp, "APP_DASHBOARD");
	}
	public static function app_slow_transactions($poApp){
		return self::pr__get_app_location($poApp, "APP_SLOW_TRANSACTIONS");
	}
	
	public static function app_BT_config($poApp){
		return self::pr__get_app_location($poApp, "APP_TX_CONFIG_RULES");
	}
		
	//###############################################################################################
	//# Dashboards
	public static function dashboard_home(){
		return  self::pr__get_location("DASHBOARD_LIST");
	}
	public static function dashboard_detail($piDash, $poTimes){
		$sUrl = self::pr__get_location("CDASHBOARD_DETAIL");
		$sUrl .= "&".self::pr__time_command($poTimes);
		$sUrl .= "&mode=MODE_DASHBOARD&dashboard=$piDash";
		return $sUrl;
	}
	
	//###############################################################################################
	//# Databases
	public static function databases(){
		return  self::pr__get_location("DB_MONITORING_SERVER_LIST");
	}
	public static function db_custom_metrics(){
		return  self::pr__get_location("DB_MONITORING_CUSTOM_SQL_METRICS_LIST");
	}
	
	//###############################################################################################
	//# data collectors
	public static function data_collectors($poApp){
		$sUrl = self::pr__get_location("APP_CONFIG_DATA_COLLECTOR");
		$sUrl.= "&application=$poApp->id";
		return $sUrl;
	}
	
	//###############################################################################################
	//# Events
	public static function events($poApp){
		return self::pr__get_app_location($poApp, "APP_EVENTSTREAM_LIST");
	}

	public static function event_detail($piEventID){
		$sUrl = self::pr__get_location("APP_EVENT_VIEWER_MODAL");	
		$sUrl = cHttp::build_qs($sUrl, "eventSummary", $piEventID);
		return $sUrl;
	}
	public static function app_health_rules($poApp){
		$sUrl = self::pr__get_location("ALERT_RESPOND_HEALTH_RULES");	
		$sUrl = $sUrl."&application=$poApp->id";
		
		return $sUrl;
	}
	
	public static function app_health_policies($poApp){
		$sUrl = self::pr__get_location("ALERT_RESPOND_POLICIES");	
		$sUrl .= "&application=$poApp->id";
		
		return $sUrl;
	}
	
	//###############################################################################################
	//home
	public static function home(){
		$sURL = self::pr__get_location("AD_HOME");
		return $sURL;
	}	
	
	//###############################################################################################
	//# Nodes
	public static function nodes($poApp){
		$sURL = self::pr__get_app_location($poApp,"APP_INFRASTRUCTURE");
		return $sURL."&appServerListMode=grid";
	}

	public static function nodeDashboard($poApp, $piNodeID){
		$sURL = self::pr__get_app_location($poApp,"APP_NODE_MANAGER");
		return $sURL."&node=$piNodeID&dashboardMode=force";
	}
	
	public static function nodeAgent($poApp, $piNode){
		$sURL = self::pr__get_app_location($poApp, "APP_NODE_AGENTS");
		return $sURL."&bypassAssociatedLocationsCheck=true&tab=10&node=$piNode&memoryViewMode=0";
	}
	
	public static function machineDetails($piMachineID){
		$sURL = self::pr__get_location("INFRASTRUCTURE_MACHINE_DETAIL");	
		return $sURL."&machine=$piMachineID";
	}

	//###############################################################################################
	//# Remote services
	public static function remoteServices($poApp){
		return self::pr__get_app_location($poApp, "APP_BACKEND_LIST");
	}

	//###############################################################################################
	//# Tiers
	public static function tier_errors($poApp, $poTier){
		return self::pr__get_tier_location($poTier, "APP_TIER_ERROR_TRANSACTIONS");
	}
	
	public static function tier_slow_transactions($poApp, $poTier){
		return self::pr__get_tier_location($poTier, "APP_TIER_SLOW_TRANSACTIONS");
	}
	public static function tier_slow_remote($poApp, $poTier){
		return self::pr__get_tier_location($poTier, "APP_TIER_SLOW_DB_REMOTE_SERVICE_CALLS");
	}
	
	public static function tier($poApp, $poTier){
		$sURL = self::pr__get_tier_location($poTier, "APP_COMPONENT_MANAGER");
		return $sURL."&dashboardMode=force";
	}
	
	//###############################################################################################
	//# Service End POints
	public static function serviceEndPoints($poApp){
		return self::pr__get_app_location($poApp, "APP_SERVICE_ENDPOINT_LIST");
	}
	
	public static function serviceEndPoint($poTier, $piServiceID){
		$sURL = self::pr__get_tier_location($poTier, "APP_SERVICE_ENDPOINT_DASHBOARD");
		return $sURL."&serviceEndpoint=$piServiceID";
	}
	//###############################################################################################
	//# servers
	public static function servers(){
		return  self::pr__get_location("SERVER_LIST_PAGINATED");
	}

	//###############################################################################################
	//# snapshots
	
	public static function snapshot($poSnapshot){
		$oApp = $poSnapshot->trans->tier->app;
		$sTrid = $poSnapshot->trans->id;
		$oTime = new cADTimes($poSnapshot->starttime);
		$sGuuid = $poSnapshot->guuid;
		
		$sURL = self::pr__get_app_location($oApp, "APP_SNAPSHOT_VIEWER");
		$sTimeRange = self::pr__time_command($oTime);
		$sTimeRSD = self::pr__time_command($oTime, "rsdTime");
		return $sURL."&$sTimeRange&bypassAssociatedLocationsCheck=true&businessTransaction=$sTrid&requestGUID=$sGuuid&$sTimeRSD&dashboardMode=force";
	}
	
	public static function transaction_snapshots($poTrans, $poTimes){
		$oApp = $poTrans->tier->app;
		$sTrid = $poTrans->id;
		$sURL = self::pr__get_app_location($oApp, "APP_BT_ALL_SNAPSHOT_LIST");
		$sTime = self::pr__time_command($poTimes);
		return $sURL."&bypassAssociatedLocationsCheck=true&tab=1&businessTransaction=$sTrid&$sTime";
	}
	
	//###############################################################################################
	//# Transactions
	public static function businessTransactions($poApp){
		return  self::pr__get_app_location($poApp, "APP_BT_LIST");
	}
	
	public static function transaction($poTrans){
		$oApp = $poTrans->tier->app;
		$sTrid = $poTrans->id;
		$sURL = self::pr__get_app_location($oApp, "APP_BT_DETAIL");
		return $sURL."&businessTransaction=$sTrid&dashboardMode=force";
	}

	//###############################################################################################
	//webrum
	public static function webrum_pages($poApp){
		return self::pr__get_app_location($poApp, "EUM_PAGES_LIST");
	}
	public static function webrum($poApp){
		return self::pr__get_app_location($poApp, "APP_EUM_WEB_MAIN_DASHBOARD");
	}
	public static function webrum_detail($poApp, $psID){
		$sURL = self::pr__get_app_location($poApp, "EUM_PAGE_DASHBOARD");
		return $sURL."&addId=$psID";
	}
	public static function webrum_synthetics($poApp, $poTimes){
		$sUrl = self::pr__get_app_location($poApp, "EUM_SYNTHETIC_SCHEDULE_LIST");
		$sTime = self::pr__time_command($poTimes);
		return "$sUrl&$sTime";
	}
			
}
?>
