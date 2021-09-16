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
require_once("$ADlib/appdynamics.php");

//#################################################################
//# 
//#################################################################
class cADTrans{
	public $name, $id, $tier;

   function __construct($poTier, $psTransName, $piTransId) {	
		$this->tier = $poTier;
		$this->name = $psTransName; 
		$this->id = $piTransId;
   }
	
	//*****************************************************************
	public function GET_ExtTiers(){
		$sMetricPath= cADMetric::transExtNames($this->tier->name,$this->name);
		return $this->tier->app->GET_Metric_heirarchy( $sMetricPath, false);
	}
}
?>
