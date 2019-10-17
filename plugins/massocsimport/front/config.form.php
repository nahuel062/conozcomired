<?php
/*
 * @version $Id: config.form.php 135 2011-11-08 11:34:36Z remi $
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
if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}

include (GLPI_ROOT . "/inc/includes.php");

Plugin::load('massocsimport', true).

Session::checkRight("ocsng", "w");


function configHeader() {
   global $LANG;

   echo "<div class='center'>";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='2'>" . $LANG["massocsimport"]["config"][1] . "</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center'>";
   echo "<a href='https://forge.indepnet.net/projects/massocsimport/wiki' target='_blank'>" .
          $LANG["massocsimport"]["setup"][8] . "</a></td></tr>";
}


$config = new PluginMassocsimportConfig();

if (isset ($_POST["update"])) {
   $config->update($_POST);
   Html::redirect($_SERVER['PHP_SELF']);
}
if (isset ($_POST["soft_lock"])) {
   $config->setScriptLock();
}
if (isset ($_POST["soft_unlock"])) {
   $config->removeScriptLock();
}

$plugin = new Plugin();
if ($plugin->isInstalled("massocsimport") && $plugin->isActivated("massocsimport")) {
   Html::header($LANG["massocsimport"]["config"][1], $_SERVER["PHP_SELF"], "config", "plugins");

   if (!$CFG_GLPI["use_ocs_mode"]) {
      configHeader();
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["massocsimport"]["setup"][0];
      echo "<a href='".Toolbox::getItemTypeFormURL("Config")."?forcetab=3'>".
             $LANG["massocsimport"]["setup"][2]."</a></td></tr>";
      echo "</table></div>";

   } else {
      if (!countElementsInTable("glpi_ocsservers")) {
         configHeader();
         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["massocsimport"]["setup"][1];
         echo "<a href='".Toolbox::getItemTypeSearchURL("OcsServer")."'>" .
                $LANG["massocsimport"]["setup"][2]."</a></td></tr>";
         echo "</table></div>";
      } else {
         $config->showConfigForm($_SERVER['PHP_SELF']);
      }
   }

} else {
   Html::header($LANG["common"][12], $_SERVER['PHP_SELF'], "config", "plugins");
   echo "<div class='center'><br><br>";
   echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\" alt=\"warning\"><br><br>";
   echo "<b>Please activate the plugin</b></div>";
}

Html::footer();
?>
