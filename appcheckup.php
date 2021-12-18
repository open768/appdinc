<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//#################################################################
//# 
//#################################################################


class cAppCheckupMessage{
	public $message;
	public $is_bad;
	public $extra;
	
	function __construct($pbIsBad, $psMessage, $psExtra="") {	
		$this->is_bad = $pbIsBad;
		$this->message = $psMessage;
		$this->extra = $psExtra;
	}
}

class cAppCheckupAnalysis{
	public $general = [];
	public $DCs = [];
	public $BTs = [];
	public $tiers = [];
	public $backends = [];
	public $sendpoints = [];
}


//#################################################################
//# CLASSES
//#################################################################

class cADAppCheckup {
	static 	$badnames = ["swagger", "well-known", "WEB-INF", ".axd", "favicon", "actuator"];

	public static function checkup($poApp){
		cDebug::enter();

		$aTrans = $poApp->GET_Transactions();
		$oOut = new cAppCheckupAnalysis;
		
		//---------------------------------------------
		self::pr__check_general($poApp,$oOut);
		self::pr__check_BTs($poApp,$aTrans, $oOut);
		self::pr__check_DCs($poApp, $oOut);
		self::pr__check_Tiers($poApp,$aTrans, $oOut);
		self::pr__check_Backends($poApp, $oOut);
		self::pr__check_ServiceEndpoints($poApp, $oOut);
		
		//---------------------------------------------
		cDebug::leave();
		return $oOut;
	}
	
	//**************************************************************************
	private static function pr__check_ServiceEndpoints($poApp, $poOut){
		cDebug::enter();
		$aEndPoints = $poApp->GET_ServiceEndPoints();
		
		//-----------------------------------------------------------------
		$iCount = count($aEndPoints);
		cDebug::extra_debug("there are :$iCount endpoints");
		$sCaption = "There are $iCount Service endpoints. ";
		$bBad = false;
		if ($iCount >200){
			$bBad = true;
			$sCaption .= "Thats very high - <b>investigate instrumentation</b>";
		}elseif ($iCount >100){
			$bBad = true;
			$sCaption .= "Thats quite high";
		}
		$poOut->sendpoints[] = new cAppCheckupMessage($bBad,$sCaption,"endpoints") ;
		
		//-------------known bad BT names --------------------------------
		$aBadNames = [];
		foreach ($aEndPoints as $oEndPoint){
			foreach (self::$badnames as $sNeedle)
				if (stripos($oEndPoint->name, $sNeedle )) cCommon::add_count_to_array($aBadNames, $sNeedle);
		}
		foreach ($aBadNames as $sKey=>$iCount)
			 $poOut->sendpoints[] = new cAppCheckupMessage(true, "$iCount x  endpoints containing '$sKey' detected", "known unwanted names");
			 
		//-------------numeric --------------------------------
		$iIDCount = 0;
		
		foreach ($aEndPoints as $oEndPoint)
			if (cCommon::is_numeric_name($oEndPoint->name))$iIDCount++;
		if ($iIDCount >0 )
			$poOut->sendpoints[] = new cAppCheckupMessage(true, "$iIDCount x names found containing IDs", "Names");
			 
		cDebug::leave();
	}
	
	//**************************************************************************
	private static function pr__check_Backends($poApp, $poOut){
		cDebug::enter();
		//-------------backends --------------------------------
		$aBackends = $poApp->GET_Backends();
		$iCount = count($aBackends);
		if ($iCount ==0){
			$sCaption = "There no remote services detected.";
			$bBad = false;
		}else{
			$bBad = true;
			$sCaption = "There are $iCount remote services.";
			if ($iCount >=50)
				$sCaption .= " its a little on the high side";
			elseif ($iCount >=100)
				$sCaption .= " this doesnt look right, check the detection";
			else
				$bBad = false;
		}
		$poOut->backends[] = new cAppCheckupMessage($bBad,$sCaption,"backends") ;
		cDebug::leave();
	}
	
	
	//**************************************************************************
	private static function pr__check_Tiers($poApp, $paTrans, $poOut){
		cDebug::enter();
		//-------------tiers --------------------------------
		cDebug::extra_debug("counting tiers");
		$aTierCount = []; 	//counts the transactions per tier
		$aTierTrans = [];
		foreach ($paTrans as $oTrans){
			$sTier = $oTrans->tierName;
			if (! isset($aTierCount[$sTier])) {
				$aTierCount[$sTier] = 0;
				$aTierTrans[$sTier] = [];
			}
			$aTierCount[$sTier] = $aTierCount[$sTier] +1;
			$aTierTrans[$sTier][] = $oTrans->name;
		}
		ksort($aTierCount);
		
		
		cDebug::extra_debug("analysing tiers");
		if (count($aTierCount) == 0)
			$poOut->tiers[] = new cAppCheckupMessage(true,"no tiers defined","tiers") ;
		else{
			foreach ($aTierCount as $sTier=>$iCount){
				
				//- - - -  BTs in Tiers - - - - - - - - - - 
				$bBad = true;
				$sCaption = "There are $iCount BTs in this tier.";
				if ($iCount >=50)
					$sCaption .= " This must be below 50. <b>Investigate instrumentation</b>";
				elseif ($iCount >=10)
					$sCaption .= " The number of transactions is on the high side. we recommend around a max of 10 BTs per tier ";
				else
					$bBad = false;
				$poOut->tiers[] = new cAppCheckupMessage($bBad,$sCaption,$sTier) ;
				
				//- - - -  numeric BTs  - - - - - - - - - - 
				$iIDCount = 0;
				
				$aTransList = $aTierTrans[$sTier];
				foreach ($aTransList as $sTrans)
					if (cCommon::is_numeric_name($sTrans))$iIDCount++;
				if ($iIDCount >0 )
					$poOut->tiers[] = new cAppCheckupMessage(true, "$iIDCount x BTs found containing IDs", $sTier);

			}
		}	
		cDebug::leave();
	}
	
	//**************************************************************************
	private static function pr__check_DCs($poApp, $poOut){
		cDebug::enter();
		//-------------Data Collectors ----------------------
		cDebug::extra_debug("counting data collectors");
		$aDCs = $poApp->GET_data_collectors();
		$bBad = true;
		if (!$aDCs || count($aDCs) ==0){
			$sMsg = "no Data Collectors defined";
			$bBad = true;
		}elseif (count($aDCs)==1 && ($aDCs[0]->name === "Default HTTP Request Data Collector")){
			$sMsg = "only the Default HTTP Request DC defined: ";
			$bBad = true;
		}else{
			$sMsg = "number of DCs defined: ".count($aDCs);
			$bBad = false;
		}
		$oMsg = new cAppCheckupMessage($bBad, $sMsg, "data collectors");
		$poOut->DCs[] = $oMsg;
		cDebug::leave();
	}
	
	//**************************************************************************
	private static function pr__check_BTs($poApp, $paTrans, $poOut){
		//-------------BTs --------------------------------
		cDebug::extra_debug("analysing app $poApp->name");
		$iCount = count($paTrans);
		$sCaption  = "There are $iCount BTs.";
		$bBad = true;
		
		if ($iCount >=250)
			$sCaption .= " This must be below 250. <b>Investigate configuration</b>";
		elseif ($iCount >=100)
			$sCaption .= " The number of transactions is <b>very</b> high";
		elseif ($iCount >=50)
			$sCaption .= " The number of transactions is high";
		elseif ($iCount >=30)
			$sCaption .= " The number of transactions is on the high side. we recommend no more than about 30 BTs per application";
		elseif ($iCount < 5)
			$sCaption .= " There are too few BTs - check BT detection configuration";
		else
			$bBad = false;
		
		$poOut->BTs[] = new cAppCheckupMessage($bBad, $sCaption, "Count");
		
		//-------------known bad BT names --------------------------------
		$aBadBTs = [];
		foreach ($paTrans as $oTrans){
			$sTName = $oTrans->name;
			foreach (self::$badnames as $sNeedle)
				if (stripos($sTName, $sNeedle )) cCommon::add_count_to_array($aBadBTs, $sNeedle);
		}
		foreach ($aBadBTs as $sKey=>$iCount)
			 $poOut->BTs[] = new cAppCheckupMessage(true, "$iCount x $sKey known unwanted BTs detected", "BT Names");
		
		//-------------duplicate BTs  ----------------------
		$aTNames = [];
		foreach ($paTrans as $oTrans){
			$sTName = $oTrans->name;
			if (! isset($aTNames[$sTName])) $aTNames[$sTName] = 0;
			$aTNames[$sTName] ++;
		}
		ksort($aTNames);
		$iCountDup = 0;
		foreach ($aTNames as $sTName=>$iTcount){
			if ($sTName === cADCore::APPDYN_OVERFLOWING_BT ) continue;
			if ($iTcount >2){
				$oMsg = new cAppCheckupMessage(true, "$iTcount x $sTName", "duplicate BT");
				$poOut->BTs[] = $oMsg;
				$iCountDup ++;
			}
		}
		if ($iCountDup ==0){
			$oMsg = new cAppCheckupMessage(false, "no duplicate BTs found", "duplicate BT");
			$poOut->BTs[] = $oMsg;
		}

		//- - - -  numeric BTs  - - - - - - - - - - 
		$iIDCount = 0;
		$iAtCount = 0;
		foreach ($paTrans as $oTrans){
			$sTName = $oTrans->name;
			if (preg_match('/\/\d+/', $sTName, $matches)) $iIDCount++;
			elseif (preg_match('/\-d+/', $sTName, $matches)) $iIDCount++;
			if (strpos($sTName,"@")) $iAtCount++;
		}
		if ($iIDCount >0 )	$poOut->BTs[] = new cAppCheckupMessage(true, "$iIDCount x BTs found containing IDs", "BTs with IDs");
		if ($iAtCount >0 )	$poOut->BTs[] = new cAppCheckupMessage(true, "$iAtCount x BTs found containing '@' - possibly an email", "BTs with IDs");
		cDebug::leave();
	}
	
	//**************************************************************************
	private static function pr__check_general($poApp, $poOut){
		cDebug::enter();
		//-------------Active BTs --------------------------------
		if ($poApp->is_active())
			$poOut->general[] = new cAppCheckupMessage(false, "active application", "BT Activity");
		else
			$poOut->general[] = new cAppCheckupMessage(true, "this is an inactive application", "BT Activity");
		
		//-------------test application --------------------------------
		if (stripos($poApp->name, "test" ) || stripos($poApp->name, "dev" ) || stripos($poApp->name, "uat" ))
			$poOut->general[] = new cAppCheckupMessage(true, "this is a non production application", "test");
		
		//-------------general --------------------------------
		$aDiagnotics = $poApp->GET_diagnostic_stats();
		if (!array_key_exists( "numberOfMetricsUploaded", $aDiagnotics))
			$poOut->general[] = new cAppCheckupMessage(true, "unable to get Diagnostic stats", "Metrics");
		else{
			$iCount = $aDiagnotics["numberOfMetricsUploaded"];
			if ( $iCount == 0)
				$poOut->general[] = new cAppCheckupMessage(true, "no metrics uploaded", "Metrics");
			else
				$poOut->general[] = new cAppCheckupMessage(false, "$iCount metrics uploaded in the last hr", "Metrics");
			
			$iCount = $aDiagnotics["numberOfMetricsUploadRequestsExceedingLimit"];
			if ( $iCount > 0)
				$poOut->general[] = new cAppCheckupMessage(true, "$iCount metrics Exceeded limit in the last hr", "Metrics");
					
			$iCount = $aDiagnotics["numberOfMetricsUploadRequestLicenseErrors"];
			if ( $iCount > 0)
				$poOut->general[] = new cAppCheckupMessage(true, "$iCount metrics rejected due license issue in the last hr", "Metrics");
		}	
		
		//----- check lockdown------------------------------
		try{
			$oConfig = $poApp->GET_AppLevel_BT_Detection_Config();
		}catch (Exception $e){
			$oConfig = null;
			$poOut->general[] = new cAppCheckupMessage(true, "unable to check whether Business Transaction lockdown is enabled", "config");
		}
		if ($oConfig)
			if ($oConfig->isBtLockDownEnabled)
				$poOut->general[] = new cAppCheckupMessage(false, "Business Transaction lockdown is enabled", "config");
			else
				$poOut->general[] = new cAppCheckupMessage(true, "we recommend that Business Transaction lockdown is enabled", "config");
		
		cDebug::leave();
	}

}

?>
