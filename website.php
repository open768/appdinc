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
//# CLASSES
//#################################################################
class cADWebsite{
	const DOWNLOAD_URL = "https://download.appdynamics.com/download/downloadfile/?apm=jvm%2Cdotnet%2Cphp%2Cmachine%2Cwebserver%2Cdb%2cAD4db%2Canalytics%2Cios%2Candroid%2Ccpp-sdk%2Cpython%2Cnodejs%2Cgolang-sdk%2Cuniversal-agent%2Ciot%2Cnetviz&eum=linux%2Cosx%2Cwindows%2Cgeoserver%2Cgeodata%2Csynthetic&events=linuxwindows&format=json&os=linux%2Cosx%2Cwindows&platform_admin_os=linux%2Cosx%2Cwindows";
	public static function GET_latest_downloads(){
		$oHttp = new cCachedHttp();
		$oHttp->USE_CURL = false;
		$sUrl = self::DOWNLOAD_URL;
		$aData = [];
		while ($sUrl){
			$oData = $oHttp->getCachedJson($sUrl);
			if ($oData->count >0){
				$sUrl = $oData->next;
				foreach ($oData->results as $oDownload)
					$aData[] = $oDownload;
			}else
				$sUrl = null;
		}
		
		usort($aData,"AD_title_sort_fn");
		return $aData;
	}
}
?>
