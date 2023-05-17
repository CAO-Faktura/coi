<?php

/**
 *  Modul        : coi6.php
 *  Beschreibung : Script zum Datenaustausch CAO-Faktura <--> OXID eShop
 *                 Lauffähig unter OXID V 6.0.0
 * @author Thoren Strunk <edv@tstrunk.de>
 * @copyright Copyright (c) T.Strunk Software e.K.
 * Hinweis:
 * Dieses Projekt ist gemäß den Bedingungen der AGPL V3 lizenziert
 **/

if ((int) (str_replace(".", "", phpversion())) < 560) {
    die("Fehler: Die PHP-Version muss mindestens 5.6.0 sein");
}

define('COI_ROOT', 'coi6.php');

/**
 * Benötigte Bibliotheken laden
 */
include dirname(__FILE__) . '/coiFunc.php';

$oXML = new xmlFunc;
$oHTM = new htmlFunc;

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'version':
            if (isset($_REQUEST['cao_version'])) {
                Config::setCaoVersion($_REQUEST['cao_version']);
            }
            if (isset($_REQUEST['cao_language'])) {
                Config::setCaoLanguage($_REQUEST['cao_language']);
            } else
                Config::setCaoLanguage(0);

            if (isset($_REQUEST['cao_varseperator'])) {
                Config::setCaoVariantSeperator($_REQUEST['cao_varseperator']);
            } else
                Config::setCaoVariantSeperator(',');

            $oXML->GetScript($_REQUEST['action']);
            break;

        case 'customers_export':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->CustomersExport($_REQUEST);
            } else {
                if ($oXML->CheckAgent()) {
                    $oXML->CustomersExport($_REQUEST);
                } else
                    $oHTM->Login();
            }
            break;

        case 'customers_update':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->CustomersUpdate($_REQUEST);
            } else
                $oXML->CustomersUpdate($_REQUEST);
            break;

        case 'customers_erase':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->CustomersDelete($_REQUEST);
            } else
                $oXML->CustomersDelete($_REQUEST);
            break;

        case 'manufacturers_export':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ManufacturersExport();
            } else {
                if ($oXML->CheckAgent()) {
                    $oXML->ManufacturersExport();
                } else
                    $oHTM->Login();
            }
            break;

        case 'manufacturers_update':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ManufacturersUpdate($_REQUEST);
            } else
                $oXML->ManufacturersUpdate($_REQUEST);
            break;

        case 'manufacturers_erase':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ManufacturersDelete($_REQUEST);
            } else
                $oXML->ManufacturersDelete($_REQUEST);
            break;

        case 'manufacturers_image_upload':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ManufacturersImage($_REQUEST);
            } else
                $oXML->ManufacturersImage($_REQUEST);
            break;

        case 'categories_export':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->CategoriesExport();
            } else {
                if ($oXML->CheckAgent()) {
                    $oXML->CategoriesExport();
                } else {
                    $oHTM->Login();
                }
            }

            break;

        case 'categories_update':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->CategoriesUpdate($_REQUEST);
            } else
                $oXML->CategoriesUpdate($_REQUEST);
            break;

        case 'categories_erase':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->CategoriesErase($_REQUEST);
            } else
                $oXML->CategoriesErase($_REQUEST);
            break;

        case 'categories_image_upload':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->CategoriesImage($_REQUEST);
            } else
                $oXML->CategoriesImage($_REQUEST);
            break;

        case 'products_export':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ProductsExport($_REQUEST);
            } else {
                if ($oXML->CheckAgent()) {
                    $oXML->ProductsExport($_REQUEST);
                } else
                    $oHTM->Login();
            }
            break;

        case 'products_update':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ProductUpdate($_REQUEST);
            } else
                $oXML->ProductUpdate($_REQUEST);
            break;

        case 'products_erase':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ProductErase($_REQUEST);
            } else
                $oXML->ProductErase($_REQUEST);
            break;

        case 'products_image_upload':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ProductImage($_REQUEST, '/product/1/');
            } else
                $oXML->ProductImage($_REQUEST, '/product/1/');
            break;

        case 'products_image_upload_med':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ProductImage($_REQUEST, '/product/2/');
            } else
                $oXML->ProductImage($_REQUEST, '/product/2/');
            break;

        case 'products_image_upload_large':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ProductImage($_REQUEST, '/product/3/');
            } else
                $oXML->ProductImage($_REQUEST, '/product/3/');
            break;

        case 'prod2cat_update':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ProductToCategoryUpdate($_REQUEST);
            } else
                $oXML->ProductToCategoryUpdate($_REQUEST);
            break;

        case 'prod2cat_erase':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->ProductToCategoryErase($_REQUEST);
            } else
                $oXML->ProductToCategoryErase($_REQUEST);
            break;

        case 'orders_export':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->OrderExport($_REQUEST);
            } else {
                if ($oXML->CheckAgent()) {
                    $oXML->OrderExport($_REQUEST);
                } else
                    $oHTM->Login();
            }
            break;

        case 'order_update':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oXML->OrderUpdate($_REQUEST);
            } else
                $oXML->OrderUpdate($_REQUEST);
            break;

        case 'orderstatus':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oHTM->ShowOrderStatus($oXML);
                else
                    $oHTM->Login();
            } else
                $oHTM->ShowOrderStatus($oXML);
            break;

        case 'setorderstatus':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin()) {
                    if ($oXML->setOrderStatus())
                        $oHTM->HTMLMenu();
                } else
                    $oHTM->Login();
            }
            else if ($oXML->setOrderStatus())
                $oHTM->HTMLMenu();
            break;

        case 'delivery':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oHTM->ShowDelivery($oXML);
                else
                    $oHTM->Login();
            } else {
                if ($oXML->CheckAgent()) {
                    $oHTM->ShowDelivery($oXML);
                } else
                    $oHTM->Login();
            }
            break;

        case 'payment':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oHTM->ShowPayment($oXML);
                else
                    $oHTM->Login();
            } else {
                if ($oXML->CheckAgent()) {
                    $oHTM->ShowPayment($oXML);
                } else
                    $oHTM->Login();
            }
            break;

        case 'config':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin())
                    $oHTM->ShowConfig();
                else
                    $oHTM->Login();
            } else {
                if ($oXML->CheckAgent()) {
                    $oHTM->ShowConfig();
                } else
                    $oHTM->Login();
            }
            break;

        case 'setconfig':
            if (!Config::$IsNoAuth) {
                if ($oXML->oxidIsAdmin()) {
                    Config::saveCOIConfig();
                    $oHTM->HTMLMenu();
                } else
                    $oHTM->Login();
            } else {
                Config::saveCOIConfig();
                $oHTM->HTMLMenu();
            }
            break;

        default :
            if (!Config::$NoHtmlLogin) {
                if ($oXML->oxidIsAdmin(1))
                    $oHTM->HTMLMenu();
                else
                    $oHTM->Login();
            } else
                $oHTM->HTMLMenu();
            exit;
    }
} else {
    if (!Config::$NoHtmlLogin) {
        if ($oXML->oxidIsAdmin(1))
            $oHTM->HTMLMenu();
        else
            $oHTM->Login();
    } else
        $oHTM->HTMLMenu();
}

