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
require_once("$ADlib/common.php");
require_once("$ADlib/metrics.php");

//#################################################################
//# CLASSES
//#################################################################

class cADDemo{
	
	private static function pr__gimme5($psCaption){
		$aData = [];
		for ($i=1; $i<6; $i++){
			$oInfo = new cADDetails("$psCaption - $i", $i, null,null);
			array_push($aData, $oInfo);
		}
		return $aData;
	}
	
	//*****************************************************************
	public static function GET_Backends($psApp){		return self::pr__gimme5("Backend");}
	public static function GET_AppInfoPoints($psApp){ 	return self::pr__gimme5("Infopoint");}
	public static function GET_AppExtTiers($psApp){ 	return self::pr__gimme5("Call to External System ");}
	public static function GET_Tiers($poApp){			return self::pr__gimme5("Tier");}
	public static function GET_TierServiceEndPoints($p,$t){ return self::pr__gimme5("$t EndPoint");}
	//*****************************************************************
	public static function GET_Applications(){
		$aData = [];
		for ($i=1; $i<5; $i++){
			$oApp = new cADApp("Application ".$i, $i);
			array_push($aData, $oApp);
		}
		return $aData;
	}
	
	//*****************************************************************
	public static function GET_MetricData($poApp, $psMetricPath, $poTimes , $pbRollup=false, $pbCacheable=false, $pbMulti = false){
		$aOutput = [];
		
		$epoch_start = $poTimes->start;
		$epoch_end = $poTimes->end;
		
		$iDTime = ($epoch_end - $epoch_start)/100;
		
		//set up random values for y = a + bx + cx^2 + dx^3
		 $x=0;
		 $a=rand(1,100)-50;
		 $b=rand(1,100)-50;
		 $c=rand(1,100)-50;
		 $d=rand(1,100)-50;
		
		for ($i = $epoch_start; $i<=$epoch_end; $i+=$iDTime){
		
			$iVal = $a + ($b*$x) + ($c*pow($x,2)) + ($d*pow($x,3));
			$oRow = new cADMetricRow;
			$oRow->startTimeInMillis = $i;
			$oRow->value = $iVal;
			$oRow->max = $iVal;
			
			
			array_push( $aOutput, $oRow);
			$x ++;
		}
		
		return $aOutput;
	}
}
?>
