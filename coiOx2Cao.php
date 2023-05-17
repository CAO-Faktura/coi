<?php

/**
 *  Modul        : coiOx2Cao.php
 *  Beschreibung : Script zum Datenaustausch CAO-Faktura <--> OXID eShop
 *                 Lauffähig unter OXID V 6.0.0
 * @author Thoren Strunk <edv@tstrunk.de>
 * @copyright Copyright (c) T.Strunk Software e.K.
 * Hinweis:
 * Dieses Projekt ist gemäß den Bedingungen der AGPL V3 lizenziert
 **/

if (!defined('COI_ROOT'))
    die('Diese Datei kann nicht aufgerufen werden');

class ox2Cao extends Func {

    private $_iCaoId = 0;
    private $_IsUpdateOxUser = 0;
    private $_IsUpdateOxManufactur = 0;
    private $_IsUpdateOxArticles = 0;
    private $_IsUpdateOxDiscount = 0;
    private $_IsUpdateOrder = 0;

    private function _CheckOxidTable($sTableName) {
        $sQ = "SHOW COLUMNS FROM " . $sTableName . " LIKE 'COI_CAOID'";
        return $this->oxDB->getOne($sQ);
    }

    private function _CheckCOITables($sTableName) {
        $sQ = "SHOW TABLES LIKE '" . $sTableName . "'";
        return $this->oxDB->getOne($sQ);
    }

    private function _UpdateOxidTables() {
        /* User */
        if (!$this->_CheckOxidTable('oxuser')) {
            $sQ = "ALTER TABLE oxuser ADD COI_CAOID INT(11) UNSIGNED NULL DEFAULT NULL";
            try {
                $this->oxDB->Execute($sQ);
            } catch (Exception $ex) {
                $this->_IsUpdateOxUser = 1;
            }
        } else
            $this->_IsUpdateOxUser = 1;
        /*  Manufacturer */
        if (!$this->_CheckOxidTable('oxmanufacturers')) {
            $sQ = "ALTER TABLE oxmanufacturers ADD COI_CAOID INT(11) UNSIGNED NULL DEFAULT NULL";
            try {
                $this->oxDB->Execute($sQ);
            } catch (Exception $ex) {
                $this->_IsUpdateOxManufactur = 1;
            }
        } else
            $this->_IsUpdateOxManufactur = 1;
        /* Articles */
        if (!$this->_CheckOxidTable('oxarticles')) {
            $sQ = "ALTER TABLE oxarticles ADD COI_CAOID INT(11) UNSIGNED NULL DEFAULT NULL";
            try {
                $this->oxDB->Execute($sQ);
            } catch (Exception $ex) {
                $this->_IsUpdateOxArticles = 1;
            }
        } else
            $this->_IsUpdateOxArticles = 1;
        /* Object2Discount */
        if (!$this->_CheckOxidTable('oxobject2discount')) {
            $sQ = "ALTER TABLE oxobject2discount ADD COI_CAOID INT(11) UNSIGNED NULL DEFAULT NULL";
            try {
                $this->oxDB->Execute($sQ);
            } catch (Exception $ex) {
                $this->_IsUpdateOxDiscount = 1;
            }
        } else
            $this->_IsUpdateOxDiscount = 1;
        /* Order */
        if (!$this->_CheckOxidTable('oxorder')) {
            $sQ = "ALTER TABLE oxorder ADD COI_CAOID INT(11) UNSIGNED NOT NULL,ADD ORDERSTATUS INT(11) UNSIGNED DEFAULT '1' NOT NULL,ADD COI_DEADLINE DATE NOT NULL DEFAULT '0000-00-00'";
            try {
                $this->oxDB->Execute($sQ);
            } catch (Exception $ex) {
                $this->_IsUpdateOrder = 1;
            }
        } else
            $this->_IsUpdateOrder = 1;
    }

    private function _CreateTablesInOxid() {
        /* caoautoid */
        $iError = 0;
        if (!$this->_CheckCOITables('caoautoid')) {
            $sQ = "CREATE TABLE caoautoid (TABLENAME VARCHAR(255) NOT NULL, COI_CAOID INT(11) UNSIGNED NOT NULL DEFAULT '0' ) ENGINE=InnoDB";
            try {
                $this->oxDB->Execute($sQ);
            } catch (Exception $ex) {
                $iError = 1;
                echo 'Create caoautoid: ' . $ex;
                exit;
            }
        }
        /* oxcat2cao */
        if ($iError == 0) {
            if (!$this->_CheckCOITables('oxcat2cao')) {
                $sQ = "CREATE TABLE oxcat2cao (COI_CAOID INT(11) UNSIGNED NOT NULL, OXCATID CHAR(32) character set latin1 collate latin1_general_ci NOT NULL, CAOTOPID INT(11) NOT NULL, OXCATPARENTID CHAR(32) character set latin1 collate latin1_general_ci NOT NULL, PRIMARY KEY(COI_CAOID)) ENGINE=InnoDB";
                try {
                    $this->oxDB->Execute($sQ);
                } catch (Exception $ex) {
                    $iError = 1;
                    echo 'Create oxcat2cao: ' . $ex;
                    exit;
                }
                $iCaoId = 1;
                $sQ = "SELECT OXID,OXPARENTID FROM oxcategories WHERE OXACTIVE=1";
                $this->oxDB->setFetchMode(MODE_ASSOC);
                $this->aDbRes = $this->oxDB->getAll($sQ);
                if (is_array($this->aDbRes)) {
                    foreach ($this->aDbRes as $fields) {
                        $iTopID = 0;
                        if ($fields['OXPARENTID'] == 'oxrootid') {
                            $oxParentId = $fields['OXID'];
                            $iTopID = -1;
                        } else
                            $oxParentId = $fields['OXPARENTID'];

                        $sQ = "INSERT INTO oxcat2cao (COI_CAOID,CAOTOPID,OXCATID,OXCATPARENTID) VALUES('" . $iCaoId . "','" . $iTopID . "','" . $fields['OXID'] . "','" . $oxParentId . "')";
                        try {
                            $this->oxDB->Execute($sQ);
                        } catch (Exception $ex) {
                            $iError = 1;
                            echo 'Insert in oxcat2cao: ' . $ex;
                            exit;
                        }
                        $iCaoId++;
                    }
                }
                if ($iError == 0) {
                    $sQ = "SELECT y.COI_CAOID,x.OXCATPARENTID FROM oxcat2cao x JOIN oxcat2cao y ON y.OXCATID=x.OXCATPARENTID";
                    $this->oxDB->setFetchMode(MODE_ASSOC);
                    $this->aDbRes = $this->oxDB->getAll($sQ);
                    if (is_array($this->aDbRes)) {
                        foreach ($this->aDbRes as $fields) {
                            if ($doubleData != $fields['COI_CAOID']) {
                                $sQ = "UPDATE oxcat2cao SET CAOTOPID='" . $fields['COI_CAOID'] . "' WHERE OXCATPARENTID='" . $fields['OXCATPARENTID'] . "' AND OXCATID!='" . $fields['OXCATPARENTID'] . "'";
                                try {
                                    $this->oxDB->Execute($sQ);
                                } catch (Exception $ex) {
                                    $iError = 1;
                                    echo 'Update oxcat2cao: ' . $ex;
                                    exit;
                                }
                            }
                            $doubleData = $fields['COI_CAOID'];
                        }
                    }
                }
            }
        }
        //oxstatus2cao
        if ($iError == 0) {
            if (!$this->_CheckCOITables('oxstatus2cao')) {
                $sQ = "CREATE TABLE oxstatus2cao (COI_CAOID INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                       OXDESC VARCHAR(255) NULL, OXID CHAR(32) character set latin1 collate latin1_general_ci NOT NULL,
                       OXSENDID TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
                       OXPAYID TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
                       STORNOID TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL,
                       PRIMARY KEY(COI_CAOID)) ENGINE=InnoDB";
                try {
                    $this->oxDB->Execute($sQ);
                } catch (Exception $ex) {
                    $iError = 1;
                    echo 'Create oxstatus2cao: ' . $ex;
                    exit;
                }
            }
        }
    }

    public function SetFolders() {
        $sDelQ = "DELETE FROM oxstatus2cao WHERE OXID = ?";
        $sInsQ = "INSERT INTO oxstatus2cao (OXDESC,OXID) VALUES (?,?)";
        $sUpdQ = "UPDATE oxstatus2cao SET OXDESC = ? WHERE OXID = ?";
        $sQ = "SELECT OXID FROM oxstatus2cao WHERE OXID = ?";
        $folders = $this->oxConfig->getConfigParam('aOrderfolder');

        if (!$this->_CheckCOITables('oxstatus2cao')) {
            foreach ($folders as $sKey => $sValue) {
                try {
                    $this->oxDB->Execute($sInsQ, array($sKey, $sValue));
                } catch (Exception $ex) {
                    $iError = 1;
                    echo 'Insert into oxstatus2cao: ' . $ex;
                    exit;
                }
            }
        } else {
            if ($folders) {
                foreach ($folders as $sKey => $sValue) {
                    if (!$this->oxDB->getOne($sQ, array($sValue))) {
                        try {
                            $this->oxDB->Execute($sInsQ, array($sKey, $sValue));
                        } catch (Exception $ex) {
                            $iError = 1;
                            echo 'Insert into oxstatus2cao: ' . $ex;
                            exit;
                        }
                    } else {
                        try {
                            $this->oxDB->Execute($sUpdQ, array($sKey, $sValue));
                        } catch (Exception $ex) {
                            $iError = 1;
                            echo 'Update oxstatus2cao: ' . $ex;
                            exit;
                        }
                    }
                }

                $sQ = "SELECT OXID FROM oxstatus2cao";
                $this->oxDB->setFetchMode(MODE_ASSOC);
                $aDbRes = $this->oxDB->getAll($sQ);
                if (is_array($aDbRes)) {
                    foreach ($aDbRes as $fields) {
                        if (!array_search($fields['OXID'], $folders)) {
                            $this->oxDB->Execute($sDelQ, array($fields['OXID']));
                        }
                    }
                }
            }
        }
    }

    private function _UpdateCaoIdInOxid() {
        /* Oxuser */
        if (!$this->_IsUpdateOxUser) {
            $sQ = "UPDATE oxuser SET COI_CAOID=OXCUSTNR";
            $this->oxDB->Execute($sQ);
        }
        // Eigentlich unötig, da OXCUSTNR autoincrement
        $sQ = 'SELECT MAX(COI_CAOID) FROM oxuser';
        $this->_iCaoId = $this->oxDB->getOne($sQ);
        $this->_SetCaoStartId('user');

        /* Oxarticles */
        if (!$this->_IsUpdateOxArticles) {
            $sQ = "SELECT OXID FROM oxarticles WHERE oxactive=1";
            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);
            if (is_array($this->aDbRes)) {
                $iCaoId = 1;
                foreach ($this->aDbRes as $fields) {
                    $sQ = "UPDATE oxarticles SET COI_CAOID=" . $this->oxDB->quote($iCaoId) . " WHERE OXID=" . $this->oxDB->quote($fields['OXID']);
                    $this->oxDB->Execute($sQ);
                    $iCaoId++;
                }
                $this->_iCaoId = $iCaoId;
                $this->_SetCaoStartId('articles');
            }
        }
        /* Oxorder */
        if (!$this->_IsUpdateOrder) {
            $sQ = "SELECT OXID,OXORDERNR FROM oxorder ORDER BY OXORDERNR ASC";
            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);
            if (is_array($this->aDbRes)) {
                $iCaoId = 0;
                foreach ($this->aDbRes as $fields) {
                    $iCaoId = $fields['OXORDERNR'];
                    $sQ = "UPDATE oxorder SET COI_CAOID=" . $this->oxDB->quote($iCaoId) . " WHERE OXID=" . $this->oxDB->quote($fields['OXID']);
                    $this->oxDB->Execute($sQ);
                }
                if ($iCaoId > 0) {
                    $this->_iCaoId = $iCaoId;
                    $this->_SetCaoStartId('order');
                }
            }
        }
        
        /* oxmanufacturers */
        $sQ = "SHOW TABLES LIKE 'oxvendor2cao'";
        $sUpdate = "UPDATE oxmanufacturers SET COI_CAOID=? WHERE OXID=?";
        if ($this->oxDB->getOne($sQ)) {
            $sQ = "SELECT OXID,COI_CAOID FROM oxvendor2cao";
            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);
            if (is_array($this->aDbRes)) {
                foreach ($this->aDbRes as $fields) {
                  $this->oxDB->Execute($sUpdate, array($fields['COI_CAOID'], $fields['OXID']));
                }
            }
        }
    }

    private function _SetCaoStartId($sTableName) {
        $sQ = "SELECT COI_CAOID FROM caoautoid WHERE TABLENAME ='" . $sTableName . "'";
        if (!$this->oxDB->getOne($sQ)) {
            $this->oxDB->Execute("INSERT INTO caoautoid (TABLENAME) VALUES('" . $sTableName . "')");
        }
        $iNextId = $this->_iCaoId + 1;
        $sQ = "UPDATE caoautoid SET COI_CAOID='" . $iNextId . "' WHERE TABLENAME='" . $sTableName . "'";
        $this->oxDB->Execute($sQ);
    }

    public function InitTables() {
        /* Abfragen ob schon durchgeführt */
        if ($this->oxConfig->getConfigParam('iCoiInitTables') == 0) {
            $this->_UpdateOxidTables();
            $this->_CreateTablesInOxid();
            $this->_UpdateCaoIdInOxid();
            $this->SetFolders();
            $this->getOxMetaObject();
            $this->oxMetaData->updateViews();

// In Config schreiben das Tabellen Update/Create durchgeführt
            $this->oxConfig->saveShopConfVar('str', 'iCoiInitTables', 1);
        }
    }

    public function GetCoiId($sTableName) {
        $sQ = "SELECT COI_CAOID FROM caoautoid WHERE TABLENAME ='" . $sTableName . "'";
        if (!$iId = $this->oxDB->getOne($sQ)) {
            $this->oxDB->Execute("INSERT INTO caoautoid (TABLENAME) VALUES('" . $sTableName . "')");
            $iId = 1;
        }
        $iNextId = $iId + 1;
        $sQ = "UPDATE caoautoid SET COI_CAOID='" . $iNextId . "' WHERE TABLENAME='" . $sTableName . "'";
        $this->oxDB->Execute($sQ);
        return $iId;
    }

    public function UpdateCaoIdInOxTable($sTableName, $sOxId) {
        $iCoiId = $this->GetCoiId($sTableName);
        switch ($sTableName) {
            case 'articles': $sTableName = 'oxarticles';
                break;
            case 'order': $sTableName = 'oxorder';
                break;
        }
        $sQ = "UPDATE " . $sTableName . " SET COI_CAOID=" . $this->oxDB->quote($iCoiId) . " WHERE OXID=" . $this->oxDB->quote($sOxId);
        $this->oxDB->Execute($sQ);
        return $iCoiId;
    }

    public function UpdateCaoCategories() {
        $iError = 0;
// Datensatz einfügen wenn nicht in oxcat2cao vorhanden
        $sQ = "SELECT (MAX(COI_CAOID)+1) FROM oxcat2cao WHERE 1";
        $iCaoId = $this->oxDB->getOne($sQ);
        if (!$iCaoId) {
            $iCaoId = 1;
        }

        $sQ = "SELECT ox.OXID, ox.OXPARENTID FROM oxcategories ox WHERE NOT EXISTS (SELECT * FROM oxcat2cao c WHERE ox.OXID=c.OXCATID) AND ox.OXACTIVE=1";
        $this->oxDB->setFetchMode(MODE_ASSOC);
        $this->aDbRes = $this->oxDB->getAll($sQ);
        if (is_array($this->aDbRes)) { 
            foreach ($this->aDbRes as $fields) {
                $iTopID = 0;
                if ($fields['OXPARENTID'] == 'oxrootid') {
                    $oxParentId = $fields['OXID'];
                    $iTopID = -1;
                } else
                    $oxParentId = $fields['OXPARENTID'];

                $sQ = "INSERT INTO oxcat2cao (COI_CAOID,CAOTOPID,OXCATID,OXCATPARENTID) VALUES('" . $iCaoId . "','" . $iTopID . "','" . $fields['OXID'] . "','" . $oxParentId . "')";
                try {
                    $this->oxDB->Execute($sQ);
                } catch (Exception $ex) {
                    $iError = 1;
                    echo 'UpdateCaoCategories - Insert in oxcat2cao: ' . $ex;
                    exit;
                }
                $iCaoId++;
            }
        }
        if ($iError == 0) {
            $sQ = "SELECT y.COI_CAOID,x.OXCATPARENTID FROM oxcat2cao x JOIN oxcat2cao y ON y.OXCATID=x.OXCATPARENTID";
            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);
            if (is_array($this->aDbRes)) {
                foreach ($this->aDbRes as $fields) {
                    if ($doubleData != $fields['COI_CAOID']) {
                        $sQ = "UPDATE oxcat2cao SET CAOTOPID='" . $fields['COI_CAOID'] . "' WHERE OXCATPARENTID='" . $fields['OXCATPARENTID'] . "' AND OXCATID!='" . $fields['OXCATPARENTID'] . "'";
                        try {
                            $this->oxDB->Execute($sQ);
                        } catch (Exception $ex) {
                            $iError = 1;
                            echo 'UpdateCaoCategories - Update oxcat2cao: ' . $ex;
                            exit;
                        }
                    }
                    $doubleData = $fields['COI_CAOID'];
                }
            }
        }

// Datensatz löschen wenn in oxcategories nicht vorhanden
        if ($iError == 0) {
            $sQ = "SELECT c.COI_CAOID FROM oxcat2cao c WHERE NOT EXISTS (SELECT * FROM oxcategories ox WHERE ox.OXID=c.OXCATID)";
            $this->oxDB->setFetchMode(MODE_ASSOC);
            $this->aDbRes = $this->oxDB->getAll($sQ);
            if (is_array($this->aDbRes)) {
                foreach ($this->aDbRes as $fields) {
                    $sQ = "DELETE FROM oxcat2cao WHERE COI_CAOID=" . $this->oxDB->quote($fields['COI_CAOID']);
                    try {
                        $this->oxDB->Execute($sQ);
                    } catch (Exception $ex) {
                        $iError = 1;
                        echo 'UpdateCaoCategories - Delete oxcat2cao: ' . $ex;
                        exit;
                    }
                }
            }
        }
    }

}
