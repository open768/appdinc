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
require_once(cAppGlobals::$ADlib."/AD.php");

//#################################################################
//# 
//#################################################################
class cADSnapshot{
	public $guuid, $starttime, $trans;
	public $timeTakenInMilliSecs, $url, $applicationComponentNodeId, $summary, $startdate; 

   function __construct($poTrans, $psGuuid, $psStartTime) {	
		if (!$poTrans ) cDebug::error("must provide a transaction");
		if (!$psGuuid ) cDebug::error("must provide a guuid");
		if (!$psStartTime ) cDebug::error("must provide an start time");
		$this->trans = $poTrans;
		$this->guuid = $psGuuid; 
		$this->starttime = $psStartTime;
		$iEpoch = (int) ($psStartTime/1000);
		$this->startdate = date(cCommon::ENGLISH_DATE_FORMAT, $iEpoch);
   }
	//*****************************************************************
	public function GET_segments(){
		cTracing::enter();

		$oResult = null;
		try{
			$oResult = cADRestUI::GET_snapshot_segments($this->guuid, $this->starttime);	
		}catch (Exception $e){
			cDebug::write("no Segments found");
		}
		cTracing::leave();
		return $oResult;
	}
	//*****************************************************************
	public function GET_segments_flow($oSegment){
		cTracing::enter();
		$oResult = cADRestUI::GET_snapshot_flow($oSegment);
		cTracing::leave();
		return $oResult;
	}
	
	public function GET_expensive_methods(){
		cTracing::enter();
		$oResult = cADRestUI::GET_snapshot_expensive_methods($this->guuid, $this->starttime);
		cTracing::leave();
		return $oResult;
	}
	
	//*****************************************************************
	public function count_ext_calls(){
		cTracing::enter();
		$oFlow = null;
		
		//---------------- get the segments
		$oSegments = $this->GET_segments();
		
		//---------------- get the flow
		if ($oSegments)
			try{
				$oFlow = cADRestUI::GET_snapshot_flow($oSegments);
			}catch (Exception $e){
				cDebug::write("no flows found");
			}
		
		//---------------- analyse the flow
		$oExtCalls = null;
		if ($oFlow) $oExtCalls = cADUtil::count_flow_ext_calls($oFlow);
		
		cTracing::leave();		
		return $oExtCalls;
	}
}
?>
