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
class cTransExtCalls{
    public $trans1, $trans2, $calls, $times;
}

class cAppdObj{
	public $application;
	public $tier;
	public $business_transaction;
	public $backend;
	public $metric_name;
	public $data = null;
}

class cAppdMetricLeaf{
	public $tier = null;
	public $name = null;
	public $metric = null;
	public $notes = null;
	public $children = [];
	
	public function has_children(){
		return count($this->children);
	}
	
	public function get_matching_names($psFragment, &$aOutput){
		if (strstr($this->name,$psFragment))
			$aOutput[] = $this;
		
		foreach ($this->children as $oChild)
			$oChild->get_matching_names($psFragment, $aOutput);
	}
	
	public function add_child($oChild){
		$this->children[] = $oChild;
	}
}

class cAppDDetails extends cAppdObj{
   public $name, $id, $calls, $times, $type;
   function __construct($psName, $psId, $poCalls, $poTimes) {
		$this->name = $psName;
		$this->id = $psId;
		$this->calls = $poCalls;
		$this->times = $poTimes;
   }
}

?>