<?php

/**
 *  Modul        : coiFunc.php
 *  Beschreibung : Script zum Datenaustausch CAO-Faktura <--> OXID eShop
 *                 Lauffähig unter OXID V 6.0.0
 * @author Thoren Strunk <edv@tstrunk.de>
 * @copyright Copyright (c) T.Strunk Software e.K.
 * Hinweis:
 * Dieses Projekt ist gemäß den Bedingungen der GPL V3 lizenziert
 **/

if (!defined('COI_ROOT'))
    die('Diese Datei kann nicht aufgerufen werden');

if (!defined('OXID_ROOT_PATH')) {
    define('OXID_ROOT_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
}

if (!defined('COI_ROOT_PATH')) {
    define('COI_ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}

require_once COI_ROOT_PATH . 'coiConfig.php';
require_once COI_ROOT_PATH . 'coiOx2Cao.php';
require_once COI_ROOT_PATH . 'coiXml.php';
require_once COI_ROOT_PATH . 'coiHtml.php';
require_once COI_ROOT_PATH . 'coiEmail.php';

require_once OXID_ROOT_PATH . 'bootstrap.php';

use OxidEsales\Eshop\Core\Exception\SystemComponentException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsObject;

const MODE_NUM = 1;
const MODE_ASSOC = 2;
const MODE_BOTH = 3;

class Func {

    public $oxDB = Null;
    public $aDbRes = array();
    public $oxConfig;
    public $oxUser = Null;
    public $oxManufacturer = Null;
    public $oxCategorie = Null;
    public $oxArticle = Null;
    public $oxMetaData = Null;
    public $oxCountry = Null;
    public $oxPayment = Null;
    public $oxOrder = Null;
    public $oxEmail = Null;
    public $oxContent = Null;
    public $oxSmarty = Null;
    public $coi_iShopVersion;
    public $coi_iError;
    public $coi_sError;
    public $UploadedFileName;
    public $sMetaKey;
    public $sMetaDesc;
    public $iShopId;
    public $dDefaultTax;
    public $IsNetPrice;
    public $IsDeliveryTaxOnTop;
    public $IsPayMmentTaxOnTop;
    public $IsShowNettoPrice;
    public $IsTaxForDelivery;
    public $IsTaxForPayCharge;
    public $IsTaxForWrapping;
    public $CountryName;
    public $CountryIso2;
    public $dABCPrice;
    public $aNotAllowedField = array('OXID', 'OXPARENTID', 'OXARTNUM', 'OXEAN', 'OXTITLE',
        'OXSHORTDESC', 'OXPRICE', 'OXTPRICE', 'OXUNITNAME',
        'OXUNITQUANTITY', 'OXVAT', 'OXTHUMB', 'OXICON', 'OXPIC1',
        'OXPIC2', 'OXPIC3', 'OXZOOM1', 'OXZOOM2', 'OXZOOM3',
        'OXWEIGHT', 'OXSTOCK', 'OXINSERT',
        'OXTIMESTAMP', 'OXLENGHT', 'OXWIDTH', 'OXHEIGHT', 'OXVARNAME',
        'OXVARSTOCK', 'OXVARCOUNT', 'OXVARSELECT', 'OXVARMINPRICE', 'OXVENDORID',
        'OXMANUFACTURERID');
    protected $coi_iUtfMode;
    protected $aSearchPattern = array('/ä/', '/ö/', '/ü/', '/ß/', '/Ä/', '/Ö/', '/Ü/');
    protected $aReplacePattern = array('ae', 'oe', 'ue', 'ss', 'AE', 'OE', 'UE');
    private $_iExpireTime = 3;
    private $_aOrderText = array('COI_ORDERGIFTCARD' => 'Geschenkkarte',
        'COI_ORDERGIFTPAPER' => 'Geschenkpapier',
        'COI_ORDERDISCOUNT' => 'eingelöster Gutschein',
        'COI_DISCOUNTPLUS' => 'Zuschlag',
        'COI_DISCOUNTMINUS' => 'Rabatt',
        'COI_ORDERPAYCOST' => 'Zuzahlung Zahlart',
        'COI_ORDERSHIPPINGCOST' => 'Versandkosten',
        'COI_ORDERTAX' => 'MwSt',
        'COI_ORDERSUBTOTAL' => 'Zwischensumme',
        'COI_ORDERTOTAL' => 'Summe',
        'COI_ORDERSELVARIANT' => 'Auswahl zu Artikel: ',
        'COI_PERSPARAM' => 'Artikelbeschriftung:',
    );
    private $_actionrequest;

    public function __construct() {
        if (isset($_REQUEST['action'])) {
            $this->_actionrequest = $_REQUEST['action'];
        };

        $this->coi_iError = 0;

        Config::$aLicense = array(
            'Name1' => '- COI -',
            'Name2' => 'Interface zwischen CAO und Oxid',
            'Name3' => '');

        if (!file_exists(COI_ROOT_PATH . 'xmlscheme.inc')) {
            $this->coi_iError = 1;
            $this->coi_sError .= "<br />Die XML-Schema-Datei kann nicht geladen werden!";
        } else {
            include COI_ROOT_PATH . 'xmlscheme.inc';
        }

        if (file_exists(COI_ROOT_PATH . '/noauth.coi')) {
            Config::$IsNoAuth = 1;
            if (file_get_contents(COI_ROOT_PATH . '/noauth.coi')) {
                Config::$NoHtmlLogin = 1;
            }
        }

        if (COI_ROOT_PATH . '/utfmode.coi') {
            Config::$UseOtherUtf8Mode = 1;
        }

        if (file_exists(COI_ROOT_PATH . '/save_error_off.coi')) {
            Config::$SaveErrorOff = 1;
        }

        $this->oxConfig = Registry::getConfig();
        $this->coi_iShopVersion = Config::getShopVers();
        $this->iShopId = $this->oxConfig->getShopId();
        $this->dDefaultTax = $this->oxConfig->getConfigParam('dDefaultVAT');
        $this->IsNetPrice = $this->oxConfig->getConfigParam('blEnterNetPrice');
        $this->IsDeliveryTaxOnTop = $this->oxConfig->getConfigParam('blDeliveryVatOnTop');
        $this->IsPymentTaxOnTop = $this->oxConfig->getConfigParam('blPaymentVatOnTop');
        $this->IsShowNettoPrice = $this->oxConfig->getConfigParam('blShowNetPrice');
        $this->IsTaxForDelivery = $this->oxConfig->getConfigParam('blCalcVATForDelivery');
        $this->IsTaxForPayCharge = $this->oxConfig->getConfigParam('blCalcVATForDelivery');
        $this->IsTaxForWrapping = $this->oxConfig->getConfigParam('blWrappingVatOnTop');

        if (!$this->coi_iShopVersion) {
            $this->coi_iError = 1;
            $this->coi_sError = "<br />Shop ist Offline. Das CAO-OXID Script kann nicht genutzt werden! " . $this->oxConfig->getVersion();
        } else if ($this->coi_iShopVersion < Config::$MinShopVersion || $this->coi_iShopVersion > Config::MaxOxidShopVersion()) {
            $this->coi_iError = 1;
            $this->coi_sError = "<br />Das CAO-OXID Script kann mit der installierten OXID-Shopversion nicht genutzt werden! " . $this->coi_iShopVersion;
        }

        try {
            $this->oxDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $this->oxDB->setFetchMode(MODE_ASSOC);
        } catch (Exception $e) {
            $this->coi_iError = 1;
            $this->coi_sError .= '<br />' . $e->getMessage();
        }

        Config::setCOIConfig();

        $this->coi_iUtfMode = Config::ShopInUtf8();
        if ($this->coi_iError) {
            $this->ErrorHandler();
        }

        ox2Cao::InitTables();
    }

    protected function ErrorHandler() {
        die($this->coi_sError);
    }

    public function CheckAgent() {
        if ($_SERVER["HTTP_USER_AGENT"] == 'CAO-Faktura') {
            return 1;
        } else if (Config::$NoHtmlLogin) {
            return 1;
        } else if ((isset($_GET['sSID'])) && (isset($_GET['sid']))) {
            if ($_GET[session_name()] == '') {
                return 0;
            } else {
                session_start();
                if (!isset($_SESSION['sUser'])) {
                    session_unset();
                    session_destroy();
                    return 0;
                } else
                if ($iExTime = $_SESSION['iExTime']) {
                    if ($iExTime < time()) {
                        $_SESSION['sUser'] = '';
                        $_SESSION['iExTime'] = 0;
                        $_SESSION = array();
                        session_unset();
                        session_destroy();
                        return 0;
                    } else
                    if ($sUser = $_SESSION['sUser']) {
                        if ($this->oxDB->getOne("SELECT OXID FROM oxuser WHERE OXUSERNAME='" . $sUser . "' AND OXRIGHTS='malladmin'")) {
                            return 1;
                        } else {
                            $_SESSION['sUser'] = '';
                            $_SESSION['iExTime'] = 0;
                            $_SESSION = array();
                            session_unset();
                            session_destroy();
                            return 0;
                        }
                    } else
                        return 0;
                } else
                    return 0;
            }
        }
        return 0;
    }

    public function getSalt($sOXID) {
        return $this->oxDB->getOne("SELECT OXPASSSALT FROM oxuser WHERE OXID='" . $sOXID . "'");
    }

    public function decodePW($sPassword, $sOXID) {
        $sSalt = $this->getSalt($sOXID);
        return $this->encodePassword($sPassword, $sSalt);
    }

    public function oxidIsAdmin($iIsStart = 0) {
        $iExpire = time() + 60 * $this->_iExpireTime;
        if (isset($_GET['sSID'])) { // per HTML-Link aufgerufen? Dann SESSION abfragen
            $_coiHTML = new htmlFunc;

            if ($_GET[session_name()] == '') {
                $_coiHTML->Login();
                exit;
            }
            session_start();
            if ($iExTime = $_SESSION['iExTime']) {
                if ($iExTime < time()) {
                    $_SESSION['sUser'] = '';
                    $_SESSION['iExTime'] = 0;
                    $_SESSION = array();
                    session_unset();
                    session_destroy();
                    $_oHTML->Login();
                    exit;
                } else {
                    if ($sUser = $_SESSION['sUser']) {
                        if ($this->oxDB->getOne("SELECT OXID FROM oxuser WHERE OXUSERNAME='" . $sUser . "' AND OXRIGHTS='malladmin'")) {
                            $_SESSION['iExTime'] = $iExpire;
                            return 1;
                        } else {
                            $_SESSION['sUser'] = '';
                            $_SESSION['iExTime'] = 0;
                            $_SESSION = array();
                            session_unset();
                            session_destroy();
                            $_oHTML->Login();
                            exit;
                        }
                    }
                }
            } else {
                $_SESSION['sUser'] = '';
                $_SESSION['iExTime'] = 0;
                $_SESSION = array();
                session_unset();
                session_destroy();
                $_coiHTML->Login();
                exit;
            }
        }

        if ((isset($_SERVER['PHP_AUTH_USER'])) && $_SERVER['PHP_AUTH_USER'] > '' && (isset($_SERVER['PHP_AUTH_PW'])) && $_SERVER['PHP_AUTH_PW'] > '') {
            $sUser = $_SERVER['PHP_AUTH_USER'];
            $sPW = $_SERVER['PHP_AUTH_PW'];
            unset($_SERVER['PHP_AUTH_USER']);
            unset($_SERVER['PHP_AUTH_PW']);
        } else if ((isset($_POST['user'])) && (isset($_POST['password'])) && $_POST['user'] > '' && $_POST['password'] > '') {
            $sUser = $_POST['user'];
            $sPW = $_POST['password'];
            unset($_POST['user']);
            unset($_POST['password']);
        } else {
            if (!$iIsStart)
                xmlFunc::_XMLStatus(106, $_REQUEST['action'], 'ERROR: WRONG LOGIN', '', '', '');
            return 0;
        }

        if ($this->CheckUserLogin($sUser, $sPW)) {
            if (isset($_POST['sSID'])) { // per HTML? Dann SESSION setzten
                session_start();
                $_SESSION['sUser'] = $sUser;
                $_SESSION['iExTime'] = $iExpire;
                return 1;
            }
            return 1; // OK, ist Admin
        } else
            return 0;

        if (!$iIsStart)
            xmlFunc::_XMLStatus(106, $_REQUEST['action'], 'ERROR: WRONG LOGIN', '', '', '');
        return 0;
    }

    public function convertXMLString($S) {
        $aReplace = array("&nbsp;" => " ", "&acute;" => "&#180;", "& " => "+ ");
        $S = strtr($S, $aReplace);
        if ($this->coi_iUtfMode) {
            $sRet = htmlspecialchars($S, ENT_COMPAT, 'UTF-8');
        } else
            $sRet = htmlspecialchars($S);

        $sRet = htmlentities($sRet);
        $sRet = str_replace('&amp;amp;', '&amp;', $sRet);

        if (strlen($sRet) == 0)
            return $sRet;
        else
            return '<![CDATA[' . $sRet . ']]>';
    }

    public function convertString($S) {
        if ($this->coi_iUtfMode) {
            return iconv("ISO-8859-1", "UTF-8", $S);
        } else
            return $S;
    }

    public function convertHtmlToString($s) {
        $s = str_replace("&nbsp;", " ", $s);
        $s = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $s);
        $s = strip_tags($s);
        $s = str_replace('<', '&#60;', $s);
        $s = str_replace("& ", "+ ", $s);
        $s = str_replace("&", "+", $s);

        if ($this->coi_iUtfMode)
            $s = html_entity_decode($s, ENT_COMPAT, "UTF-8");
        else
            $s = html_entity_decode($s);
        return $s;
    }

    public function CoiImageUpload($sImageDir, $sUploadFile) {
        $sPath = $this->oxConfig->getMasterPictureDir(false) . $sImageDir;

        if (isset($_FILES[$sUploadFile])) {
            $aUploadFile = array('name' => $_FILES[$sUploadFile]['name'],
                'type' => $_FILES[$sUploadFile]['type'],
                'size' => $_FILES[$sUploadFile]['size'],
                'tmp_name' => $_FILES[$sUploadFile]['tmp_name']);

            $sEnd = pathinfo($aUploadFile['name'], PATHINFO_EXTENSION);
            $sFilename = pathinfo($aUploadFile['name'], PATHINFO_FILENAME);
            $sFilename = strtolower(preg_replace($this->aSearchPattern, $this->aReplacePattern, $sFilename));
            $sFilename = str_replace(' ', '_', $sFilename);
            $sFilename = $sFilename . '.' . $sEnd;

            if (getimagesize($aUploadFile['tmp_name'])) {
                if (move_uploaded_file($aUploadFile['tmp_name'], $sPath . $sFilename)) {
                    if ($sUploadFile == 'categories_image') {
                        copy($sPath . $sFilename, $this->oxConfig->getMasterPictureDir(false) . '/category/icon/' . $sFilename);
                    }

                    $this->UploadedFileName = $sFilename;
                    return 0;
                } else
                    return 'FILE NOT UPLOADED';
            } else
                return 'NO IMAGEFILE ' . $aUploadFile['name'];
        } else
            return 'NO FILE TO UPLOAD';
    }

    public function GetNextCoiId($sTableName) {
        $sQ = 'SELECT MAX(COI_CAOID) FROM ' . $sTableName;
        $Id = $this->oxDB->getOne($sQ);
        if ($Id == 0)
            $Id = 1;
        else
            $Id += 1;

        return $Id;
    }

    public function GetOxIdOverCaoId($sTableName, $iCoiId) {
        if ($sTableName == 'oxcat2cao')
            $sField = 'OXCATID';
        else
            $sField = 'OXID';
        $sQ = "SELECT " . $sField . " FROM " . $sTableName . " WHERE COI_CAOID=" . $this->oxDB->quote($iCoiId);
        return $this->oxDB->getOne($sQ);
    }

    public function GetOxIdOverOrderNumber($iOrderNumber) {
        $sQ = "SELECT OXID FROM oxorder WHERE OXORDERNR=" . $this->oxDB->quote($iOrderNumber);
        return $this->oxDB->getOne($sQ);
    }

    public function GetCaoIdOverOxId($sTableName, $sOxId) {
        $sQ = "SELECT COI_CAOID FROM " . $sTableName . " WHERE OXID=" . $this->oxDB->quote($sOxId);
        return $this->oxDB->getOne($sQ);
    }

    protected function GetOxMetaFromSeo($sOxId, $sDataType, $iLang = 0) {
        if (!$iLang)
            $iLang = Registry::getLang()->getBaseLanguage();
        if ($sOxId && Registry::getUtils()->seoIsActive() &&
                ( $sKeywords = Registry::getSeoEncoder()->getMetaData($sOxId, $sDataType, $this->iShopId, $iLang) )) {
            return $sKeywords;
        }
    }

    public function GetMetaData($sOxId, $iLang = 0) {
        $this->sMetaKey = '';
        $this->sMetaDesc = '';
        $this->sMetaDesc = $this->GetOxMetaFromSeo($sOxId, 'oxdescription', $iLang);
        $this->sMetaKey = $this->GetOxMetaFromSeo($sOxId, 'oxkeywords', $iLang);
    }

    public function SetOxSeo($sOxId, $iLang = 0) {
        if (!$sOxId)
            return;

        if (($this->sMetaKey == '') && ($this->sMetaDesc == ''))
            return;

        if (!$iLang)
            $iLang = Registry::getLang()->getBaseLanguage();

        $sQtedObjectId = $this->oxDB->quote($sOxId);
        $iQtedShopId = $this->oxDB->quote($this->iShopId);
        $sKeywords = $this->oxDB->quote(html_entity_decode($this->sMetaKey));
        $sDescription = $this->oxDB->quote(html_entity_decode($this->sMetaDesc));

        $sQ = "insert into oxobject2seodata
                       ( oxobjectid, oxshopid, oxlang, oxkeywords, oxdescription )
                   values
                       ( {$sQtedObjectId}, {$iQtedShopId}, {$iLang}, " . ($sKeywords ? $sKeywords : "''") . ", " . ($sDescription ? $sDescription : "''") . " )
                   on duplicate key update
                       oxkeywords = " . ($sKeywords ? $sKeywords : "oxkeywords") . ", oxdescription = " . ($sDescription ? $sDescription : "oxdescription");

        $this->oxDB->Execute($sQ);
    }

    public function GetOxId() {
        return Registry::getUtilsObject()->generateUID();
    }

    public function GetGrossPrice($dNetPrice, $dTax) {
        return round((float) ($dNetPrice * ((100 + $dTax) / 100)), 2);
    }

    public function GetDeliveryDate($Data) {
        $Date = '';
        if (isset($Data['products_delivery'])) {
            if (strlen($Data['products_delivery']) == 10) {
                $sDay = substr($Data['products_delivery'], 0, 2);
                $sMonth = substr($Data['products_delivery'], 3, 2);
                $sYear = substr($Data['products_delivery'], 6, 4);

                $Date = date('Y-m-d', mktime(0, 0, 0, $sMonth, $sDay, $sYear));
            }
        }
        return $Date;
    }

    /* Tools  */

    public function getOxMetaObject() {
        if (!$this->oxMetaData) {
            $this->oxMetaData = oxNew(\OxidEsales\Eshop\Core\DbMetaDataHandler::class);
        }
    }

    /* User Funktionen */

    public function getOxUserObject() {
        if (!$this->oxUser) {
            $this->oxUser = oxNew(\OxidEsales\Eshop\Application\Model\User::class);
        }
    }

    public function CheckUserLogin($sUser, $sPassWord) {
        try {
            $this->getOxUserObject();
            $this->oxUser->login($sUser, $sPassWord);
        } catch (\OxidEsales\Eshop\Core\Exception\UserException $oEx) {
            xmlFunc::_XMLStatus(108, $_REQUEST['action'], 'ERROR: WRONG PASSWORD', '', '', '');
            $this->oxUser = null;
            return 0;
        } catch (\OxidEsales\Eshop\Core\Exception\CookieException $oEx) {
            xmlFunc::_XMLStatus(108, $_REQUEST['action'], 'ERROR: NO COOKIE SUPPORT', '', '', '');
            $this->oxUser = null;
            return 0;
        } catch (\OxidEsales\Eshop\Core\Exception\ConnectionException $oEx) {
            xmlFunc::_XMLStatus(108, $_REQUEST['action'], 'ERROR: DATABASE CONNECTION', '', '', '');
            $this->oxUser = null;
            return 0;
        }

        if ($this->oxUser->oxuser__oxrights->value == 'malladmin') {
            $this->oxUser = null;
            return 1;
        } else {
            xmlFunc::_XMLStatus(108, $_REQUEST['action'], 'ERROR: NO ADMIN USER', '', '', '');
            $this->oxUser = null;
            return 0;
        }
    }

    public function GetOxIdByUserName($sUserName) {
        $this->getOxUserObject();
        return $this->oxUser->getIdByUserName($sUserName);
    }

    public function SetCoiUserId($sOxid, $iCoiId) {
        $sQ = "UPDATE oxuser SET COI_CAOID=" . $this->oxDB->quote($iCoiId) . " WHERE OXID=" . $this->oxDB->quote($sOxid);
        $this->oxDB->Execute($sQ);
    }

    public function GetUserPriceGroup($sOxId) {
        $sGroup = '';
        $this->getOxUserObject();
        $this->oxUser->load($sOxId);
        if ($this->oxUser->inGroup('oxidpricea'))
            $sGroup = 'oxidpricea';
        else if ($this->oxUser->inGroup('oxidpriceb'))
            $sGroup = 'oxidpriceb';
        else if ($this->oxUser->inGroup('oxidpricec'))
            $sGroup = 'oxidpricec';

        return $sGroup;
    }

    public function SetUserPriceGroup($sOxId, $iPriceLevel) {
        $sGroup = $this->GetUserPriceGroup($sOxId);
        if ($sGroup)
            $this->oxUser->removeFromGroup($sGroup);

        $sGroup = '';
        if (Config::getCOIConfig('PRICEGROUPA') == $iPriceLevel)
            $sGroup = 'oxidpricea';
        else if (Config::getCOIConfig('PRICEGROUPB') == $iPriceLevel)
            $sGroup = 'oxidpriceb';
        else if (Config::getCOIConfig('PRICEGROUPC') == $iPriceLevel)
            $sGroup = 'oxidpricec';

        if ($sGroup)
            $this->oxUser->addToGroup($sGroup);
    }

    /* Hersteller Funktionen */

    public function getOxManufacturerObject() {
        if (!$his->oxManufacturer) {
            $this->oxManufacturer = oxNew(\OxidEsales\Eshop\Application\Model\Manufacturer::class);
        }
    }

    public function SetCoiManufacturerId($sOxid, $iCoiId) {
        $sQ = "UPDATE oxmanufacturers SET COI_CAOID=" . $this->oxDB->quote($iCoiId) . " WHERE OXID=" . $this->oxDB->quote($sOxid);
        $this->oxDB->Execute($sQ);
    }

    /* Kategorien */

    public function getOxCategorieObject() {
        if (!$this->oxCategorie) {
            $this->oxCategorie = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
        }
    }

    public function UpdateOxCategorieTree() {
        $CategoriList = oxNew(\OxidEsales\Eshop\Application\Model\CategoryList::class);
        $CategoriList->updateCategoryTree();
    }

    /* Artikel */

    public function getOxArticleObject() {
        if (!$this->oxArticle) {
            $this->oxArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        }
    }

    public function SetCoiIdInArticle($sOxId, $iCoiId) {
        $sQ = 'UPDATE oxarticles SET COI_CAOID = ? WHERE OXID = ?';
        $this->oxDB->Execute($sQ, array($iCoiId, $sOxId));
    }

    public function GetArticleLongDescription($sOxId, $iLang = 0) {
        $sField = 'OXLONGDESC';
        if ($iLang)
            $sField = 'OXLONGDESC_' . $iLang;
        $sQ = "SELECT " . $sField . " FROM oxartextends WHERE OXID=" . $this->oxDB->quote($sOxId);
        return $this->oxDB->getOne($sQ);
    }

    public function setVariantArticle($sOxId, $Data) {
        $I = 0;
        $aVariantLangText = array();
        $aLanguage = Config::getLanguageArray();
        $sSeperator = Config::getCaoVariantSeperator();

        if (Config::getCaoLanguage()) {
            foreach ($aLanguage as $x => $sVal) {
                if (isset($Data['products_var_langtext'][$x]))
                    $aVariantLangText[$x] = explode($sSeperator, $Data['products_var_langtext'][$x]);
            }
        }

        // Variante auflösen
        $sQ = "UPDATE oxarticles SET OXPARENTID='',OXVARSELECT='',OXVARSELECT_1='',OXVARSELECT_2='',OXVARSELECT_3='',OXVARMINPRICE=0 WHERE OXPARENTID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);
        $sQ = "UPDATE oxarticles SET OXPARENTID='',OXVARSELECT='',OXVARSELECT_1='',OXVARSELECT_2='',OXVARSELECT_3='',OXVARMINPRICE=0,OXVARCOUNT=0,OXVARSTOCK=0 WHERE OXID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);

        if ((isset($Data['products_var_id'])) && ($Data['products_var_id'] > '')) {
            $aArticleObject = explode($sSeperator, $Data['products_var_id']);
            $aVariantText = explode($sSeperator, $Data['products_var_text']);

            // Varianten anlegen
            foreach ($aArticleObject as $iValue) {
                $sOxVar = '';
                if ($sParentId = $this->GetOxIdOverCaoId('oxarticles', $iValue)) {

                    if (Config::getCaoLanguage()) {
                        foreach ($aLanguage as $x => $sVal) {
                            $sQ = "DESCRIBE oxarticles 'OXVARSELECT_" . $x . "'";
                            if ($this->oxDB->getOne($sQ))
                                $sOxVar .= "OXVARSELECT_" . $x . "=" . $this->oxDB->quote($this->convertString($aVariantLangText[$x][$I])) . ",";
                        }
                    }
                    $sQ = "UPDATE oxarticles SET OXPARENTID=" . $this->oxDB->quote($sOxId) . ",OXVARSELECT=" . $this->oxDB->quote($this->convertString($aVariantText[$I])) . "," . $sOxVar . "OXVARCOUNT='0',OXVARSTOCK='0',OXVARMINPRICE='0' WHERE OXID=" . $this->oxDB->quote($sParentId);
                    $this->oxDB->Execute($sQ);
                }
                $I++;
            }

            // Vater anlegen
            $sSet = "OXVARNAME=" . $this->oxDB->quote($this->convertString($Data['products_variantname'])) . ",OXVARSELECT=" . $this->oxDB->quote($this->convertString($Data['products_varianttext'])) . ",";
            if (Config::getCaoLanguage()) {
                for ($x = 1; $x <= $iLanguage; $x++) {
                    $sQ = "DESCRIBE oxarticles 'OXVARSELECT_" . $x . "'";
                    if ($this->oxDB->getOne($sQ))
                        $sSet .= "OXVARSELECT_" . $x . "=" . $this->oxDB->quote($this->convertString($Data['products_vartext'][$x])) . ",";
                    $sQ = "DESCRIBE oxarticles 'OXVARNAME_" . $x . "'";
                    if ($this->oxDB->getOne($sQ))
                        $sSet .= "OXVARNAME_" . $x . "=" . $this->oxDB->quote($this->convertString($Data['products_varname'][$x])) . ",";
                }
            }

            $sQ = "SELECT SUM(OXSTOCK) AS SUMSTOCK FROM oxarticles WHERE OXPARENTID=" . $this->oxDB->quote($sOxId);
            $iSum = $this->oxDB->getOne($sQ);
            $sQ = "SELECT MIN(OXPRICE) AS MINPRICE FROM oxarticles WHERE OXPARENTID=" . $this->oxDB->quote($sOxId);
            $dMin = $this->oxDB->getOne($sQ);
            $sQ = "SELECT MAX(OXPRICE) AS MAXPRICE FROM oxarticles WHERE OXPARENTID=" . $this->oxDB->quote($sOxId);
            $dMax = $this->oxDB->getOne($sQ);
            $sQ = "SELECT COUNT(*) FROM oxarticles WHERE OXPARENTID=" . $this->oxDB->quote($sOxId);
            $iCount = $this->oxDB->getOne($sQ);
            if ($iCount)
                $sSet .= "OXVARCOUNT=" . $this->oxDB->quote($iCount) . ",OXVARSTOCK=" . $this->oxDB->quote($iSum) . ",OXVARMINPRICE=" . $this->oxDB->quote($dMin) . ",OXVARMAXPRICE=" . $this->oxDB->quote($dMax) . ",OXPRICE=" . $this->oxDB->quote($dMin);
            else
                $sSet .= "OXVARCOUNT=" . $this->oxDB->quote(0);

            $sQ = "UPDATE oxarticles SET " . $sSet . " WHERE OXID=" . $this->oxDB->quote($sOxId);
            $this->oxDB->Execute($sQ);
        }
    }

    public function deleteVariantArticle($sOxId) {
        // Variante auflösen
        $sQ = "UPDATE oxarticles SET OXPARENTID='',OXVARSELECT='',OXVARSELECT_1='',OXVARSELECT_2='',OXVARSELECT_3='',OXVARMINPRICE=0 WHERE OXPARENTID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);
        $sQ = "UPDATE oxarticles SET OXPARENTID='',OXVARSELECT='',OXVARSELECT_1='',OXVARSELECT_2='',OXVARSELECT_3='',OXVARMINPRICE=0,OXVARCOUNT=0,OXVARSTOCK=0 WHERE OXID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);
    }

    public function SetArticleToVariant($sOxId, $Data) {
        if ((isset($Data['variant_parent_id'])) && ($Data['variant_parent_id'] > '')) {
            if ($sParentId = $this->GetOxIdOverCaoId('oxarticles', $Data['variant_parent_id'])) {
                $sQ = "UPDATE oxarticles SET OXPARENTID=" . $this->oxDB->quote($sParentId) . ",OXVARSELECT=" . $this->oxDB->quote($this->convertString($Data['variant_vartext'])) . ",OXVARCOUNT='0',OXVARSTOCK='0',OXVARMINPRICE='0' WHERE OXID=" . $this->oxDB->quote($sOxId);
                $this->oxDB->Execute($sQ);
            }
        }
    }

    public function SetArticleScalePrice($sOxId, $Data, $dTax) {
        $x = $y = $z = $Flag = 0;
        $aPrice = array();
        $aQuantity = array();
        $aData = array();
        $aPercent = array();
        $aTempPercent = array();
        $iMaxQuantity = 9999;

        foreach ($Data as $sKey => $aValue) {
            if (($sKey == 'products_gp2_unitprice') || ($sKey == 'products_gp3_unitprice') || ($sKey == 'products_gp4_unitprice') || ($sKey == 'products_gp5_unitprice')) {
                if ($this->IsNetPrice)
                    $aPrice[$x++] = $aValue;
                else
                    $aPrice[$x++] = $this->GetGrossPrice($aValue, $dTax);
                $Flag = 1;
            }
            if (($sKey == 'products_gp2_quantity') || ($sKey == 'products_gp3_quantity') || ($sKey == 'products_gp4_quantity') || ($sKey == 'products_gp5_quantity')) {
                $aQuantity[$y++] = $aValue;
            }

            if (Config::getCOIConfig('SCALEPRICEINPERCENT')) {
                if (($sKey == 'products_gp2_percent') || ($sKey == 'products_gp3_percent') || ($sKey == 'products_gp4_percent') || ($sKey == 'products_gp5_percent')) {
                    if ($aValue) {
                        $aTempPercent[$z++] = $aValue;
                        $Flag = 2;
                    }
                }
            }
        }

        $sQ = "DELETE FROM oxprice2article WHERE OXARTID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);

        switch ($Flag) {
            case 1: // fester Preis
                $aData = array_combine($aQuantity, $aPrice);
                krsort($aData);
                foreach ($aData as $iKey => $Value) {

                    $sQ = "INSERT INTO oxprice2article (oxid,oxshopid,oxartid,oxaddabs,oxamount,oxamountto) " .
                            "VALUES (" .
                            $this->oxDB->quote($this->GetOxId()) . "," .
                            $this->oxDB->quote($this->iShopId) . "," .
                            $this->oxDB->quote($sOxId) . "," .
                            $this->oxDB->quote($Value) . "," .
                            $this->oxDB->quote($iKey) . "," .
                            $this->oxDB->quote($iMaxQuantity) . ")";
                    $this->oxDB->Execute($sQ);

                    $iMaxQuantity = $iKey;
                }
                break;

            case 2: // Prozent
                if (count($aQuantity)) {
                    for ($i = 0; $i < count($aQuantity); $i++) {
                        $aPercent[$i] = $aTempPercent[$i];
                    }

                    $aData = array_combine($aQuantity, $aPercent);
                    krsort($aData);
                    foreach ($aData as $iKey => $Value) {

                        $sQ = "INSERT INTO oxprice2article (oxid,oxshopid,oxartid,oxaddperc,oxamount,oxamountto) " .
                                "VALUES (" .
                                $this->oxDB->quote($this->GetOxId()) . "," .
                                $this->oxDB->quote($this->iShopId) . "," .
                                $this->oxDB->quote($sOxId) . "," .
                                $this->oxDB->quote($Value) . "," .
                                $this->oxDB->quote($iKey) . "," .
                                $this->oxDB->quote($iMaxQuantity) . ")";
                        $this->oxDB->Execute($sQ);

                        $iMaxQuantity = $iKey;
                    }
                }
                break;
        }
    }

    public function SetArticleSpecialPrice($sOxId, $Data, $dTax) {
        $IsSetDateFrom = false;
        $IsSetDateTo = false;

        if ((isset($Data['pID']) ) && ($Data['pID'] > 0)) {
            // alten Aktionspreis löschen
            $sQ = "SELECT od.OXDISCOUNTID FROM oxobject2discount od JOIN oxdiscount d on d.OXID = od.OXDISCOUNTID WHERE od.OXTYPE ='oxarticles' and od.COI_CAOID = " . $this->oxDB->quote($Data['pID']);
            $sObjectId = $this->oxDB->getOne($sQ);

            if ($sObjectId) {
                $sQ = "DELETE FROM oxdiscount WHERE OXID = " . $this->oxDB->quote($sObjectId);
                $this->oxDB->Execute($sQ);
                $sQ = "DELETE FROM oxobject2discount WHERE COI_CAOID = " . $this->oxDB->quote($Data['pID']);
                $this->oxDB->Execute($sQ);
            }

            if (isset($Data['products_ac_date_from'])) {
                if (strlen($Data['products_ac_date_from']) == 10) {
                    $sDayFrom = substr($Data['products_ac_date_from'], 0, 2);
                    $sMonthFrom = substr($Data['products_ac_date_from'], 3, 2);
                    $sYearFrom = substr($Data['products_ac_date_from'], 6, 4);
                    $IsSetDateFrom = true;
                }
            }

            if (isset($Data['products_ac_date_to'])) {
                if (strlen($Data['products_ac_date_to']) == 10) {
                    $sDayTo = substr($Data['products_ac_date_to'], 0, 2);
                    $sMonthTo = substr($Data['products_ac_date_to'], 3, 2);
                    $sYearTo = substr($Data['products_ac_date_to'], 6, 4);
                    $IsSetDateTo = true;
                }
            }

            if ($IsSetDateFrom && $IsSetDateTo) {
                if (mktime(0, 0, 0, $sMonthFrom, $sDayFrom, $sYearFrom) <= mktime(0, 0, 0, $sMonthTo, $sDayTo, $sYearTo)) {
                    if (mktime(0, 0, 0, $sMonthTo, $sDayTo, $sYearTo) >= Time()) {
                        $dDiscount = $this->GetGrossPrice($Data['products_price'] - $Data['products_ac_price'], $dTax);
                        $sDateFrom = date('Y-m-d H:i:s', mktime(0, 0, 0, $sMonthFrom, $sDayFrom, $sYearFrom));
                        $sDateTo = date('Y-m-d H:i:s', mktime(0, 0, 0, $sMonthTo, $sDayTo, $sYearTo));
                        $sSumType = 'abs';
                        $sType = 'oxarticles';
                        $sDiscountId = $this->GetOxId();

                        $sQ = "SELECT MAX(OXSORT)+10 FROM oxdiscount";
                        $iSort = $this->oxDB->getOne($sQ);

                        if (Config::getCOIConfig('SPECIALPRICEINPERCENT')) {
                            if ((isset($Data['products_ac_percent'])) && ($Data['products_ac_percent'] > 0)) {
                                $sSumType = '%';
                                $dDiscount = $Data['products_ac_percent'];
                            }
                        }
                        $sQ = "INSERT INTO oxdiscount(OXID,OXSHOPID,OXACTIVEFROM,OXACTIVETO,OXTITLE,OXTITLE_1,OXTITLE_2,OXTITLE_3,OXADDSUMTYPE,OXADDSUM,OXSORT) " .
                                "VALUES (" .
                                $this->oxDB->quote($sDiscountId) . "," .
                                $this->oxDB->quote($this->iShopId) . "," .
                                $this->oxDB->quote($sDateFrom) . "," .
                                $this->oxDB->quote($sDateTo) . "," .
                                $this->oxDB->quote($Data['products_model']) . "," .
                                $this->oxDB->quote($Data['products_model']) . "," .
                                $this->oxDB->quote($Data['products_model']) . "," .
                                $this->oxDB->quote($Data['products_model']) . "," .
                                $this->oxDB->quote($sSumType) . "," .
                                $this->oxDB->quote($dDiscount) . "," .
                                $this->oxDB->quote($iSort) . ")";
                        $this->oxDB->Execute($sQ);

                        $sDscountObjectId = $this->getOXID();
                        $sQ = "INSERT INTO oxobject2discount(OXID,OXDISCOUNTID,OXOBJECTID,OXTYPE,COI_CAOID) " .
                                "VALUES (" .
                                $this->oxDB->quote($this->GetOxId()) . "," .
                                $this->oxDB->quote($sDiscountId) . "," .
                                $this->oxDB->quote($sOxId) . "," .
                                $this->oxDB->quote($sType) . "," .
                                $this->oxDB->quote($Data['pID']) . ")";
                        $this->oxDB->Execute($sQ);
                    }
                }
            }
        }
    }

    public function SetArticleSortInCategorie($sOxId, $iSort) {
        $sQ = "UPDATE oxobject2category SET OXPOS=" . $this->oxDB->quote($iSort) . " WHERE OXOBJECTID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);
    }

    public function SetArticleCrossSelling($sParam, $sOxId) {
        $aArticleObject = explode(',', $sParam);
        $iSort = 0;

        $sQ = "DELETE FROM oxobject2article WHERE OXARTICLENID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);

        foreach ($aArticleObject as $iValue) {
            $sArticleObjectId = $this->GetOxIdOverCaoId('oxarticles', $iValue);
            if ($sArticleObjectId) {
                $iSort++;
                $sQ = "INSERT INTO oxobject2article (OXID,OXOBJECTID,OXARTICLENID,OXSORT) " .
                        "VALUES (" .
                        $this->oxDB->quote($this->GetOxId()) . "," .
                        $this->oxDB->quote($sArticleObjectId) . "," .
                        $this->oxDB->quote($sOxId) . "," .
                        $this->oxDB->quote($iSort) . ")";
                $this->oxDB->Execute($sQ);
            }
        }
    }

    public function SetArticleAccessoire($sParam, $sOxId) {
        $aArticleObject = explode(',', $sParam);
        $iSort = 0;

        $sQ = "DELETE FROM oxaccessoire2article WHERE OXARTICLENID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);

        foreach ($aArticleObject as $iValue) {
            $sArticleObjectId = $this->GetOxIdOverCaoId('oxarticles', $iValue);
            if ($sArticleObjectId) {
                $iSort++;
                $sQ = "INSERT INTO oxaccessoire2article (OXID,OXOBJECTID,OXARTICLENID,OXSORT) " .
                        "VALUES (" .
                        $this->oxDB->quote($this->GetOxId()) . "," .
                        $this->oxDB->quote($sArticleObjectId) . "," .
                        $this->oxDB->quote($sOxId) . "," .
                        $this->oxDB->quote($iSort) . ")";
                $this->oxDB->Execute($sQ);
            }
        }
    }

    public function GetArticleSort($sOxId) {
        $sQ = "SELECT OXSORT FROM oxarticles WHERE OXID=" . $this->oxDB->quote($sOxId);
        $iSort = $this->oxDB->getOne($sQ);
        if (!$iSort)
            $iSort = 0;
        return $iSort;
    }

    public function ResetCounts($sOxId) {
        $this->getOxArticleObject();
        $this->oxArticle->load($sOxid);

        $oxUtilsCount = Registry::getUtilsCount();

        if ($this->oxArticle->oxarticles__oxvendorid->value) {
            $oxUtilsCount->resetVendorArticleCount($this->oxArticle->oxarticles__oxvendorid->value);
        }

        if ($this->oxArticle->oxarticles__oxmanufacturerid->value) {
            $oxUtilsCount->resetManufacturerArticleCount($this->oxArticle->oxarticles__oxmanufacturerid->value);
        }

        $aCategoryIds = $this->oxArticle->getCategoryIds();
        foreach ($aCategoryIds as $sCatId)
            $oxUtilsCount->resetCatArticleCount($sCatId);
    }

    public function GetArticleUserFields($sOxId) {
        $sQ = "DESCRIBE oxarticles ?";
        $sUserfields = "";
        $aUserfield = array();
        $aOxFields = array();
        $sOxFields = '';
        $i = 0;
        $aXml = array();
        $aUserfield = unserialize(Config::getCOIConfig('USERFIELDS'));

        if ($aUserfield) {
            if (!is_array($aUserfield))
                $sUserfields = '';
            else {
                foreach ($aUserfield as $iKey => $sVal) {
                    if (!in_array(strtoupper($sVal), $this->aNotAllowedField)) {
                        if ($this->oxDB->getOne($sQ, array($sVal))) {
                            $aOxFields[$i] = $sVal;
                            if ($iKey < 10)
                                $aXml[$i] = "<PRODUCTS_USERFIELD_0" . $iKey . ">{#}</PRODUCTS_USERFIELD_0" . $iKey . ">";
                            else
                                $aXml[$i] = "<PRODUCTS_USERFIELD_" . $iKey . ">{#}</PRODUCTS_USERFIELD_" . $iKey . ">";
                            $i++;
                        }
                    }
                }
            }
            if ($aOxFields) {
                $sOxFields = implode(',', $aOxFields);
                $sQ = "SELECT " . $sOxFields . " FROM oxarticles WHERE OXID = ?";
                $this->oxDB->setFetchMode(MODE_NUM);
                $aDbRes = $this->oxDB->getAll($sQ, array($sOxId));
                if (is_array($aDbRes)) {
                    foreach ($aDbRes as $fields) {
                        foreach ($aXml as $iKey => $sValue) {
                            if ($iKey != 99) {
                                $sUserfields .= str_replace("{#}", $this->convertXMLString($fields[$iKey]), $sValue);
                            }
                        }
                    }
                }
            }
        }
        return $sUserfields;
    }

    public function SetUserFieldInArticle($sOxId, $Data) {
        $sQ = "DESCRIBE oxarticles ?";
        $aUserfield = unserialize(Config::getCOIConfig('USERFIELDS'));
        $sValue = '';
        $sFields = '';

        if ($aUserfield) {
            if (!is_array($aUserfield))
                exit;
            else {
                foreach ($aUserfield as $iKey => $sVal) {
                    if (!in_array(strtoupper($sVal), $this->aNotAllowedField)) {
                        if ($this->oxDB->getOne($sQ, array($sVal))) {
                            $sValue = $this->convertString($Data['products_userfield'][$iKey]);
                            $sFields .= $sVal . "=" . $this->oxDB->quote($sValue) . ",";
                        }
                    }
                }
            }
            $sFields = substr($sFields, 0, -1);
            $sQ = "UPDATE oxarticles SET " . $sFields . " WHERE OXID= ?";
            if ($sFields)
                $this->oxDB->Execute($sQ, array($sOxId));
        }
    }

    public function GetABCPrice($sPriceGroup, $Data, $dTax) {
        $dPrice = 0;
        $iValue = 0;
        switch ($sPriceGroup) {
            case 'A':
                $iValue = Config::getCOIConfig('PRICEGROUPA');
                if ($iValue > 0)
                    $dPrice = $Data['products_vk' . $iValue];
                break;
            case 'B':
                $iValue = Config::getCOIConfig('PRICEGROUPB');
                if ($iValue > 0)
                    $dPrice = $Data['products_vk' . $iValue];
                break;
            case 'C':
                $iValue = Config::getCOIConfig('PRICEGROUPC');
                if ($iValue > 0)
                    $dPrice = $Data['products_vk' . $iValue];
                break;
        }

        if ($dPrice > 0) {
            if (!$this->IsNetPrice)
                $dPrice = $this->GetGrossPrice($dPrice, $dTax);
        }

        return $dPrice;
    }

    /* Bestellung */

    public function getOxOrderObject() {
        if (!$this->oxOrder) {
            $this->oxOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        }
    }

    public function UpdateOxOrder() {
        $sQ = "UPDATE oxorder SET ORDERSTATUS='1' WHERE ORDERSTATUS='0'";
        $this->oxDB->Execute($sQ);

        $sQ = "SELECT OXID,COI_CAOID FROM oxorder WHERE COI_CAOID = 0 AND OXSTORNO=0 ORDER BY OXORDERDATE";
        $this->oxDB->setFetchMode(MODE_ASSOC);
        $this->aDbRes = $this->oxDB->getAll($sQ);
        if (is_array($this->aDbRes)) {
            foreach ($this->aDbRes as $fields) {
                if (!$fields['COI_CAOID'])
                    ox2Cao::UpdateCaoIdInOxTable('order', $fields['OXID']);
            }
        }
    }

    public function GetDelivery($sOxId) {
        $sQ = "SELECT OXTITLE FROM oxdeliveryset WHERE OXID=" . $this->oxDB->quote($sOxId);
        return $this->oxDB->getOne($sQ);
    }

    public function getOrderText($sKey) {
        if ($this->_aOrderText[$sKey])
            if ($this->coi_iUtfMode)
                return utf8_encode($this->_aOrderText[$sKey]);
            else
                return $this->_aOrderText[$sKey];
    }

    public function getIsTax($aTax) {
        foreach ($aTax as $key => $value) {
            if ($value > 0)
                return true;
        }
        return false;
    }

    public function GetOrderStatus($Id) {
        if (Config::getCOIConfig('USEORDERNR'))
            $sField = 'OXORDERNR';
        else
            $sField = 'COI_CAOID';
        $sQ = "SELECT ORDERSTATUS FROM oxorder WHERE " . $sField . " = ?";
        return $this->oxDB->getOne($sQ, array($Id));
    }

    public function GetOxFolderDescription($sOxFolderId) {
        $Folders = $this->oxConfig->getConfigParam('aOrderfolder');
        if (!$sFolderDesc = array_search($sOxFolderId, $Folders)) {
            $sFolderDesc = '';
        }

        return $sFolderDesc;
    }

    public function SetOrderDeadLine($sOxId, $Value) {
        $sQ = 'UPDATE oxorder SET COI_DEADLINE = ? WHERE OXID = ?';
        $this->oxDB->Execute($sQ, array($Value, $sOxId));
    }

    public function UpdateOrderStatus($sOxId, $Value) {
        $sQ = 'UPDATE oxorder SET ORDERSTATUS = ? WHERE OXID = ?';
        $this->oxDB->Execute($sQ, array($Value, $sOxId));
    }

    public function GetOtherTax($sOxId) {
        $sLand = Config::getCOIConfig('STANDARDLAND');
        $dTax1 = Config::getCOIConfig('STANDARDTAX');
        $dTax2 = Config::getCOIConfig('REDUCEDTAX');
        $sQ = "SELECT IF((o.OXDELCOUNTRYID <> '' AND o.OXDELCOUNTRYID IS NOT NULL), 'DELTAX', 'BILLTAX') AS OTHERTAX FROM oxorder o 
               LEFT JOIN oxcountry c ON c.OXID = IF((o.OXDELCOUNTRYID <> '' AND o.OXDELCOUNTRYID IS NOT NULL), o.OXDELCOUNTRYID, o.OXBILLCOUNTRYID)
               WHERE c.OXVATSTATUS = 1 AND c.OXISOALPHA2 <> ? 
               AND IF((o.OXDELCOUNTRYID <> '' AND o.OXDELCOUNTRYID IS NOT NULL AND o.OXDELCOUNTRYID <> o.OXBILLCOUNTRYID),'', o.OXBILLUSTID) = ''
               AND ((o.OXARTVAT1 <> 0 AND o.OXARTVAT1 <> ?) or (o.OXARTVAT2 <> 0 AND o.OXARTVAT2 <> ?)) AND o.OXID = ?";
        return $this->oxDB->getOne($sQ, array(Config::getCOIConfig('STANDARDLAND'), Config::getCOIConfig('STANDARDTAX'), Config::getCOIConfig('REDUCEDTAX'), $sOxId));
    }

    /* Land */

    public function getOxCountryObject() {
        if (!$this->oxCountry) {
            $this->oxCountry = oxNew(\OxidEsales\Eshop\Application\Model\Country::class);
        }
    }

    public function GetCountryData($sOxId) {
        $this->getOxCountryObject();
        $this->oxCountry->load($sOxId);
        $this->CountryName = $this->oxCountry->oxcountry__oxtitle->value;
        $this->CountryIso2 = $this->oxCountry->oxcountry__oxisoalpha2->value;
    }

    public function getCountryID($sOxId) {
        $this->getOxCountryObject();
        return $this->oxCountry->getIdByCode($sOxId);
    }

    /* Bezahlung */

    public function getOxPaymentObject() {
        if (!$this->oxPayment) {
            $this->oxPayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
        }
    }

    public function GetValueFromPayment($sId) {
        $aPayment = array();
        $this->getOxPaymentObject();
        $this->oxPayment->setDynValues(null);
        if ($this->oxPayment->load($sId)) {
            $aPayment = $this->oxPayment->getDynValues();
            if ($aPayment) {
                return $this->GetOxCreditCardData($aPayment);
            }
        } else {
            return array();
        }
    }

    public function GetPayment($sOxId) {
        $sQ = "SELECT OXDESC FROM oxpayments WHERE OXID=" . $this->oxDB->quote($sOxId);
        return $this->oxDB->getOne($sQ);
    }

    public function GetOxCreditCardData($aPayment) {
        $aCardData = array();

        if ($this->oxConfig->getConfigParam('blStoreCreditCardInfo')) {
            if ($aPayment[0]) {
                $aCardData[0] = $aPayment[0]->value . '-Valid:' . $aPayment[2]->value . '.' . $aPayment[3]->value . '-Check:' . $aPayment[5]->value;
            }
            $aCardData[2] = $aPayment[1]->value;
            $aCardData[3] = $aPayment[4]->value;
        } else {
            foreach ($aPayment as $sKey => $aValue) {
                switch ($aValue->name) {
                    case 'lsbankname':
                        $aCardData[0] = $aValue->value;
                        break;
                    case 'lsblz':
                        $aCardData[1] = $aValue->value;
                        break;
                    case 'lsktonr':
                        $aCardData[2] = $aValue->value;
                        break;
                    case 'lsktoinhaber':
                        $aCardData[3] = $aValue->value;
                        break;
                }
            }
        }
        return $aCardData;
    }

    // Email
    public function getOxEmailObject() {
        if (!$this->oxEmail) {
            $this->oxEmail = oxNew(\OxidEsales\Eshop\Core\Email::class);
        }
    }

    public function getOxContentObject() {
        if (!$this->oxContent) {
            $this->oxContent = oxNew(\OxidEsales\Eshop\Application\Model\Content::class);
        }
    }

    public function getOxSmartyObject() {
        if (!$this->oxSmarty) {
            $this->oxSmarty = Registry::getUtilsView()->getSmarty();
        }
    }

    public function getSmartyDir() {
        return Registry::getUtilsView()->getSmartyDir();
    }

    // Settings
    public function setOrderStatus() {
        $i = 0;

        if ($_GET['delivery']) {
            $Id = isset($_GET['delivery']) ? $_GET['delivery'] + 0 : 0;
            $sQ = "UPDATE oxstatus2cao SET OXSENDID='0' WHERE COI_CAOID != ?";
            $this->oxDB->Execute($sQ, array($Id));
            $sQ = "UPDATE oxstatus2cao SET OXSENDID='1' WHERE COI_CAOID= ?";
            $this->oxDB->Execute($sQ, array($Id));
            $i = 1;
        }
        if ($_GET['bill']) {
            $Id = isset($_GET['bill']) ? $_GET['bill'] + 0 : 0;
            $sQ = "UPDATE oxstatus2cao SET OXPAYID='0' WHERE COI_CAOID != ?";
            $this->oxDB->Execute($sQ, array($Id));
            $sQ = "UPDATE oxstatus2cao SET OXPAYID='1' WHERE COI_CAOID= ?";
            $this->oxDB->Execute($sQ, array($Id));
            $i = 1;
        }
        if ($_GET['storno']) {
            $Id = isset($_GET['storno']) ? $_GET['storno'] + 0 : 0;
            $sQ = "UPDATE oxstatus2cao SET STORNOID='0' WHERE COI_CAOID != ?";
            $this->oxDB->Execute($sQ, array($Id));
            $sQ = "UPDATE oxstatus2cao SET STORNOID='1' WHERE COI_CAOID= ?";
            $this->oxDB->Execute($sQ, array($Id));
            $i = 1;
        }
        return $i;
    }

}
