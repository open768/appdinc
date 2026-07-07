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
require_once(cAppGlobals::$ADlib."/common.php");

//#################################################################
//#
//#################################################################
class cAD_RBAC_User{
	public $id = null;
	public $username = null;
	public $display_name = null;
	public $email = null;
	public $groups = [];
	public $roles = [];
}

//#################################################################
//#
//#################################################################
class cAD_RBAC_Group{
	public $id = null;
	public $name = null;
	public $security_type;
	public $users = [];
	
	//*************************************************************
	//theres a bug with /api/rbac/v1/groups/name/$name - gives a 500 error
	public function get_info(){
		cTracing::enter();
		if (! $this->id) cDebug::error ("group ID missing");
		
		$oData = cADCore::GET("/api/rbac/v1/groups/$this->id",true,false,false);
		
		cTracing::leave();
		return $oData;
	}
	
	//*************************************************************
	public function get_security_provider_type(){
		cTracing::enter();
		
		if ($this->security_type) return $this->security_type;
		
		$oInfo = $this->get_info();
		$sType = $oInfo->security_provider_type;
		$this->security_type = $sType;
		
		cTracing::leave();
		return $sType;
	}
	
	//*************************************************************
	public function get_users(){
		$sType = $this->security_type;
		if ( $sType === "LDAP"){
			$aUsers = $this->pr__get_ldap_users();
			$this->users = $aUsers;
		}elseif ($sType === "INTERNAL"){
			$aUsers = $this->pr__get_internal_users($this);
			$this->users = $aUsers;
		}else
			cDebug::error("unknown source type $sType");
	}
	
	//*************************************************************
	private function pr__get_ldap_users(){
		cTracing::enter();
		
		$oRestUIData = cADRestUI::get_rbac_ldap_group_users($this->name);
		//cDebug::vardump($oRestUIData);
		$aData = self::pr__trim_ldap_group_users($oRestUIData, $this->name);
		usort ($aData, "AD_name_sort_fn");
		cTracing::leave();
		return $aData;
	}
	
	//*************************************************************
	//*************************************************************
	static private function pr__trim_ldap_group_users($poData, $psGroup){
		cTracing::enter();
		if ($poData == null){
			cDebug::extra_debug("no raw data");
			cTracing::leave();
			return null;
		}

		$aUsers = $poData->users;
		if (cArrayUtil::array_is_empty($aUsers)){
			cDebug::extra_debug("no user data");
			cTracing::leave();
			return null;
		}

		$aData = [];
		foreach ($aUsers as $oUser){
			if ($oUser->name == null) continue;
			//cDebug::vardump($oUser);
			
			$oOutUser = new cAD_RBAC_User;
			$oOutUser->name= $oUser->name;
			$oOutUser->display_name= $oUser->displayName;
			$oOutUser->email = $oUser->email;

			$aGroups = $oUser->groups;
			//cDebug::vardump($aGroups);
			$bInGroup = false;
			if ($aGroups !== null)
				foreach ($aGroups as $oGroup){
					$oOutUser->groups[] = $oGroup->name;
					if ($oGroup->name === $psGroup)  $bInGroup = true; //bug in Appd groups - shows all users regardless if they are in the group
				}
			if (!$bInGroup) 	continue;		//user not in group



			$aRoles = $oUser->roles;
			if ($aRoles !== null)
				foreach ($aRoles as $oRole)
					$oOutUser->roles[] = $oRole->name;

			$aData[] = $oOutUser;
		}
		cTracing::leave();
		return $aData;
	}
	
	//*************************************************************
	private function pr__get_internal_users(){
		cTracing::enter();
		$aAllUsers = cAD_RBAC::get_all_users();
		//get the userIDs that belong to the group
		$aGroupUserIDs = cADRestUI::get_rbac_internal_group_users($this->id);

		//merge the user ids with the all users data
		$aOut = [];
		foreach ($aGroupUserIDs as $iUserID){
			$oUser = $aAllUsers["U $iUserID"];
			$aOut[] = $oUser;
		}
		
		cTracing::leave();
		return $aOut;
	}
}

//#################################################################
//#
//#################################################################
class cAD_RBAC{
	//*************************************************************
	static function get_all_groups(){
		cTracing::enter();

		$aData = cADCore::GET("/api/rbac/v1/groups",true,false,false);
		if ($aData !== null) $aData = $aData->groups;
		usort ($aData, "AD_name_sort_fn");

		cTracing::leave();
		return $aData;
	}

		
	//*************************************************************
	static function get_all_users(){
		cTracing::enter();
		$aData = cADRestUI::get_rbac_all_users();
		$aUsers = [];
		foreach ($aData as $oInUser){
			$oOutUser = new cAD_RBAC_User;
			$oOutUser->id = $oInUser->id;
			$oOutUser->username = $oInUser->name;
			$oOutUser->display_name = $oInUser->displayName;
			$oOutUser->email = $oInUser->email;
			$aOutUsers["U $oOutUser->id"] = $oOutUser;
		}
		cTracing::leave();
		return $aOutUsers;
	}	
}
?>