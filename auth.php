<?php

/**************************************************************************
	Copyright (C) Chicken Katsu 2013 - 2022

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//see 
require_once("$phpinc/ckinc/header.php");
require_once("$phpinc/ckinc/hash.php");
require_once("$phpinc/ckinc/http.php");
require_once("$ADlib/common.php");
require_once("$phpinc/ckinc/debug.php");
require_once("$phpinc/ckinc/common.php");
require_once("$phpinc/ckinc/audit.php");
require_once("$phpinc/extra/php-openssl-crypt/cryptor.php");

//#################################################################
//# 
//#################################################################
//##################################################################
class cLogin{
	const KEY_HOST = "h";
	const KEY_ACCOUNT = "a";
	const KEY_USERNAME = "u";
	const KEY_PASSWORD = "p";
	const KEY_HTTPS = "ss";
	const KEY_REFERRER = "r";
	const KEY_SUBMIT = "s";
}

//#################################################################
//# encrypt with a key that is randomly generated - 
//# so that even the person hosting cant easily find the details
//#################################################################
class cADCrypt{
	public static $credentials = null;
	
	private static function pr__check_credentials(){
		if (self::$credentials == null) cDebug::error("account credentials missing");
		if (!cCommon::is_string_set(self::$credentials->host)) cDebug::error("host missing");
		if (!cCommon::is_string_set(self::$credentials->account)) cDebug::error("account missing");
	}
	
	private static function get_key(){
		self::pr__check_credentials();
		$sHash = "cADCrypt.key.".(self::$credentials->host).(self::$credentials->account);
		$sKey = "##key is not set##";
		if (cHash::exists($sHash)){
			$sKey = cHash::get($sHash);
		}else{
			$sKey = uniqid("",true);
			cHash::put($sHash, $sKey);
		}
		
		//return cADSecret::SESSION_ENCRYPTION_KEY;
		return $sKey;
	}
	
	public static function encrypt($psWhat){
		self::pr__check_credentials();
		return Cryptor::Encrypt($psWhat, self::get_key());
	}
	public static function decrypt($psWhat){
		self::pr__check_credentials();
		return Cryptor::Decrypt($psWhat, self::get_key());		
	}
}

//#################################################################
//# 
//#################################################################
class cADCredentials{
	const HOST_KEY = "apple";
	const ACCOUNT_KEY = "pear";
	const USERNAME_KEY = "orange";
	const PASSWORD_KEY = "lemon";
	const USE_HTTPS_KEY = "quince";
	const PROXY_KEY = "melon";
	const RESTRICTED_LOGIN_KEY = "basil";
	const PROXY_PORT_KEY = "grape";
	const PROXY_CRED_KEY = "diet";
	const LOGGEDIN_KEY = "log";
	const JSESSION_KEY = "boar";
	const CSRF_TOKEN_KEY = "spike";
	const API_SECRET_KEY = "van";
	const API_APP_KEY = "car";
	const API_TOKEN_KEY = "flat";
	const ANALYTICS_API_KEY = "wall";
	const ANALYTICS_API_APP = "stick";
	const GLOBAL_ACCOUNT_NAME = "bean";
	const ANALYTICS_HOST = "curd";
	
	const DEMO_USER = "demo";
	const DEMO_PASS = "d3m0";
	const DEMO_ACCOUNT = "demo";
	
	public $account;
	public $account_id;
	public $host;
	public $encrypted_username;
	public $jsessionid;
	public $csrftoken;
	public $encrypted_password;
	public $use_https;
	public $restricted_login = null;
	private $mbLogged_in = false;
	public $encryption_key = "no encryption key set";
	public $api_app;
	public $api_secret;
	public $api_token;
	public $analytics_api_key;
	public $analytics_api_app;
	public $global_account_name;
	public $analytics_host;
	
	//**************************************************************************************
	//* construct
	//**************************************************************************************
	function __construct() {
		//cDebug::enter();
		//retrieves stored values from the session $_SESSION
		$this->account = cCommon::get_session(self::ACCOUNT_KEY);
		$this->encrypted_username = cCommon::get_session(self::USERNAME_KEY);
		$this->encrypted_password = cCommon::get_session(self::PASSWORD_KEY); 

		$this->host = cCommon::get_session(self::HOST_KEY);
		$this->use_https = cCommon::get_session(self::USE_HTTPS_KEY);

		$this->restricted_login = cCommon::get_session(self::RESTRICTED_LOGIN_KEY);
		$this->mbLogged_in = cCommon::get_session(self::LOGGEDIN_KEY);  
		$this->jsessionid = cCommon::get_session(self::JSESSION_KEY);  
		$this->csrftoken = cCommon::get_session(self::CSRF_TOKEN_KEY);  
		$this->api_secret = cCommon::get_session(self::API_SECRET_KEY);  
		$this->api_app = cCommon::get_session(self::API_APP_KEY);  
		$this->api_token = cCommon::get_session(self::API_TOKEN_KEY);  
		$this->analytics_api_key = cCommon::get_session(self::ANALYTICS_API_KEY);  
		$this->analytics_api_app = cCommon::get_session(self::ANALYTICS_API_APP);  
		$this->global_account_name = cCommon::get_session(self::GLOBAL_ACCOUNT_NAME);
		$this->analytics_host = cCommon::get_session(self::ANALYTICS_HOST);
		
		//if (cDebug::is_extra_debugging()) cDebug::vardump($this);
		//cDebug::leave();
	}
	
	//**************************************************************************************
	function load_from_header(){
		cDebug::enter();
		$username = null;
		$password = null;
		
		$this->host = cHeader::get(cLogin::KEY_HOST);
		$this->account  = cHeader::get(cLogin::KEY_ACCOUNT);
		cADCrypt::$credentials = $this;
		
		$username  = cHeader::get(cLogin::KEY_USERNAME);
		if ($username)	$this->encrypted_username = cADCrypt::encrypt($username);
		$password  = cHeader::get(cLogin::KEY_PASSWORD);
		if ($password)	$this->encrypted_password = cADCrypt::encrypt($password);
		
		$sUse_https = cHeader::get(cLogin::KEY_HTTPS);
		
		$this->use_https = ($sUse_https=="yes");		
		
		$this->save();		//populate the session
		cDebug::leave();
	}
	
	//**************************************************************************************
	//* utility functions
	//**************************************************************************************
	function check(){
		global $_SESSION;
		//cDebug::enter();
		try{
			if(!cCommon::is_string_set($this->account)) cDebug::error("missing account");
			if(!cCommon::is_string_set($this->encrypted_username)) cDebug::error("missing username");
			
			if (cCommon::is_string_set($this->analytics_api_key)){
				//cDebug::extra_debug("checking for analytics api credentials");
				if(!cCommon::is_string_set($this->analytics_api_app)) cDebug::error("missing analytics API app");
				if(!cCommon::is_string_set($this->global_account_name)) cDebug::error("missing global account name ");
				if(!cCommon::is_string_set($this->analytics_host)) cDebug::error("missing analytics host ");
			}else{
				if (!$this->is_demo() && !cCommon::is_string_set($this->host)) cDebug::error("missing host");
				if (cCommon::is_string_set($this->api_secret)){
					//cDebug::extra_debug("checking for api credentials");
					if(!cCommon::is_string_set($this->api_app)) cDebug::error("missing api_app ");
				}else{
					//cDebug::extra_debug("checking for normal credentials");
					if(!cCommon::is_string_set($this->encrypted_password)) cDebug::error("password ");
				}
			}
		}	
		catch (Exception $e){
			$sMsg = $e->getMessage();
			cDebug::error($sMsg);
		}
		//cDebug::leave();
		
	}
		
	//**************************************************************************************
	//* Save
	//**************************************************************************************
	private function pr_save_to_session(){
		global $_SESSION;
		$_SESSION[self::HOST_KEY]  = $this->host;
		$_SESSION[self::ACCOUNT_KEY]  = $this->account;
		$_SESSION[self::USERNAME_KEY]  = $this->encrypted_username;
		$_SESSION[self::PASSWORD_KEY]  = $this->encrypted_password;
		$_SESSION[self::USE_HTTPS_KEY]  = $this->use_https;
		$_SESSION[self::RESTRICTED_LOGIN_KEY]  = $this->restricted_login;
		$_SESSION[self::API_SECRET_KEY]  = $this->api_secret;
		$_SESSION[self::API_APP_KEY]  = $this->api_app;
		$_SESSION[self::API_TOKEN_KEY]  = $this->api_token;
		$_SESSION[self::ANALYTICS_API_KEY]  = $this->analytics_api_key;
		$_SESSION[self::ANALYTICS_API_APP]  = $this->analytics_api_app;
		$_SESSION[self::GLOBAL_ACCOUNT_NAME]  = $this->global_account_name;
		$_SESSION[self::ANALYTICS_HOST]  = $this->analytics_host;
		$_SESSION[self::JSESSION_KEY]  = $this->jsessionid;
		$_SESSION[self::CSRF_TOKEN_KEY]  = $this->csrftoken;
	}
	
	//**************************************************************************************
	//this performs the login
	public function save(){
		//cDebug::enter();
		//cDebug::write("saving TO SESSION");
		global $_SESSION;
		
		$this->pr_save_to_session();

		//try to login - if it worked you are logged in
		if($this->analytics_api_key){
			cAdAnalytics::list_schemas();
			$this->mbLogged_in = false; //having an analytics key doesnt mean you are logged in
		}else{
			if ($this->api_secret){
				//try and get a temporary access token
				$this->get_access_token();
			}else{
				cADCore::login();
				cDebug::write("logged in");
				cAudit::audit($this, "login"); //audit on success
				
				$_SESSION[self::LOGGEDIN_KEY] = true;
			}
			$this->mbLogged_in = false;
		}
		//cDebug::leave();
	}
	
	//**************************************************************************************
	public function save_api_access_token( $poData){
		$_SESSION[self::API_TOKEN_KEY]  = $poData->access_token;
	}
	
	//**************************************************************************************
	public function save_restui_auth( $poHttp){
		cDebug::enter();
		global $_SESSION;
		
		$aHeaders = $poHttp->response_headers;
		
		foreach ($aHeaders as $oTuple)
			if ($oTuple->key === "Set-Cookie"){
				$aSplit = preg_split("/=/",$oTuple->value);
				if (count($aSplit) == 2){
					if ($aSplit[0] === "JSESSIONID")
						$this->jsessionid = $aSplit[1];
					elseif($aSplit[0] === "X-CSRF-TOKEN")
						$this->csrftoken = $aSplit[1];
				}
			}
		$this->pr_save_to_session();
		//cDebug::vardump($this);
		cDebug::leave();
	}

	//**************************************************************************************
	//* Getters
	//**************************************************************************************
	public function logged_in(){
		if (!$this->mbLogged_in)
			cDebug::error("not logged in");
			
		if ($this->restricted_login)
			if (!cHttp::page_matches($this->restricted_login))
				cDebug::error("restricted login");
		return true;
	}
	
	//**************************************************************************************
	public function get_username(){
		cADCrypt::$credentials = $this;
		return cADCrypt::decrypt($this->encrypted_username);
	}
	
	//**************************************************************************************
	public function get_password(){
		cADCrypt::$credentials = $this;
		return cADCrypt::decrypt($this->encrypted_password);
	}
	
	//**************************************************************************************
	public function encode(){
		//return urlencode(urlencode($this->get_username())."@".$this->account); //dont double encode
		return urlencode($this->get_username()."@".$this->account);
	}

	
	//**************************************************************************************
	public function is_demo(){
		//cDebug::enter();
		if ($this->account == self::DEMO_ACCOUNT){
			if (($this->get_username() == self::DEMO_USER) && ( $this->get_password() == self::DEMO_PASS)){
				cDebug::write("this is a demo login");
				return true;
			}else
				cDebug::error("wrong demo login details");
		}
		//cDebug::leave();
		return false;
	}
	
	//**************************************************************************************
	public function clear(){
		$this->host = null;
		$this->account = null;
		$this->encrypted_username = null;
		$this->encrypted_password = null;
		$this->use_https = null;
		$this->restricted_login = null;
		$this->api_secret = null;
		$this->api_app = null;
		$this->api_token = null;
		$this->analytics_api_key = null;
		$this->analytics_api_app = null;
		$this->global_account_name = null;
		$this->analytics_host = null;
		$this->pr_save_to_session();
	}
	
	//**************************************************************************************
	//* Access Tokens 
	//**************************************************************************************
	public function get_access_token(){
		//cDebug::enter();
		$this->check();
		
		$oHttp = new cHttp();
		$oHttp->USE_CURL = false;
		$oHttp->request_payload =
			"grant_type=client_credentials&" .
			"client_id=$oCred->api_app@$oCred->account&" .
			"client_secret=$oCred->api_secret";
		//cDebug::write("payload: $oHttp->request_payload");
		
		$sUrl = self::GET_controller().self::API_TOKEN_ACCESS_URL;
		$oResponse = $oHttp->getJson($sUrl);
		//cDebug::vardump($oResponse);
		
		$this->save_api_access_token($oResponse);
		//cDebug::leave();
		return $oResponse;
	}

	//**************************************************************************************
	//* Tokens - do these need to be static TODO
	//**************************************************************************************
	public static function get_login_token(){
		cDebug::enter();
		
		//------------- check login credentials --------------------------
		$oCred = new cADCredentials;
		if (!$oCred->logged_in()) cDebug::error("must be logged in");
		if ($oCred->restricted_login) cDebug::error("token not available in restricted login");
		
		//------------- generate the token --------------------------------
		$sKey = cCommon::my_IP_address().$oCred->host.$oCred->account.$oCred->get_username();
		$sHash = cHash::hash($sKey);
		cDebug::write("Key is $sKey, hash is $sHash");
		cHash::pr__put_obj($sHash, $oCred, true );
			
		return $sHash;
	}
	
	//**************************************************************************************
	public static function login_with_token($psToken ){
		$oCred = cHash::pr__get_obj($psToken);
		if ($oCred == null) cDebug::error("token not found");
		if (get_class($oCred) !== "cADCredentials") cDebug::error("unexpected class");

		//perform the login
		$oCred->save();
	}
	

}
?>