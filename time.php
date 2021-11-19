<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

require_once("$ADlib/common.php");

class cADTimes{
	const BEFORE_NOW = 1;
	const BETWEEN = 2;
	public $time_type;
	public $start;
	public $end;
	public $duration;
	
	function __construct($piTime = null) {	
		$this->time_type = self::BETWEEN;
		if ($piTime){
			$this->start = $piTime - 5000; //5secs either way
			$this->end = $piTime + 5000;
		}else{
			$this->time_type = SELF::BEFORE_NOW;
			$this->end = time() * 1000;
			$this->start = $this->end - 3600000; //past hour
			$this->duration = 60;
		}
	}
}

class cADTime {
	public static function last_hour(){
		return "time-range=last_1_hour.BEFORE_NOW.-1.-1.60";
	}
	
	//*****************************************************************
	public static function beforenow($piMinutes=60){
		return "time-range-type=BEFORE_NOW&duration-in-mins=$piMinutes";
	}
	//*****************************************************************
	public static function make($poTime, $psKey="time-range-type"){
		if ($poTime->time_type == cADTimes::BEFORE_NOW)
			return "$psKey=BEFORE_NOW&duration-in-mins=".$poTime->duration;
		else
			return "$psKey=BETWEEN_TIMES&start-time=".$poTime->start."&end-time=".$poTime->end;
	}
	//*****************************************************************
	public static function make_short($poTime,$psKey="timeRange"){
		$sTime = "Custom_Time_Range.BETWEEN_TIMES.".$poTime->end.".".$poTime->start.".60";
		if ($psKey)
			return "$psKey=$sTime";
		else
			return $sTime;
	}
	
	//*****************************************************************
	public static function timestamp_to_date( $piMs){
		$iEpoch = (int) ($piMs/1000);
		return date(cCommon::ENGLISH_DATE_FORMAT, $iEpoch);
	}
		
}

?>
