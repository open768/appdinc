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
require_once(cAppGlobals::$ckPhpInc."//http.php");
require_once(cAppGlobals::$ckPhpInc."//cached_http.php");
require_once(cAppGlobals::$ADlib."/objects.php");
require_once(cAppGlobals::$ADlib."/demo.php");
require_once(cAppGlobals::$ADlib."/common.php");
require_once(cAppGlobals::$ADlib."/auth.php");
require_once(cAppGlobals::$ADlib."/core.php");
require_once(cAppGlobals::$ADlib."/account.php");
require_once(cAppGlobals::$ADlib."/util.php");
require_once(cAppGlobals::$ADlib."/analyse.php");
require_once(cAppGlobals::$ADlib."/time.php");
require_once(cAppGlobals::$ADlib."/metrics.php");
require_once(cAppGlobals::$ADlib."/controllerui.php");
require_once(cAppGlobals::$ADlib."/restui.php");
require_once(cAppGlobals::$ADlib."/website.php");

require_once(cAppGlobals::$ADlib."/controller.php");
require_once(cAppGlobals::$ADlib."/app.php");
require_once(cAppGlobals::$ADlib."/appcheckup.php");
require_once(cAppGlobals::$ADlib."/tier.php");
require_once(cAppGlobals::$ADlib."/bts.php");
require_once(cAppGlobals::$ADlib."/snapshot.php");
require_once(cAppGlobals::$ADlib."/db.php");
require_once(cAppGlobals::$ADlib."/server.php");
require_once(cAppGlobals::$ADlib."/rbac.php");
require_once(cAppGlobals::$ADlib."/analytics.php");


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
