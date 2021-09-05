<?php

/**************************************************************************
Copyright (C) Chicken Katsu 2013 

This code is protected by copyright under the terms of the 
Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License
http://creativecommons.org/licenses/by-nc-nd/4.0/legalcode

For licenses that allow for commercial use please contact cluck@chickenkatsu.co.uk

// USE AT YOUR OWN RISK - NO GUARANTEES OR ANY FORM ARE EITHER EXPRESSED OR IMPLIED
**************************************************************************/

//#####################################################################################
//#
//#####################################################################################
class cAppdFlowMapStats{
	public $callsPerMin = 0;
	public $avgResponse = 0;
	public $errors = 0;
	function parse( $poStats){
		$this->callsPerMin = $poStats->callsPerMinute->metricValue;
		$this->avgResponse = $poStats->averageResponseTime->metricValue;
		$this->errors = $poStats->numberOfErrors->metricValue;
	}
}

class cAppdFlowMapNode{
	public $id = "not set";
	public $name = "not set";
	public $type;
	public $nodeCount = 0;
	public $stats = null;
	
    public function __construct(){
		$this->stats = new cAppdFlowMapStats;
	}
}
class cAppdFlowMapLink{
	public $source = null;
	public $target = null;
	public $stats = null;
	
    public function __construct(){
		$this->stats = new cAppdFlowMapStats;
	}
}

//#####################################################################################
//#
//#####################################################################################
class cAppdFlowMap{
	public $nodes = [];
	public $links = [];
	
	public function parse($paResponse){
		cDebug::enter();
		$this->pr__parse_nodes($paResponse->nodes);
		$this->pr__parse_edges($paResponse->edges);
		cDebug::leave();
	}
	
	//--------------------------------------------------------------------------------
	private function  pr__parse_edges($paEdges){
		cDebug::enter();
		$bDumped = true; //set this to false to dump the first edge
		foreach ($paEdges as $poEdge ){
			if (!$bDumped && cDebug::is_extra_debugging()) {
				cDebug::vardump($poEdge);
				$bDumped = true;
			}
			$oLink = new cAppdFlowMapLink;
			$id = $poEdge->sourceNodeDefinition->entityId;
			$oLink->source = $this->nodes[$id];
			$id = $poEdge->targetNodeDefinition->entityId;
			$oLink->target = $this->nodes[$id];
			$oLink->stats->parse($poEdge->stats[0]);			
			$this->links[] = $oLink ;
		}
		cDebug::leave();
	}

	//--------------------------------------------------------------------------------
	private function  pr__parse_nodes($paNodes){
		cDebug::enter();
		$bDumped = true;//set this to false to dump the first edge
		foreach ($paNodes as $poNode ){
			if (!$bDumped && cDebug::is_extra_debugging()) {
				cDebug::vardump($poNode);
				$bDumped = true;
			}
			$oNode = new cAppdFlowMapNode;
			$oNode->id = $poNode->idNum;
			$oNode->name = $poNode->name;
			$oNode->type = $poNode->entityType;
			$oNode->nodeCount = $poNode->nodeCount;
			$oNode->stats->parse($poNode->stats);			
			
			$this->nodes[$oNode->id] = $oNode ;
		}
		cDebug::leave();
	}

}
