<?php

/**
 *  Modul        : coiXml.php
 *  Beschreibung : Script zum Datenaustausch CAO-Faktura <--> OXID eShop
 *                 Lauffähig unter OXID V 6.0.0
 * @author Thoren Strunk <edv@tstrunk.de>
 * @copyright Copyright (c) T.Strunk Software e.K.
 * Hinweis:
 * Dieses Projekt ist gemäß den Bedingungen der AGPL V3 lizenziert
 **/

if (!defined('COI_ROOT'))
    die('Diese Datei kann nicht aufgerufen werden');

class xmlFunc extends ox2Cao {

    protected $_sXMLout;
    private $_sGender;
    private $_sMatchCode;
    private $_iCustomerNumber;
    private $_sOrderBy;

    /**
     * XML-Header für Ausgabe vorbereiten
     */
    public function XMLHeader($sXMLheader) {
        if ($this->coi_iUtfMode)
            $sXMLEncode = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "\n" . $sXMLheader . "\n";
        else
            $sXMLEncode = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>" . "\n" . $sXMLheader . "\n";
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Content-type: text/xml");
        echo $sXMLEncode;
    }

    /**
     * XML-Statusausgabe für Modulausgabe bereitstellen
     */
    public function sendXMLStatus($iCode, $sAction, $sMsg, $sMode, $sItem, $iValue) {
        $this->_XMLStatus($iCode, $sAction, $sMsg, $sMode, $sItem, $iValue);
        return;
    }

    /**
     * XML-Tags ausgeben
     */
    protected function _doXMLout($sXMLstring) {
        echo $sXMLstring;
    }

    /**
     * XML Statusausgabe - Gibt Status an CAO zurück
     */
    protected function _XMLStatus($iCode, $sAction, $sMsg, $sMode, $sItem, $iValue) {
        $this->XMLHeader("<STATUS>");
        $this->_sXMLout = "<STATUS_DATA>\n" .
                "<CODE>" . $iCode . "</CODE>\n" .
                "<ACTION>" . $sAction . "</ACTION>\n" .
                "<MESSAGE>" . $sMsg . "</MESSAGE>\n";
        if (strlen($sMode) > 0) {
            $this->_sXMLout .= "<MODE>" . $sMode . "</MODE>\n";
        }
        if (strlen($sItem) > 0) {
            $this->_sXMLout .= "<" . $sItem . ">" . $iValue . "</" . $sItem . ">\n";
        }
        $this->_sXMLout .= "</STATUS_DATA>\n</STATUS>\n\n";
        $this->_doXMLout($this->_sXMLout);
        return;
    }

    /**
     * Scriptversion ausgeben
     * wird bei jeder Aktion von CAO abgefragt
     */
    public function GetScript($action) {
        if ($this->xmlversion) {
            $this->XMLHeader('<STATUS>');
            $this->_sXMLout = ("<STATUS_DATA>\n" .
                    "<ACTION>" . $action . "</ACTION>\n" .
                    "<CODE>111</CODE>\n" .
                    "<SCRIPT_VER>" . Config::$COIVersion . "</SCRIPT_VER>\n" .
                    "<SCRIPT_DATE>" . Config::$COIVersionDate . "</SCRIPT_DATE>\n" .
                    "</STATUS_DATA></STATUS>\n\n");
            $this->_doXMLout($this->_sXMLout);
        }
    }

    /* Kunden */

    public function CustomersExport($Data) {
        $sQ = "SELECT u.*,CONCAT_WS(' ',u.OXSTREET,u.OXSTREETNR) AS STREET,co.OXTITLE,co.OXISOALPHA2 FROM oxuser u " .
                "LEFT JOIN oxcountry co ON u.OXCOUNTRYID=co.OXID WHERE u.OXACTIVE=1 AND u.OXRIGHTS!='malladmin'";

        if (isset($Ddata['customers_from']) && isset($Data['customers_count'])) {
            $sQ = $sQ . " AND u.OXCUSTNR >= " . $this->oxDB->quote($Data['customers_from']) . " ORDER BY u.OXCUSTNR ASC LIMIT " . $Data['customers_count'];
        }

        if ($this->aXMLCustomer) {
            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);

            $this->XMLHeader($this->aXMLCustomer['START']);
            if (is_array($this->aDbRes)) {
                foreach ($this->aDbRes as $fields) {

                    switch ($fields['OXSAL']) {
                        case 'Herr':
                        case 'MR':
                        case 'Mr.': $this->_sGender = 'm';
                            break;
                        case 'Frau':
                        case 'MRS':
                        case 'Mrs.': $this->_sGender = 'w';
                            break;
                        default: $this->_sGender = '';
                    }

                    if (Config::getCOIConfig('USEMATCHCODE')) {
                        switch (Config::getCOIConfig('MATCHCOMPANYNAME')) {
                            case '0': $sCompanyName = '';
                                break;
                            case '1': $sCompanyName = $fields['OXCOMPANY'];
                                break;
                            case '2': $sCompanyName = $fields['OXFNAME'];
                                break;
                            case '3': $sCompanyName = $fields['OXLNAME'];
                                break;
                        }
                        switch (Config::getCOIConfig('MATCHFIRSTNAME')) {
                            case '0': $sFirstName = '';
                                break;
                            case '1': $sFirstName = $fields['OXCOMPANY'];
                                break;
                            case '2': $sFirstName = $fields['OXFNAME'];
                                break;
                            case '3': $sFirstName = $fields['OXLNAME'];
                                break;
                        }
                        switch (Config::getCOIConfig('MATCHLASTNAME')) {
                            case '0': $sLastName = '';
                                break;
                            case '1': $sLastName = $fields['OXCOMPANY'];
                                break;
                            case '2': $sLastName = $fields['OXFNAME'];
                                break;
                            case '3': $sLastName = $fields['OXLNAME'];
                                break;
                        }

                        $this->_sMatchCode = $this->convertXMLString(trim($sCompanyName . ' ' . $sFirstName . ' ' . $sLastName));
                    } else
                        $this->_sMatchCode = '';

                    if (!$fields['COI_CAOID']) {
                        $iCustomerNumber = $this->GetCoiId('user');
                        $this->SetCoiUserId($fields['OXID'], $iCustomerNumber);
                        $fields['COI_CAOID'] = $iCustomerNumber;
                    }

                    if (Config::getCOIConfig('USECUSTNUMBER')) {
                        $this->_iCustomerNumber = $fields['OXCUSTNR'];
                    } else {
                        $this->_iCustomerNumber = $fields['COI_CAOID'];
                    }

                    $this->_sXMLout = $this->aXMLCustomer['DATA'];

                    $this->_sXMLout .= str_replace("{#}", $this->_iCustomerNumber, $this->aXMLCustomer['ID']);

                    $this->_sXMLout .= str_replace("{#}", $this->_sMatchCode, $this->aXMLCustomer['MATCHCODE']);

                    $sCompanyName = $sFirstName = $sLastName = '';
                    switch (Config::getCOIConfig('COMPANYNAME')) {
                        case '0': $sCompanyName = $this->convertXMLString($fields['OXCOMPANY']);
                            break;
                        case '1': $sCompanyName = $this->convertXMLString($fields['OXFNAME']);
                            break;
                        case '2': $sCompanyName = $this->convertXMLString($fields['OXLNAME']);
                            break;
                        case '3': $sCompanyName = $this->convertXMLString($fields['OXFNAME']) . ' ' . $this->convertXMLString($fields['OXLNAME']);
                            break;
                        case '4': $sCompanyName = '';
                            break;
                    }
                    switch (Config::getCOIConfig('FIRSTNAME')) {
                        case '0': $sFirstName = $this->convertXMLString($fields['OXCOMPANY']);
                            break;
                        case '1': $sFirstName = $this->convertXMLString($fields['OXFNAME']);
                            break;
                        case '2': $sFirstName = $this->convertXMLString($fields['OXLNAME']);
                            break;
                        case '3': $sFirstName = $this->convertXMLString($fields['OXFNAME']) . ' ' . $this->convertXMLString($fields['OXLNAME']);
                            break;
                        case '4': $sFirstName = '';
                            break;
                    }
                    switch (Config::getCOIConfig('LASTNAME')) {
                        case '0': $sLastName = $this->convertXMLString($fields['OXCOMPANY']);
                            break;
                        case '1': $sLastName = $this->convertXMLString($fields['OXFNAME']);
                            break;
                        case '2': $sLastName = $this->convertXMLString($fields['OXLNAME']);
                            break;
                        case '3': $sLastName = $this->convertXMLString($fields['OXFNAME']) . ' ' . $this->convertXMLString($fields['OXLNAME']);
                            break;
                        case '4': $sLastName = '';
                            break;
                    }

                    if ($sCompanyName == '' && $sFirstName == '' && $sLastName == '') {
                        $sCompanyName = $this->convertXMLString($fields['OXCOMPANY']);
                        $sFirstName = $this->convertXMLString($fields['OXFNAME']);
                        $sLastName = $this->convertXMLString($fields['OXLNAME']);
                    }

                    $this->_sXMLout .= str_replace("{#}", $this->_sGender, $this->aXMLCustomer['GENDER']);
                    $this->_sXMLout .= str_replace("{#}", $sCompanyName, $this->aXMLCustomer['COMPANY']);
                    $this->_sXMLout .= str_replace("{#}", $sFirstName, $this->aXMLCustomer['FIRSTNAME']);
                    $this->_sXMLout .= str_replace("{#}", $sLastName, $this->aXMLCustomer['LASTNAME']);

                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['STREET']), $this->aXMLCustomer['STREET']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXZIP']), $this->aXMLCustomer['POSTCODE']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXCITY']), $this->aXMLCustomer['CITY']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE']), $this->aXMLCustomer['STATE']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXISOALPHA2']), $this->aXMLCustomer['COUNTRY']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXFON']), $this->aXMLCustomer['FON']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXFAX']), $this->aXMLCustomer['FAX']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXMOBFON']), $this->aXMLCustomer['MOBIL']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXUSERNAME']), $this->aXMLCustomer['EMAIL']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXUSTID']), $this->aXMLCustomer['VAT']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBIRTHDATE']), $this->aXMLCustomer['BIRTHDAY']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXCREATE']), $this->aXMLCustomer['CREATED']);
                    $this->_sXMLout .= trim($this->aXMLCustomer['DATA_END']);
                    $this->_doXMLout($this->_sXMLout);
                }
            }
            $this->_doXMLout($this->aXMLCustomer['END']);
        }
    }

    public function CustomersUpdate($Data) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($Data['customers_street'])) {
                preg_match_all("|(\d+){1,}|", $Data['customers_street'], $aStreetNr);
                if ($aStreetNr[0][0]) {
                    $aStreet = substr($Data['customers_street'], 0, strpos($Data['customers_street'], $aStreetNr[0][0]));
                } else
                    $aStreet = $Data['customers_street'];
            }

            switch ($Data['customers_gender']) {
                case 'm': $this->_sGender = "MR";
                    break;
                case 'w': $this->_sGender = "MRS";
                    break;
                default: $this->_sGender = "";
                    if ($postdata['customers_company']) {
                        $this->_sGender = "COMPANY";
                    }
            }

            if (isset($Data['cID']))
                $mode = "UPDATE";
            else
                $mode = "APPEND";

            if ($mode == "UPDATE") {
                if (Config::getCOIConfig('USECUSTNUMBER')) {
                    $sQ = "SELECT OXID FROM oxuser WHERE OXCUSTNR='" . $Data['cID'] . "' AND OXRIGHTS!='malladmin'";
                    $sOxId = $this->oxDB->getOne($sQ);
                } else {
                    $sQ = "SELECT OXID FROM oxuser WHERE COI_CAOID='" . $Data['cID'] . "' AND OXRIGHTS!='malladmin'";
                    $sOxId = $this->oxDB->getOne($sQ);
                }

                if ($sOxId) {
                    if ($sAOxId = $this->GetOxIdByUserName($Data['customers_email'])) {
                        if ($sOxId != $sAOxId) {
                            $this->_XMLStatus(105, $Data['action'], 'ERROR: EMAILADRESS ' . $Data['customers_email'] . ' IN USE BY OTHER USER', '', '', '');
                            exit;
                        }
                    }

                    switch (Config::getCOIConfig('COMPANYNAME')) {
                        case '0': $OXCOMPANY = $this->convertString($Data['customers_company']);
                            break;
                        case '1': $OXCOMPANY = $this->convertString($Data['customers_firstname']);
                            break;
                        case '2': $OXCOMPANY = $this->convertString($Data['customers_lastname']);
                            break;
                    }
                    switch (Config::getCOIConfig('FIRSTNAME')) {
                        case '0': $OXFNAME = $this->convertString($Data['customers_company']);
                            break;
                        case '1': $OXFNAME = $this->convertString($Data['customers_firstname']);
                            break;
                        case '2': $OXFNAME = $this->convertString($Data['customers_lastname']);
                            break;
                    }
                    switch (Config::getCOIConfig('LASTNAME')) {
                        case '0': $OXLNAME = $this->convertString($Data['customers_company']);
                            break;
                        case '1': $OXLNAME = $this->convertString($Data['customers_firstname']);
                            break;
                        case '2': $OXLNAME = $this->convertString($Data['customers_lastname']);
                            break;
                    }

                    $OXSAL = $this->_sGender;
                    $OXUSERNAME = $Data['customers_email'];
                    $OXSTREET = $this->convertString($aStreet);
                    $OXSTREETNR = $aStreetNr[0][0];
                    $OXCITY = $this->convertString($Data['customers_city']);
                    $OXZIP = $Data['customers_postcode'];
                    $OXFON = $Data['customers_tele'];
                    $OXFAX = $Data['customers_fax'];
                    $OXMOBFON = $Data['customers_mobil'];
                    $OXUSTID = $Data['customers_vat_id'];
                    $OXACTIVE = 1;
                    $OXCOUNTRYID = $this->getCountryID($Data['customers_country_id']);
                    $OXBIRTHDATE = $Data['customers_dob'];
                    if (isset($Data['customers_credit']) && $Data['customers_credit'] > 0) {
                        $OXBONI = $Data['customers_credit'];
                    } else
                        $OXBONI = 0;

                    $this->getOxUserObject();
                    $this->oxUser->load($sOxId);

                    $this->oxUser->oxuser__oxcompany = new \OxidEsales\Eshop\Core\Field($OXCOMPANY, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxsal = new \OxidEsales\Eshop\Core\Field($OXSAL, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxfname = new \OxidEsales\Eshop\Core\Field($OXFNAME, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxlname = new \OxidEsales\Eshop\Core\Field($OXLNAME, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxusername = new \OxidEsales\Eshop\Core\Field($OXUSERNAME, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxstreet = new \OxidEsales\Eshop\Core\Field($OXSTREET, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxstreetnr = new \OxidEsales\Eshop\Core\Field($OXSTREETNR, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxcity = new \OxidEsales\Eshop\Core\Field($OXCITY, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxzip = new \OxidEsales\Eshop\Core\Field($OXZIP, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxfon = new \OxidEsales\Eshop\Core\Field($OXFON, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxfax = new \OxidEsales\Eshop\Core\Field($OXFAX, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxmobfon = new \OxidEsales\Eshop\Core\Field($OXMOBFON, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxactive = new \OxidEsales\Eshop\Core\Field($OXACTIVE, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxcountryid = new \OxidEsales\Eshop\Core\Field($OXCOUNTRYID, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxustid = new \OxidEsales\Eshop\Core\Field($OXUSTID, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxUser->oxuser__oxbirthdate = new \OxidEsales\Eshop\Core\Field($OXBIRTHDATE, \OxidEsales\Eshop\Core\Field::T_RAW);
                    if ($OXBONI > 0)
                        $this->oxUser->oxuser__oxboni = new \OxidEsales\Eshop\Core\Field($OXBONI, \OxidEsales\Eshop\Core\Field::T_RAW);

                    if (isset($Data['customers_password'])) {
                        $this->oxUser->setPassword($Data['customers_password']);
                    }

                    if ($this->oxUser->save()) {
                        $this->SetUserPriceGroup($sOxId, $Data['customers_price_level']);
                        $this->_XMLStatus(0, $Data['action'], 'OK', 'UPDATE', 'CUSTOMERS_ID', $Data['cID']);
                    } else
                        $this->_XMLStatus(99, $Data['action'], 'ERROR: CANT UPDATE USER', '', '', '');
                } else
                    $mode = "APPEND";
            }

            if ($mode == "APPEND") {
                if (isset($Data['customers_email']) && $Data['customers_email'] > '') {
                    if (!$this->GetOxIdByUserName($Data['customers_email'])) {

                        $iCaoId = $this->GetCoiId('user');

                        switch (Config::getCOIConfig('COMPANYNAME')) {
                            case '0': $OXCOMPANY = $this->convertString($Data['customers_company']);
                                break;
                            case '1': $OXCOMPANY = $this->convertString($Data['customers_firstname']);
                                break;
                            case '2': $OXCOMPANY = $this->convertString($Data['customers_lastname']);
                                break;
                        }
                        switch (Config::getCOIConfig('FIRSTNAME')) {
                            case '0': $OXFNAME = $this->convertString($Data['customers_company']);
                                break;
                            case '1': $OXFNAME = $this->convertString($Data['customers_firstname']);
                                break;
                            case '2': $OXFNAME = $this->convertString($Data['customers_lastname']);
                                break;
                        }
                        switch (Config::getCOIConfig('LASTNAME')) {
                            case '0': $OXLNAME = $this->convertString($Data['customers_company']);
                                break;
                            case '1': $OXLNAME = $this->convertString($Data['customers_firstname']);
                                break;
                            case '2': $OXLNAME = $this->convertString($Data['customers_lastname']);
                                break;
                        }

                        $OXSAL = $this->_sGender;
                        $OXUSERNAME = $Data['customers_email'];
                        $OXSTREET = $this->convertString($aStreet);
                        $OXSTREETNR = $aStreetNr[0][0];
                        $OXCITY = $this->convertString($Data['customers_city']);
                        $OXZIP = $Data['customers_postcode'];
                        $OXFON = $Data['customers_tele'];
                        $OXFAX = $Data['customers_fax'];
                        $OXMOBFON = $Data['customers_mobil'];
                        $OXUSTID = $Data['customers_vat_id'];
                        $OXACTIVE = 1;
                        $OXCOUNTRYID = $this->getCountryID($Data['customers_country_id']);
                        $OXBIRTHDATE = $Data['customers_dob'];
                        if (isset($Data['customers_credit']) && $Data['customers_credit'] > 0) {
                            $OXBONI = $Data['customers_credit'];
                        } else
                            $OXBONI = 0;

                        $this->getOxUserObject();

                        $this->oxUser->oxuser__oxcompany = new \OxidEsales\Eshop\Core\Field($OXCOMPANY, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxsal = new \OxidEsales\Eshop\Core\Field($OXSAL, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxfname = new \OxidEsales\Eshop\Core\Field($OXFNAME, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxlname = new \OxidEsales\Eshop\Core\Field($OXLNAME, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxusername = new \OxidEsales\Eshop\Core\Field($OXUSERNAME, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxstreet = new \OxidEsales\Eshop\Core\Field($OXSTREET, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxstreetnr = new \OxidEsales\Eshop\Core\Field($OXSTREETNR, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxcity = new \OxidEsales\Eshop\Core\Field($OXCITY, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxzip = new \OxidEsales\Eshop\Core\Field($OXZIP, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxfon = new \OxidEsales\Eshop\Core\Field($OXFON, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxfax = new \OxidEsales\Eshop\Core\Field($OXFAX, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxmobfon = new \OxidEsales\Eshop\Core\Field($OXMOBFON, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxactive = new \OxidEsales\Eshop\Core\Field($OXACTIVE, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxustid = new \OxidEsales\Eshop\Core\Field($OXUSTID, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxcountryid = new \OxidEsales\Eshop\Core\Field($OXCOUNTRYID, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxUser->oxuser__oxbirthdate = new \OxidEsales\Eshop\Core\Field($OXBIRTHDATE, \OxidEsales\Eshop\Core\Field::T_RAW);
                        if ($OXBONI > 0)
                            $this->oxUser->oxuser__oxboni = new \OxidEsales\Eshop\Core\Field($OXBONI, \OxidEsales\Eshop\Core\Field::T_RAW);

                        if ($this->oxUser->createUser()) {
                            $sOxId = $this->GetOxIdByUserName($Data['customers_email']);
                            $this->SetUserPriceGroup($sOxId, $Data['customers_price_level']);
                            $this->oxUser->load($sOxId);

                            if (isset($Data['customers_password'])) {
                                $this->oxUser->setPassword($Data['customers_password']);
                                $this->oxUser->save();
                            }

                            $this->SetCoiUserId($sOxId, $iCaoId);

                            if (Config::getCOIConfig('USECUSTNUMBER')) {
                                $iCaoId = $this->oxUser->oxuser__oxcustnr->value;
                            }

                            $this->_XMLStatus(0, $Data['action'], 'OK', 'APPEND USER ' . $iCaoId, 'CUSTOMERS_ID', $iCaoId);
                        } else
                            $this->_XMLStatus(99, $Data['action'], 'ERROR: CANT CREATE USER', '', '', '');
                    } else
                        $this->_XMLStatus(105, $Data['action'], 'ERROR: USER EXISTS ' . $Data['customers_email'], '', '', '');
                } else
                    $this->_XMLStatus(99, $Data['action'], 'ERROR: NO EMAILADRESS IS GIVEN', '', '', '');
            }
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR: NO DATA FOR UDATE OR APPEND', '', '', '');
    }

    public function CustomersDelete($Data) {
        if (isset($Data['cID']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            if (Config::getCOIConfig('OXCUSTNR')) {
                $sQ = "SELECT OXID FROM oxuser WHERE OXCUSTNR='" . $Data['cID'] . "' AND OXRIGHTS!='malladmin'";
                $sOxId = $this->oxDB->getOne($sQ);
            } else {
                $sQ = "SELECT OXID FROM oxuser WHERE COI_CAOID='" . $Data['cID'] . "' AND OXRIGHTS!='malladmin'";
                $sOxId = $this->oxDB->getOne($sQ);
            }

            if ($sOxId) {
                $this->getOxUserObject();
                if ($this->oxUser->delete($sOxId)) {
                    $this->_XMLStatus(0, $Data['action'], 'OK: USER DELETET', '', '', '');
                } else
                    $this->_XMLStatus(0, $Data['action'], 'ERROR: CANT DELETE USER', '', '', '');
            } else
                $this->_XMLStatus(0, $Data['action'], 'ERROR: NO SHOPUSER OR IS ADMIN', '', '', '');
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR: NO USER-ID POST', '', '', '');
    }

    /* Hersteller */

    public function ManufacturersExport() {
        $sQ = "SELECT * FROM oxmanufacturers  WHERE OXACTIVE=1";

        if ($this->aXMLManufact) {
            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);

            $this->XMLHeader($this->aXMLManufact['START']);

            if (is_array($this->aDbRes)) {
                foreach ($this->aDbRes as $fields) {
                    if ($fields['COI_CAOID'] == 0) {
                        $Id = $this->GetNextCoiId('oxmanufacturers');
                        $this->SetCoiManufacturerId($fields['OXID'], $Id);
                    } else
                        $Id = $fields['COI_CAOID'];
                    $this->_sXMLout = $this->aXMLManufact['DATA'];
                    $this->_sXMLout .= str_replace("{#}", $Id, $this->aXMLManufact['ID']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE']), $this->aXMLManufact['NAME']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXICON']), $this->aXMLManufact['IMAGE']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXTIMESTAMP'], $this->aXMLManufact['CREATED']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXTIMESTAMP'], $this->aXMLManufact['MODIFIED']);
                    $this->_sXMLout .= str_replace("{#1}", "2", str_replace("{#2}", "de", str_replace("{#3}", "Deutsch", $this->aXMLManufact['DESC_ID'])));
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXSHORTDESC']), $this->aXMLManufact['DESCRIPTION']);
                    $this->_sXMLout .= $this->aXMLManufact['DESC_ID_END'];
                    $this->_sXMLout .= str_replace("{#1}", "1", str_replace("{#2}", "en", str_replace("{#3}", "English", $this->aXMLManufact['DESC_ID'])));
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXSHORTDESC_1']), $this->aXMLManufact['URL']);
                    $this->_sXMLout .= $this->aXMLManufact['DESC_ID_END'];
                    $this->_sXMLout .= $this->aXMLManufact['DATA_END'];
                    $this->_doXMLout($this->_sXMLout);
                }
            }
            $this->_doXMLout($this->aXMLManufact['END']);
        }
    }

    public function ManufacturersUpdate($Data) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $OXTITLE = $this->convertString($Data['manufacturers_name']);
            $OXSHORTDESC = $this->convertString($Data['manufacturers_description'][2]);
            $OXICON = $Data['manufacturers_image'];

            $this->getOxManufacturerObject();

            $iCaoId = $Data['mID'];
            $sOxId = $this->GetOxIdOverCaoId('oxmanufacturers', $Data['mID']);

            if ($sOxId)
                $this->oxManufacturer->load($sOxId);

            $this->oxManufacturer->oxmanufacturers__oxtitle = new \OxidEsales\Eshop\Core\Field($OXTITLE, \OxidEsales\Eshop\Core\Field::T_RAW);
            $this->oxManufacturer->oxmanufacturers__oxshortdesc = new \OxidEsales\Eshop\Core\Field($OXSHORTDESC, \OxidEsales\Eshop\Core\Field::T_RAW);
            $this->oxManufacturer->oxmanufacturers__oxicon = new \OxidEsales\Eshop\Core\Field($OXICON, \OxidEsales\Eshop\Core\Field::T_RAW);

            if ($this->oxManufacturer->save()) {
                if ($sOxId)
                    $Mode = 'UPDATE';
                else {
                    $sOxId = $this->oxManufacturer->getId();
                    $this->SetCoiManufacturerId($sOxId, $iCaoId);
                    $Mode = 'APPEND';
                }

                if (Config::getCaoLanguage())
                    $this->_ManufacturersLanguage($Data, $sOxId);

                if ($Mode == 'UPDATE')
                    $this->_XMLStatus(0, $Data['action'], 'OK', 'UPDATE', 'MANUFACTURERS_ID', $Data['mID']);
                else
                    $this->_XMLStatus(0, $Data['action'], 'OK', 'APPEND', 'MANUFACTURERS_ID', $iCaoId);
            } else
                $this->_XMLStatus(0, $Data['action'], 'NOTHING TO UPDATE FOR MANUFACTURERS', 'UPDATE', 'MANUFACTURERS_ID', $Data['mID']);
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR: NO DATA FOR UDATE OR APPEND', '', '', '');
    }

    private function _ManufacturersLanguage($Data, $sOxId) {
        $this->getOxManufacturerObject();
        $aLanguage = Config::getLanguageArray();
        foreach ($aLanguage as $i => $sVal) {
            $this->oxManufacturer->loadInLang($i, $sOxId);
            if (isset($Data['manufacturers_description'][$i])) {
                $OXSHORTDESC = $this->convertString($Data['manufacturers_description'][$i]);
                $this->oxManufacturer->oxmanufacturers__oxshortdesc = new \OxidEsales\Eshop\Core\Field($OXSHORTDESC, \OxidEsales\Eshop\Core\Field::T_RAW);
            }

            $OXTITLE = $this->convertString($Data['manufacturers_name']);
            $this->oxManufacturer->oxmanufacturers__oxtitle = new \OxidEsales\Eshop\Core\Field($OXTITLE, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXICON = $Data['manufacturers_image'];
            $this->oxManufacturer->oxmanufacturers__oxicon = new \OxidEsales\Eshop\Core\Field($OXICON, \OxidEsales\Eshop\Core\Field::T_RAW);

            $this->oxManufacturer->save();
        }
    }

    public function ManufacturersDelete($Data) {
        if (isset($Data['mID']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $sOxId = $this->GetOxIdOverCaoId('oxmanufacturers', $Data['mID']);
            if ($sOxId) {
                $this->getOxManufacturerObject();
                if ($this->oxManufacturer->delete($sOxId)) {
                    $this->_XMLStatus(0, $Data['action'], 'OK: MANUFACTURERS DELETET', '', '', '');
                }
            } else
                $this->_XMLStatus(0, $Data['action'], 'ERROR: NO MANUFACTURERS', '', '', '');
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR: NO MANUFACTURERS-ID POST', '', '', '');
    }

    public function ManufacturersImage($Data) {
        $sMsg = $this->CoiImageUpload('/manufacturer/icon/', 'manufacturers_image');
        if ($sMsg)
            $this->_XMLStatus(-1, $Data['action'], $sMsg, '', '', '');
        else
            $this->_XMLStatus(0, $Data['action'], 'OK: IMAGE UPLOAD', '', 'FILE_NAME', $this->UploadedFileName);
    }

    /* Kategorien */

    public function CategoriesExport() {
        $sQ = "SELECT ox.*,c.COI_CAOID,c.CAOTOPID FROM oxcategories ox JOIN oxcat2cao c ON ox.OXID=c.OXCATID WHERE ox.OXACTIVE=1";

        if ($this->aXMLCategories) {
            $this->UpdateCaoCategories();
            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);

            $this->XMLHeader($this->aXMLCategories['START']);

            if (is_array($this->aDbRes)) {
                foreach ($this->aDbRes as $fields) {
                    $this->GetMetaData($fields['OXID']);
                    $this->_sXMLout = $this->aXMLCategories['DATA'];
                    $this->_sXMLout .= str_replace("{#}", $fields['COI_CAOID'], $this->aXMLCategories['ID']);
                    $this->_sXMLout .= str_replace("{#}", $fields['CAOTOPID'], $this->aXMLCategories['PARENT_ID']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTHUMB']), $this->aXMLCategories['IMAGE_URL']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXSORT'], $this->aXMLCategories['SORT_ORDER']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXTIMESTAMP'], $this->aXMLCategories['CREATED']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXTIMESTAMP'], $this->aXMLCategories['MODIFIED']);

//--> SprachenModul
                    if (Config::getCaoLanguage()) {
                        $aLanguage = Config::getLanguageArray();
                        foreach ($aLanguage as $iKey => $sVal) {
                            $this->_sXMLout .= str_replace("{#1}", $iKey, str_replace("{#2}", $aLanguage[$iKey]['ISO'], str_replace("{#3}", $aLanguage[$iKey]['NAME'], $this->aXMLCategories['CAT_DESC'])));
                            if (!(int) $iKey) {
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE']), $this->aXMLCategories['NAME']);
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDESC']), $this->aXMLCategories['HEAD_TITLE']);
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXLONGDESC']), $this->aXMLCategories['DESC']);
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDESC']), $this->aXMLCategories['META_TITLE']);
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaDesc), $this->aXMLCategories['META_DESC']);
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaKey), $this->aXMLCategories['META_KEY']);
                            } else {
                                $this->GetMetaData($fields['OXID'], $iKey);

                                $sQ = "DESCRIBE oxcategories 'OXTITLE_" . $iKey . "'";
                                if ($this->oxDB->getOne($sQ)) {
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE_' . $iKey]), $this->aXMLCategories['NAME']);
                                }

                                $sQ = "DESCRIBE oxcategories 'OXDESC_" . $iKey . "'";
                                if ($this->oxDB->getOne($sQ)) {
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDESC_' . $iKey]), $this->aXMLCategories['HEAD_TITLE']);
                                }

                                $sQ = "DESCRIBE oxcategories 'OXLONGDESC_" . $iKey . "'";
                                if ($this->oxDB->getOne($sQ)) {
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXLONGDESC_' . $iKey]), $this->aXMLCategories['DESC']);
                                }

                                $sQ = "DESCRIBE oxcategories 'OXDESC_" . $iKey . "'";
                                if ($this->oxDB->getOne($sQ)) {
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDESC_' . $iKey]), $this->aXMLCategories['META_TITLE']);
                                }

                                if (strlen($this->sMetaDesc) > 0) {
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaDesc), $this->aXMLCategories['META_DESC']);
                                }

                                if (strlen($this->sMetaKey) > 0) {
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaKey), $this->aXMLCategories['META_KEY']);
                                }
                            }
                            $this->_sXMLout .= $this->aXMLCategories['CAT_DESC_END'];
                        }
                    }
//--> ohne Sprachenmodul
                    else {
                        $this->_sXMLout .= str_replace("{#1}", "2", str_replace("{#2}", "de", str_replace("{#3}", "Deutsch", $this->aXMLCategories['CAT_DESC'])));
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE']), $this->aXMLCategories['NAME']);
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDESC']), $this->aXMLCategories['HEAD_TITLE']);
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXLONGDESC']), $this->aXMLCategories['DESC']);
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDESC']), $this->aXMLCategories['META_TITLE']);
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaDesc), $this->aXMLCategories['META_DESC']);
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaKey), $this->aXMLCategories['META_KEY']);
                        $this->_sXMLout .= $this->aXMLCategories['CAT_DESC_END'];
                    }

                    $sQ = "SELECT oxarticles.COI_CAOID FROM oxobject2category ox JOIN oxarticles ON ox.OXOBJECTID= oxarticles.OXID WHERE ox.OXCATNID=" . $this->oxDB->quote($fields['OXID']);
                    $this->oxDB->setFetchMode(MODE_BOTH);
                    $aProdCat = $this->oxDB->getAll($sQ);
                    if (is_array($aProdCat)) {
                        foreach ($aProdCat as $prodField) {
                            $this->_sXMLout .= str_replace("{#}", $prodField[0], $this->aXMLCategories['PROD_ID']);
                        }
                    }

                    $this->_sXMLout .= $this->aXMLCategories['DATA_END'];
                    $this->_doXMLout($this->_sXMLout);
                }
            }
            $this->_doXMLout($this->aXMLCategories['END']);
        }
    }

    public function CategoriesUpdate($Data) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($Data['catid']) && isset($Data['parentid']))
                $mode = "UPDATE";
            else
                $mode = "APPEND";

            $sQ = "SELECT ox.OXID FROM oxcategories ox JOIN oxcat2cao c ON ox.OXID=c.OXCATID WHERE c.COI_CAOID=" . $this->oxDB->quote($Data['catid']);
            $sOxId = $this->oxDB->getOne($sQ);
            $iTopID = $Data['parentid'];
            $sDescription = '';
            if ((isset($Data['categories_meta_title'])) && (strlen($Data['categories_meta_title']) > 0))
                $sDescription = $this->convertString($Data['categories_meta_title']);
            else if ((isset($Data['shortdescr'])) && (strlen($Data['shortdescr']) > 0))
                $sDescription = $this->convertString($Data['shortdescr']);
            $this->getOxCategorieObject();

            if (!$sOxId)
                $mode = "APPEND";

            if (($sOxId) && ($mode == 'UPDATE')) {
                $this->oxCategorie->load($sOxId);

                if ($Data['parentid'] <= 0) {
                    $sPOXID = 'oxrootid';
                    $sROOTID = $sOxId;
                    $iTopID = -1;
                } else {
                    $sQ = "SELECT OXCATPARENTID FROM oxcat2cao WHERE CAOTOPID=" . $this->oxDB->quote($Data['parentid']) . " AND OXCATPARENTID > '' ";
                    $sPOXID = $this->oxDB->getOne($sQ);
                    if ($sPOXID) {
                        $sQ = "SELECT OXROOTID FROM oxcategories WHERE OXPARENTID=" . $this->oxDB->quote($sPOXID);
                        $sROOTID = $this->oxDB->getOne($sQ);
                    } else {
                        $sQ = "SELECT OXCATID FROM oxcat2cao WHERE COI_CAOID=" . $this->oxDB->quote($Data['parentid']);
                        $sPOXID = $this->oxDB->getOne($sQ);
                        if ($sPOXID) {
                            $sQ = "SELECT OXROOTID FROM oxcategories WHERE OXID=" . $this->oxDB->quote($sPOXID);
                            $sROOTID = $this->oxDB->getOne($sQ);
                        }
                    }
                }
            } else
            if ($mode == 'APPEND') {
                if ($Data['parentid'] > 0) {
                    $sQ = "SELECT OXCATPARENTID FROM oxcat2cao WHERE CAOTOPID=" . $this->oxDB->quote($Data['parentid']) . " AND OXCATPARENTID > '' ";
                    $sCPOXID = $this->oxDB->getOne($sQ);
                    if ($sCPOXID) {
                        $sPOXID = $sCPOXID;
                        $sQ = "SELECT OXROOTID FROM oxcategories WHERE OXPARENTID=" . $this->oxDB->quote($sPOXID);
                        $sROOTID = $this->oxDB->getOne($sQ);
                        if (!$sROOTID) {
                            $sQ = "SELECT OXROOTID FROM oxcategories WHERE OXID=" . $this->oxDB->quote($sPOXID);
                            $sROOTID = $this->oxDB->getOne($sQ);
                        }
                    } else {
                        $sQ = "SELECT OXCATID FROM oxcat2cao WHERE COI_CAOID=" . $this->oxDB->quote($Data['parentid']);
                        $sCPOXID = $this->oxDB->getOne($sQ);
                        if ($sCPOXID) {
                            $sPOXID = $sCPOXID;
                            $sQ = "SELECT OXROOTID FROM oxcategories WHERE OXID=" . $this->oxDB->quote($sPOXID);
                            $sROOTID = $this->oxDB->getOne($sQ);
                        }
                    }
                } else
                    $sROOTID = 'xyz';
            }

            if ($sROOTID) {
                $OXTITLE = '';
                $OXLONGDESC = '';
                $OXDESC = '';

                $OXICON = $Data['image'];
                $OXSORT = $Data['sort'];
                $OXTHUMB = $Data['image'];

                if (!Config::getCaoLanguage()) {
                    $OXTITLE = $this->convertString($Data['name']);
                    $OXLONGDESC = $this->convertString(html_entity_decode($Data['descr']));
                    $OXDESC = $sDescription;
                    $OXACTIVE = 1;

// Metas
                    $this->sMetaDesc = $this->convertString($Data['categories_meta_description']);
                    $this->sMetaKey = $this->convertString($Data['categories_meta_keywords']);

                    $this->oxCategorie->oxcategories__oxtitle = new \OxidEsales\Eshop\Core\Field($OXTITLE, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxCategorie->oxcategories__oxlongdesc = new \OxidEsales\Eshop\Core\Field($OXLONGDESC, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxCategorie->oxcategories__oxdesc = new \OxidEsales\Eshop\Core\Field($OXDESC, \OxidEsales\Eshop\Core\Field::T_RAW);
                    $this->oxCategorie->oxcategories__oxactive = new \OxidEsales\Eshop\Core\Field($OXACTIVE, \OxidEsales\Eshop\Core\Field::T_RAW);
                }

                $this->oxCategorie->oxcategories__oxsort = new \OxidEsales\Eshop\Core\Field($OXSORT, \OxidEsales\Eshop\Core\Field::T_RAW);
                $this->oxCategorie->oxcategories__oxthumb = new \OxidEsales\Eshop\Core\Field($OXTHUMB, \OxidEsales\Eshop\Core\Field::T_RAW);
                $this->oxCategorie->oxcategories__oxicon = new \OxidEsales\Eshop\Core\Field($OXICON, \OxidEsales\Eshop\Core\Field::T_RAW);

                if (($sOxId) && ($mode == 'UPDATE')) {
                    if ($this->oxCategorie->save()) {

                        if ($sPOXID == 'oxrootid')
                            $sPOXID = $sOxId;
                        $sQ = "UPDATE oxcat2cao SET CAOTOPID=" . $this->oxDB->quote($iTopID) . ",OXCATPARENTID=" . $this->oxDB->quote($sPOXID) . " WHERE OXCATID=" . $this->oxDB->quote($sOxId);
                        $this->oxDB->Execute($sQ);

                        $this->UpdateOxCategorieTree();

                        if (Config::getCaoLanguage()) {
                            $this->_CategoriesLanguage($Data, $sOxId);
                        } else
                            $this->SetOxSeo($sOxId);

                        $this->_XMLStatus(0, $Data['action'], 'OK: CAT ' . $Data['catid'] . ' UPDATED', 'UPDATE', '', '');
                    } else
                        $this->_XMLStatus(99, $Data['action'], 'ERROR UPDATE CATEGORIES - SAVE FAILED', 'UPDATE', '', '');
                } else {
                    if ($Data['parentid'] > 0) {
                        $this->oxCategorie->oxcategories__oxparentid = new \OxidEsales\Eshop\Core\Field($sPOXID, \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxCategorie->oxcategories__oxrootid = new \OxidEsales\Eshop\Core\Field($sROOTID, \OxidEsales\Eshop\Core\Field::T_RAW);
                    } else
                        $this->oxCategorie->oxcategories__oxparentid = new \OxidEsales\Eshop\Core\Field('oxrootid', \OxidEsales\Eshop\Core\Field::T_RAW);

                    $this->oxCategorie->oxcategories__oxshopid = new \OxidEsales\Eshop\Core\Field($this->iShopId, \OxidEsales\Eshop\Core\Field::T_RAW);
                    if ($this->oxCategorie->save()) {
                        $sOxId = $this->oxCategorie->getId();

                        if ($Data['parentid'] <= 0) {
                            $sPOXID = 'oxrootid';
                            $sROOTID = $sCPOXID = $sOxId;
                            $iTopID = -1;
                            $this->oxCategorie->load($sOxId);
                            $this->oxCategorie->oxcategories__oxparentid = new \OxidEsales\Eshop\Core\Field($sPOXID, \OxidEsales\Eshop\Core\Field::T_RAW);
                            $this->oxCategorie->oxcategories__oxrootid = new \OxidEsales\Eshop\Core\Field($sROOTID, \OxidEsales\Eshop\Core\Field::T_RAW);
                            $this->oxCategorie->save();
                        }

                        if (Config::getCaoLanguage())
                            $this->_CategoriesLanguage($Data, $sOxId);

                        if (isset($Data['catid']))
                            $iCaoID = $Data['catid'];
                        else
                            $iCaoID = $this->GetNextCoiId('oxcat2cao');
                        $sQ = "INSERT INTO oxcat2cao (COI_CAOID,CAOTOPID,OXCATID,OXCATPARENTID) "
                                . "VALUES("
                                . $this->oxDB->quote($iCaoID) . ","
                                . $this->oxDB->quote($iTopID) . ","
                                . $this->oxDB->quote($sOxId) . ","
                                . $this->oxDB->quote($sCPOXID) . ")";
                        $this->oxDB->Execute($sQ);
                        $this->UpdateOxCategorieTree();
                        $this->_XMLStatus(0, $Data['action'], 'OK', 'CATEGORIES APPEND', '', $iCaoID);
                    } else
                        $this->_XMLStatus(99, $Data['action'], 'ERROR APPEND CATEGORIES - SAVE FAILED ' . $ret, 'UPDATE', '', '');
                }
            } else
                $this->_XMLStatus(99, $Data['action'], 'ERROR UPDATE CATEGORIES - NO ROOT', 'UPDATE', '', '');
        }
    }

    private function _CategoriesLanguage($Data, $sOxId) {
        $this->getOxCategorieObject();
        $aLanguage = Config::getLanguageArray();
        foreach ($aLanguage as $i => $sVal) {
            $this->oxCategorie->loadInLang($i, $sOxId);
            if (isset($Data['cat_name'][$i])) {
                $OXTITLE = $this->convertString($Data['cat_name'][$i]);
                $this->oxCategorie->oxcategories__oxtitle = new \OxidEsales\Eshop\Core\Field($OXTITLE, \OxidEsales\Eshop\Core\Field::T_RAW);
            }
            if (isset($Data['cat_shortdescr'][$i])) {
                $OXDESC = $this->convertString($Data['cat_shortdescr'][$i]);
                $this->oxCategorie->oxcategories__oxdesc = new \OxidEsales\Eshop\Core\Field($OXDESC, \OxidEsales\Eshop\Core\Field::T_RAW);
            }
            if (isset($Datata['cat_descr'][$i])) {
                $OXLONGDESC = $this->convertString(html_entity_decode($Data['cat_descr'][$i]));
                $this->oxCategorie->oxcategories__oxlongdesc = new \OxidEsales\Eshop\Core\Field($OXLONGDESC, \OxidEsales\Eshop\Core\Field::T_RAW);
            }
            $OXACTIVE = 1;
            $this->oxCategorie->oxcategories__oxactive = new \OxidEsales\Eshop\Core\Field($OXACTIVE, \OxidEsales\Eshop\Core\Field::T_RAW);

            $OXTHUMB = $Data['image'];
            $this->oxCategorie->oxcategories__oxthumb = new \OxidEsales\Eshop\Core\Field($OXTHUMB, \OxidEsales\Eshop\Core\Field::T_RAW);

            if (isset($Data['cat_meta_description'][$i])) {
                $this->sMetaDesc = $this->convertString($Data['cat_meta_description'][$i]);
            }
            if (isset($Data['cat_meta_keywords'][$i])) {
                $this->sMetaKey = $this->convertString($Data['cat_meta_keywords'][$i]);
            }

            $this->oxCategorie->save();
            $this->SetOxSeo($sOxId, $i);
        }
    }

    public function CategoriesErase($Data) {
        if (isset($Data['catid']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $sQ = "SELECT OXCATID FROM oxcat2cao WHERE COI_CAOID=" . $this->oxDB->quote($Data['catid']);
            $sOxId = $this->oxDB->getOne($sQ);
            if ($sOxId) {
                $this->getOxCategorieObject();

                if ($this->oxCategorie->delete($sOxId)) {
                    $sQ = "DELETE FROM oxcat2cao WHERE COI_CAOID=" . $this->oxDB->quote($Data['catid']);
                    $this->oxDB->Execute($sQ);
                    $this->_XMLStatus(0, $Data['action'], 'OK: CATEGORIES DELETET', '', '', '');
                } else
                    $this->_XMLStatus(99, $Data['action'], 'ERROR: NO CATEGORIES DELETE', '', '', '');
            } else
                $this->_XMLStatus(0, $Data['action'], 'ERROR: NO CATEGORIES', '', '', '');
        } else
            $this->XMLStatus(99, $Data['action'], 'ERROR: NO CATEGORIES-ID POST', '', '', '');
    }

    public function CategoriesImage($Data) {
        $sMsg = $this->CoiImageUpload('/category/thumb/', 'categories_image');
        if ($sMsg)
            $this->_XMLStatus(-1, $Data['action'], $sMsg, '', '', '');
        else
            $this->_XMLStatus(0, $Data['action'], 'OK: IMAGE UPLOAD', '', 'FILE_NAME', $this->UploadedFileName);
    }

    /* Artikel */

    public function ProductsExport($Data) {
        $sQ = "SELECT a.* FROM oxarticles a";
        if (Config::getCOIConfig('ACTIVEARTICLE') == 0)
            $sQ .= " WHERE a.oxactive=1";
        $sQ .= " ORDER BY a.OXPARENTID ASC";
        if (isset($Data['products_from']) && isset($Data['products_count']))
            $sQ .= " LIMIT " . $Data['products_from'] . "," . $Data['products_count'];

        $iArticleMatch = Config::getCOIConfig('ARTICLEMATCHCODE');

        if ($this->aXMLProducts) {
            $this->XMLHeader($this->aXMLProducts['START']);

            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);
            if (is_array($this->aDbRes)) {
                foreach ($this->aDbRes as $fields) {
                    $sMatchCode = '';
                    $sVarChildName = '';
                    $iVendorId = $this->GetCaoIdOverOxId('oxmanufacturers', $fields['OXMANUFACTURERID']);
                    $sLongDescription = $this->GetArticleLongDescription($fields['OXID']);

                    switch ($iArticleMatch) {
                        case 0: $sMatchCode = $fields['OXARTNUM'];
                            break;
                        case 1: $sMatchCode = $fields['OXTITLE'];
                            break;
                        case 2: $sMatchCode = $fields['OXARTNUM'] . ' ' . $fields['OXTITLE'];
                            break;
                        case 4: $sMatchCode = $fields['OXTITLE'] . ' ' . $fields['OXARTNUM'];
                            break;
                        default: $sMatchCode = $fields['OXARTNUM'];
                    }

                    if ($fields['OXVAT'])
                        $dTax = $fields['OXVAT'];
                    else
                        $dTax = $this->dDefaultTax;

                    if ($this->IsNetPrice)
                        $dPrice = (float) $fields['OXPRICE'];
                    else
                        $dPrice = (float) (($fields['OXPRICE'] / (100 + $dTax)) * 100);

// Varianten
                    if ($fields['OXVARNAME'] && $fields['OXVARCOUNT']) {
                        $aParentArticle[$fields['OXID']] = $fields['OXARTNUM'];
                        $aParentTitle[$fields['OXID']] = $fields['OXTITLE'];
                        $aParentShortDescription[$fields['OXID']] = $fields['OXSHORTDESC'];
                        $aParentLongDescription[$fields['OXID']] = $sLongDescription;
                        $sVariantName = $fields['OXVARNAME'];
                    }
                    if ($fields['OXVARNAME'] || $fields['OXVARSELECT'])
                        $sVariantChildName = $fields['OXVARSELECT'];

                    $iVariantId = $this->GetCaoIdOverOxId('oxarticles', $fields['OXPARENTID']);

                    if (Config::getCaoLanguage()) {
                        $aLanguage = Config::getLanguageArray();
                        foreach ($aLanguage as $iKey => $sVal) {
                            if ((int) $iKey) {
                                if ($fields['OXVARNAME_' . $iKey] && $fields['OXVARCOUNT']) {
                                    $aVariantName[$iKey] = $fields['OXVARNAME_' . $iKey];
                                }
                                if ($fields['OXVARNAME_' . $iKey] || $fields['OXVARSELECT_' . $iKey])
                                    $aVariantChildName[$iKey] = $fields['OXVARSELECT_' . $iKey];
                            }
                        }
                    }

// Schalter für Beschreibung Ja/Nein
                    if (Config::getCOIConfig('CHANGEDESCRIPTION')) {
                        if ($fields['OXTITLE'] == '')
                            $sOXTitle = $aParentTitle[$fields['OXPARENTID']];
                        else
                            $sOXTitle = $fields['OXTITLE'];
                        if ($fields['OXSHORTDESC'] == '')
                            $sOXShortDesc = $aParentShortDesc[$fields['OXPARENTID']];
                        else
                            $sOXShortDesc = $fields['OXSHORTDESC'];
                    } else {
                        $sOXTitle = $fields['OXTITLE'];
                        $sOXShortDesc = $fields['OXSHORTDESC'];
                    }

                    if (!$fields['COI_CAOID'])
                        $iCaoId = $this->UpdateCaoIdInOxTable('articles', $fields['OXID']);
                    else
                        $iCaoId = $fields['COI_CAOID'];

                    $this->GetMetaData($fields['OXID']);

                    $this->_sXMLout = $this->aXMLProducts['INFO'];
                    $this->_sXMLout .= $this->aXMLProducts['DATA'];
                    $this->_sXMLout .= str_replace("{#}", $iCaoId, $this->aXMLProducts['ID']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXSTOCK'], $this->aXMLProducts['QUANTITY']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXARTNUM']), $this->aXMLProducts['MODEL']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sMatchCode), $this->aXMLProducts['MATCH']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXSORT'], $this->aXMLProducts['SORT']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXSTOCKFLAG'], $this->aXMLProducts['DELSTATUS']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXPIC1'], $this->aXMLProducts['IMAGE']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXPIC2'], $this->aXMLProducts['IMAGE_MED']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXPIC3'], $this->aXMLProducts['IMAGE_LARGE']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXEAN'], $this->aXMLProducts['EAN']);
                    $this->_sXMLout .= str_replace("{#}", $dPrice, $this->aXMLProducts['PRICE']);

// EK-Preis überr Schalter
                    if (Config::getCOIConfig('EKPRICE'))
                        $this->_sXMLout .= str_replace("{#}", $fields['OXBPRICE'], $this->aXMLProducts['EK']);

                    $this->_sXMLout .= str_replace("{#}", $fields['OXWEIGHT'], $this->aXMLProducts['WEIGHT']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXACTIVE'], $this->aXMLProducts['STATUS']);
                    $this->_sXMLout .= str_replace("{#}", $dTax, $this->aXMLProducts['TAX_RATE']);
                    $this->_sXMLout .= str_replace("{#}", $iVendorId, $this->aXMLProducts['MID']);

//--> Sprachen
                    if (Config::getCaoLanguage()) {
                        $aLanguage = Config::getLanguageArray();
                        foreach ($aLanguage as $iKey => $sVal) {
                            $this->_sXMLout .= str_replace("{#1}", $iKey, str_replace("{#2}", $aLanguage[$iKey]['ISO'], str_replace("{#3}", $aLanguage[$iKey]['NAME'], $this->aXMLProducts['DESC_ID'])));
                            if (!(int) $iKey) {
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE']), $this->aXMLProducts['NAME']);
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXEXTURL']), $this->aXMLProducts['URL']);
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sLongDescription), $this->aXMLProducts['DESC']);
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXSHORTDESC']), $this->aXMLProducts['SHORT_DESC']);

                                if (Config::getCOIConfig('TEXTDESCRIPTION')) {
                                    $this->_sXMLout .= str_replace("{#}", $this->convertHtmlToString($sLongDescription), $this->aXMLProducts['TXT_DESC']);
                                    $this->_sXMLout .= str_replace("{#}", $this->convertHtmlToString($fields['OXSHORTDESC']), $this->aXMLProducts['TXT_SHORT_DESC']);
                                }

                                $this->_sXMLout .= str_replace("{#}", $iVariantId, $this->aXMLProducts['VARID']);
                                if ($sVariantChildName > '')
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sVariantChildName), $this->aXMLProducts['VARCNAME']); //Variantenkindname
                                if ($sVariantName > '')
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sVariantName), $this->aXMLProducts['VARNAME']); //Variantenname

                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaDesc), $this->aXMLProducts['META_DESC']); //Meta-Beschreibung
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaKey), $this->aXMLProducts['META_KEY']); //Meta-Schlï¿½ssel
                            } else {
                                $sQ = "DESCRIBE oxarticles 'OXTITLE_" . $iKey . "'";
                                if ($this->oxDB->getOne($sQ))
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE_' . $iKey]), $this->aXMLProducts['NAME']);

                                $sQ = "DESCRIBE oxarticles 'OXEXTURL_" . $iKey . "'";
                                if ($this->oxDB->getOne($sQ))
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXEXTURL_' . $iKey]), $this->aXMLProducts['URL']);

                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->GetArticleLongDescription($fields['OXID'], $iKey)), $this->aXMLProducts['DESC']);

                                $sQ = "DESCRIBE oxarticles 'OXSHORTDESC_" . $iKey . "'";
                                if ($this->oxDB->getOne($sQ))
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXSHORTDESC_' . $iKey]), $this->aXMLProducts['SHORT_DESC']);

                                if (Config::getCOIConfig('TXTDESCRIPTION')) {
                                    $this->_sXMLout .= str_replace("{#}", $this->convertHtmlToString($this->GetArticleLongDescription($fields['OXID'])), $this->aXMLProducts['TXT_DESC']);
                                    $sQ = "DESCRIBE oxarticles 'OXSHORTDESC_" . $iKey . "'";
                                    if ($this->oxDB->getOne($sQ))
                                        $this->_sXMLout .= str_replace("{#}", $this->convertHtmlToString($fields['OXSHORTDESC_' . $iKey]), $this->aXMLProducts['TXT_SHORT_DESC']);
                                }

                                $this->_sXMLout .= str_replace("{#}", $iVariantId, $this->aXMLProducts['VARID']); //ID des Vaterartikel
                                if ($aVariantChildName[$iKey] > '')
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($aVariantChildName[$iKey]), $this->aXMLProducts['VARCNAME']); //Variantenkindname
                                if ($aVariantName[$iKey] > '')
                                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($aVariantName[$iKey]), $this->aXMLProducts['VARNAME']); //Variantenname

                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaDesc), $this->aXMLProducts['META_DESC']); //Meta-Beschreibung
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaKey), $this->aXMLProducts['META_KEY']); //Meta-Schlï¿½ssel
                            }
                            $this->_sXMLout .= $this->aXMLProducts['DESC_ID_END'];
                        }
                    } else {
                        $this->_sXMLout .= str_replace("{#1}", "2", str_replace("{#2}", "de", str_replace("{#3}", "Deutsch", $this->aXMLProducts['DESC_ID'])));
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE']), $this->aXMLProducts['NAME']);
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXEXTURL']), $this->aXMLProducts['URL']);
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sLongDescription), $this->aXMLProducts['DESC']);
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXSHORTDESC']), $this->aXMLProducts['SHORT_DESC']);

                        if (Config::getCOIConfig('TEXTDESCRIPTION')) {
                            $this->_sXMLout .= str_replace("{#}", $this->convertHtmlToString($sLongDescription), $this->aXMLProducts['TXT_DESC']);
                            //$this->_sXMLout .= str_replace("{#}", $this->convertHtmlToString($fields['OXSHORTDESC']), $this->aXMLProducts['TXT_SHORT_DESC']);
                            $this->_sXMLout .= str_replace("{#}", $this->convertHtmlToString($fields['OXTITLE']), $this->aXMLProducts['TXT_SHORT_DESC']);
                        }

                        $this->_sXMLout .= str_replace("{#}", $iVariantId, $this->aXMLProducts['VARID']); //ID des Vaterartikel
                        if ($sVariantChildName > '')
                            $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sVariantChildName), $this->aXMLProducts['VARCNAME']); //Variantenkindname
                        if ($sVariantName > '')
                            $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sVariantName), $this->aXMLProducts['VARNAME']); //Variantenname

                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaDesc), $this->aXMLProducts['META_DESC']); //Meta-Beschreibung
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->sMetaKey), $this->aXMLProducts['META_KEY']); //Meta-Schlï¿½ssel
                        $this->_sXMLout .= $this->aXMLProducts['DESC_ID_END'];
                    }

//Benutzerfelder
                    $this->_sXMLout .= $this->GetArticleUserFields($fields['OXID']);

                    $this->_sXMLout .= $this->aXMLProducts['DATA_END'];
                    $this->_sXMLout .= $this->aXMLProducts['INFO_END'];
                    $this->_doXMLout($this->_sXMLout);
                }
            }
            $this->_doXMLout($this->aXMLProducts['END']);
        }
    }

    public function ProductUpdate($Data) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($Data['pID']))
                $mode = "UPDATE";
            else
                $mode = "APPEND";
            $sOxId = $this->GetOxIdOverCaoId('oxarticles', $Data['pID']);
            if (!$sOxId)
                $mode = "APPEND";

            $dTax = $Data['products_tax_rate']; // MwSt-Satz
            if ($dTax == $this->dDefaultTax)
                $dTax = NULL;

            if ($this->IsNetPrice) // Netto oder Bruttopreis
                $dPrice = $Data['products_price'];
            else
                $dPrice = $this->GetGrossPrice($Data['products_price'], $dTax);

            $iUvpPriceField = Config::getCOIConfig('UVPPRICEFIELD');
            if ($iUvpPriceField) {
                if ($this->IsNetPrice)
                    $dUvpPrice = $Data['products_vk' . $iUvpPriceField];
                else
                    $dUvpPrice = $this->GetGrossPrice($Data['products_vk' . $iUvpPriceField], $dTax);
            } else
                $dUvpPrice = 0;

            $dMinPrice = $dPrice;

            $IsChangeDescription = Config::getCOIConfig('CHANGEDESCRIPTION');

            if (Config::getCOIConfig('USEBASEUNIT'))
                $sUnitName = $this->convertString($Data['products_me']);
            else
                $sUnitName = $this->convertString($Data['products_basis_me']);

// Hersteller zum Produkt
            $sManufacturOxId = $this->GetOxIdOverCaoId('oxmanufacturers', $Data['manufacturers_id']);

            $this->getOxArticleObject();

            if (($sOxId) && ($mode == 'UPDATE')) {
                $this->oxArticle->load($sOxId);
            }

            if (!Config::getCaoLanguage()) {
                $OXTITLE = $this->convertString($Data['products_name'][2]);
                $this->oxArticle->oxarticles__oxtitle = new \OxidEsales\Eshop\Core\Field($OXTITLE, \OxidEsales\Eshop\Core\Field::T_RAW);

                if ($IsChangeDescription) {
                    if ($Data['products_shop_short_description'][2] == '')
                        $OXSHORTDESC = $Data['products_short_description'][2];
                    else
                        $OXSHORTDESC = $this->convertString($Data['products_shop_short_description'][2]);
                } else
                    $OXSHORTDESC = $this->convertString($Data['products_shop_short_description'][2]);
                $this->oxArticle->oxarticles__oxshortdesc = new \OxidEsales\Eshop\Core\Field($OXSHORTDESC, \OxidEsales\Eshop\Core\Field::T_RAW);

                $OXEXTURL = $Data['products_url'][2];
                $this->oxArticle->oxarticles__oxexturl = new \OxidEsales\Eshop\Core\Field($OXEXTURL, \OxidEsales\Eshop\Core\Field::T_RAW);
                $this->oxArticle->oxarticles__oxurldesc = new \OxidEsales\Eshop\Core\Field($OXEXTURL, \OxidEsales\Eshop\Core\Field::T_RAW);

                $this->sMetaDesc = $this->convertString($Data['products_meta_description'][2]);
                $this->sMetaKey = $this->convertString($Data['products_meta_keywords'][2]);
            }

// Lagermenge
            if (isset($Data['products_quantity'])) {
                $OXSTOCK = $Data['products_quantity'];
                if (Config::getCOIConfig('OPENSTOCKINVOICE'))
                    $OXSTOCK -= $Data['products_quantity_rels'];
                if (Config::getCOIConfig('OPENSTOCKORDER'))
                    $OXSTOCK -= $Data['products_quantity_vkau'];

                $this->oxArticle->oxarticles__oxstock = new \OxidEsales\Eshop\Core\Field($OXSTOCK, \OxidEsales\Eshop\Core\Field::T_RAW);
            }

            if (isset($Data['products_stockstatus']))
                $OXSTOCKFLAG = $Data['products_stockstatus'];
            else
                $OXSTOCKFLAG = 0;
            $this->oxArticle->oxarticles__oxstockflag = new \OxidEsales\Eshop\Core\Field($OXSTOCKFLAG, \OxidEsales\Eshop\Core\Field::T_RAW);

            if (isset($Data['products_sort']))
                $OXSORT = $Data['products_sort'];
            else
                $OXSORT = 0;
            $this->oxArticle->oxarticles__oxsort = new \OxidEsales\Eshop\Core\Field($OXSORT, \OxidEsales\Eshop\Core\Field::T_RAW);

            if (Config::$OverwriteImgName) {
                $OXPIC1 = $this->convertString($Data['products_image']);
                $OXPIC2 = $this->convertString($Data['products_image_med']);
                $OXPIC3 = $this->convertString($Data['products_image_large']);

                $this->oxArticle->oxarticles__oxpic1 = new \OxidEsales\Eshop\Core\Field($OXPIC1, \OxidEsales\Eshop\Core\Field::T_RAW);
                $this->oxArticle->oxarticles__oxpic2 = new \OxidEsales\Eshop\Core\Field($OXPIC2, \OxidEsales\Eshop\Core\Field::T_RAW);
                $this->oxArticle->oxarticles__oxpic3 = new \OxidEsales\Eshop\Core\Field($OXPIC3, \OxidEsales\Eshop\Core\Field::T_RAW);
            }

            $OXARTNUM = $Data['products_model'];
            $this->oxArticle->oxarticles__oxartnum = new \OxidEsales\Eshop\Core\Field($OXARTNUM, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXMPN = $this->convertString($Data['manufacturers_model']);
            $this->oxArticle->oxarticles__oxmpn = new \OxidEsales\Eshop\Core\Field($OXMPN, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXEAN = $Data['products_ean'];
            $this->oxArticle->oxarticles__oxean = new \OxidEsales\Eshop\Core\Field($OXEAN, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXPRICE = $dPrice;
            $this->oxArticle->oxarticles__oxprice = new \OxidEsales\Eshop\Core\Field($OXPRICE, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXBPRICE = $Data['products_ek'];
            $this->oxArticle->oxarticles__oxbprice = new \OxidEsales\Eshop\Core\Field($OXBPRICE, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXTPRICE = $dUvpPrice;
            $this->oxArticle->oxarticles__oxtprice = new \OxidEsales\Eshop\Core\Field($OXTPRICE, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXVARMINPRICE = $dMinPrice;
            $this->oxArticle->oxarticles__oxvarminprice = new \OxidEsales\Eshop\Core\Field($OXVARMINPRICE, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXVAT = $dTax;
            $this->oxArticle->oxarticles__oxvat = new \OxidEsales\Eshop\Core\Field($OXVAT, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXWEIGHT = $Data['products_weight'];
            $this->oxArticle->oxarticles__oxweight = new \OxidEsales\Eshop\Core\Field($OXWEIGHT, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXACTIVE = $Data['products_status'];
            $this->oxArticle->oxarticles__oxactive = new \OxidEsales\Eshop\Core\Field($OXACTIVE, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXMANUFACTURERID = $sManufacturOxId;
            $this->oxArticle->oxarticles__oxmanufacturerid = new \OxidEsales\Eshop\Core\Field($OXMANUFACTURERID, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXUNITNAME = $sUnitName;
            $this->oxArticle->oxarticles__oxunitname = new \OxidEsales\Eshop\Core\Field($OXUNITNAME, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXUNITQUANTITY = $Data['products_basis_factor'];
            $this->oxArticle->oxarticles__oxunitquantity = new \OxidEsales\Eshop\Core\Field($OXUNITQUANTITY, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXLENGTH = $Data['products_length'];
            $this->oxArticle->oxarticles__oxlength = new \OxidEsales\Eshop\Core\Field($OXLENGTH, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXWIDTH = $Data['products_width'];
            $this->oxArticle->oxarticles__oxwidth = new \OxidEsales\Eshop\Core\Field($OXWIDTH, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXHEIGHT = $Data['products_height'];
            $this->oxArticle->oxarticles__oxheight = new \OxidEsales\Eshop\Core\Field($OXHEIGHT, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXDELIVERY = $this->GetDeliveryDate($Data);
            $this->oxArticle->oxarticles__oxdelivery = new \OxidEsales\Eshop\Core\Field($OXDELIVERY, \OxidEsales\Eshop\Core\Field::T_RAW);
            $OXSUBCLASS = 'oxarticle';
            $this->oxArticle->oxarticles__oxsubclass = new \OxidEsales\Eshop\Core\Field($OXSUBCLASS, \OxidEsales\Eshop\Core\Field::T_RAW);

            if (Config::getCOIConfig('OVERWRITEPRICEGROUP')) {
                $dPrice = $this->GetABCPrice('A', $Data, $dTax);
                $this->oxArticle->oxarticles__oxpricea = new \OxidEsales\Eshop\Core\Field($dPrice, \OxidEsales\Eshop\Core\Field::T_RAW);
                $dPrice = $this->GetABCPrice('B', $Data, $dTax);
                $this->oxArticle->oxarticles__oxpriceb = new \OxidEsales\Eshop\Core\Field($dPrice, \OxidEsales\Eshop\Core\Field::T_RAW);
                $dPrice = $this->GetABCPrice('C', $Data, $dTax);
                $this->oxArticle->oxarticles__oxpricec = new \OxidEsales\Eshop\Core\Field($dPrice, \OxidEsales\Eshop\Core\Field::T_RAW);
            }

            $response = $this->oxArticle->save();
            if (Config::$SaveErrorOff) {
                if (!$response)
                    $response = 1;
            }
            if ($response) {
                if ($mode == "APPEND") {
                    $sOxId = $this->oxArticle->getId();
                    $iCaoId = $this->UpdateCaoIdInOxTable('articles', $sOxId);
                    $this->SetCoiIdInArticle($sOxId, $iCaoId);
                    $this->oxArticle->load($sOxId);
                }

                if (Config::getCaoLanguage())
                    $this->_ProductLanguage($Data, $sOxId);
                else {
                    $OXLONGDESC = $this->convertString($Data['products_shop_long_description'][2]);
                    $this->oxArticle->setArticleLongDesc($OXLONGDESC);
                    $this->oxArticle->save();
                    $this->SetOxSeo($sOxId);
                }

                if ((isset($Data['products_var_id'])) && ($Data['products_var_id'] > '')) {
                    $this->setVariantArticle($sOxId, $Data);
                } else {
                    $this->deleteVariantArticle($sOxId);
                    $this->SetArticleToVariant($sOxId, $Data);
                }

                $this->SetArticleScalePrice($sOxId, $Data, $dTax);
                $this->SetArticleSpecialPrice($sOxId, $Data, $dTax);
                $this->SetUserFieldInArticle($sOxId, $Data);

                if (Config::GetCOIConfig('ARTICLESORTINCATEGORIE'))
                    $this->SetArticleSortInCategorie($sOxId, $OXSORT);

                if ((isset($Data['products_spares'])) && ($Data['products_spares']))
                    $this->SetArticleCrossSelling($Data['products_spares'], $sOxId);

                if ((isset($Data['products_accessory'])) && ($Data['products_accessory']))
                    $this->SetArticleAccessoire($Data['products_accessory'], $sOxId);

                $this->ResetCounts($sOxId);

                if ($mode == "APPEND")
                    $this->_XMLStatus(0, $Data['action'], 'OK', 'ARTICLE ' . $iCaoId . ' APPEND', 'PRODUCTS_ID', $iCaoId);
                else
                    $this->_XMLStatus(0, $Data['action'], 'OK', 'ARTICLE ' . $Data['pID'] . ' -' . $sArtNum . ' UPDATED', 'PRODUCTS_ID', $Data['pID']);
            } else
                $this->_XMLStatus(99, $Data['action'], 'SAVEERROR APPEND/UPDATE ARTICLE', '', '', '');
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR APPEND/UPDATE ARTICLE', '', '', '');
    }

    private function _ProductLanguage($Data, $sOxId) {
        $this->getOxArticleObject();
        $aLanguage = Config::getLanguageArray();
        foreach ($aLanguage as $i => $sVal) {
            $this->oxArticle->loadInLang($i, $sOxId);
            $OXTITLE = $this->convertString($Data['products_name'][$i]);
            $this->oxArticle->oxarticles__oxtitle = new \OxidEsales\Eshop\Core\Field($OXTITLE, \OxidEsales\Eshop\Core\Field::T_RAW);

            if ($IsChangeDescription) {
                if ($Data['products_shop_short_description'][$i] == '')
                    $OXSHORTDESC = $Data['products_short_description'][$i];
                else
                    $OXSHORTDESC = $this->convertString($Data['products_shop_short_description'][$i]);
            } else
                $OXSHORTDESC = $this->convertString($Data['products_shop_short_description'][$i]);
            $this->oxArticle->oxarticles__oxshortdesc = new \OxidEsales\Eshop\Core\Field($OXSHORTDESC, \OxidEsales\Eshop\Core\Field::T_RAW);

            $OXEXTURL = $Data['products_url'][$i];
            $this->oxArticle->oxarticles__oxexturl = new \OxidEsales\Eshop\Core\Field($OXEXTURL, \OxidEsales\Eshop\Core\Field::T_RAW);
            $this->oxArticle->oxarticles__oxurldesc = new \OxidEsales\Eshop\Core\Field($OXEXTURL, \OxidEsales\Eshop\Core\Field::T_RAW);
            if (isset($Data['products_meta_description'][$i]))
                $this->sMetaDesc = $this->convertString($Data['products_meta_description'][$i]);

            if (isset($Data['products_meta_keywords'][$i]))
                $this->sMetaKey = $this->convertString($Data['products_meta_keywords'][$i]);

            $OXLONGDESC = $this->convertString($Data['products_shop_long_description'][$i]);
            $this->oxArticle->setArticleLongDesc($OXLONGDESC);
            $this->oxArticle->save();
            $this->SetOxSeo($sOxId, $i);
        }
    }

    public function ProductErase($Data) {
        if (isset($Data['prodid']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $sOxId = $this->GetOxIdOverCaoId('oxarticles', $Data['prodid']);
            if ($sOxId) {
                $this->getOxArticleObject();
                try {
                    if ($sReturn = $this->oxArticle->delete($sOxId))
                        $this->_XMLStatus(0, $Data['action'], 'OK: ARTICLE DELETED', '', 'SQL_RES1', 1);
                    else
                        $this->_XMLStatus(99, $Data['action'], 'ERROR: ARTICLE NOT DELETED ' . $sReturn, '', 'SQL_RES1', 0);
                } catch (Exception $ex) {
                    $this->_XMLStatus(99, $Data['action'], 'ERROR: ARTICLE NOT DELETED ' . $ex, '', 'SQL_RES1', 0);
                }
            } else
                $this->_XMLStatus(0, $Data['action'], 'ERROR: NO ARTICLE IN OXID', '', 'SQL_RES1', 1);
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR: NO ARTICLE-ID', '', 'SQL_RES1', 0);
    }

    public function ProductImage($Data, $sDirectory) {
        $sMsg = $this->CoiImageUpload($sDirectory, 'products_image');
        if ($sMsg)
            $this->_XMLStatus(-1, $Data['action'], $sMsg, '', '', '');
        else
            $this->_XMLStatus(0, $Data['action'], 'OK: IMAGE UPLOAD', '', 'FILE_NAME', $this->UploadedFileName);
    }

    public function ProductToCategoryUpdate($Data) {
        if (isset($Data['catid']) && isset($Data['prodid'])) {
            $sCatOxId = $this->GetOxIdOverCaoId('oxcat2cao', $Data['catid']);
            $sArtOxId = $this->GetOxIdOverCaoId('oxarticles', $Data['prodid']);

            $sQ = "SELECT OXOBJECTID FROM oxobject2category WHERE OXOBJECTID=" . $this->oxDB->quote($sArtOxId) . " AND OXCATNID=" . $this->oxDB->quote($sCatOxId);
            if (!$this->oxDB->getOne($sQ)) {
                $sQ = "INSERT INTO oxobject2category (OXID,OXOBJECTID,OXCATNID,OXPOS) " .
                        "VALUES (" .
                        $this->oxDB->quote($this->GetOxId()) . "," .
                        $this->oxDB->quote($sArtOxId) . "," .
                        $this->oxDB->quote($sCatOxId) . "," .
                        $this->oxDB->quote($this->GetArticleSort($sArtOxId)) . ")";
                $this->oxDB->Execute($sQ);
                $this->ResetCounts($sArtOxId);
            }
            $this->_XMLStatus(0, $Data['action'], 'OK: PRODUKT TO CAT APPEND', '', 'SQL_RES', $Data['prodid']);
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR: NO IDs FOR CAT/PROD GIVEN', '', '', '');
    }

    public function ProductToCategoryErase($Data) {
        if (isset($Data['catid']) && isset($Data['prodid'])) {
            $sCatOxId = $this->GetOxIdOverCaoId('oxcat2cao', $Data['catid']);
            $sArtOxId = $this->GetOxIdOverCaoId('oxarticles', $Data['prodid']);

            $sQ = "DELETE FROM oxobject2category WHERE OXOBJECTID=" . $this->oxDB->quote($sArtOxId) . " AND OXCATNID=" . $this->oxDB->quote($sCatOxId);
            $this->oxDB->Execute($sQ);
            $this->ResetCounts($sArtOxId);
            $this->_XMLStatus(0, $Data['action'], 'OK: PRODUKT DELETET FROM CAT', '', 'SQL_RES', $Data['prodid']);
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR: NO IDs FOR CAT/PROD GIVEN', '', '', '');
    }

    public function OrderExport($Data) {
        if (isset($Data['order_status']))
            $iOrderState = $Data['order_status'];
        else
            $iOrderState = 1;

        $this->UpdateOxOrder();

        $sQ = "SELECT o.*,oc.OXTITLE,oc.OXISOALPHA2,ou.OXBIRTHDATE, ou.OXCUSTNR, ou.OXPRIVFON as ORDERFON, ou.OXID as CUSTOMERID, ou.OXADDINFO AS ORDERUSERINFO
              FROM oxorder o
              LEFT JOIN oxcountry oc ON o.OXBILLCOUNTRYID=oc.OXID
              LEFT JOIN oxuser ou ON o.OXUSERID=ou.OXID
              WHERE
              o.ORDERSTATUS IN(" . $iOrderState . ",99)
              AND o.OXSTORNO=0 AND o.OXTRANSSTATUS<>'NOT_FINISHED'
              AND o.COI_CAOID >= " . $this->oxDB->quote($Data['order_from']) .
                " ORDER BY o.OXORDERDATE";

        $sQOrder = "SELECT oa.*,ca.COI_CAOID FROM oxorderarticles oa "
                . "LEFT JOIN oxarticles ca ON oa.OXARTID=ca.OXID "
                . "WHERE oa.OXORDERID = ?";
        $aOrderProduct = array();

        $sQWrap = "SELECT OXNAME,OXPRICE FROM oxwrapping WHERE OXID = ?";
        $aOrderWrap = array();
        $aWrap = array();
        $aOrderTax = array();
        $j = 0;
        $i = 0;

        $this->XMLHeader($this->aXMLOrder['START']);

        $this->oxDB->setFetchMode(MODE_ASSOC);
        $this->aDbRes = $this->oxDB->getAll($sQ);
        if (is_array($this->aDbRes)) {
            foreach ($this->aDbRes as $fields) {
                $aPayment = $this->GetValueFromPayment($fields['OXPAYMENTID']);
                $sPaymentMethod = $this->GetPayment($fields['OXPAYMENTTYPE']);

                if (Config::getCOIConfig('USEMATCHCODE')) {
                    switch (Config::getCOIConfig('MATCHCOMPANYNAME')) {
                        case '0': $sCompanyName = '';
                            break;
                        case '1': $sCompanyName = $fields['OXBILLCOMPANY'];
                            break;
                        case '2': $sCompanyName = $fields['OXBILLFNAME'];
                            break;
                        case '3': $sCompanyName = $fields['OXBILLLNAME'];
                            break;
                    }
                    switch (Config::getCOIConfig('MATCHFIRSTNAME')) {
                        case '0': $sFirstName = '';
                            break;
                        case '1': $sFirstName = $fields['OXBILLCOMPANY'];
                            break;
                        case '2': $sFirstName = $fields['OXBILLFNAME'];
                            break;
                        case '3': $sFirstName = $fields['OXBILLLNAME'];
                            break;
                    }
                    switch (Config::getCOIConfig('MATCHLASTNAME')) {
                        case '0': $sLastName = '';
                            break;
                        case '1': $sLastName = $fields['OXBILLCOMPANY'];
                            break;
                        case '2': $sLastName = $fields['OXBILLFNAME'];
                            break;
                        case '3': $sLastName = $fields['OXBILLLNAME'];
                            break;
                    }

                    $this->_sMatchCode = $this->convertXMLString(trim($sCompanyName . ' ' . $sFirstName . ' ' . $sLastName));
                } else
                    $this->_sMatchCode = '';

                $iOrderId = $fields['COI_CAOID'];
                if ((!$iOrderId) or ( $iOrderId == 0))
                    $iOrderId = $this->UpdateCaoIdInOxTable('order', $fields['OXORDER']);

                switch ($fields['OXBILLSAL']) {
                    case 'Herr':
                    case 'Mr.':
                    case 'MR': $BillGender = 'm';
                        break;
                    case 'Frau':
                    case 'MRS':
                    case 'Mrs.': $BillGender = 'f';
                        break;
                    default: $BillGender = '';
                }

                switch ($fields['OXDELSAL']) {
                    case 'Herr':
                    case 'Mr.':
                    case 'MR': $DeliveryGender = 'm';
                        break;
                    case 'Frau':
                    case 'MRS':
                    case 'Mrs.': $DeliveryGender = 'f';
                        break;
                    default: $DeliveryGender = '';
                }

                $this->_sXMLout = $this->aXMLOrder['INFO']; // info
                $this->_sXMLout .= $this->aXMLOrder['HEADER']; // header
                if (Config::getCOIConfig('USEORDERNR'))
                    $this->_sXMLout .= str_replace("{#}", $fields['OXORDERNR'], $this->aXMLOrder['ID']);
                else
                    $this->_sXMLout .= str_replace("{#}", $iOrderId, $this->aXMLOrder['ID']);
                $this->_sXMLout .= str_replace("{#}", $fields['OXORDERNR'], $this->aXMLOrder['ONR']);
                $this->_sXMLout .= str_replace("{#}", $fields['OXCUSTNR'], $this->aXMLOrder['CID']);

                $this->_sXMLout .= str_replace("{#}", $this->_sMatchCode, $this->aXMLOrder['MATCHCODE']);

                $this->_sXMLout .= str_replace("{#}", $fields['OXORDERDATE'], $this->aXMLOrder['ORDER_DATE']);
                $this->_sXMLout .= str_replace("{#}", $fields['ORDERSTATUS'], $this->aXMLOrder['STATUS']);
                $this->_sXMLout .= str_replace("{#}", $fields['OXCURRENCY'], $this->aXMLOrder['CURRENCY']);
                $this->_sXMLout .= str_replace("{#}", $fields['OXCURRATE'], $this->aXMLOrder['CURRENCY_VALUE']);
                $this->_sXMLout .= str_replace("{#}", $fields['OXTRACKCODE'], $this->aXMLOrder['TRACKINGCODE']);

                if ($fields['ORDERUSERINFO']) {
                    switch (Config::getCOIConfig('ORDERUSERADDINFO')) {
                        case '1': $this->_sXMLout .= str_replace("{#}", $fields['ORDERUSERINFO'], $this->aXMLOrder['INFO1']);
                            break;
                        case '2': $this->_sXMLout .= str_replace("{#}", $fields['ORDERUSERINFO'], $this->aXMLOrder['INFO2']);
                            break;
                    }
                }

                $this->_sOrderBy = '';
                $sCompanyName = $sFirstName = $sLastName = '';

                switch (Config::getCOIConfig('ORDERADDINFO')) {
                    case '0': $sAddInfo = '';
                        break;
                    case '1': $sAddInfo = $fields['OXBILLADDINFO'];
                        break;
                    case '2': $sAddInfo = $fields['OXBILLCOMPANY'];
                        break;
                    case '3': $sAddInfo = $fields['OXBILLFNAME'];
                        break;
                    case '4': $sAddInfo = $fields['OXBILLLNAME'];
                        break;
                }
                switch (Config::getCOIConfig('ORDERCOMPANYNAME')) {
                    case '0': $sCompanyName = '';
                        break;
                    case '1': $sCompanyName = $fields['OXBILLADDINFO'];
                        break;
                    case '2': $sCompanyName = $fields['OXBILLCOMPANY'];
                        break;
                    case '3': $sCompanyName = $fields['OXBILLFNAME'];
                        break;
                    case '4': $sCompanyName = $fields['OXBILLLNAME'];
                        break;
                }
                switch (Config::getCOIConfig('ORDERFIRSTNAME')) {
                    case '0': $sFirstName = '';
                        break;
                    case '1': $sFirstName = $fields['OXBILLADDINFO'];
                        break;
                    case '2': $sFirstName = $fields['OXBILLCOMPANY'];
                        break;
                    case '3': $sFirstName = $fields['OXBILLFNAME'];
                        break;
                    case '4': $sFirstName = $fields['OXBILLLNAME'];
                        break;
                }
                switch (Config::getCOIConfig('ORDERLASTNAME')) {
                    case '0': $sLastName = '';
                        break;
                    case '1': $sLastName = $fields['OXBILLADDINFO'];
                        break;
                    case '2': $sLastName = $fields['OXBILLCOMPANY'];
                        break;
                    case '3': $sLastName = $fields['OXBILLFNAME'];
                        break;
                    case '4': $sLastName = $fields['OXBILLLNAME'];
                        break;
                }

                $this->_sOrderBy = $this->convertXMLString(trim($sAddInfo . ' ' . $sCompanyName . ' ' . $sFirstName . ' ' . $sLastName));
                if ($this->_sOrderBy)
                    $this->_sXMLout .= str_replace("{#}", $this->_sOrderBy, $this->aXMLOrder['ORDERNAME']);
                else
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLFNAME'] . ' ' . $fields['OXBILLLNAME']), $this->aXMLOrder['ORDERNAME']);

                $this->_sXMLout .= $this->aXMLOrder['HEADER_END']; // /header
                $this->_sXMLout .= $this->aXMLOrder['BADDRESS']; // billingadress
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLUSTID']), $this->aXMLOrder['VAT_ID']); // UStd-ID

                $sCompanyName = $sFirstName = $sLastName = '';
                switch (Config::getCOIConfig('COMPANYNAME')) {
                    case '0': $sCompanyName = $this->convertXMLString($fields['OXBILLCOMPANY']);
                        break;
                    case '1': $sCompanyName = $this->convertXMLString($fields['OXBILLFNAME']);
                        break;
                    case '2': $sCompanyName = $this->convertXMLString($fields['OXBILLLNAME']);
                        break;
                    case '3': $sCompanyName = $this->convertXMLString($fields['OXBILLFNAME']) . ' ' . $this->convertXMLString($fields['OXBILLLNAME']);
                        break;
                    case '4': $sCompanyName = '';
                        break;
                }
                switch (Config::getCOIConfig('FIRSTNAME')) {
                    case '0': $sFirstName = $this->convertXMLString($fields['OXBILLCOMPANY']);
                        break;
                    case '1': $sFirstName = $this->convertXMLString($fields['OXBILLFNAME']);
                        break;
                    case '2': $sFirstName = $this->convertXMLString($fields['OXBILLLNAME']);
                        break;
                    case '3': $sFirstName = $this->convertXMLString($fields['OXBILLFNAME']) . ' ' . $this->convertXMLString($fields['OXBILLLNAME']);
                        break;
                    case '4': $sFirstName = '';
                        break;
                }
                switch (Config::getCOIConfig('LASTNAME')) {
                    case '0': $sLastName = $this->convertXMLString($fields['OXBILLCOMPANY']);
                        break;
                    case '1': $sLastName = $this->convertXMLString($fields['OXBILLFNAME']);
                        break;
                    case '2': $sLastName = $this->convertXMLString($fields['OXBILLLNAME']);
                        break;
                    case '3': $sLastName = $this->convertXMLString($fields['OXBILLFNAME']) . ' ' . $this->convertXMLString($fields['OXBILLLNAME']);
                        break;
                    case '4': $sLastName = '';
                        break;
                }

                if ($sCompanyName == '' && $sFirstName == '' && $sLastName == '') {
                    $sCompanyName = $this->convertXMLString($fields['OXBILLCOMPANY']);
                    $sFirstName = $this->convertXMLString($fields['OXBILLFNAME']);
                    $sLastName = $this->convertXMLString($fields['OXBILLLNAME']);
                }

                $this->_sXMLout .= str_replace("{#}", $sCompanyName, $this->aXMLOrder['COMPANY']);
                $this->_sXMLout .= str_replace("{#}", $sFirstName, $this->aXMLOrder['FNAME']);
                $this->_sXMLout .= str_replace("{#}", $sLastName, $this->aXMLOrder['LNAME']);
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLFNAME']) . ' ' . $this->convertXMLString($fields['OXBILLLNAME']), $this->aXMLOrder['NAME']);

                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLSTREET']) . ' ' . $this->convertXMLString($fields['OXBILLSTREETNR']), $this->aXMLOrder['STREET']);
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLZIP']), $this->aXMLOrder['ZIP']);
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLCITY']), $this->aXMLOrder['CITY']);
                $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['SUBURB']); // suburb
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE']), $this->aXMLOrder['STATE']); // state
                $this->_sXMLout .= str_replace("{#}", $fields['OXISOALPHA2'], $this->aXMLOrder['COUNTRY']);
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLFON']), $this->aXMLOrder['FON']);
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLFAX']), $this->aXMLOrder['FAX']);
                $this->_sXMLout .= str_replace("{#}", $fields['OXBILLEMAIL'], $this->aXMLOrder['EMAIL']);
                $this->_sXMLout .= str_replace("{#}", $fields['OXBIRTHDATE'], $this->aXMLOrder['BIRTHDAY']); // birthday
                $this->_sXMLout .= str_replace("{#}", $BillGender, $this->aXMLOrder['GENDER']); // gender


                /* Abweichende MwSt */
                if (Config::getCOIConfig('USEOTHERTAX')) {
                    $sOtherTax = $this->GetOtherTax($fields['OXID']);
                }

                if (isset($sOtherTax) && $sOtherTax == 'BILLTAX') {
                    $this->_sXMLout .= str_replace("{#}", 1, $this->aXMLOrder['OTAX']); // abweichende MwSt. 
                }

                $this->_sXMLout .= $this->aXMLOrder['BADDRESS_END'];

                $this->_sXMLout .= $this->aXMLOrder['DADDRESS'];

                if (($fields['OXDELLNAME']) ||
                        ($fields['OXDELFNAME']) ||
                        ($fields['OXDELCOMPANY'])) {

                    $this->GetCountryData($fields['OXDELCOUNTRYID']);

                    switch (Config::getCOIConfig('COMPANYNAME')) {
                        case '0': $sCompanyName = $this->convertXMLString($fields['OXDELCOMPANY']);
                            break;
                        case '1': $sCompanyName = $this->convertXMLString($fields['OXDELFNAME']);
                            break;
                        case '2': $sCompanyName = $this->convertXMLString($fields['OXDELLNAME']);
                            break;
                        case '3': $sCompanyName = $this->convertXMLString($fields['OXDELFNAME']) . ' ' . $this->convertXMLString($fields['OXDELLNAME']);
                            break;
                        case '4': $sCompanyName = '';
                            break;
                    }
                    switch (Config::getCOIConfig('FIRSTNAME')) {
                        case '0': $sFirstName = $this->convertXMLString($fields['OXDELCOMPANY']);
                            break;
                        case '1': $sFirstName = $this->convertXMLString($fields['OXDELFNAME']);
                            break;
                        case '2': $sFirstName = $this->convertXMLString($fields['OXDELLNAME']);
                            break;
                        case '3': $sFirstName = $this->convertXMLString($fields['OXDELFNAME']) . ' ' . $this->convertXMLString($fields['OXDELLNAME']);
                            break;
                        case '4': $sCompanyName = '';
                            break;
                    }
                    switch (Config::getCOIConfig('LASTNAME')) {
                        case '0': $sLastName = $this->convertXMLString($fields['OXDELCOMPANY']);
                            break;
                        case '1': $sLastName = $this->convertXMLString($fields['OXDELFNAME']);
                            break;
                        case '2': $sLastName = $this->convertXMLString($fields['OXDELLNAME']);
                            break;
                        case '3': $sLastName = $this->convertXMLString($fields['OXDELFNAME']) . ' ' . $this->convertXMLString($fields['OXDELLNAME']);
                            break;
                        case '4': $sLastName = '';
                            break;
                    }

                    if ($sCompanyName == '' && $sCompanyName == '' && $sLastName == '') {
                        $sCompanyName = $this->convertXMLString($fields['OXDELCOMPANY']);
                        $sFirstName = $this->convertXMLString($fields['OXDELFNAME']);
                        $sLastName = $this->convertXMLString($fields['OXDELLNAME']);
                    }


                    $this->_sXMLout .= str_replace("{#}", $sCompanyName, $this->aXMLOrder['DCOMPANY']);
                    $this->_sXMLout .= str_replace("{#}", $sFirstName, $this->aXMLOrder['DFNAME']);
                    $this->_sXMLout .= str_replace("{#}", $sLastName, $this->aXMLOrder['DLNAME']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDELFNAME']) . ' ' . $this->convertXMLString($fields['OXDELLNAME']), $this->aXMLOrder['DNAME']);

                    $this->_sXMLout .= str_replace("{#}", $DeliveryGender, $this->aXMLOrder['DGENDER']); // gender
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDELSTREET']) . ' ' . $this->convertXMLString($fields['OXDELSTREETNR']), $this->aXMLOrder['DSTREET']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDELZIP']), $this->aXMLOrder['DZIP']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDELCITY']), $this->aXMLOrder['DCITY']);
                    $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['DSUBURB']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->CountryName), $this->aXMLOrder['DSTATE']);
                    $this->_sXMLout .= str_replace("{#}", $this->CountryIso2, $this->aXMLOrder['DCOUNTRY']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXDELFON']), $this->aXMLOrder['DFON']);
                } else {
                    $this->_sXMLout .= str_replace("{#}", $sCompanyName, $this->aXMLOrder['DCOMPANY']);
                    $this->_sXMLout .= str_replace("{#}", $sFirstName, $this->aXMLOrder['DFNAME']);
                    $this->_sXMLout .= str_replace("{#}", $sLastName, $this->aXMLOrder['DLNAME']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLFNAME']) . ' ' . $this->convertXMLString($fields['OXBILLNAME']), $this->aXMLOrder['DNAME']);

                    $this->_sXMLout .= str_replace("{#}", $BillGender, $this->aXMLOrder['GENDER']); // gender
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLSTREET']) . ' ' . $this->convertXMLString($fields['OXBILLSTREETNR']), $this->aXMLOrder['DSTREET']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLZIP']), $this->aXMLOrder['DZIP']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXBILLCITY']), $this->aXMLOrder['DCITY']);
                    $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['DSUBURB']);
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($fields['OXTITLE']), $this->aXMLOrder['DSTATE']);
                    $this->_sXMLout .= str_replace("{#}", $fields['OXISOALPHA2'], $this->aXMLOrder['DCOUNTRY']);
                }

                /* Abweichende MwSt Lieferadresse */
                if (isset($sOtherTax) && $sOtherTax == 'DELTAX') {
                    $this->_sXMLout .= str_replace("{#}", 1, $this->aXMLOrder['DOTAX']); // abweichende MwSt. 
                }


                $this->_sXMLout .= $this->aXMLOrder['DADDRESS_END'];

                $this->_sXMLout .= $this->aXMLOrder['PAY']; // payment
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sPaymentMethod), $this->aXMLOrder['PAY_METHOD']); // methode
                $this->_sXMLout .= str_replace("{#}", $fields['OXPAYMENTTYPE'], $this->aXMLOrder['PAY_CLASS']); // class
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($aPayment[0]), $this->aXMLOrder['PAY_BNAME']); // bankname
// SEPA
                if (preg_match('#^[a-z]+$#i', substr($aPayment[2], 0, 2))) {
                    $this->_sXMLout .= str_replace("{#}", $aPayment[1], $this->aXMLOrder['PAY_BIC']); // BIC/Swift
                    $this->_sXMLout .= str_replace("{#}", $aPayment[2], $this->aXMLOrder['PAY_IBAN']); // IBan
                } else {
                    $this->_sXMLout .= str_replace("{#}", $aPayment[1], $this->aXMLOrder['PAY_BLZ']); // blz
                    $this->_sXMLout .= str_replace("{#}", $aPayment[2], $this->aXMLOrder['PAY_NUMBER']); // ktonumber
                }

                $this->_sXMLout .= str_replace("{#}", $aPayment[3], $this->aXMLOrder['PAY_OWNER']); // owner
                $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['PAY_STATUS']); // status
                $this->_sXMLout .= $this->aXMLOrder['PAY_END'];

                $sDelivery = $this->GetDelivery($fields['OXDELTYPE']);
                $this->_sXMLout .= $this->aXMLOrder['SHIP']; // shipping
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sDelivery), $this->aXMLOrder['SHIP_METHOD']); // method
                $this->_sXMLout .= str_replace("{#}", $fields['OXDELTYPE'], $this->aXMLOrder['SHIP_CLASS']); // class
                $this->_sXMLout .= $this->aXMLOrder['SHIP_END'];

                $this->_doXMLout($this->_sXMLout);

                $this->_sXMLout = $this->aXMLOrder['ORDER']; // orderproducts

                $this->oxDB->setFetchMode(MODE_ASSOC);
                $aOrderProducts = $this->oxDB->getAll($sQOrder, array($fields['OXID']));
                if (is_array($aOrderProducts) && count($aOrderProducts[0])) {
                    foreach ($aOrderProducts as $Orderfields) {

                        if (!in_array($Orderfields['OXVAT'], $aOrderTax))
                            $aOrderTax[$i++] = $Orderfields['OXVAT'];

                        if ($Orderfields['OXWRAPID']) {
                            $this->oxDB->setFetchMode(MODE_ASSOC);
                            $aOrderWrap = $this->oxDB->getAll($sQWrap, array($Orderfields['OXWRAPID']));
                            if (is_array($aOrderWrap) && count($aOrderWrap[0])) {
                                foreach ($aOrderWrap as $Wrapfields) {
                                    $aWrap[$j] = 'Geschenkpapier ' . $Wrapfields['OXNAME'];
                                    $aWrap[$j + 1] = $Wrapfields['OXPRICE'] * $Orderfields['OXAMOUNT'];
                                    $j += 2;
                                }
                            }
                        }

                        $sDescription = $Orderfields['OXTITLE'];

                        if ((Config::getCOIConfig('SELECTVARIANT')) && (Config::getCOIConfig('SELECTPRICE'))) { // PrÃ¼fen ob Auswahl mit Preis vorhanden
                            if ($Orderfields['OXSELVARIANT'] > '') {
                                if ($this->IsNetPrice)
                                    $dProductPrice = $Orderfields['OXPRICE'];
                                else
                                    $dProductPrice = ( $Orderfields['OXPRICE'] / (100 + $Orderfields['OXVAT']) ) * 100;
                            } else {
                                if (Config::getCOIConfig('USEORDERGROSS'))
                                    $dProductPrice = ( $Orderfields['OXBPRICE'] / (100 + $Orderfields['OXVAT']) ) * 100;
                                else
                                    $dProductPrice = $Orderfields['OXNPRICE'];
                            }
                        } else {
                            if (Config::getCOIConfig('USEORDERGROSS'))
                                $dProductPrice = ( $Orderfields['OXBPRICE'] / (100 + $Orderfields['OXVAT']) ) * 100;
                            else
                                $dProductPrice = $Orderfields['OXNPRICE'];
                        }

                        if (Config::getCOIConfig('PERSPARAM')) {
                            if ($Orderfields['OXPERSPARAM']) {
                                $sPersParam = '';
                                $aPersParam = unserialize($Orderfields['OXPERSPARAM']);
                                foreach ($aPersParam as $sKey => $sValue) {
                                    $sPersParam .= "\r" . $sKey . ': ' . $sValue;
                                }
                                $sDescription .= $sPersParam;
                            }
                        }

                        // Auswahlliste als Varianten - Text übernehmen
                        if ((Config::getCOIConfig('SELECTVARIANTTEXT')) &&
                                (!Config::getCOIConfig('SELECTVARIANT'))) {
                            $sDescription .= "\n" . $Orderfields['OXSELVARIANT'];
                        }

                        $this->_sXMLout .= $this->aXMLOrder['PRODUCT']; // products
                        $this->_sXMLout .= str_replace("{#}", $Orderfields['COI_CAOID'], $this->aXMLOrder['PID']); // id
                        $this->_sXMLout .= str_replace("{#}", $Orderfields['OXAMOUNT'], $this->aXMLOrder['QUANTITY']); // quantity
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($Orderfields['OXARTNUM']), $this->aXMLOrder['MODEL']); // artnumber
                        $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sDescription), $this->aXMLOrder['PNAME']); // artname
                        $this->_sXMLout .= str_replace("{#}", $dProductPrice, $this->aXMLOrder['PRICE']); // price
                        $this->_sXMLout .= str_replace("{#}", $Orderfields['OXVAT'], $this->aXMLOrder['PTAX']); // tax
                        $this->_sXMLout .= $this->aXMLOrder['PRODUCT_END'];

                        if (Config::getCOIConfig('SELECTVARIANT')) {
                            if ($Orderfields['OXSELVARIANT'] > '') { // Auswahlliste als freier Artikel Ãœbertragen
                                if (Config::getCOIConfig('SELECTPRICE')) {
                                    if ($this->IsNetPrice)
                                        $dSelPrice = $Orderfields['OXNPRICE'] - $Orderfields['OXPRICE'];
                                    else
                                        $dSelPrice = $Orderfields['OXNPRICE'] - (( $Orderfields['OXPRICE'] / (100 + $Orderfields['OXVAT']) ) * 100);
                                } else
                                    $dSelPrice = 0.00;
                                $sArtTitle .= $this->getOrderText('COI_ORDERSELVARIANT') . $Orderfields['OXARTNUM'] . "\n" . $Orderfields['OXSELVARIANT'];
                                $this->_sXMLout .= $this->aXMLOrder['PRODUCT']; // products
                                $this->_sXMLout .= str_replace("{#}", '1', $this->aXMLOrder['QUANTITY']); // quantity
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sArtTitle), $this->aXMLOrder['PNAME']); // artname
                                $this->_sXMLout .= str_replace("{#}", $dSelPrice, $this->aXMLOrder['PRICE']); // price
                                $this->_sXMLout .= str_replace("{#}", $Orderfields['OXVAT'], $this->aXMLOrder['PTAX']); // tax
                                $this->_sXMLout .= $this->aXMLOrder['PRODUCT_END'];
                            }
                        }

// Persparam
                        if (Config::getCOIConfig('FREEPERSPARAM')) {
                            if ($Orderfields['OXPERSPARAM'] > '') { // Persparam als freier Artikel übertragen
                                $dSelPrice = 0.00;
                                $aPersParam = unserialize($Orderfields['OXPERSPARAM']);
                                $sArtTitle = $sDiscountTxt = $this->getOrderText('COI_PERSPARAM') . "\r";

                                foreach ($aPersParam as $sKey => $sValue) {
                                    $sArtTitle .= $sKey . ': ' . $sValue . "\r";
                                }

                                $this->_sXMLout .= $this->aXMLOrder['PRODUCT']; // products
                                $this->_sXMLout .= str_replace("{#}", '1', $this->aXMLOrder['QUANTITY']); // quantity
                                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sArtTitle), $this->aXMLOrder['PNAME']); // artname
                                $this->_sXMLout .= str_replace("{#}", $dSelPrice, $this->aXMLOrder['PRICE']); // price
                                $this->_sXMLout .= str_replace("{#}", $Orderfields['OXVAT'], $this->aXMLOrder['PTAX']); // tax
                                $this->_sXMLout .= $this->aXMLOrder['PRODUCT_END'];
                            }
                        }
                    }
                }
                $this->_sXMLout .= $this->aXMLOrder['ORDER_END'];
                $this->_doXMLout($this->_sXMLout);

// Totals
                $this->_sXMLout = $this->aXMLOrder['TORDER']; // ordertotal

                $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->getOrderText('COI_ORDERSUBTOTAL')) . ':', $this->aXMLOrder['TTITLE']); // title
                $this->_sXMLout .= str_replace("{#}", $fields['OXTOTALBRUTSUM'], $this->aXMLOrder['TVALUE']); // value
                $this->_sXMLout .= str_replace("{#}", 'ot_subtotal', $this->aXMLOrder['TCLASS']); // class
                $this->_sXMLout .= str_replace("{#}", 1, $this->aXMLOrder['TSORT']); // sortorder
                $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['TPREFIX']); // prefix
                $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['TTAX']); // tax
                $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];

                if (($fields['OXDELTYPE'] > '') && $fields['OXDELCOST']) {
                    $dDeliveryTax = $fields['OXDELVAT'];
                    if (!$dDeliveryTax) {
                        if ($this->getIsTax($aOrderTax))
                            $dDeliveryTax = $this->dDefaultTax;
                    }

                    $fShipCost = $fields['OXDELCOST'];
                    if ($this->IsDeliveryTaxOnTop)
                        $fShipCost = $fields['OXDELCOST'] / (100 + $dDeliveryTax) * 100;

                    $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->getOrderText('COI_ORDERSHIPPINGCOST')) . ' ' . $_sShippingMethod, $this->aXMLOrder['TTITLE']); // title
                    $this->_sXMLout .= str_replace("{#}", $fShipCost, $this->aXMLOrder['TVALUE']); // value
                    $this->_sXMLout .= str_replace("{#}", 'ot_shipping', $this->aXMLOrder['TCLASS']); // class
                    $this->_sXMLout .= str_replace("{#}", 2, $this->aXMLOrder['TSORT']); // sortorder
                    $this->_sXMLout .= str_replace("{#}", '+', $this->aXMLOrder['TPREFIX']); // prefix
                    $this->_sXMLout .= str_replace("{#}", $dDeliveryTax, $this->aXMLOrder['TTAX']); // tax
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];
                }

                if ($fields['OXDISCOUNT']) {
                    $dTax = 0;
                    if ($this->getIsTax($aOrderTax))
                        $dTax = $this->dDefaultTax;
                    $sPrefix = '-';
                    $sDiscountTxt = $this->getOrderText('COI_DISCOUNTMINUS');
                    $fDiscount = $fields['OXDISCOUNT'];
                    if ($this->IsNetPrice)
                        $fDiscount = $fields['OXDISCOUNT'] / (100 + $dTax) * 100;
                    if ($this->IsShowNettoPrice)
                        $fDiscount = $fields['OXDISCOUNT'] * ((100 + $dTax) / 100);
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                    if ($fDiscount < 0.00) {
                        $sPrefix = '+';
                        $sDiscountTxt = $this->getOrderText('COI_DISCOUNTPLUS');
                        $fDiscount = $fDiscount * -1;
                    }
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($sDiscountTxt), $this->aXMLOrder['TTITLE']);
                    $this->_sXMLout .= str_replace("{#}", $fDiscount, $this->aXMLOrder['TVALUE']); // value
                    $this->_sXMLout .= str_replace("{#}", 'ot_customer_discount', $this->aXMLOrder['TCLASS']); // class
                    $this->_sXMLout .= str_replace("{#}", 2, $this->aXMLOrder['TSORT']); // sortorder
                    $this->_sXMLout .= str_replace("{#}", $sPrefix, $this->aXMLOrder['TPREFIX']); // prefix
                    $this->_sXMLout .= str_replace("{#}", $dTax, $this->aXMLOrder['TTAX']); // tax
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];
                }

                if ($fields['OXPAYCOST']) {
                    $sPaymentTxt = '';
                    $dTax = $fields['OXPAYVAT'];
                    if (!$dTax) {
                        if ($this->getIsTax($aOrderTax))
                            $dTax = $this->dDefaultTax;
                    }
                    $fPayCost = $fields['OXPAYCOST'];
                    if ($this->IsPymentTaxOnTop)
                        $fPayCost = $fields['OXPAYCOST'] / (100 + $dTax) * 100;
                    if ($fPayCost > 0)
                        $sPaymentTxt = $this->convertXMLString($this->getOrderText('COI_ORDERPAYCOST')) . ' ';
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                    $this->_sXMLout .= str_replace("{#}", $sPaymentTxt . $sPaymentMethod, $this->aXMLOrder['TTITLE']); // title
                    $this->_sXMLout .= str_replace("{#}", $fPayCost, $this->aXMLOrder['TVALUE']); // value
                    $this->_sXMLout .= str_replace("{#}", 'ot_cod_fee', $this->aXMLOrder['TCLASS']); // class
                    $this->_sXMLout .= str_replace("{#}", 3, $this->aXMLOrder['TSORT']); // sortorder
                    $this->_sXMLout .= str_replace("{#}", '+', $this->aXMLOrder['TPREFIX']); // prefix
                    $this->_sXMLout .= str_replace("{#}", $dTax, $this->aXMLOrder['TTAX']); // tax
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];
                }

                if ($fields['OXWRAPCOST'] && $fields['OXCARDID']) {
                    $dTax = $fields['OXGIFTCARDVAT'];
                    if (!$dTax) {
                        if (!$this->IsTaxForWrapping) {
                            if ($this->getIsTax($aOrderTax))
                                $dTax = $this->dDefaultTax;
                        }
                    }

                    $this->oxDB->setFetchMode(MODE_ASSOC);
                    $aCardData = $this->oxDB->getAll($sQWrap, array($fields['OXCARDID']));
                    if (is_array($aCardData) && count($aCardData[0])) {
                        foreach ($aCardData as $Cardfields) {
                            $sCardTxt = $this->convertXMLString($this->getOrderText('COI_ORDERGIFTCARD')) . ' ' . $Cardfields['OXNAME'];
                            $fCardCost = $Cardfields['OXPRICE'];
                        }
                    }
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                    $this->_sXMLout .= str_replace("{#}", $sCardTxt, $this->aXMLOrder['TTITLE']); // title
                    $this->_sXMLout .= str_replace("{#}", $fCardCost, $this->aXMLOrder['TVALUE']); // value
                    $this->_sXMLout .= str_replace("{#}", 'ot_cod_fee', $this->aXMLOrder['TCLASS']); // class
                    $this->_sXMLout .= str_replace("{#}", 3, $this->aXMLOrder['TSORT']); // sortorder
                    $this->_sXMLout .= str_replace("{#}", '+', $this->aXMLOrder['TPREFIX']); // prefix
                    $this->_sXMLout .= str_replace("{#}", $dTax, $this->aXMLOrder['TTAX']); // tax
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];
                }

                if ($iX = count($aWrap)) {
                    //$dTax = $fields['OXWRAPVAT'];
                    $dTax = $this->dDefaultTax; // VorlÃ¤ufig, da OXWRAPVAT falsche Werte enthÃ¤lt
                    for ($i = 0; $i < $iX; $i += 2) {
                        $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                        $this->_sXMLout .= str_replace("{#}", $aWrap[$i], $this->aXMLOrder['TTITLE']); // title
                        $this->_sXMLout .= str_replace("{#}", $aWrap[$i + 1], $this->aXMLOrder['TVALUE']); // value
                        $this->_sXMLout .= str_replace("{#}", 'ot_cod_fee', $this->aXMLOrder['TCLASS']); // class
                        $this->_sXMLout .= str_replace("{#}", 3, $this->aXMLOrder['TSORT']); // sortorder
                        $this->_sXMLout .= str_replace("{#}", '+', $this->aXMLOrder['TPREFIX']); // prefix
                        $this->_sXMLout .= str_replace("{#}", $dTax, $this->aXMLOrder['TTAX']); // tax
                        $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];
                    }
                }

                if ($fields['OXVOUCHERDISCOUNT']) {
                    $dTax = $this->dDefaultTax;
                    if ($this->IsNetPrice) {
                        if ($this->IsShowNettoPrice)
                            $fVoucherCost = $fields['OXVOUCHERDISCOUNT'];
                        else
                            $fVoucherCost = $fields['OXVOUCHERDISCOUNT'] / (100 + $dTax) * 100;
                    } else {
                        if ($this->IsShowNettoPrice)
                            $fVoucherCost = $fields['OXVOUCHERDISCOUNT'] * ((100 + $dTax) / 100);
                        else
                            $fVoucherCost = $fields['OXVOUCHERDISCOUNT'];
                    }
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->getOrderText('COI_ORDERDISCOUNT')), $this->aXMLOrder['TTITLE']); // title
                    $this->_sXMLout .= str_replace("{#}", $fVoucherCost, $this->aXMLOrder['TVALUE']); // value
                    $this->_sXMLout .= str_replace("{#}", 'ot_gv', $this->aXMLOrder['TCLASS']); // class
                    $this->_sXMLout .= str_replace("{#}", 4, $this->aXMLOrder['TSORT']); // sortorder
                    $this->_sXMLout .= str_replace("{#}", '-', $this->aXMLOrder['TPREFIX']); // prefix
                    $this->_sXMLout .= str_replace("{#}", $dTax, $this->aXMLOrder['TTAX']); // tax
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];
                }

                foreach ($aOrderTax as $key => $value) {
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                    $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->getOrderText('COI_ORDERTAX')) . ' ' . $value . '%', $this->aXMLOrder['TTITLE']); // title
                    $this->_sXMLout .= str_replace("{#}", $value, $this->aXMLOrder['TVALUE']); // value
                    $this->_sXMLout .= str_replace("{#}", 'ot_tax', $this->aXMLOrder['TCLASS']); // class
                    $this->_sXMLout .= str_replace("{#}", 5, $this->aXMLOrder['TSORT']); // sortorder
                    $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['TPREFIX']); // prefix
                    $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['TTAX']); // tax
                    $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];
                }

                $this->_sXMLout .= $this->aXMLOrder['TOTAL']; // total
                $this->_sXMLout .= str_replace("{#}", $this->convertXMLString($this->getOrderText('COI_ORDERTOTAL')) . ':', $this->aXMLOrder['TTITLE']); // title
                $this->_sXMLout .= str_replace("{#}", $fields['OXTOTALORDERSUM'], $this->aXMLOrder['TVALUE']); // value
                $this->_sXMLout .= str_replace("{#}", 'ot_total', $this->aXMLOrder['TCLASS']); // class
                $this->_sXMLout .= str_replace("{#}", 6, $this->aXMLOrder['TSORT']); // sortorder
                $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['TPREFIX']); // prefix
                $this->_sXMLout .= str_replace("{#}", '', $this->aXMLOrder['TTAX']); // tax
                $this->_sXMLout .= $this->aXMLOrder['TOTAL_END'];

                $this->_sXMLout .= $this->aXMLOrder['TORDER_END'];

                switch (Config::getCOIConfig('ORDERREMARKMESSAGE')) {
                    case '0': $sRemarkMsg = '';
                        break;
                    case '1': $sRemarkMsg = $fields['OXREMARK'];
                        break;
                    case '2': $sRemarkMsg = $fields['OXBILLADDINFO'];
                        break;
                    case '3': $sRemarkMsg = $fields['OXDELADDINFO'];
                        break;
                    case '4': $sRemarkMsg = $fields['OXBILLFON'];
                        break;
                    case '5': $sRemarkMsg = $fields['OXDELFON'];
                }
                if (Trim($sRemarkMsg))
                    $sRemarkMsg = $sRemarkMsg . "\r";

                switch (Config::getCOIConfig('ORDERREMARKBILLINFO')) {
                    case '0': $sRemarkBillInfo = '';
                        break;
                    case '1': $sRemarkBillInfo = $fields['OXREMARK'];
                        break;
                    case '2': $sRemarkBillInfo = $fields['OXBILLADDINFO'];
                        break;
                    case '3': $sRemarkBillInfo = $fields['OXDELADDINFO'];
                        break;
                    case '4': $sRemarkBillInfo = $fields['OXBILLFON'];
                        break;
                    case '5': $sRemarkBillInfo = $fields['OXDELFON'];
                }
                if (Trim($sRemarkBillInfo))
                    $sRemarkBillInfo = 'Bestellzusatz: ' . $sRemarkBillInfo . "\r";

                switch (Config::getCOIConfig('ORDERREMARKDELIVERYINFO')) {
                    case '0': $sRemarkDelInfo = '';
                        break;
                    case '1': $sRemarkDelInfo = $fields['OXREMARK'];
                        break;
                    case '2': $sRemarkDelInfo = $fields['OXBILLADDINFO'];
                        break;
                    case '3': $sRemarkDelInfo = $fields['OXDELADDINFO'];
                        break;
                    case '4': $sRemarkDelInfo = $fields['OXBILLFON'];
                        break;
                    case '5': $sRemarkDelInfo = $fields['OXDELFON'];
                }
                if (Trim($sRemarkDelInfo))
                    $sRemarkDelInfo = 'Lieferzusatz: ' . $sRemarkDelInfo . "\r";

                switch (Config::getCOIConfig('ORDERREMARKBILLFON')) {
                    case '0': $sRemarkBillFon = '';
                        break;
                    case '1': $sRemarkBillFon = $fields['OXREMARK'];
                        break;
                    case '2': $sRemarkBillFon = $fields['OXBILLADDINFO'];
                        break;
                    case '3': $sRemarkBillFon = $fields['OXDELADDINFO'];
                        break;
                    case '4': $sRemarkBillFon = $fields['OXBILLFON'];
                        break;
                    case '5': $sRemarkBillFon = $fields['OXDELFON'];
                }
                if (Trim($sRemarkBillFon))
                    $sRemarkBillFon = 'Bestelltelefon: ' . $sRemarkBillFon . "\r";

                switch (Config::getCOIConfig('ORDERREMARKDELIVERYFON')) {
                    case '0': $sRemarkDelFon = '';
                        break;
                    case '1': $sRemarkDelFon = $fields['OXREMARK'];
                        break;
                    case '2': $sRemarkDelFon = $fields['OXBILLADDINFO'];
                        break;
                    case '3': $sRemarkDelFon = $fields['OXDELADDINFO'];
                        break;
                    case '4': $sRemarkDelFon = $fields['OXBILLFON'];
                        break;
                    case '5': $sRemarkDelFon = $fields['OXDELFON'];
                }
                if (Trim($sRemarkDelFon))
                    $sRemarkDelFon = 'Liefertelefon: ' . $sRemarkDelFon . "\r";

                $sMsg = $this->convertXMLString($sRemarkMsg . $sRemarkBillInfo . $sRemarkDelInfo . $sRemarkBillFon . $sRemarkDelFon);
                $this->_sXMLout .= str_replace("{#}", $sMsg, $this->aXMLOrder['MSG']); // comments

                $this->_sXMLout .= $this->aXMLOrder['INFO_END'];

                $this->_doXMLout($this->_sXMLout);
            }
        }
        $this->_doXMLout($this->aXMLOrder['END']);
    }

    public function OrderUpdate($Data) {
        if ((isset($Data['order_id'])) && (isset($Data['status']))) {
            $sQ = "SELECT * FROM oxstatus2cao WHERE COI_CAOID = ?";

            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ, array($Data['status']));
            if (is_array($this->aDbRes)) {
                foreach ($this->aDbRes as $fields) {
                    $sOxFolderId = $fields['OXID'];
                    $iOxSendId = $fields['OXSENDID'];
                    $iOxPayId = $fields['OXPAYID'];
                    $iStornoId = $fields['STORNOID'];
                }
            }

            $IsSaveOrderState = False;
            if (Config::getCOIConfig('USEORDERNR'))
                $sOxId = $this->GetOxIdOverOrderNumber($Data['order_id']);
            else
                $sOxId = $this->GetOxIdOverCaoId('oxorder', $Data['order_id']);
            if ($sOxId) {
                $this->getOxOrderObject();
                $this->oxOrder->load($sOxId);

                $iOrderStatus = $this->GetOrderStatus($Data['order_id']);
                if ($iOrderStatus) {
                    if ($iOrderStatus != $Data['status']) {
                        $IsSaveOrderState = True;
                        if ($sOxFolderId) {
                            $sFolderDescription = $this->GetOxFolderDescription($sOxFolderId);
                            if ($sFolderDescription)
                                $this->oxOrder->oxorder__oxfolder = new \OxidEsales\Eshop\Core\Field($sFolderDescription, \OxidEsales\Eshop\Core\Field::T_RAW);
                        }

                        if ($iOxSendId) {
                            $sSendDate = date('Y-m-d H:i:s', time());
                            $this->oxOrder->oxorder__oxsenddate = new \OxidEsales\Eshop\Core\Field($sSendDate, \OxidEsales\Eshop\Core\Field::T_RAW);
                        }

                        if ($iOxPayId) {
                            $sPayDate = date('Y-m-d H:i:s', time());
                            $this->oxOrder->oxorder__oxpaid = new \OxidEsales\Eshop\Core\Field($sPayDate, \OxidEsales\Eshop\Core\Field::T_RAW);
                            if (Config::getCOIConfig('SENDASPAIDATE')) {
                                if ($this->oxOrder->oxorder__oxsenddate->value == '0000-00-00 00:00:00')
                                    $this->oxOrder->oxorder__oxsenddate = new \OxidEsales\Eshop\Core\Field($sPayDate, \OxidEsales\Eshop\Core\Field::T_RAW);
                            }
                        }
                    }
                }
                
                if ($iStornoId) {
                    $this->oxOrder->cancelOrder();
                    $this->_XMLStatus(0, $Data['action'], 'OK: ORDER ' . $Data['order_id'] . ' ORDER STORNO', '', 'ORDER_ID', $Data['order_id']);
                    exit;
                }
                

                if ((isset($Data['trackingcode'])) && (strlen($Data['trackingcode']) > 0)) {
                    $this->oxOrder->oxorder__oxtrackcode = new \OxidEsales\Eshop\Core\Field($Data['trackingcode'], \OxidEsales\Eshop\Core\Field::T_RAW);
                }

                if ((isset($Data['recordnum'])) && (strlen($Data['recordnum']) > 0)) {
                    if (Config::getCOIConfig('SETINVOICENUMBER')) {
                        $this->oxOrder->oxorder__oxbillnr = new \OxidEsales\Eshop\Core\Field($Data['recordnum'], \OxidEsales\Eshop\Core\Field::T_RAW);
                        $this->oxOrder->oxorder__oxbilldate = new \OxidEsales\Eshop\Core\Field($Data['recorddate'], \OxidEsales\Eshop\Core\Field::T_RAW);
                    }
                }

                if ($this->oxOrder->save()) {
                    if ($IsSaveOrderState) {
                        $this->UpdateOrderStatus($sOxId, $Data['status']);
                    }

                    if ((isset($Data['termin'])) && (strlen($Data['termin']) > 0)) {
                        if (Config::getCOIConfig('SETDEADLINE')) {
                            $this->SetOrderDeadLine($sOxId, $Data['termin']);
                        }
                    }
                    $this->_XMLStatus(0, $postdata['action'], 'OK: ORDER ' . $Data['order_id'] . ' CHANGE', '', 'ORDER_ID', $Data['order_id']);
                } else {
                    $this->_XMLStatus(0, $Data['action'], 'ERROR: ORDER ' . $Data['order_id'] . ' NOT CHANGE', '', 'ORDER_ID', $Data['order_id']);
                    exit;
                }

                // Mailbenachrichtigung
                if (($Data['notify'] == 'on') && ($Data['status'] > 1)) {
                    $sOxFolderId = substr($sOxFolderId, 1);
                    $oEmail = new coiEmail;
                    $Ret = $oEmail->send($sOxFolderId, $sOxId);
                }
            } else
                $this->_XMLStatus(1, $Data['action'], 'ERROR: ORDER ' . $Data['order_id'] . ' NOT FOUND', '', 'ORDER_ID', $Data['order_id']);
        } else
            $this->_XMLStatus(99, $Data['action'], 'ERROR: NO ORDER AND STATUS GIVEN', '', 'ORDER_ID', $Data['order_id']);
    }

}
