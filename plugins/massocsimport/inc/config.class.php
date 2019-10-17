<?php
/*
 * @version $Id: config.class.php 146 2012-07-04 07:01:34Z remi $
 -------------------------------------------------------------------------
 massocsimport - Massive OCS import plugin for GLPI
 Copyright (C) 2003-2011 by the massocsimport Development Team.

 https://forge.indepnet.net/projects/massocsimport
 -------------------------------------------------------------------------

 LICENSE

 This file is part of massocsimport.

 massocsimport is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 massocsimport is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with massocsimport. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

class PluginMassocsimportConfig extends CommonDBTM {

   public $displaylist = false;


   static function getTypeName() {
      global $LANG;

      return $LANG["massocsimport"]["config"][1];
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);
      return $ong;
   }


   function canCreate() {
      return Session::haveRight('config', 'w');
   }


   function canView() {
      return Session::haveRight('config', 'r');
   }


   function showConfigForm($target) {
      global $LANG;

      $this->getFromDB(1);
      $this->showTabs();
      $this->showFormHeader();

      echo "<tr class='tab_bg_1'>";
      echo "<td class='right' colspan='2'> " .$LANG["massocsimport"]["config"][21]. " </td>";
      echo "<td colspan='2'>&nbsp;&nbsp;&nbsp;";
      Dropdown::showFromArray("ocsservers_id",$this->getAllOcsServers(),
                              array('value' => $this->fields["ocsservers_id"]));
      echo "</td></tr>";

      echo "<tr><th colspan='4'>" . $LANG["massocsimport"]["config"][16]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .$LANG["massocsimport"]["config"][5] . " </td><td>";
      Dropdown::showYesNo("is_displayempty", $this->fields["is_displayempty"]);
      echo "</td>";
      echo "<td rowspan='3' class='middle right'> " .$LANG['common'][25]."</td>";
      echo "<td class='center middle' rowspan='3'>";
      echo "<textarea cols='40' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .$LANG["massocsimport"]["setup"][3] . " </td><td>";
      Dropdown::showYesNo('allow_ocs_update',$this->fields['allow_ocs_update']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .$LANG["massocsimport"]["config"][14] . " </td><td>";
      Html::autocompletionTextField($this,"delay_refresh", array('size' => 5));
      echo "&nbsp;".$LANG["massocsimport"]["time"][3]."</td>";
      echo "</tr>";

      $this->showFormButtons(array('canedit' => true, 'candel' => false));
      $this->addDivForTabs();
      return true;
   }


   static function showScriptLock() {
      global $LANG;

      $config = new self();
      echo "<div class='center'>";
      echo "<form name='lock' action=\"".$_SERVER['HTTP_REFERER']."\" method='post'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th>&nbsp;" . $LANG["massocsimport"]["config"][7] ." ".
             $LANG["massocsimport"]["config"][8]."&nbsp;</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>";
      $status = $config->isScriptLocked();
      if (!$status) {
         echo $LANG["massocsimport"]["config"][9]."&nbsp;<img src='../pics/export.png'>";
      } else {
         echo $LANG["massocsimport"]["config"][10]."&nbsp;<img src='../pics/ok2.png'>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
      echo "<input type='submit' name='".(!$status?"soft_lock":"soft_unlock")."' class='submit' ".
            "value='".(!$status?$LANG["massocsimport"]["config"][17]
                               :$LANG["massocsimport"]["config"][18])."'>";
      echo "</td/></tr/></table><br>";
      Html::closeForm();
      echo "</div>";

   }


   static function isScriptLocked() {
      return file_exists(PLUGIN_MASSOCSIMPORT_LOCKFILE);
   }


   function setScriptLock() {

      $fp = fopen(PLUGIN_MASSOCSIMPORT_LOCKFILE, "w+");
      fclose($fp);
   }


   function removeScriptLock() {

      if (file_exists(PLUGIN_MASSOCSIMPORT_LOCKFILE)) {
         unlink(PLUGIN_MASSOCSIMPORT_LOCKFILE);
      }
   }


   function getAllOcsServers() {
      global $DB, $LANG;

      $servers[-1] = $LANG["massocsimport"]["config"][22];
      $sql = "SELECT `id`, `name`
              FROM `glpi_ocsservers`";
      $result = $DB->query($sql);

      while ($conf = $DB->fetch_array($result)) {
         $servers[$conf["id"]] = $conf["name"];
      }

      return $servers;
   }


   function showOcsReportsConsole($id) {
      global $LANG;

      $ocsconfig = OcsServer::getConfig($id);

      echo "<div class='center'>";
      if ($ocsconfig["ocs_url"] != '') {
         echo "<iframe src='" . $ocsconfig["ocs_url"] . "/index.php?multi=4' width='95%' height='650' >";
      }
      echo "</div>";
   }


   static function canUpdateOCS() {

      $config = new self();
      $config->getFromDB(1);
      return $config->fields['allow_ocs_update'];
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      NotificationEvent::debugEvent(new PluginMassocsimportNotimported(),
                                    array('entities_id' => 0,
                                          'notimported' => array()));
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            self::showScriptLock();
            break;
      }
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               return $LANG['title'][26];
         }
      }
      return '';
   }
}
?>