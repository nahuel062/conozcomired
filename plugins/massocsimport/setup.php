<?php
/*
 * @version $Id: setup.php 146 2012-07-04 07:01:34Z remi $
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
define("PLUGIN_MASSOCSIMPORT_STATE_STARTED", 1);
define("PLUGIN_MASSOCSIMPORT_STATE_RUNNING", 2);
define("PLUGIN_MASSOCSIMPORT_STATE_FINISHED", 3);

define("PLUGIN_MASSOCSIMPORT_LOCKFILE", GLPI_LOCK_DIR . "/massocsimport.lock");

function plugin_init_massocsimport() {
   global $PLUGIN_HOOKS, $CFG_GLPI, $LANG;

   $PLUGIN_HOOKS['csrf_compliant']['massocsimport'] = true;

   $plugin = new Plugin;

   // Params : plugin name - string type - id - Array of attributes
   Plugin::registerClass('PluginMassocsimportNotimported',
                         array ('massiveaction_noupdate_types' => true,
                                'massiveaction_nodelete_types' => true,
                                'notificationtemplates_types'  => true));

   Plugin::registerClass('PluginMassocsimportDetail',
                         array ('massiveaction_noupdate_types' => true,
                                'massiveaction_nodelete_types' => true));

   Plugin::registerClass('PluginMassocsimportThread');

   Plugin::registerClass('PluginMassocsimportConfig');

   Plugin::registerClass('PluginMassocsimportServer');


   $PLUGIN_HOOKS['change_profile']['massocsimport'] = 'plugin_massocsimport_changeprofile';

   if (Session::haveRight("ocsng", "w")) {
      // Config page
      if ($plugin->isActivated("massocsimport")) {
         $PLUGIN_HOOKS['config_page']['massocsimport'] = 'front/config.form.php';
      }
      $PLUGIN_HOOKS['use_massive_action']['massocsimport'] = 1;
      if (Session::haveRecursiveAccessToEntity(0)) {
      $image = "<img src='".$CFG_GLPI["root_doc"]."/pics/stats_item.png' title='".
                $LANG["massocsimport"]["common"][1]."' alt='".$LANG["massocsimport"]["common"][1]."'>";
         $PLUGIN_HOOKS['submenu_entry']['massocsimport'][$image] = 'front/thread.php';
      }
      $image = "<img src='".$CFG_GLPI["root_doc"]."/pics/puce-delete2.png' title='".
                $LANG["massocsimport"]["common"][18]."' alt='".$LANG["massocsimport"]["common"][18]."'>";
      $PLUGIN_HOOKS['submenu_entry']['massocsimport'][$image]
                   = 'front/notimported.php';
      $image = "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv.png' title='".
                $LANG["massocsimport"]["common"][21]."' alt='".$LANG["massocsimport"]["common"][21]."'>";
      $PLUGIN_HOOKS['submenu_entry']['massocsimport'][$image]
                   = 'front/detail.php';

      if (Session::haveRight("logs", "r")) {
         $PLUGIN_HOOKS['menu_entry']['massocsimport'] = 'front/thread.php';
         if (Session::haveRecursiveAccessToEntity(0)) {
            $PLUGIN_HOOKS['submenu_entry']['massocsimport']['config']
                         = 'front/config.form.php';
         }
      }
   }
   $PLUGIN_HOOKS['redirect_page']['massocsimport']    = "front/notimported.form.php";
   $PLUGIN_HOOKS['headings']['massocsimport']         = 'plugin_get_headings_massocsimport';
   $PLUGIN_HOOKS['headings_action']['massocsimport']  = 'plugin_headings_actions_massocsimport';

}


function plugin_version_massocsimport() {
   global $LANG;

   return array ('name'             => $LANG["massocsimport"]["name"][1],
                 'minGlpiVersion'   => '0.83.3',
                 'version'          => '1.6.1',
                 'author'           => 'Remi Collet, Nelly Mahu-Lasson, Walid Nouh',
                 'license'          => 'GPLv2+',
                 'homepage'         => 'https://forge.indepnet.net/projects/massocsimport');
}


function plugin_massocsimport_check_prerequisites() {

   if (version_compare(GLPI_VERSION,'0.83.3','lt') || version_compare(GLPI_VERSION,'0.84','ge')) {
      echo "This plugin requires GLPI >= 0.83.3 and < 0.84";
      return false;
   }
   return true;
}


function plugin_massocsimport_check_config() {
   return true;
}
?>