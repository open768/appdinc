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
require_once("$phpinc/ckinc/http.php");
require_once("$phpinc/ckinc/cached_http.php");
require_once("$phpinc/pubsub/pub-sub.php");
require_once("$ADlib/objects.php");
require_once("$ADlib/demo.php");
require_once("$ADlib/common.php");
require_once("$ADlib/auth.php");
require_once("$ADlib/core.php");
require_once("$ADlib/account.php");
require_once("$ADlib/util.php");
require_once("$ADlib/time.php");
require_once("$ADlib/metrics.php");
require_once("$ADlib/controllerui.php");
require_once("$ADlib/restui.php");
require_once("$ADlib/website.php");

require_once("$ADlib/controller.php");
require_once("$ADlib/app.php");
require_once("$ADlib/tier.php");
require_once("$ADlib/trans.php");


//#################################################################
//# CLASSES
//#################################################################
class cAD{
	public static function is_demo(){
		$oCred = new cADCredentials();
		$oCred->check();
		return $oCred->is_demo();
	}
}
?>
