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
class cADLogin{
	const KEY_HOST = "ho";
	const KEY_ACCOUNT = "ac";
	const KEY_USERNAME = "us";
	const KEY_PASSWORD = "pw";
	const KEY_REFERRER = "rf";
	const KEY_SUBMIT = "go";
	const KEY_APISECRET = "as";
	const KEY_APIAPP = "aa";
	const KEY_APITOKEN = "at";
	const KEY_JSESSION_ID = "js";
	const KEY_XCSRFTOKEN = "xc";
	const KEY_LOGINTOKEN = "lt";
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
	const ACCOUNT_KEY 		= "a";
	const ANALYTICS_API_APP = "b";
	const ANALYTICS_API_KEY = "c";
	const ANALYTICS_HOST 	= "d";
	const API_APP_KEY 	 	= "e";
	const API_SECRET_KEY 	= "f";
	const API_TOKEN_KEY 	= "g";
	const CSRF_TOKEN_KEY 	= "h";
	const LOGIN_TOKEN_KEY 	= "u";
	const GLOBAL_ACCOUNT_NAME = "i";
	const HOST_KEY 			= "j";
	const JSESSION_KEY 		= "k";
	const LOGGEDIN_KEY 		= "l";
	const PASSWORD_KEY 		= "m";
	const PROXY_CRED_KEY 	= "n";
	const PROXY_KEY 		= "o";
	const PROXY_PORT_KEY 	= "p";
	const RESTRICTED_LOGIN_KEY = "q";
	const USERNAME_KEY 		= "s";
	const USE_HTTPS_KEY 	= "t";
	
	const DEMO_USER = "demo";
	const DEMO_PASS = "d3m0";
	const DEMO_ACCOUNT = "demo";
	
	public $account;
	public $account_id;
	public $analytics_api_app;
	public $analytics_api_key;
	public $analytics_host;
	public $api_app;
	public $api_secret;
	public $api_token;
	public $csrftoken;
	public $encrypted_password;
	public $encrypted_username;
	public $encryption_key = "no encryption key set";
	public $global_account_name;
	public $host;
	public $is_logged_in = false;
	public $jsessionid;
	public $login_token;
	public $restricted_login = null;
	public $use_https;
	
	//**************************************************************************************
	//* construct
	//**************************************************************************************
	function __construct() {
		//cDebug::enter();
		$this->load_from_session();
		//cDebug::leave();
	}
	
	//**************************************************************************************
	function load_from_session(){
		$this->account = cCommon::get_session(self::ACCOUNT_KEY);
		$this->analytics_api_app = cCommon::get_session(self::ANALYTICS_API_APP);  
		$this->analytics_api_key = cCommon::get_session(self::ANALYTICS_API_KEY);  
		$this->analytics_host = cCommon::get_session(self::ANALYTICS_HOST);
		$this->api_app = cCommon::get_session(self::API_APP_KEY);  
		$this->api_secret = cCommon::get_session(self::API_SECRET_KEY);  
		$this->api_token = cCommon::get_session(self::API_TOKEN_KEY);  
		$this->csrftoken = cCommon::get_session(self::CSRF_TOKEN_KEY);  
		$this->login_token = cCommon::get_session(self::LOGIN_TOKEN_KEY);  
		$this->encrypted_password = cCommon::get_session(self::PASSWORD_KEY); 
		$this->encrypted_username = cCommon::get_session(self::USERNAME_KEY);
		$this->global_account_name = cCommon::get_session(self::GLOBAL_ACCOUNT_NAME);
		$this->host = cCommon::get_session(self::HOST_KEY);
		$this->jsessionid = cCommon::get_session(self::JSESSION_KEY);  
		$this->is_logged_in = cCommon::get_session(self::LOGGEDIN_KEY);  
		$this->restricted_login = cCommon::get_session(self::RESTRICTED_LOGIN_KEY);
		$this->use_https = cCommon::get_session(self::USE_HTTPS_KEY);
	}
	
	//**************************************************************************************
	function load_from_header(){
		//cDebug::enter();
		$username = null;
		$password = null;
		
		$this->host = cHeader::get(cADLogin::KEY_HOST);
		if (strstr($this->host , "http")) cDebug::error("host mustnt contain http");
		if (strstr($this->host , "/")) cDebug::error("host mustnt contain slashes");
		
		$this->account  = cHeader::get(cADLogin::KEY_ACCOUNT);
		cADCrypt::$credentials = $this;
		
		$username  = cHeader::get(cADLogin::KEY_USERNAME);
		if ($username)	$this->encrypted_username = cADCrypt::encrypt($username);
		$password  = cHeader::get(cADLogin::KEY_PASSWORD);
		if ($password)	$this->encrypted_password = cADCrypt::encrypt($password);
		
		$this->api_secret  = cHeader::get(cADLogin::KEY_APISECRET);
		$this->api_app  = cHeader::get(cADLogin::KEY_APIAPP);
		$this->api_token  = cHeader::get(cADLogin::KEY_APITOKEN);
		$this->login_token  = cHeader::get(cADLogin::KEY_LOGINTOKEN);
		
		$this->jsessionid  = cHeader::get(cADLogin::KEY_JSESSION_ID);
		$this->csrftoken  = cHeader::get(cADLogin::KEY_XCSRFTOKEN);
		
		$this->use_https = true;		
		
		$this->save();		//populate the session
		//cDebug::leave();
	}
	
	//**************************************************************************************
	//* utility functions
	//**************************************************************************************
	function check(){
		global $_SESSION, $_GET, $_POST;
		//cDebug::enter();
		try{
			if (!cCommon::is_string_set($this->login_token)){
				if(!cCommon::is_string_set($this->host)) cDebug::error("missing host");
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
					}elseif (cCommon::is_string_set($this->jsessionid)){
						if(!cCommon::is_string_set($this->csrftoken)) cDebug::error("missing csrftoken ");
					}elseif (cCommon::is_string_set($this->api_token)){
						//ok, thats all we need
					}else{
						//cDebug::extra_debug("checking for normal credentials");
						if(!cCommon::is_string_set($this->encrypted_password)) cDebug::error("missing password ");
					}
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
		cDebug::enter();
		
		cCommon::save_to_session(self::ACCOUNT_KEY, $this->account);
		cCommon::save_to_session(self::ANALYTICS_API_APP, $this->analytics_api_app);
		cCommon::save_to_session(self::ANALYTICS_API_KEY, $this->analytics_api_key);
		cCommon::save_to_session(self::ANALYTICS_HOST, $this->analytics_host);
		cCommon::save_to_session(self::API_APP_KEY, $this->api_app);
		cCommon::save_to_session(self::API_SECRET_KEY, $this->api_secret);
		cCommon::save_to_session(self::API_TOKEN_KEY, $this->api_token);
		cCommon::save_to_session(self::CSRF_TOKEN_KEY, $this->csrftoken);
		cCommon::save_to_session(self::GLOBAL_ACCOUNT_NAME, $this->global_account_name);
		cCommon::save_to_session(self::HOST_KEY, $this->host);
		cCommon::save_to_session(self::JSESSION_KEY, $this->jsessionid);
		cCommon::save_to_session(self::LOGGEDIN_KEY, $this->is_logged_in);
		cCommon::save_to_session(self::PASSWORD_KEY, $this->encrypted_password);
		cCommon::save_to_session(self::RESTRICTED_LOGIN_KEY, $this->restricted_login);
		cCommon::save_to_session(self::USERNAME_KEY, $this->encrypted_username);
		cCommon::save_to_session(self::USE_HTTPS_KEY, $this->use_https);
		
		cDebug::leave();
}
	
	//**************************************************************************************
	//this performs the login
	public function save(){
		cDebug::enter();
		//cDebug::write("saving TO SESSION");
		$this->is_logged_in = false; 			//always disregard previous flag
		$this->pr_save_to_session();
		

		//try to login - if it worked you are logged in
		if($this->analytics_api_key){
			cAdAnalytics::list_schemas();
		}else{
			if ($this->jsessionid){
				try{
					cADRestUI::GET_application_ids(); 
				}catch(Exception $e){
					cDebug::error("JsessionID or CSRF_TOKEN invalid");
				}
				$this->restricted_login = true;
			}else{
				if ($this->api_secret){
					cDebug::extra_debug("getting temporary access token");
					$this->get_api_token();
				}
				cADCore::login(); //will save jsession and csrf to the session
				cDebug::write("logged in");
				$this->load_from_session();
				cAudit::audit($this, "login"); //audit on success
				
				$this->is_logged_in = true;
			}
		}

		//-------------------------------------------------------------------------
		// save after successful login. wont get here if there is an exception
		cDebug::extra_debug("logged in successfully");
		$this->pr_save_to_session();
		cDebug::leave();
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
		cDebug::leave();
	}

	//**************************************************************************************
	//* Getters
	//**************************************************************************************
	public function logged_in(){
		if (!$this->is_logged_in){
			cDebug::vardump($this);
			cDebug::error("not logged in");
		}
			
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
		$this->account = null;
		$this->analytics_api_app = null;
		$this->analytics_api_key = null;
		$this->analytics_host = null;
		$this->api_app = null;
		$this->api_secret = null;
		$this->api_token = null;
		$this->encrypted_password = null;
		$this->encrypted_username = null;
		$this->global_account_name = null;
		$this->host = null;
		$this->jsessionid = null;
		$this->restricted_login = null;
		$this->use_https = null;
		
		$this->pr_save_to_session();
	}
	
	//**************************************************************************************
	//* Access Tokens 
	//**************************************************************************************
	public function get_api_token(){
		cDebug::enter();
		$this->check();
		
		$oHttp = new cHttp();
		$oHttp->USE_CURL = false;
		$oHttp->request_payload =
			"grant_type=client_credentials&" .
			"client_id=$this->api_app@$this->account&" .
			"client_secret=$this->api_secret";
		$oHttp->extra_headers = ["Content-Type" => "application/vnd.appd.cntrl+protobuf;v=1"];
		
		
		$sController = cADCore::GET_controller();
		$sUrl = $sController.cADCore::API_TOKEN_ACCESS_URL;
		$oResponse = $oHttp->getJson($sUrl);
		
		$this->api_token = $oResponse->access_token;
		$this->pr_save_to_session();
		
		cDebug::leave();
		return $this->api_token;
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

		//remove jsession ID from session 
		$oCred->jsessionid = null;
		
		//perform the login
		$oCred->save();
	}
	

}
?>