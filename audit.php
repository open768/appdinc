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
class cAuditActivity{
	public $name;
	public $count;
	public $entries = [];
}

class cADAudit{
	//********************************************************************
	static function getEntries($poTimes, $psFilter=""){
		cDebug::enter();
		if (! $poTimes instanceof cADTimes) cDebug::error("not a cADTimes");
		
		//get the start and end times //TODO need to get the time zone of the controller
		$dStart = $poTimes->start_time();
		$dEnd = $poTimes->end_time();
		$sStart = $dStart->format('Y-m-d\TH:i').":00.000-0000"; //-0000 is the timezone
		$sEnd = $dEnd->format('Y-m-d\TH:i').":00.000-0000";
		
		//check that the difference between start and end time is less than 24hrs
		
		//call the API
		$sUrl="/ControllerAuditHistory?startTime=$sStart&endTime=$sEnd&output=JSON";
		if ($psFilter !== "") $sUrl .= "&include=$psFilter";
		$aData = cADCore::GET($sUrl,false,false,false);
		
		cDebug::leave();
		return $aData;
	}
	
	//********************************************************************
	static function getActions($poTimes){
		cDebug::enter();
		$aEntries = self::getEntries($poTimes);
		$aCount = [];
		foreach ($aEntries as $oEntry)
			cArrayUtil::add_count_to_array($aCount, $oEntry->action);
		ksort($aCount);
		
		$aActions = [];
		foreach ($aCount as $sName=>$iCount){
			$oItem = new cAuditActivity;
			$oItem->name = $sName;
			$oItem->count = $iCount;
			$aActions[] = $oItem;
		}
		
		cDebug::leave();
		return $aActions;
	}
	
	//********************************************************************
	static function getActionDetail($psAction, $poTimes){
		cDebug::enter();
		$aDetails = [];
		$aEntries = self::getEntries($poTimes, "action:$psAction");
		foreach ($aEntries as $oEntry)
			if ($oEntry->action ==  $psAction)
				$aDetails[] = $oEntry;
		
		cDebug::leave();
		return $aDetails;
	}
	
}
?>