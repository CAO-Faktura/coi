<?php

/**
 *  Modul        : coiHtml.php
 *  Beschreibung : Script zum Datenaustausch CAO-Faktura <--> OXID eShop
 *                 Lauffähig unter OXID V 6.0.0
 * @author Thoren Strunk <edv@tstrunk.de>
 * @copyright Copyright (c) T.Strunk Software e.K.
 * Hinweis:
 * Dieses Projekt ist gemäß den Bedingungen der GPL V3 lizenziert
 **/

if (!defined('COI_ROOT'))
    die('Diese Datei kann nicht aufgerufen werden');

class htmlFunc {

    private $aConfigParam = array();

    public function sendHTML($sBodyText) {
        $sOut = "<html>
              <head>
              <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
              <style>" . Config::$sCSS . "</style>
              <title>COI - CAO<->OXID Interface</title>
              </head>
              <body>
              <div align=center><div class=\"main\">
              <h1><span id=\"red\">COI</span><span id=\"black\"> - CAO<->OXID Interface</span></h1>
              <h3>Interface zur Anbindung von CAO-FAKTURA an OXID eShop 6</h3>
              <h4>Version: " . Config::$COIVersion . " - Datum: " . Config::$COIVersionDate . "<br>&copy; 2009 - " . date("Y") . ", <span id=\"orange\">T. Strunk Software und mehr...</span></h4>
              <table style=\"border:none;\"><tr><td class=\"main\"><strong>Registriert auf:</strong></td>
              <td class=\"main\">";
        if (Config::$aLicense['Name1'])
            $sOut .= Config::$aLicense['Name1'] . "<br>";
        $sOut .= Config::$aLicense['Name2'] . " " . Config::$aLicense['Name3'] . "</td></tr></table>";
        $sOut .= $sBodyText;
        $sOut .= "</div></div>\n</body></html>";
        echo $sOut;
    }

    public function Login() {
        $sBody .= "<form name=\"login\" method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\">
              <input type=\"hidden\" name=\"sSID\" value=\"" . md5(Config::$sSID) . "\">
              <h3>Login:</h3>
              <label for=\"user\">Benutzername:</label><br>
              <input name=\"user\" type=\"text\" maxlength=\"32\" id=\"user\" class=\"input\"><br>
              <label for=\"pass\">Kennwort:</label><br>
              <input name=\"password\" type=\"password\" id=\"pass\" class=\"input\">
              <p><input type=\"submit\" name=\"Submit\" value=\"Anmelden\" class=\"button\"></p>
              </form>";
        $this->sendHTML($sBody);
    }

    public function HTMLMenu() {
        $sBody = "<p>Um CAO-FAKTURA korrekt an das Interfacescript anzubinden, ist folgender Pfad in den Shopeinstellungen einzugeben:<br>
              <b>http(s)://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . "</b><br>
              Benutzen Sie zur Anmeldung den Benutzernamen und das Kennwort von OXID eShop in den CAO Shop-Einstellungen.<br>
              Weiterhin muss in den CAO Shop-Einstellungen die Software f&uuml;r osCommerce bzw. OXID eingestellt sein, da sonst die Authentifizierung nicht m&ouml;glich ist.<br />
              Sollte die Authentifizierung trotz korrektem Benutzername und Kennwort nicht m&ouml;glich sein, so melden Sie dieses bitte per Email an edv@tstrunk.de</p>
              <hr><b>Funktions-Menu :</b><br>
              <a href=\"" . $_SERVER['PHP_SELF'] . "?action=delivery&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Lieferarten</a><br>
              <a href=\"" . $_SERVER['PHP_SELF'] . "?action=payment&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Zahlungsarten</a><br>
              <a href=\"" . $_SERVER['PHP_SELF'] . "?action=orderstatus&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Orderstatus</a><br>
              <a href=\"" . $_SERVER['PHP_SELF'] . "?action=config&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Einstellungen</a>
              <hr>
              <a href=\"" . $_SERVER['PHP_SELF'] . "?action=customers_export&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Ausgabe XML Kundendaten</a><br>
	      <a href=\"" . $_SERVER['PHP_SELF'] . "?action=manufacturers_export&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Ausgabe XML Herstellerdaten</a><br>
	      <a href=\"" . $_SERVER['PHP_SELF'] . "?action=categories_export&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Ausgabe XML Produktkategorien</a><br>
  	      <a href=\"" . $_SERVER['PHP_SELF'] . "?action=products_export&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Ausgabe XML Produkte</a><br>
 	      <a href=\"" . $_SERVER['PHP_SELF'] . "?action=orders_export&" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">Ausgabe XML Bestellung</a><br>
              <hr>";

        $this->sendHTML($sBody);
    }

    // Orderstatus
    public function ShowOrderStatus($oCOI) {
        $oCOI->SetFolders();

        $sQ = "SELECT * FROM oxstatus2cao ORDER BY COI_CAOID";

        $sBody = "<h3>Orderstatus:</h3>
        <p class=\"main\">Folgende ID's sind in dem Feld ID innerhalb der CAO Shop-Einstellung f&uuml;r den Order-Status einzugeben, damit der Order-Status von OXID in CAO bekannt ist.
        Die Beschreibung muss nicht mit der von OXID &uuml;bereinstimmen und kann frei gew&auml;hlt werden.<br />
        Geben Sie weiterhin an welche ID f&uuml;r den Versand und welche ID f&uuml;r die Bezahlung der Bestellung gelten soll.</p>
        <form name=\"status\" method=\"get\" action=\"" . $_SERVER['PHP_SELF'] . "\">
        <input type=\"hidden\" name=\"sSID\" value=\"" . md5(Config::$sSID) . "\">
        <input type=\"hidden\" name=\"" . session_name() . "\" value=\"" . session_id() . "\">
        <input type=\"hidden\" name=\"action\" value=\"setorderstatus\">";

        $oCOI->oxDB->setFetchMode(MODE_ASSOC);
        $oCOI->aDbRes = $oCOI->oxDB->getAll($sQ);
        if (is_array($oCOI->aDbRes)) {
            $sBody .= "<table width=\"100%\">
                 <tr><th width=\"10%\">ID</th><th width=\"45%\">Bezeichnung</th><th width=\"15%\">Versand-ID</th><th width=\"15%\">Bezahl-ID</th><th width=\"15%\">Storno-ID</th></tr>";

            foreach ($oCOI->aDbRes as $fields) {
                $delchecked = $billchecked = $stornochecked = '';
                if ($fields['OXSENDID'] == 1)
                    $delchecked = 'checked';
                if ($fields['OXPAYID'] == 1)
                    $billchecked = 'checked';
                if ($fields['STORNOID'] == 1)
                    $stornochecked = 'checked';

                $sBody .= "<tr><td width=\"10%\" class=\"list\">" . $fields['COI_CAOID'] .
                        "</td><td width=\"45%\" class=\"list\">" . $fields['OXDESC'] . "</td>
	            <td width=\"15%\" class=\"list\" align=\"center\"><input type=\"radio\" name=\"delivery\" value=\"" . $fields['COI_CAOID'] . "\"" . $delchecked . " /></td><td width=\"15%\" class=\"list\" align=\"center\"><input type=\"radio\" name=\"bill\" value=\"" . $fields['COI_CAOID'] . "\"" . $billchecked . " /></td>
		    <td width=\"15%\" class=\"list\" align=\"center\"><input type=\"radio\" name=\"storno\" value=\"" . $fields['COI_CAOID'] . "\"" . $stornochecked . " /></td></tr>";
            }
            $sBody .= "</table>";
        }
        $sBody .= "<div align=\"center\"><a href=\"" . $_SERVER['PHP_SELF'] . "?" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">> ZUR&Uuml;CK <</a> <input type=\"submit\" value=\"Speichern\" /></div>";
        $this->sendHTML($sBody);
    }

    // Liefercode
    public function ShowDelivery($oCOI) {
        $sBody = "<h3>Lieferarten:</h3>
              <p>Folgende ID's sind in dem Feld Shop-Class (shipping_class) innerhalb der CAO Shop-Einstellung f&uuml;r die Lieferart einzugeben, damit die Lieferarten von OXID in CAO bekannt sind.
              Die Lieferarten m&uuml;ssen in CAO entsprechend angelegt sein.</p>";

        $sQ = "SELECT OXID,OXTITLE FROM oxdeliveryset WHERE OXACTIVE=1";
        $oCOI->oxDB->setFetchMode(MODE_ASSOC);
        $oCOI->aDbRes = $oCOI->oxDB->getAll($sQ);
        if (is_array($oCOI->aDbRes)) {
            $sBody .= "<table width=\"100%\">
                  <tr><th width=\"50%\">Shop-Class ID</th><th width=\"50%\">Bezeichnung</th></tr>";

            foreach ($oCOI->aDbRes as $fields) {
                $sBody .= "<tr><td width=\"50%\" class=\"list\">" . $fields['OXID'] . "</td><td width=\"50%\" class=\"list\">" . $fields['OXTITLE'] . "</td></tr>";
            }
            $sBody .= "</table>";
        }
        $sBody .= "<div align=\"center\"><a href=\"" . $_SERVER['PHP_SELF'] . "?" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">> ZUR&Uuml;CK <</a></div>";
        $this->sendHTML($sBody);
    }

    public function ShowPayment($oCOI) {
        $sBody = "<h3>Zahlungsarten:</h3>
		  <p>Folgende ID's sind in dem Feld Shop-Class (payment_class) innerhalb der CAO Shop-Einstellung f&uuml;r die Zahlart einzugeben, damit die Zahlarten von OXID in CAO bekannt sind.
		  Die Zahlungsarten m&uuml;ssen in CAO entsprechend angelegt sein.</p>";

        $sQ = "SELECT OXID,OXDESC FROM oxpayments WHERE OXACTIVE=1";
        $oCOI->oxDB->setFetchMode(MODE_ASSOC);
        $oCOI->aDbRes = $oCOI->oxDB->getAll($sQ);
        if (is_array($oCOI->aDbRes)) {
            $sBody .= "<table width=\"100%\">
                       <tr><th width=\"50%\">Shop-Class ID</th><th width=\"50%\">Bezeichnung</th></tr>";
            foreach ($oCOI->aDbRes as $fields) {
                $sBody .= "<tr><td width=\"50%\" class=\"list\">" . $fields['OXID'] . "</td><td width=\"50%\" class=\"list\">" . $fields['OXDESC'] . "</td></tr>";
            }
            $sBody .= "</table>";
        }
        $sBody .= "<div align=\"center\"><a href=\"" . $_SERVER['PHP_SELF'] . "?" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">> ZUR&Uuml;CK <</a></div>";
        $this->sendHTML($sBody);
    }

    public function ShowConfig() {
        $this->aConfigParam = Config::getCOIConfig('all');

        if ($this->aConfigParam['ACTIVEARTICLE']) {
            $sActiveArt = "selected";
        }
        if ($this->aConfigParam['ARTICLEMATCHCODE']) {
            $aArtMatch[$this->aConfigParam['ARTICLEMATCHCODE']] = "selected";
        }
        if ($this->aConfigParam['TEXTDESCRIPTION']) {
            $sDescription = "selected";
        }
        if ($this->aConfigParam['CHANGEDESCRIPTION']) {
            $sChangeDescription = "selected";
        }
        if ($this->aConfigParam['EKPRICE']) {
            $sEkPrice = "selected";
        }
        if ($this->aConfigParam['OPENSTOCKINVOICE']) {
            $sOpenSockInvoice = "selected";
        }
        if ($this->aConfigParam['OPENSTOCKORDER']) {
            $sOpenStockOrder = "selected";
        }
        if ($this->aConfigParam['USEBASEUNIT']) {
            $sBaseUnit = "selected";
        }
        if ($this->aConfigParam['SCALEPRICEINPERCENT']) {
            $sScalePrice = "selected";
        }
        if ($this->aConfigParam['SPECIALPRICEINPERCENT']) {
            $sSpecialPrice = "selected";
        }
        if ($this->aConfigParam['ARTICLESORTINCATEGORIE']) {
            $sArtSort = "selected";
        }
        if ($this->aConfigParam['SELECTVARIANT']) {
            $sSelectVariant = "selected";
        }
        if ($this->aConfigParam['SELECTPRICE']) {
            $sSelectPrice = "selected";
        }
        if ($this->aConfigParam['SETINVOICENUMBER']) {
            $sSetInvoiceNumber = "selected";
        }
        if ($this->aConfigParam['SETDEADLINE']) {
            $sSetDeadLine = "selected";
        }
        if ($this->aConfigParam['USEMATCHCODE'])
            $sUseMatchCode = "selected";
        if ($this->aConfigParam['SENDASPAIDATE'])
            $sSetPaiDate = "selected";
        if ($this->aConfigParam['USEORDERGROSS'])
            $sUseOrderGross = "selected";
        if ($this->aConfigParam['COMPANYNAME'])
            $aCompanyName[$this->aConfigParam['COMPANYNAME']] = "selected";
        if ($this->aConfigParam['FIRSTNAME'])
            $aFirstName[$this->aConfigParam['FIRSTNAME']] = "selected";
        if ($this->aConfigParam['LASTNAME'])
            $aLastName[$this->aConfigParam['LASTNAME']] = "selected";
        if ($this->aConfigParam['MATCHCOMPANYNAME'])
            $aMatchCompanyName[$this->aConfigParam['MATCHCOMPANYNAME']] = "selected";
        if ($this->aConfigParam['MATCHFIRSTNAME'])
            $aMatchFirstName[$this->aConfigParam['MATCHFIRSTNAME']] = "selected";
        if ($this->aConfigParam['MATCHLASTNAME'])
            $aMatchLastName[$this->aConfigParam['MATCHLASTNAME']] = "selected";
        if ($this->aConfigParam['ORDERADDINFO'])
            $aOrderAddInfo[$this->aConfigParam['ORDERADDINFO']] = "selected";
        if ($this->aConfigParam['ORDERCOMPANYNAME'])
            $aOrderCompanyName[$this->aConfigParam['ORDERCOMPANYNAME']] = "selected";
        if ($this->aConfigParam['ORDERFIRSTNAME'])
            $aOrderFirstName[$this->aConfigParam['ORDERFIRSTNAME']] = "selected";
        if ($this->aConfigParam['ORDERLASTNAME'])
            $aOrderLastName[$this->aConfigParam['ORDERLASTNAME']] = "selected";
        if ($this->aConfigParam['ORDERREMARKMESSAGE'])
            $aOrderRemarkMsg[$this->aConfigParam['ORDERREMARKMESSAGE']] = "selected";
        if ($this->aConfigParam['ORDERREMARKBILLINFO'])
            $aOrderRemarkInfoBill[$this->aConfigParam['ORDERREMARKBILLINFO']] = "selected";
        if ($this->aConfigParam['ORDERREMARKDELIVERYINFO'])
            $aOrderRemarkInfoDel[$this->aConfigParam['ORDERREMARKDELIVERYINFO']] = "selected";
        if ($this->aConfigParam['ORDERREMARKBILLFON'])
            $aOrderRemarkFonBill[$this->aConfigParam['ORDERREMARKBILLFON']] = "selected";
        if ($this->aConfigParam['ORDERREMARKDELIVERYFON'])
            $aOrderRemarkFonDel[$this->aConfigParam['ORDERREMARKDELIVERYFON']] = "selected";
        if ($this->aConfigParam['USERFIELDS'])
            $sUserFields = $this->_GetUserfields([$this->aConfigParam['USERFIELDS']]);
        if ($this->aConfigParam['PRICEGROUPA'])
            $aPriceGroupA[$this->aConfigParam['PRICEGROUPA']] = "selected";
        if ($this->aConfigParam['PRICEGROUPB'])
            $aPriceGroupB[$this->aConfigParam['PRICEGROUPB']] = "selected";
        if ($this->aConfigParam['PRICEGROUPC'])
            $aPriceGroupC[$this->aConfigParam['PRICEGROUPC']] = "selected";
        if ($this->aConfigParam['OVERWRITEPRICEGROUP'])
            $sOverWritePriceGroup = "selected";
        if ($this->aConfigParam['PERSPARAM'])
            $sPersParam = "selected";
        if ($this->aConfigParam['FREEPERSPARAM'])
            $sFreePersParam = "selected";
        if ($this->aConfigParam['USEORDERNR'])
            $sUseOrdernr = "selected";
        if ($this->aConfigParam['SELECTVARIANTTEXT'])
            $sUseSelVariantText = 'selected';
        if ($this->aConfigParam['USECUSTNUMBER'])
            $sUseCustomerNumber = 'selected';
        if ($this->aConfigParam['ORDERUSERADDINFO'])
            $aOrderUserAddInfo[$this->aConfigParam['ORDERUSERADDINFO']] = "selected";
        if ($this->aConfigParam['USEOTHERTAX'])
            $sUseOtherTax = 'selected';


        $sBody = "<form name=\"config\" method=\"get\" action=\"" . $_SERVER['PHP_SELF'] . "\">
              <input type=\"hidden\" name=\"sSID\" value=\"" . md5(Config::$sSID) . "\">
              <input type=\"hidden\" name=\"" . session_name() . "\" value=\"" . session_id() . "\">
              <input type=\"hidden\" name=\"action\" value=\"setconfig\">
               
              <h3>Einstellungen:</h3> 
               
              <table>
              <tr><td colspan=\"4\" class=\"head\">&nbsp;Kundeneinstellungen</td></tr>
              
              <tr>
              <td width=\"35%\"><label for=\"usecustnumber\">Kundennumer als Shop-ID in CAO-Faktura</label></td>
              <td width=\"15%\"><select name=\"usecustnumber\" size=\"1\" id=\"usecustnumber\"><option value=\"0\">Nein</option><option value=\"1\" $sUseCustomerNumber>Ja</option></select></td>
              <td width=\"50%\" colspan=\"2\">&nbsp;</td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"companyname\">Feld für Name1 (Firmenname)</label></td>
              <td width=\"15%\"><select name=\"companyname\" size=\"1\" id=\"companyname\">
              <option value=\"0\" $aCompanyName[0]>Firmenname</option>
              <option value=\"1\" $aCompanyName[1]>Vorname</option>
              <option value=\"2\" $aCompanyName[2]>Nachname</option>
              <option value=\"3\" $aCompanyName[3]>Vorname Nachname</option>    
              <option value=\"4\" $aCompanyName[4]>-</option>        
              </select></td>
              
              <td width=\"35%\"><label for=\"firstname\">Feld für Name2 (Vorname)</label></td>
              <td width=\"15%\"><select name=\"firstname\" size=\"1\" id=\"firstname\">
              <option value=\"0\" $aFirstName[0]>Firmenname</option>
              <option value=\"1\" $aFirstName[1]>Vorname</option>
              <option value=\"2\" $aFirstName[2]>Nachname</option>
              <option value=\"3\" $aFirstName[3]>Vorname Nachname</option>    
              <option value=\"4\" $aFirstName[4]>-</option>        
              </select></td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"lastname\">Feld für Name3 (Nachname)</label></td>
              <td width=\"15%\"><select name=\"lastname\" size=\"1\" id=\"lastname\">
              <option value=\"0\" $aLastName[0]>Firmenname</option>
              <option value=\"1\" $aLastName[1]>Vorname</option>
              <option value=\"2\" $aLastName[2]>Nachname</option>
              <option value=\"3\" $aLastName[3]>Vorname Nachname</option>   
              <option value=\"4\" $aLastName[4]>-</option>       
              </select></td>

              <td width=\"50%\" colspan=\"2\">&nbsp;</td>
              </tr>

              <tr>
              <td width=\"100%\" colspan=\"4\">&nbsp;</td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"usematchcode\">Suchbegriff im Kundenstamm CAO-Faktura eintragen:</label></td>
              <td width=\"15%\"><select name=\"usematchcode\" size=\"1\" id=\"usematchcode\"><option value=\"0\">Nein</option><option value=\"1\" $sUseMatchCode>Ja</option></select></td>

              <td width=\"50%\" colspan=\"2\">&nbsp;</td>
              </tr>
             
              <tr>
              <td width=\"35%\"><label for=\"matchcode\">Suchbegriff:</label></td>
              <td width=\"75%\" colspan=\"3\">
              <select name=\"matchcompanyname\" size=\"1\" id=\"matchcompanyname\">
              <option value=\"0\" $aMatchCompanyName[0]>-</option>              
              <option value=\"1\" $aMatchCompanyName[1]>Firmenname</option>
              <option value=\"2\" $aMatchCompanyName[2]>Vorname</option>
              <option value=\"3\" $aMatchCompanyName[3]>Nachname</option>
              </select>
              <select name=\"matchfirstname\" size=\"1\" id=\"matchfirstname\">
              <option value=\"0\" $aMatchFirstName[0]>-</option>
              <option value=\"1\" $aMatchFirstName[1]>Firmenname</option>    
              <option value=\"2\" $aMatchFirstName[2]>Vorname</option>
              <option value=\"3\" $aMatchFirstName[3]>Nachname</option>
              </select>
              <select name=\"matchlastname\" size=\"1\" id=\"matchlastname\">
              <option value=\"0\" $aMatchLastName[0]>-</option>
              <option value=\"1\" $aMatchLastName[1]>Firmenname</option>    
              <option value=\"2\" $aMatchLastName[2]>Vorname</option>
              <option value=\"3\" $aMatchLastName[3]>Nachname</option>
              </select>
              </td>            
              </tr>

              <tr>
              <td width=\"100%\" colspan=\"4\"><br /><label>CAO-VK-Preis zur entsprechenden Oxid-Preisgruppe</label></td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"pricegroupa\">Preisgruppe A:</label></td>
              <td width=\"15%\"><select name=\"pricegroupa\" id=\"pricegroupa\">
              <option value=\"0\" $aPriceGroupA[0]>-</option>
              <option value=\"1\" $aPriceGroupA[1]>VK1</option>
              <option value=\"2\" $aPriceGroupA[2]>VK2</option>
              <option value=\"3\" $aPriceGroupA[3]>VK3</option>
              <option value=\"4\" $aPriceGroupA[4]>VK4</option>
              <option value=\"5\" $aPriceGroupA[5]>VK5</option>
              </select>
              </td>

              <td width=\"35%\"><label for=\"pricegroupb\">Preisgruppe B:</label></td>
              <td width=\"15%\"><select name=\"pricegroupb\" id=\"pricegroupb\">
              <option value=\"0\" $aPriceGroupB[0]>-</option>
              <option value=\"1\" $aPriceGroupB[1]>VK1</option>
              <option value=\"2\" $aPriceGroupB[2]>VK2</option>
              <option value=\"3\" $aPriceGroupB[3]>VK3</option>
              <option value=\"4\" $aPriceGroupB[4]>VK4</option>
              <option value=\"5\" $aPriceGroupB[5]>VK5</option>
              </select>
              </td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"pricegroupc\">Preisgruppe C:</label></td>
              <td width=\"15%\"><select name=\"pricegroupc\" id=\"pricegroupc\">
              <option value=\"0\" $aPriceGroupC[0]>-</option>
              <option value=\"1\" $aPriceGroupC[1]>VK1</option>
              <option value=\"2\" $aPriceGroupC[2]>VK2</option>
              <option value=\"3\" $aPriceGroupC[3]>VK3</option>
              <option value=\"4\" $aPriceGroupC[4]>VK4</option>
              <option value=\"5\" $aPriceGroupC[5]>VK5</option>
              </select>
              </td>

              <td width=\"50%\" colspan=\"2\">&nbsp;</td>
              </tr>

              <tr><td colspan=\"4\" class=\"head\">&nbsp;Artikeleinstellungen</td></tr>
               
              <tr>
              <td width=\"35%\"><label for=\"activearticle\">Inaktive Artikel in CAO importieren</label></td>
              <td width=\"15%\"><select name=\"activearticle\" size=\"1\" id=\"activearticle\"><option value=\"0\">Nein</option><option value=\"1\" $sActiveArt>Ja</option></select></td>
                  
              <td width=\"35%\"><label for=\"articlematchcode\">Artikelsuchfeld nach CAO &uuml;bertragen als</label></td>
              <td width=\"15%\"><select name=\"articlematchcode\" size=\"1\" id=\"articlematchcode\">
              <option value=\"0\" $aArtMatch[0]>Artikelnummer</option>
              <option value=\"1\" $aArtMatch[1]>Artikelbezeichnung</option>
              <option value=\"2\" $aArtMatch[2]>Artikelnummer, Artikelbezeichnung</option>
              <option value=\"3\" $aArtMatch[3]>Artikelbezeichnung, Artikelnummer </option></select></td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"txtdescription\">CAO-Langtext gleich OXID-Artikeltext:</label></td>
              <td width=\"15%\"><select name=\"txtdescription\" size=\"1\" id=\"txtdescription\"><option value=\"0\">Nein</option><option value=\"1\" $sDescription>Ja</option></select></td>

              <td width=\"35%\"><label for=\"changedescription\">Artikelbeschreibungen bei Leer &uuml;berschreiben:</label></td>
              <td width=\"15%\"><select name=\"changedescription\" size=\"1\" id=\"changedescription\"><option value=\"0\">Nein</option><option value=\"1\" $sChangeDescription>Ja</option></select></td>
              </tr> 

              <tr>
              <td width=\"35%\"><label for=\"ekprice\">EK-Preis nach CAO &uuml;bertragen:</label></td>
              <td width=\"15%\"><select name=\"ekprice\" size=\"1\" id=\"ekprice\"><option value=\"0\">Nein</option><option value=\"1\" $sEkPrice>Ja</option></select></td>

              <td width=\"35%\"><label for=\"uvppricefield\">CAO VK-Preisfeld für UvP (0-5):</label></td>
              <td width=\"15%\"><input name=\"uvppricefield\" type=\"text\" maxlength=\"1\" size=\"6\" id=\"uvppricefield\" class=\"input\" value=\"" . $this->aConfigParam['UVPPRICEFIELD'] . "\"></td>
              </tr> 
              
              <tr>
              <td width=\"35%\"><label for=\"openstockorder\">Artikelmenge in Auftr&auml;gen ber&uuml;cksichtigen:</label></td>
              <td width=\"15%\"><select name=\"openstockorder\" size=\"1\" id=\"openstockorder\"><option value=\"0\">Nein</option><option value=\"1\" $sOpenStockOrder>Ja</option></select></td>
              
              <td width=\"35%\"><label for=\"openstockinvoice\">Artikelmenge in offenen Rechnungen ber&uuml;cksichtigen:</label></td>
              <td width=\"15%\"><select name=\"openstockinvoice\" size=\"1\" id=\"openstockinvoice\"><option value=\"0\">Nein</option><option value=\"1\" $sOpenSockInvoice>Ja</option></select></td>
              </tr>	           

              <tr>
              <td width=\"15%\"><label for=\"usebaseunit\">Basispreis &uuml;ber Mengeneinheit berechnen (Nein=Basiseinheit):</label></td>
              <td width=\"15%\"><select name=\"usebaseunit\" size=\"1\" id=\"usebaseunit\"><option value=\"0\">Nein</option><option value=\"1\" $sBaseUnit>Ja</option></select></td>
              
              <td width=\"35%\"><label for=\"scalepriceinpercent\">Staffelpreise als Prozentwert:</label></td>
              <td width=\"15%\"><select name=\"scalepriceinpercent\" size=\"1\" id=\"scalepriceinpercent\"><option value=\"0\">Nein</option><option value=\"1\" $sScalePrice>Ja</option></select></td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"articlesortincategorie\">Artikelsortierfeld in die Kategorien &uuml;bertragen:</label></td>
              <td width=\"15%\"><select name=\"articlesortincategorie\" size=\"1\" id=\"articlesortincategorie\"><option value=\"0\">Nein</option><option value=\"1\" $sArtSort>Ja</option></select></td>
              
              <td width=\"35%\"><label for=\"specialpriceinpercent\">Aktionspreise als Prozentwert:</label></td>
              <td width=\"15%\"><select name=\"specialpriceinpercent\" size=\"1\" id=\"specialpriceinpercent\"><option value=\"0\">Nein</option><option value=\"1\" $sSpecialPrice>Ja</option></select></td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"overwritepricegroup\">Artikelpreisgruppen (A ,B ,C)  &uuml;berschreiben:</label></td>
              <td width=\"15%\"><select name=\"overwritepricegroup\" size=\"1\" id=\"overwritepricegroup\"><option value=\"0\">Nein</option><option value=\"1\" $sOverWritePriceGroup>Ja</option></select></td>
              
              <td width=\"50%\" colspan=\"2\">&nbsp;</td>
              </tr>

              <tr>
              <td width=\"100%\" colspan=\"4\">&nbsp;</td>
              </tr>
              
              <tr>
              <td width=\"35%\"><label for=\"userfield\">CAO-Benutzerfelder zu Oxid-Datenbankfelder:</label>
              <textarea name=\"userfield\" id=\"userfield\" cols=\"45\" rows=\"5\">" . $sUserFields . "</textarea>
              </td>
              
              <td width=\"75%\" colspan=\"3\">
              <label>Eingabe: CAO-Benutzerfeldnummer => OXID-Datenbankfeldname<br />z.B. Artikelbenutzerfeld 5 und Feldname OXUSERFIELD<br />
              Eingabe: 5 => OXUSERFIELD<br/>Jede Zuweisung in einer eigenen Zeile. Maximal 10 Zuweisungen sind möglich (1 bis 10).</label>
              </td>
              </tr>
              
              <tr><td colspan=\"4\" class=\"head\">&nbsp;Bestelleinstellungen</td></tr>
              
              <tr>
              <td width=\"35%\"><label for=\"useordernr\">Oxid-Bestellnummer als SHOP_ORDERID in CAO nutzen:</label></td>
              <td width=\"15%\"><select name=\"useordernr\" size=\"1\" id=\"useordernr\"><option value=\"0\">Nein</option><option value=\"1\" $sUseOrdernr>Ja</option></select></td>
              <td width=\"50%\" colspan=\"2\">&nbsp;</td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"selectvariant\">Auswahl als freier Artikel in Bestellung:</label></td>
              <td width=\"15%\"><select name=\"selectvariant\" size=\"1\" id=\"selectvariant\"><option value=\"0\">Nein</option><option value=\"1\" $sSelectVariant>Ja</option></select></td>

              <td width=\"35%\"><label for=\"selectprice\">Auswahl mit Preis &uuml;bertragen:</label></td>
              <td width=\"15%\"><select name=\"selectprice\" size=\"1\" id=\"selectprice\"><option value=\"0\">Nein</option><option value=\"1\" $sSelectPrice>Ja</option></select></td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"selvarianttext\">Text der Auswahl in Artikeltext &uuml;bernehmen:</label></td>
              <td width=\"15%\"><select name=\"selvarianttext\" size=\"1\" id=\"selvarianttext\"><option value=\"0\">Nein</option><option value=\"1\" $sUseSelVariantText>Ja</option></select></td>
              
              <td width=\"50%\" colspan=\"2\">&nbsp;</td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"persparam\">Beschriftung des individualisierbaren Artikels in Artikeltext &uuml;bernehmen:</label></td>
              <td width=\"15%\"><select name=\"persparam\" size=\"1\" id=\"persparam\"><option value=\"0\">Nein</option><option value=\"1\" $sPersParam>Ja</option></select></td>
              
              <td width=\"35%\"><label for=\"freepersparam\">Beschriftung des individualisierbaren Artikels als freier Artikel in Bestellung:</label></td>
              <td width=\"15%\"><select name=\"freepersparam\" size=\"1\" id=\"freepersparam\"><option value=\"0\">Nein</option><option value=\"1\" $sFreePersParam>Ja</option></select></td>
              </tr>
              
              <tr>
              <td width=\"35%\"><label for=\"setinvoicenumber\">Rechnungsnummer von CAO &uuml;bernehmen:</label></td>
              <td width=\"15%\"><select name=\"setinvoicenumber\" size=\"1\" id=\"setinvoicenumber\"><option value=\"0\">Nein</option><option value=\"1\" $sSetInvoiceNumber>Ja</option></select></td>
              
              <td width=\"35%\"><label for=\"setdeadline\">Terminangabe aus Rechnung von CAO &uuml;bernehmen:</label></td>
              <td width=\"15%\"><select name=\"setdeadline\" size=\"1\" id=\"setdeadline\"><option value=\"0\">Nein</option><option value=\"1\" $sSetDeadLine>Ja</option></select></td>
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"sendaspaidate\">Versanddatum setzen wenn Bestellung bezahlt:</label></td>
              <td width=\"15%\"><select name=\"sendaspaidate\" size=\"1\" id=\"sendaspaidate\"><option value=\"0\">Nein</option><option value=\"1\" $sSetPaiDate>Ja</option></select></td>
                  
              <td width=\"35%\"><label for=\"useordergross\">Artikelpreis aus Bruttopreis berechnen:</label></td>
              <td width=\"15%\"><select name=\"useordergross\" size=\"1\" id=\"useordergross\"><option value=\"0\">Nein</option><option value=\"1\" $sUseOrderGross>Ja</option></select></td>
              </tr>    

              <tr>
              <td width=\"35%\"><label for=\"orderfrom\">Felder für <i>Bestellt durch</i> in CAO:</label></td>
              
              <td width=\"50%\" colspan=\"3\">
              <select name=\"orderaddinfo\" size=\"1\" id=\"orderaddinfo\">
              <option value=\"0\" $aOrderAddInfo[0]>-</option>
              <option value=\"1\" $aOrderAddInfo[1]>Zusatzinfo (Best.)</option>
              <option value=\"2\" $aOrderAddInfo[2]>Firmenname</option>
              <option value=\"3\" $aOrderAddInfo[3]>Vorname</option>
              <option value=\"4\" $aOrderAddInfo[4]>Nachname</option>
              </select>
              <select name=\"ordercompanyname\" size=\"1\" id=\"ordercompanyname\">
              <option value=\"0\" $aOrderCompanyName[0]>-</option>              
              <option value=\"1\" $aOrderCompanyName[1]>Zusatzinfo (Best.)</option>
              <option value=\"2\" $aOrderCompanyName[2]>Firmenname</option>
              <option value=\"3\" $aOrderCompanyName[3]>Vorname</option>
              <option value=\"4\" $aOrderCompanyName[4]>Nachname</option>
              </select>
              <select name=\"orderfirstname\" size=\"1\" id=\"orderfirstname\">
              <option value=\"0\" $aOrderFirstName[0]>-</option>
              <option value=\"1\" $aOrderFirstName[1]>Zusatzinfo (Best.)</option>    
              <option value=\"2\" $aOrderFirstName[2]>Firmenname</option>    
              <option value=\"3\" $aOrderFirstName[3]>Vorname</option>
              <option value=\"4\" $aOrderFirstName[4]>Nachname</option>
              </select>
              <select name=\"orderlastname\" size=\"1\" id=\"orderlastname\">
              <option value=\"0\" $aOrderLastName[0]>-</option>
              <option value=\"1\" $aOrderLastName[1]>Zusatzinfo (Best.)</option>              
              <option value=\"2\" $aOrderLastName[2]>Firmenname</option>    
              <option value=\"3\" $aOrderLastName[3]>Vorname</option>
              <option value=\"4\" $aOrderLastName[4]>Nachname</option>
              </select>
              </td>            
              </tr>

              <tr>
              <td width=\"35%\"><label for=\"orderinfo\">Felder für <i>Beleginfo</i> in CAO:</label></td>
              
              <td width=\"50%\" colspan=\"3\">
              <select name=\"orderremarkmsg\" size=\"1\" id=\"orderremarkmsg\">
              <option value=\"0\" $aOrderRemarkMsg[0]>-</option>
              <option value=\"1\" $aOrderRemarkMsg[1]>Bestellinfo</option>
              <option value=\"2\" $aOrderRemarkMsg[2]>Zusatzinfo (Best.)</option>
              <option value=\"3\" $aOrderRemarkMsg[3]>Zusatzinfo (Lief.)</option>
              <option value=\"4\" $aOrderRemarkMsg[4]>Tel. Bestellung</option>
              <option value=\"5\" $aOrderRemarkMsg[5]>Tel. Lieferung</option>
              </select>
              <select name=\"orderremarkinfobill\" size=\"1\" id=\"orderremarkinfobill\">
              <option value=\"0\" $aOrderRemarkInfoBill[0]>-</option>
              <option value=\"1\" $aOrderRemarkInfoBill[1]>Bestellinfo</option>
              <option value=\"2\" $aOrderRemarkInfoBill[2]>Zusatzinfo (Best.)</option>
              <option value=\"3\" $aOrderRemarkInfoBill[3]>Zusatzinfo (Lief.)</option>
              <option value=\"4\" $aOrderRemarkInfoBill[4]>Tel. Bestellung</option>
              <option value=\"5\" $aOrderRemarkInfoBill[5]>Tel. Lieferung</option>
              </select>
              <select name=\"orderremarkinfodel\" size=\"1\" id=\"orderremarkinfodel\">
              <option value=\"0\" $aOrderRemarkInfoDel[0]>-</option>
              <option value=\"1\" $aOrderRemarkInfoDel[1]>Bestellinfo</option>
              <option value=\"2\" $aOrderRemarkInfoDel[2]>Zusatzinfo (Best.)</option>
              <option value=\"3\" $aOrderRemarkInfoDel[3]>Zusatzinfo (Lief.)</option>
              <option value=\"4\" $aOrderRemarkInfoDel[4]>Tel. Bestellung</option>
              <option value=\"5\" $aOrderRemarkInfoDel[5]>Tel. Lieferung</option>
              </select>
              <select name=\"orderremarkfonbill\" size=\"1\" id=\"orderremarkfonbill\">
              <option value=\"0\" $aOrderRemarkFonBill[0]>-</option>
              <option value=\"1\" $aOrderRemarkFonBill[1]>Bestellinfo</option>
              <option value=\"2\" $aOrderRemarkFonBill[2]>Zusatzinfo (Best.)</option>
              <option value=\"3\" $aOrderRemarkFonBill[3]>Zusatzinfo (Lief.)</option>
              <option value=\"4\" $aOrderRemarkFonBill[4]>Tel. Bestellung</option>
              <option value=\"5\" $aOrderRemarkFonBill[5]>Tel. Lieferung</option>
              </select>
              <select name=\"orderremarkfondel\" size=\"1\" id=\"orderremarkfondel\">
              <option value=\"0\" $aOrderRemarkFonDel[0]>-</option>
              <option value=\"1\" $aOrderRemarkFonDel[1]>Bestellinfo</option>
              <option value=\"2\" $aOrderRemarkFonDel[2]>Zusatzinfo (Best.)</option>
              <option value=\"3\" $aOrderRemarkFonDel[3]>Zusatzinfo (Lief.)</option>
              <option value=\"4\" $aOrderRemarkFonDel[4]>Tel. Bestellung</option>
              <option value=\"5\" $aOrderRemarkFonDel[5]>Tel. Lieferung</option>
              </select>
              </td>            
              </tr>
              
              <tr>
              <td width=\"35%\"><label for=\"orderuserinfo\">Feld für Zusatzkundeninfo in CAO:</label></td>
              <td width=\"15%\">
              <select name=\"orderuseraddinfo\" size=\"1\" id=\"orderuseraddinfo\">
              <option value=\"0\" $aOrderUserAddInfo[0]>-</option>
              <option value=\"1\" $aOrderUserAddInfo[1]>Überschrift 1</option>
              <option value=\"2\" $aOrderUserAddInfo[2]>Überschrift 2</option>
              </select>
              </td>
              
              <td width=\"50%\" colspan=\"2\">&nbsp;</td>
              </tr>

              <tr><td colspan=\"4\" class=\"head\">&nbsp;Abweichende MwSt (nur in Verbindung mit Oxid-Modul und Modul in CAO-Faktura m&ouml;glich)</td></tr>
              <tr>
              <td width=\"35%\"><label for=\"useothertax\">Abweichende MwSt nutzen:</label></td>
              <td width=\"15%\">
              <select name=\"useothertax\" size=\"1\" id=\"useothertax\">
              <option value=\"0\">Nein</option>
              <option value=\"1\" $sUseOtherTax>Ja</option></select></td>
              </select>
              </td>
              <td width=\"35%\"><label for=\"standardtax\">Standard-MwSt.-Satz:</label></td>
              <td width=\"15%\"><input name=\"standardtax\" type=\"text\" maxlength=\"9\" size=\"6\" id=\"standardtax\" class=\"input\" value=\"" . $this->aConfigParam['STANDARDTAX'] . "\"></td>
              </tr>
              
              <tr>
              <td width=\"35%\"><label for=\"standardland\">Standard-Land ISO2 (z.B. DE):</label></td>
              <td width=\"15%\"><input name=\"standardland\" type=\"text\" maxlength=\"2\" size=\"2\" id=\"standardland\" class=\"input\" value=\"" . $this->aConfigParam['STANDARDLAND'] . "\"></td>
              <td width=\"35%\"><label for=\"reducedtax\">Erm&auml;&szlig;igter-MwSt.-Satz:</label></td>
              <td width=\"15%\"><input name=\"reducedtax\" type=\"text\" maxlength=\"9\" size=\"6\" id=\"reducedtax\" class=\"input\" value=\"" . $this->aConfigParam['REDUCEDTAX'] . "\"></td>
              </tr>
              
              <tr>
              <td colspan=\"4\"><label><b>HINWEIS: Falsch eingegebene Daten k&ouml;nnen in CAO-Faktura zu Fehlern f&uuml;hren!</b></label></td>
              </tr>

              </table>
       ";

        $sBody .= "<p align=\"center\"><a href=\"" . $_SERVER['PHP_SELF'] . "?" . session_name() . "=" . session_id() . "&sSID=" . md5(Config::$sSID) . "\">> ZUR&Uuml;CK <</a> <input type=\"submit\" name=\"Submit\" value=\"Speichern\" class=\"button\"></p></form>";
        $this->sendHTML($sBody);
    }

    private function _GetUserfields($aValue) {
        $sUserfield = "";
        $aUserfield = array();
        $aUserfield = unserialize($aValue[0]);

        if ($aUserfield) {
            if (!is_array($aUserfield))
                $sUserfield = 'Feldzuordnung kann nicht geladen werden!';
            else {
                foreach ($aUserfield as $iKey => $sVal) {
                    $sUserfield .= $iKey . " => " . $sVal . "\n";
                }
                $sUserfield = substr($sUserfield, 0, -1);
            }
        }
        return $sUserfield;
    }

}
