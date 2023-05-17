<?php

/**
 *  Modul        : coiEmail.php
 *  Beschreibung : Script zum Datenaustausch CAO-Faktura <--> OXID eShop
 *                 Lauffähig unter OXID V 6.0.0
 * @author Thoren Strunk <edv@tstrunk.de>
 * @copyright Copyright (c) T.Strunk Software e.K.
 * Hinweis:
 * Dieses Projekt ist gemäß den Bedingungen der GPL V3 lizenziert
 **/

// Smartyresourcen
function ts_get_template($sTplName, &$sTplSource, &$oSmarty) {
    $sTplSource = $oSmarty->get_template_vars($sTplName);
    return empty($sTplSource) ? false : true;
}

function ts_get_timestamp($sTplName, &$iTplTimestamp, &$oSmarty) {
    $time = $oSmarty->get_template_vars($sTplName . '_time');
    return !empty($time) ? $time : time();
}

function ts_get_secure($sTplName, &$oSmarty) {
    return true;
}

function ts_get_trusted($sTplName, &$oSmarty) {
    // not used for templates
}

class coiEmail extends ox2Cao {

    protected $sSubject;
    protected $sBody;
    protected $sBodyPlain;
    protected $sFolderId;
    protected $sOxId;

    public function send($sOxFolderId, $sOxId) {
        $this->getOxEmailObject();
        $this->getOxContentObject();
        $this->getOxOrderObject();
        $this->getOxSmartyObject();

        $this->sFolderId = $sOxFolderId;
        $this->sOxId = $sOxId;

        // Mail bauen
        if ($this->BuildEmail()) {
            return $this->oxEmail->send();
        }
    }

    protected function getShop() {
        $shop = oxNew(\OxidEsales\Eshop\Application\Model\Shop::class);
        $shop->load($this->oxConfig->getShopId());

        return $shop;
    }

    protected function setMailParams($shop) {
        $this->oxEmail->clearAllRecipients();
        $this->oxEmail->clearReplyTos();
        $this->oxEmail->clearAttachments();

        $this->oxEmail->ErrorInfo = '';

        $this->oxEmail->setFrom($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->getRawValue());
        $this->oxEmail->setSmtp($shop);
    }

    protected function BuildEmail() {
        if ($this->oxContent->loadByIdent($this->sFolderId)) {
            $shop = $this->getShop();
            $this->setMailParams($shop);
            $this->oxOrder->load($this->sOxId);
            $user = $this->oxOrder->getOrderUser();
            $this->oxEmail->setUser($user);

            $this->sSubject = $this->oxContent->oxcontents__oxtitle->value;
            $this->sBody = $this->oxContent->oxcontents__oxcontent->value;
            $this->sBodyPlain = $this->sBody;

            $this->clear_smarty_cache();
            $this->oxSmarty->register_resource('ts', array('ts_get_template',
                'ts_get_timestamp',
                'ts_get_secure',
                'ts_get_trusted'));


            $this->oxSmarty->clear_assign("shop");
            $this->oxSmarty->clear_assign("oViewConf");
            $this->oxSmarty->clear_assign("oView");
            $this->oxSmarty->clear_assign("order");

            $this->oxSmarty->assign("content", $this->sBody);
            $this->oxSmarty->assign("content_plain", $this->sBodyPlain);
            $this->oxSmarty->assign("shop", $shop);
            $this->oxSmarty->assign("oViewConf", $this->oxConfig);
            $this->oxSmarty->assign("oView", $this->oxConfig->getActiveView());
            $this->oxSmarty->assign("order", $this->oxOrder);

            $aNewSmartyArray = $this->oxSmarty->get_template_vars();

            foreach ($aNewSmartyArray as $key => $val) {
                $this->oxSmarty->assign($key, $val);
            }

            // Emailinhalt bereit stellen
            $this->sBody = nl2br($this->oxSmarty->fetch("ts:content"));
            $this->sBodyPlain = strip_tags($this->oxSmarty->fetch("ts:content_plain"));

            $this->oxEmail->setBody($this->sBody);
            $this->oxEmail->setAltBody($this->sBodyPlain);
            $this->oxEmail->setSubject($this->sSubject);

            $fullName = $user->oxuser__oxfname->getRawValue() . " " . $user->oxuser__oxlname->getRawValue();

            $this->oxEmail->setRecipient($user->oxuser__oxusername->value, $fullName);
            $this->oxEmail->setReplyTo($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->getRawValue());
            return true;
        } else
            return false;
    }

    protected function clear_smarty_cache() {
        $sSmartyDir = $this->getSmartyDir();
        $aFileNames = glob($sSmartyDir . '*ts*content*.php');
        if (is_array($aFileNames)) {
            foreach ($aFileNames as $sFile) {
                @unlink($sFile);
            }
        }
    }

}
