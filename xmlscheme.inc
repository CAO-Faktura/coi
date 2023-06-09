<?php

/**
 *  Modul        : xmlscheme.php
 *  Beschreibung : Script zum Datenaustausch CAO-Faktura <--> OXID eShop
 *                 Lauffähig unter OXID V 6.0.0
 * @author Thoren Strunk <edv@tstrunk.de>
 * @copyright Copyright (c) T.Strunk Software e.K.
 * Hinweis:
 * Dieses Projekt ist gemäß den Bedingungen der AGPL V3 lizenziert
 **/

if (!defined('COI_ROOT'))
    die('Diese Datei kann nicht aufgerufen werden');

$this->xmlversion = "<STATUS>,
                   <STATUS_DATA>,
                   <ACTION>{#}</ACTION>,
                   <CODE>111</CODE>,              
                   <SCRIPT_VER>{#}</SCRIPT_VER>, 
                   <SCRIPT_DATE>{#}</SCRIPT_DATE>, 
                   </STATUS_DATA>,
                   </STATUS>";

$this->aXMLCustomer = array('START' => '<CUSTOMERS>',
    'DATA' => '<CUSTOMERS_DATA>',
    'ID' => '<CUSTOMERS_ID>{#}</CUSTOMERS_ID>',
    'CID' => "<CUSTOMERS_CID>{#}</CUSTOMERS_CID>",
    'MATCHCODE' => '<MATCHCODE>{#}</MATCHCODE>',
    'GENDER' => '<GENDER>{#}</GENDER>',
    'COMPANY' => '<COMPANY>{#}</COMPANY>',
    'FIRSTNAME' => '<FIRSTNAME>{#}</FIRSTNAME>',
    'LASTNAME' => '<LASTNAME>{#}</LASTNAME>',
    'STREET' => '<STREET>{#}</STREET>',
    'POSTCODE' => '<POSTCODE>{#}</POSTCODE>',
    'CITY' => '<CITY>{#}</CITY>',
    'SUBURB' => '<SUBURB></SUBURB>',
    'STATE' => '<STATE>{#}</STATE>',
    'COUNTRY' => '<COUNTRY>{#}</COUNTRY>',
    'FON' => '<TELEPHONE>{#}</TELEPHONE>',
    'FAX' => '<FAX>{#}</FAX>',
    'MOBIL' => '<MOBIL>{#}</MOBIL>',
    'EMAIL' => '<EMAIL>{#}</EMAIL>',
    'BIRTHDAY' => '<BIRTHDAY>{#}</BIRTHDAY>',
    'PRICEGRP' => '<LEVEL_ID>{#}</LEVEL_ID>',
    'CGROUP' => '<CGROUP>{#}</CGROUP>',
    'CREATED' => '<DATE_ACCOUNT_CREATED>{#}</DATE_ACCOUNT_CREATED>',
    'VAT' => '<VAT_ID>{#}</VAT_ID>',
    'DATA_END' => '</CUSTOMERS_DATA>',
    'END' => '</CUSTOMERS>');

$this->aXMLManufact = array('START' => "<MANUFACTURERS>",
    'DATA' => "<MANUFACTURERS_DATA>",
    'ID' => "<ID>{#}</ID>",
    'NAME' => "<NAME>{#}</NAME>",
    'IMAGE' => "<IMAGE>{#}</IMAGE>",
    'CREATED' => "<DATE_ADDED>{#}</DATE_ADDED>",
    'MODIFIED' => "<LAST_MODIFIED>{#}</LAST_MODIFIED>",
    'DESC_ID' => "<MANUFACTURERS_DESCRIPTION ID='{#1}' CODE='{#2}' NAME='{#3}'>",
    'URL' => "<URL>{#}</URL>",
    'URL_CLICK' => "<URL_CLICK>{#}</URL_CLICK>",
    'LAST_CLICK' => "<DATE_LAST_CLICK>{#}</DATE_LAST_CLICK>",
    'DESCRIPTION' => "<DESCRIPTION>{#}</DESCRIPTION>",
    'DESC_ID_END' => "</MANUFACTURERS_DESCRIPTION>",
    'DATA_END' => "</MANUFACTURERS_DATA>",
    'END' => "</MANUFACTURERS>");

$this->aXMLCategories = array('START' => "<CATEGORIES>",
    'DATA' => "<CATEGORIES_DATA>",
    'ID' => "<ID>{#}</ID>",
    'PARENT_ID' => "<PARENT_ID>{#}</PARENT_ID>",
    'IMAGE_URL' => "<IMAGE_URL>{#}</IMAGE_URL>",
    'SORT_ORDER' => "<SORT_ORDER>{#}</SORT_ORDER>",
    'CREATED' => "<DATE_ADDED>{#}</DATE_ADDED>",
    'MODIFIED' => "<LAST_MODIFIED>{#}</LAST_MODIFIED>",
    'CAT_DESC' => "<CATEGORIES_DESCRIPTION ID='{#1}' CODE='{#2}' NAME='{#3}'>",
    'NAME' => "<NAME>{#}</NAME>",
    'HEAD_TITLE' => "<HEADING_TITLE>{#}</HEADING_TITLE>",
    'DESC' => "<DESCRIPTION>{#}</DESCRIPTION>",
    'META_TITLE' => "<META_TITLE>{#}</META_TITLE>",
    'META_DESC' => "<META_DESCRIPTION>{#}</META_DESCRIPTION>",
    'META_KEY' => "<META_KEYWORDS>{#}</META_KEYWORDS>",
    'CAT_DESC_END' => "</CATEGORIES_DESCRIPTION>",
    'PROD_ID' => "<PRODUCTS ID='{#}'></PRODUCTS>",
    'DATA_END' => "</CATEGORIES_DATA>",
    'END' => "</CATEGORIES>");

$this->aXMLProducts = array('START' => "<PRODUCTS>",
    'INFO' => "<PRODUCT_INFO>",
    'DATA' => "<PRODUCT_DATA>",
    'ID' => "<PRODUCT_ID>{#}</PRODUCT_ID>",
    'QUANTITY' => "<PRODUCT_QUANTITY>{#}</PRODUCT_QUANTITY>",
    'MODEL' => "<PRODUCT_MODEL>{#}</PRODUCT_MODEL>",
    'VPE' => "<PRODUCT_VPE>{#}</PRODUCT_VPE>",
    'UNIT' => "<PRODUCT_UNIT>{#}</PRODUCT_UNIT>",
    'IMAGE' => "<PRODUCT_IMAGE>{#}</PRODUCT_IMAGE>",
    'IMAGE_MED' => "<PRODUCT_IMAGE_MED>{#}</PRODUCT_IMAGE_MED>",
    'IMAGE_LARGE' => "<PRODUCT_IMAGE_LARGE>{#}</PRODUCT_IMAGE_LARGE>",
    'MATCH' => "<PRODUCT_MATCHCODE>{#}</PRODUCT_MATCHCODE>",
    'PRICE' => "<PRODUCT_PRICE>{#}</PRODUCT_PRICE>",
    'WEIGHT' => "<PRODUCT_WEIGHT>{#}</PRODUCT_WEIGHT>",
    'STATUS' => "<PRODUCT_STATUS>{#}</PRODUCT_STATUS>",
    'TAX_ID' => "<PRODUCT_TAX_CLASS_ID>{#}</PRODUCT_TAX_CLASS_ID>",
    'TAX_RATE' => "<PRODUCT_TAX_RATE>{#}</PRODUCT_TAX_RATE>",
    'MID' => "<MANUFACTURERS_ID>{#}</MANUFACTURERS_ID>",
    'EAN' => "<PRODUCT_EAN>{#}</PRODUCT_EAN>",
    'GRP' => "<PRODUCT_GROUP>{#}</PRODUCT_GROUP>",
    'EK' => "<PRODUCT_EK>{#}</PRODUCT_EK>",
    'VPE' => "<PRODUCT_VPE>{#}</PRODUCT_VPE>",
    'VK1' => "<PRODUCT_VK1>{#}</PRODUCT_VK1>",
    'VK2' => "<PRODUCT_VK2>{#}</PRODUCT_VK2>",
    'VK3' => "<PRODUCT_VK3>{#}</PRODUCT_VK3>",
    'VK4' => "<PRODUCT_VK4>{#}</PRODUCT_VK4>",
    'VK5' => "<PRODUCT_VK5>{#}</PRODUCT_VK5>",
    'SORT' => "<PRODUCT_SORT>{#}</PRODUCT_SORT>",
    'DELSTATUS' => "<PRODUCT_DELSTATUS>{#}</PRODUCT_DELSTATUS>",
    'FSK18' => "<PRODUCT_FSK18>{#}</PRODUCT_FSK18>",
    'DESC_ID' => "<PRODUCT_DESCRIPTION ID='{#1}' CODE='{#2}' NAME='{#3}'>",
    'NAME' => "<NAME>{#}</NAME>",
    'URL' => "<URL>{#}</URL>",
    'DESC' => "<DESCRIPTION>{#}</DESCRIPTION>",
    'SHORT_DESC' => "<SHORT_DESCRIPTION>{#}</SHORT_DESCRIPTION>",
    'TXT_DESC' => "<TXT_LONG_DESCRIPTION>{#}</TXT_LONG_DESCRIPTION>",
    'TXT_SHORT_DESC' => "<TXT_SHORT_DESCRIPTION>{#}</TXT_SHORT_DESCRIPTION>",
    'META_TITLE' => "<META_TITLE>{#}</META_TITLE>",
    'VARID' => "<PRODUCT_VARID>{#}</PRODUCT_VARID>",
    'VARCNAME' => "<PRODUCT_VARCHILDNAME>{#}</PRODUCT_VARCHILDNAME>",
    'VARNAME' => "<PRODUCT_VARNAME>{#}</PRODUCT_VARNAME>",
    'META_DESC' => "<META_DESCRIPTION>{#}</META_DESCRIPTION>",
    'META_KEY' => "<META_KEYWORDS>{#}</META_KEYWORDS>",
    'DESC_ID_END' => "</PRODUCT_DESCRIPTION>",
    'CREATED' => "<PRODUCT_DATE_ADDED>{#}</PRODUCT_DATE_ADDED>",
    'MODIFIED' => "<PRODUCT_LAST_MODIFIED>{#}</PRODUCT_LAST_MODIFIED>",
    'AVAILABLE' => "<PRODUCT_DATE_AVAILABLE>{#}</PRODUCT_DATE_AVAILABLE>",
    'ORDERED' => "<PRODUCTS_ORDERED>{#}</PRODUCTS_ORDERED>",
    'SPECIAL' => "<SPECIAL>",
    'SPECIAL_PRICE' => "<SPECIAL_PRICE>{#}</SPECIAL_PRICE>",
    'SPECIAL_CREATE' => "<SPECIAL_DATE_ADDED>{#}</SPECIAL_DATE_ADDED>",
    'SPECIAL_MODIFIED' => "<SPECIAL_LAST_MODIFIED>{#}</SPECIAL_LAST_MODIFIED>",
    'SPECIAL_EXPIRE' => "<SPECIAL_DATE_EXPIRES>{#}</SPECIAL_DATE_EXPIRES>",
    'SPECIAL_STATUS' => "<SPECIAL_STATUS>{#}</SPECIAL_STATUS>",
    'SPECIAL_CHANGE' => "<SPECIAL_DATE_STATUS_CHANGE>{#}</SPECIAL_DATE_STATUS_CHANGE>",
    'SPECIAL_END' => "</SPECIAL>",
    'DATA_END' => "</PRODUCT_DATA>",
    'INFO_END' => "</PRODUCT_INFO>",
    'END' => "</PRODUCTS>");

$this->aXMLOrder = array('START' => "<ORDER>",
    'INFO' => "<ORDER_INFO>",
    'HEADER' => "<ORDER_HEADER>",
    'ID' => "<ORDER_ID>{#}</ORDER_ID>",
    'ONR' => "<ORDER_NR>{#}</ORDER_NR>",
    'CID' => "<CUSTOMER_ID>{#}</CUSTOMER_ID>",
    'CCID' => "<CUSTOMER_CID>{#}</CUSTOMER_CID>",
    'CGROUP' => "<CUSTOMER_GROUP>{#}</CUSTOMER_GROUP>",
    'VID' => "<ORDER_PARTNER_ID>{#}</ORDER_PARTNER_ID>",
    'ORDERNAME' => "<ORDER_FROM>{#}</ORDER_FROM>",
    'PROJECT' => "<ORDER_PROJECT>{#}</ORDER_PROJECT>",
    'INFO1' => "<ORDER_HEADERINFO_1>{#}</ORDER_HEADERINFO_1>",
    'INFO2' => "<ORDER_HEADERINFO_2>{#}</ORDER_HEADERINFO_2>",
    'PRICEGRP' => "<LEVEL_ID>{#}</LEVEL_ID>",
    'MATCHCODE' => "<CUSTOMER_MATCHCODE>{#}</CUSTOMER_MATCHCODE>",
    'ORDER_DATE' => "<ORDER_DATE>{#}</ORDER_DATE>",
    'STATUS' => "<ORDER_STATUS>{#}</ORDER_STATUS>",
    'IP' => "<ORDER_IP>{#}</ORDER_IP>",
    'CURRENCY' => "<ORDER_CURRENCY>{#}</ORDER_CURRENCY>",
    'CURRENCY_VALUE' => "<ORDER_CURRENCY_VALUE>{#}</ORDER_CURRENCY_VALUE>",
    'TRACKINGCODE' => "<ORDER_TRACKINGCODE>{#}</ORDER_TRACKINGCODE>",
    'HEADER_END' => "</ORDER_HEADER>",
    'BADDRESS' => "<BILLING_ADDRESS>",
    'VAT_ID' => "<VAT_ID>{#}</VAT_ID>",
    'COMPANY' => "<COMPANY>{#}</COMPANY>",
    'FNAME' => "<FIRSTNAME>{#}</FIRSTNAME>",
    'LNAME' => "<LASTNAME>{#}</LASTNAME>",
    'NAME' => "<NAME>{#}</NAME>",
    'STREET' => "<STREET>{#}</STREET>",
    'ZIP' => "<POSTCODE>{#}</POSTCODE>",
    'CITY' => "<CITY>{#}</CITY>",
    'SUBURB' => "<SUBURB>{#}</SUBURB>",
    'STATE' => "<STATE>{#}</STATE>",
    'COUNTRY' => "<COUNTRY>{#}</COUNTRY>",
    'FON' => "<TELEPHONE>{#}</TELEPHONE>",
    'BFON' => "<TELEPHONE_1>{#}</TELEPHONE_1>",
    'FAX' => '<FAX>{#}</FAX>',
    'EMAIL' => "<EMAIL>{#}</EMAIL>",
    'BIRTHDAY' => "<BIRTHDAY>{#}</BIRTHDAY>",
    'GENDER' => "<GENDER>{#}</GENDER>",
    'OTAX' => "<OTHER_TAX>{#}</OTHER_TAX>",
    'BADDRESS_END' => "</BILLING_ADDRESS>",
    'DADDRESS' => "<DELIVERY_ADDRESS>",
    'DCOMPANY' => "<COMPANY>{#}</COMPANY>",
    'DGENDER' => "<GENDER>{#}</GENDER>",
    'DFNAME' => "<FIRSTNAME>{#}</FIRSTNAME>",
    'DLNAME' => "<LASTNAME>{#}</LASTNAME>",
    'DNAME' => "<NAME>{#}</NAME>",
    'DSTREET' => "<STREET>{#}</STREET>",
    'DZIP' => "<POSTCODE>{#}</POSTCODE>",
    'DCITY' => "<CITY>{#}</CITY>",
    'DSUBURB' => "<SUBURB>{#}</SUBURB>",
    'DSTATE' => "<STATE>{#}</STATE>",
    'DCOUNTRY' => "<COUNTRY>{#}</COUNTRY>",
    'DGENDER' => "<GENDER>{#}</GENDER>",
    'DFON' => "<TELEPHONE>{#}</TELEPHONE>",
    'DFAX' => "<FAX>{#}</FAX>",
    'DEMAIL' => "<EMAIL>{#}</EMAIL>",
    'DOTAX' => "<OTHER_TAX>{#}</OTHER_TAX>",
    'DADDRESS_END' => "</DELIVERY_ADDRESS>",
    'PAY' => "<PAYMENT>",
    'PAY_METHOD' => "<PAYMENT_METHOD>{#}</PAYMENT_METHOD>",
    'PAY_CLASS' => "<PAYMENT_CLASS>{#}</PAYMENT_CLASS>",
    'PAY_BNAME' => "<PAYMENT_BANKTRANS_BNAME>{#}</PAYMENT_BANKTRANS_BNAME>",
    'PAY_BLZ' => "<PAYMENT_BANKTRANS_BLZ>{#}</PAYMENT_BANKTRANS_BLZ>",
    'PAY_NUMBER' => "<PAYMENT_BANKTRANS_NUMBER>{#}</PAYMENT_BANKTRANS_NUMBER>",
    'PAY_BIC' => "<PAYMENT_BANKTRANS_BIC>{#}</PAYMENT_BANKTRANS_BIC>",
    'PAY_IBAN' => "<PAYMENT_BANKTRANS_IBAN>{#}</PAYMENT_BANKTRANS_IBAN>",
    'PAY_OWNER' => "<PAYMENT_BANKTRANS_OWNER>{#}</PAYMENT_BANKTRANS_OWNER>",
    'PAY_STATUS' => "<PAYMENT_BANKTRANS_STATUS>{#}</PAYMENT_BANKTRANS_STATUS>",
    'PAY_END' => "</PAYMENT>",
    'SHIP' => "<SHIPPING>",
    'SHIP_METHOD' => "<SHIPPING_METHOD>{#}</SHIPPING_METHOD>",
    'SHIP_CLASS' => "<SHIPPING_CLASS>{#}</SHIPPING_CLASS>",
    'SHIP_END' => "</SHIPPING>",
    'ORDER' => "<ORDER_PRODUCTS>",
    'PRODUCT' => "<PRODUCT>",
    'PID' => "<PRODUCTS_ID>{#}</PRODUCTS_ID>",
    'QUANTITY' => "<PRODUCTS_QUANTITY>{#}</PRODUCTS_QUANTITY>",
    'MODEL' => "<PRODUCTS_MODEL>{#}</PRODUCTS_MODEL>",
    'PNAME' => "<PRODUCTS_NAME>{#}</PRODUCTS_NAME>",
    'PRICE' => "<PRODUCTS_PRICE>{#}</PRODUCTS_PRICE>",
    'PGROUP' => "<PRODUCTS_GROUP>{#}</ PRODUCTS_GROUP>",
    'PWEIGHT' => "<PRODUCTS_WEIGHT>{#}</ PRODUCTS_WEIGHT>",
    'PTAX' => "<PRODUCTS_TAX>{#}</PRODUCTS_TAX>",
    'PTAXFLAG' => "<PRODUCTS_TAX_FLAG>{#}</PRODUCTS_TAX_FLAG>",
    'OPTION' => "<OPTION>",
    'POPTION' => "<PRODUCTS_OPTIONS>{#}</PRODUCTS_OPTIONS>",
    'POPTION_VALUE' => "<PRODUCTS_OPTIONS_VALUES>{#}</PRODUCTS_OPTIONS_VALUES>",
    'POPTION_PRICE' => "<PRODUCTS_OPTIONS_PRICE>{#}</PRODUCTS_OPTIONS_PRICE>",
    'OPTION_END' => "</OPTION>",
    'PRODUCT_END' => "</PRODUCT>",
    'ORDER_END' => "</ORDER_PRODUCTS>",
    'TORDER' => "<ORDER_TOTAL>",
    'TOTAL' => "<TOTAL>",
    'TTITLE' => "<TOTAL_TITLE>{#}</TOTAL_TITLE>",
    'TVALUE' => "<TOTAL_VALUE>{#}</TOTAL_VALUE>",
    'TCLASS' => "<TOTAL_CLASS>{#}</TOTAL_CLASS>",
    'TSORT' => "<TOTAL_SORT_ORDER>{#}</TOTAL_SORT_ORDER>",
    'TPREFIX' => "<TOTAL_PREFIX>{#}</TOTAL_PREFIX>",
    'TTAX' => "<TOTAL_TAX>{#}</TOTAL_TAX>",
    'TOTAL_END' => "</TOTAL>",
    'TORDER_END' => "</ORDER_TOTAL>",
    'MSG' => "<ORDER_COMMENTS>{#}</ORDER_COMMENTS>",
    'INFO_END' => "</ORDER_INFO>",
    'END' => "</ORDER>");


