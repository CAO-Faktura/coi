<?php

/**
 *  Modul        : coiConfig.php
 *  Beschreibung : Script zum Datenaustausch CAO-Faktura <--> OXID eShop
 *                 Lauffähig unter OXID V 6.0.0
 * @author Thoren Strunk <edv@tstrunk.de>
 * @copyright Copyright (c) T.Strunk Software e.K.
 * Hinweis:
 * Dieses Projekt ist gemäß den Bedingungen der GPL V3 lizenziert
 **/

if (!defined('COI_ROOT'))
    die('Diese Datei kann nicht aufgerufen werden');

use OxidEsales\Eshop\Core\Registry;

class Config {

    public static $MinShopVersion = 6100;
    public static $MaxShopVersion = 6520;
    public static $ShopVersionRange = 6599;
    public static $COIVersion = '6.21';
    public static $COIVersionDate = "17.05.2023";
    public static $IsNoAuth = 0;
    public static $IsUserTax = 0;
    public static $IsVetena = 0;
    public static $NoHtmlLogin = 0;
    public static $UseOtherUtf8Mode = 0;
    public static $OverwriteImgName = true;
    public static $SaveErrorOff = 0;
    public static $aLicense = array();
    private static $_aCoiConfig = array(
        'ACTIVEARTICLE' => 1,
        'ARTICLEMATCHCODE' => 0,
        'TEXTDESCRIPTION' => 0,
        'CHANGEDESCRIPTION' => 0,
        'EKPRICE' => 0,
        'UVPPRICEFIELD' => 0,
        'OPENSTOCKORDER' => 0,
        'OPENSTOCKINVOICE' => 0,
        'USEBASEUNIT' => 1,
        'SCALEPRICEINPERCENT' => 1,
        'SPECIALPRICEINPERCENT' => 0,
        'ARTICLESORTINCATEGORIE' => 1,
        'SELECTVARIANT' => 0,
        'SELECTPRICE' => 0,
        'SETINVOICENUMBER' => 0,
        'SETDEADLINE' => 0,
        'USEMATCHCODE' => 1,
        'SENDASPAIDATE' => 1,
        'USEORDERGROSS' => 0,
        'USEOTHERTAX' => 0,
        'COMPANYNAME' => 0,
        'FIRSTNAME' => 1,
        'LASTNAME' => 2,
        'MATCHCOMPANYNAME' => 1,
        'MATCHFIRSTNAME' => 2,
        'MATCHLASTNAME' => 3,
        'ORDERADDINFO' => 0,
        'ORDERCOMPANYNAME' => 0,
        'ORDERFIRSTNAME' => 3,
        'ORDERLASTNAME' => 4,
        'ORDERREMARKMESSAGE' => 1,
        'ORDERREMARKBILLINFO' => 0,
        'ORDERREMARKDELIVERYINFO' => 0,
        'ORDERREMARKBILLFON' => 0,
        'ORDERREMARKDELIVERYFON' => 0,
        'USERFIELDS' => '',
        'PRICEGROUPA' => 0,
        'PRICEGROUPB' => 0,
        'PRICEGROUPC' => 0,
        'OVERWRITEPRICEGROUP' => 0,
        'PERSPARAM' => 0,
        'FREEPERSPARAM'  => 0,
        'USEORDERNR' => 0,
        'SELECTVARIANTTEXT' => 0,
        'USECUSTNUMBER' => 1,
        'ORDERUSERADDINFO' => 0,
        "STANDARDTAX" => 0,
        "STANDARDLAND" => "", 
        "REDUCEDTAX" => 0
    );
    public static $sSID = '7263';
    public static $sCSS = "body {font-size:70%;font-family:verdana, helvetica, arial;line-height:200%;background:#fff;color:#000;}
                        h1 { font-size:200%; }
                        h2 { font-size:140%; }
                        h3 { font-size:120%; }
                        h4 { font-size:100%; }
                        div.main {width:80%;text-align:left;top:20px;position:relative;border:2px solid #F0F0F0;padding:20px;}
                        input.input {border:1px solid #abadb3;backgroud:#fff;color:#000;padding:2px;}
                        textarea {border:1px solid #abadb3;backgroud:#fff;color:#000;padding:2px;}
                        input.button {background:#f2f2f2;border:1px solid #abadb3;color:#000;}
                        form {display:inline;}
                        label {color:#000;font-size:10pt;}
                        table {border:1px solid #e0e0e0;padding:2px;}
                        table.list {border: 0px;padding:0px;}
                        th {background:#f2f2f2;color:#333;font-size:11pt}
                        td.list {background:#fafafa;color:#000;font-size:11pt}
                        td.main {padding:3px;background:#fff;color:#000;font-size:8pt;vertical-align:top;}
                        td.head {font-size:100%;background:#ccc;color:#000;}
                        a {color:#333;}
                        p.main { padding:0; margin:0; }
                        #red {color: #db2000; }
                        #orange {color: #fc6306; }
                        #black { color: #000; }";
    private static $aSaveConfig = array();

    public static function getShopVers() {
        $iShopVer = (int) str_replace(".", "", Registry::getConfig()->getVersion());
        $iShopVer = $iShopVer * 10;
        return $iShopVer;
    }

    public static function MaxOxidShopVersion() {
        $iMaxVersion = self::$MaxShopVersion;
        if (file_exists(COI_ROOT_PATH . '/version.coi')) {
            if ($sVersion = file_get_contents(COI_ROOT_PATH . "/version.coi", NULL, NULL, 0, 5)) {
                if (is_numeric($sVersion)) {
                    if (($sVersion > $iMaxVersion ) && ( $sVersion <= self::$ShopVersionRange ))
                        $iMaxVersion = $sVersion;
                }
            }
        }
        return $iMaxVersion;
    }

    public static function ShopInUtf8() {
        if (Registry::getConfig()->isUtf()) {
            return 1;
        } else {
            return 0;
        }
    }

    public static function setCAOVersion($sVersionsNumber) {
        if (self::getCaoVersion() != $sVersionsNumber) {
            Registry::getConfig()->saveShopConfVar('str', 'sCaoVersion', $sVersionsNumber);
        }
    }

    public static function getCAOVersion() {
        $iVersionsNumber = (int) str_replace(".", "", Registry::getConfig()->getConfigParam('sCaoVersion'));
        if (strlen($iVersionsNumber) <= 4)
            $iVers *= 10;
        return $iVersionsNumber;
    }

    public static function setCaoLanguage($iLanguage) {
        Registry::getConfig()->saveShopConfVar('str', 'iCaoLanguage', $iLanguage);
    }

    public static function getCaoLanguage() {
        return (int) Registry::getConfig()->getConfigParam('iCaoLanguage');
    }

    public static function getLanguageArray() {
        $aLanguages = array();
        $aConfLanguages = Registry::getConfig()->getConfigParam('aLanguages');
        $aLangParams = Registry::getConfig()->getConfigParam('aLanguageParams');

        if (is_array($aConfLanguages)) {
            foreach ($aConfLanguages as $key => $val) {
                if (is_array($aLangParams)) {
                    if (!$aLangParams[$key]['active']) {
                        continue;
                    }
                }

                if ($val) {
                    $aLanguages[$aLangParams[$key]['baseId']] = array('ISO' => $key,
                        'NAME' => $val,
                        'ACTIVE' => $aLangParams[$key]['active']);
                }
            }
        }
        return $aLanguages;
    }

    public static function setCaoVariantSeperator($sStr) {
        Registry::getConfig()->saveShopConfVar('str', 'sCaoVariantSeperator', $sStr);
    }

    public static function getCaoVariantSeperator() {
        $sRet = Registry::getConfig()->getConfigParam('sCaoVariantSeperator');
        if (strlen($sRet) == 0)
            $sRet = ',';
        return $sRet;
    }

    public static function setCOIConfig() {
        if (self::$aSaveConfig = unserialize(Registry::getConfig()->getConfigParam('aCOI6Config'))) {
            if (is_array(self::$aSaveConfig)) {
                self::$_aCoiConfig = array(
                    'ACTIVEARTICLE' => self::$aSaveConfig[0],
                    'ARTICLEMATCHCODE' => self::$aSaveConfig[1],
                    'TEXTDESCRIPTION' => self::$aSaveConfig[2],
                    'CHANGEDESCRIPTION' => self::$aSaveConfig[3],
                    'EKPRICE' => self::$aSaveConfig[4],
                    'UVPPRICEFIELD' => self::$aSaveConfig[5],
                    'OPENSTOCKORDER' => self::$aSaveConfig[6],
                    'OPENSTOCKINVOICE' => self::$aSaveConfig[7],
                    'USEBASEUNIT' => self::$aSaveConfig[8],
                    'SCALEPRICEINPERCENT' => self::$aSaveConfig[9],
                    'SPECIALPRICEINPERCENT' => self::$aSaveConfig[10],
                    'ARTICLESORTINCATEGORIE' => self::$aSaveConfig[11],
                    'SELECTVARIANT' => self::$aSaveConfig[12],
                    'SELECTPRICE' => self::$aSaveConfig[13],
                    'SETINVOICENUMBER' => self::$aSaveConfig[14],
                    'SETDEADLINE' => self::$aSaveConfig[15],
                    'USEMATCHCODE' => self::$aSaveConfig[16],
                    'SENDASPAIDATE' => self::$aSaveConfig[17],
                    'USEORDERGROSS' => self::$aSaveConfig[18],
                    'USEOTHERTAX' => self::$aSaveConfig[45],
                    'COMPANYNAME' => self::$aSaveConfig[19],
                    'FIRSTNAME' => self::$aSaveConfig[20],
                    'LASTNAME' => self::$aSaveConfig[21],
                    'MATCHCOMPANYNAME' => self::$aSaveConfig[22],
                    'MATCHFIRSTNAME' => self::$aSaveConfig[23],
                    'MATCHLASTNAME' => self::$aSaveConfig[24],
                    'ORDERADDINFO' => self::$aSaveConfig[25],
                    'ORDERCOMPANYNAME' => self::$aSaveConfig[26],
                    'ORDERFIRSTNAME' => self::$aSaveConfig[27],
                    'ORDERLASTNAME' => self::$aSaveConfig[28],
                    'ORDERREMARKMESSAGE' => self::$aSaveConfig[29],
                    'ORDERREMARKBILLINFO' => self::$aSaveConfig[30],
                    'ORDERREMARKDELIVERYINFO' => self::$aSaveConfig[31],
                    'ORDERREMARKBILLFON' => self::$aSaveConfig[32],
                    'ORDERREMARKDELIVERYFON' => self::$aSaveConfig[33],
                    'USERFIELDS' => self::$aSaveConfig[34],
                    'PRICEGROUPA' => self::$aSaveConfig[35],
                    'PRICEGROUPB' => self::$aSaveConfig[36],
                    'PRICEGROUPC' => self::$aSaveConfig[37],
                    'OVERWRITEPRICEGROUP' => self::$aSaveConfig[38],
                    'PERSPARAM'  => self::$aSaveConfig[39],
                    'FREEPERSPARAM'  => self::$aSaveConfig[40],
                    'USEORDERNR' => self::$aSaveConfig[41],
                    'SELECTVARIANTTEXT' => self::$aSaveConfig[42],
                    'USECUSTNUMBER' => self::$aSaveConfig[43],
                    'ORDERUSERADDINFO' => self::$aSaveConfig[44],
                    'STANDARDTAX' => self::$aSaveConfig[46],
                    'STANDARDLAND' =>  self::$aSaveConfig[47],
                    'REDUCEDTAX' =>  self::$aSaveConfig[48]
                );
            }
        }
    }

    public static function getCOIConfig($sName) {
        if ($sName == 'all')
            return self::$_aCoiConfig;
        else if (isset(self::$_aCoiConfig[$sName]))
            return self::$_aCoiConfig[$sName];
    }

    public static function saveCOIConfig() {
        self::$aSaveConfig = array_fill(0, 42, 0);

        foreach ($_GET as $sKey => $aValue) {
            if (($sKey == session_name()) || ($sKey == 'sSID') || ($sKey == 'action') || ($sKey == 'Submit'))
                continue;
            else {
                switch ($sKey) {
                    case 'activearticle':
                        self::$aSaveConfig[0] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ACTIVEARTICLE'] = self::$aSaveConfig[0];
                        break;

                    case 'articlematchcode':
                        self::$aSaveConfig[1] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ARTICLEMATCHCODE'] = self::$aSaveConfig[1];
                        break;

                    case 'txtdescription':
                        self::$aSaveConfig[2] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['TEXTDESCRIPTION'] = self::$aSaveConfig[2];
                        break;

                    case 'changedescription':
                        self::$aSaveConfig[3] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['CHANGEDESCRIPTION'] = self::$aSaveConfig[3];
                        break;

                    case 'ekprice':
                        self::$aSaveConfig[4] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['EKPRICE'] = self::$aSaveConfig[4];
                        break;

                    case 'uvppricefield':
                        self::$aSaveConfig[5] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['UVPPRICEFIELD'] = self::$aSaveConfig[5];
                        break;

                    case 'openstockorder':
                        self::$aSaveConfig[6] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['OPENSTOCKORDER'] = self::$aSaveConfig[6];
                        break;

                    case 'openstockinvoice':
                        self::$aSaveConfig[7] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['OPENSTOCKINVOICE'] = self::$aSaveConfig[7];
                        break;

                    case 'usebaseunit':
                        self::$aSaveConfig[8] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['USEBASEUNIT'] = self::$aSaveConfig[8];
                        break;

                    case 'scalepriceinpercent':
                        self::$aSaveConfig[9] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['SCALEPRICEINPERCENT'] = self::$aSaveConfig[9];
                        break;

                    case 'specialpriceinpercent':
                        self::$aSaveConfig[10] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['SPECIALPRICEINPERCENT'] = self::$aSaveConfig[10];
                        break;

                    case 'articlesortincategorie';
                        self::$aSaveConfig[11] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ARTICLESORTINCATEGORIE'] = self::$aSaveConfig[11];
                        break;

                    case 'selectvariant':
                        self::$aSaveConfig[12] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['SELECTVARIANT'] = self::$aSaveConfig[12];
                        break;

                    case 'selectprice':
                        self::$aSaveConfig[13] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['SELECTPRICE'] = self::$aSaveConfig[13];
                        break;

                    case 'setinvoicenumber':
                        self::$aSaveConfig[14] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['SETINVOICENUMBER'] = self::$aSaveConfig[14];
                        break;

                    case 'setdeadline':
                        self::$aSaveConfig[15] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['SETDEADLINE'] = self::$aSaveConfig[15];
                        break;

                    case 'usematchcode';
                        self::$aSaveConfig[16] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['USEMATCHCODE'] = self::$aSaveConfig[16];
                        break;

                    case 'sendaspaidate':
                        self::$aSaveConfig[17] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['SENDASPAIDATE'] = self::$aSaveConfig[17];
                        break;

                    case 'useordergross';
                        self::$aSaveConfig[18] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['USEORDERGROSS'] = self::$aSaveConfig[18];
                        break;

                    case 'companyname';
                        self::$aSaveConfig[19] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['COMPANYNAME'] = self::$aSaveConfig[19];
                        break;

                    case 'firstname';
                        self::$aSaveConfig[20] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['FIRSTNAME'] = self::$aSaveConfig[20];
                        break;

                    case 'lastname';
                        self::$aSaveConfig[21] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['LASTNAME'] = self::$aSaveConfig[21];
                        break;

                    case 'matchcompanyname';
                        self::$aSaveConfig[22] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['MATCHCOMPANYNAME'] = self::$aSaveConfig[22];
                        break;

                    case 'matchfirstname';
                        self::$aSaveConfig[23] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['MATCHFIRSTNAME'] = self::$aSaveConfig[23];
                        break;

                    case 'matchlastname';
                        self::$aSaveConfig[24] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['MATCHLASTNAME'] = self::$aSaveConfig[24];
                        break;

                    case 'orderaddinfo';
                        self::$aSaveConfig[25] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERADDINFO'] = self::$aSaveConfig[25];
                        break;

                    case 'ordercompanyname';
                        self::$aSaveConfig[26] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERCOMPANYNAME'] = self::$aSaveConfig[26];
                        break;

                    case 'orderfirstname';
                        self::$aSaveConfig[27] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERFIRSTNAME'] = self::$aSaveConfig[27];
                        break;

                    case 'orderlastname';
                        self::$aSaveConfig[28] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERLASTNAME'] = self::$aSaveConfig[28];
                        break;

                    case 'orderremarkmsg';
                        self::$aSaveConfig[29] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERREMARKMESSAGE'] = self::$aSaveConfig[29];
                        break;

                    case 'orderremarkinfobill';
                        self::$aSaveConfig[30] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERREMARKBILLINFO'] = self::$aSaveConfig[30];
                        break;

                    case 'orderremarkinfodel';
                        self::$aSaveConfig[31] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERREMARKDELIVERYINFO'] = self::$aSaveConfig[31];
                        break;

                    case 'orderremarkfonbill';
                        self::$aSaveConfig[32] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERREMARKBILLFON'] = self::$aSaveConfig[32];
                        break;

                    case 'orderremarkfondel';
                        self::$aSaveConfig[33] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERREMARKDELIVERYFON'] = self::$aSaveConfig[33];
                        break;

                    case 'userfield':
                        self::$aSaveConfig[34] = self::SetUserFields($aValue);
                        self::$_aCoiConfig['USERFIELDS'] = self::$aSaveConfig[34];
                        break;

                    case 'pricegroupa';
                        self::$aSaveConfig[35] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['PRICEGROUPA'] = self::$aSaveConfig[35];
                        break;

                    case 'pricegroupb';
                        self::$aSaveConfig[36] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['PRICEGROUPB'] = self::$aSaveConfig[36];
                        break;

                    case 'pricegroupc';
                        self::$aSaveConfig[37] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['PRICEGROUPC'] = self::$aSaveConfig[37];
                        break;

                    case 'overwritepricegroup';
                        self::$aSaveConfig[38] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['OVERWRITEPRICEGROUP'] = self::$aSaveConfig[38];
                        break;
                    
                    case 'persparam';
                        self::$aSaveConfig[39] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['PERSPARAM'] = self::$aSaveConfig[39];
                        break;

                    case 'freepersparam';
                        self::$aSaveConfig[40] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['FREEPERSPARAM'] = self::$aSaveConfig[40];
                        break;
                    
                    case 'useordernr';
                        self::$aSaveConfig[41] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['USEORDERNR'] = self::$aSaveConfig[41];
                        break;
                    
                    case 'selvarianttext';
                        self::$aSaveConfig[42] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['SELECTVARIANTTEXT'] = self::$aSaveConfig[42];
                        break;

                    case 'usecustnumber';
                        self::$aSaveConfig[43] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['USECUSTNUMBER'] = self::$aSaveConfig[43];
                        break;

                    case 'orderuseraddinfo';
                        self::$aSaveConfig[44] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['ORDERUSERADDINFO'] = self::$aSaveConfig[44];
                        break;
                    
                    case 'useothertax';
                        self::$aSaveConfig[45] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['USEOTHERTAX'] = self::$aSaveConfig[45];
                        break;

                    case 'standardtax';
                        self::$aSaveConfig[46] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['STANDARDTAX'] = self::$aSaveConfig[46];
                        break;
                    
                    case 'standardland';
                        self::$aSaveConfig[47] = strtoupper($aValue);
                        self::$_aCoiConfig['STANDARDLAND'] = self::$aSaveConfig[47];
                        break;

                    case 'reducedtax';
                        self::$aSaveConfig[48] = isset($aValue) ? $aValue + 0 : 0;
                        self::$_aCoiConfig['REDUCEDTAX'] = self::$aSaveConfig[48];
                        break;
                    
                }
            }
        }

        $sSendConfig = serialize(self::$aSaveConfig);
        Registry::getConfig()->saveShopConfVar('aarr', 'aCOI6Config', $sSendConfig);
        return 1;
    }

    private static function SetUserFields($sValue) {
        $aUserfields = array();
        $aLines = explode("\n", $sValue);

        foreach ($aLines as $sLine) {
            $sLine = trim($sLine);
            if ($sLine != "" && preg_match("/(.+)=>(.+)/", $sLine, $regs)) {
                $iKey = trim($regs[1]);
                $sVal = trim($regs[2]);
                if ($iKey != "" && $sVal != "")
                    $aUserfields[$iKey] = $sVal;
            }
        }
        return serialize($aUserfields);
    }

}
