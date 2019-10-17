<?php
/*
 * @version $Id: hook.php 135 2011-11-08 11:34:36Z remi $
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

function plugin_massocsimport_changeprofile() {

   $plugin = new Plugin();
   if ($plugin->isActivated("massocsimport")) {
      if (Session::haveRight("logs", "r") || Session::haveRight("ocsng", "w")) {
         $PLUGIN_HOOKS['menu_entry']['massocsimport'] = true;
      } else {
         $PLUGIN_HOOKS['menu_entry']['massocsimport'] = false;
      }
   }
}

function plugin_massocsimport_MassiveActions($type) {
   global $LANG;

   switch ($type) {
      case 'PluginMassocsimportNotimported' :
         $actions = array ();
         $actions["plugin_massocsimport_replayrules"] = $LANG["massocsimport"]["notimported"][3];
         $actions["plugin_massocsimport_import"]      = $LANG["massocsimport"]["display"][1];
         if (isset ($_POST['target'])
            && $_POST['target'] == Toolbox::getItemTypeFormURL('PluginMassocsimportNotimported')) {
            $actions["plugin_massocsimport_link"]     = $LANG["massocsimport"]["display"][6];
         }
         $plugin = new Plugin;
         if ($plugin->isActivated("uninstall")) {
            $actions["plugin_massocsimport_delete"]   = $LANG["massocsimport"]["display"][5];
         }

         return $actions;
   }
   return array ();
}

function plugin_massocsimport_addWhere($link,$nott,$type,$ID,$val) {

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];

   $SEARCH = Search::makeTextSearch($val,$nott);
    switch ($table.".".$field) {
         case "glpi_plugin_massocsimport_details.action" :
               return $link." `$table`.`$field` = '$val' ";

         default:
            return "";
    }
   return "";
}


function plugin_massocsimport_MassiveActionsDisplay($options=array()) {
   global $LANG;

   switch ($options['itemtype']) {
      case 'PluginMassocsimportNotimported' :
         switch ($options['action']) {
            case "plugin_massocsimport_import" :
               Dropdown::show('Entity', array('name' => 'entity'));
               break;

            case "plugin_massocsimport_link" :
               Dropdown::show('Computer', array('name' => 'computers_id'));
               break;

            case "plugin_massocsimport_replayrules" :
            case "plugin_massocsimport_delete" :
               break;
         }
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' " .
              "value='".$LANG['buttons'][2]."'>";
         break;
   }
   return "";
}


function plugin_massocsimport_MassiveActionsProcess($data) {
   global $CFG_GLPI, $LANG;

   $notimport = new PluginMassocsimportNotimported();
   switch ($data["action"]) {
      case "plugin_massocsimport_import" :
         foreach ($data["item"] as $key => $val) {
            if ($val == 1) {
               PluginMassocsimportNotimported::computerImport(array('id'    => $key,
                                                                    'force' => true,
                                                                    'entity'=>$data['entity']));
            }
         }
         break;

      case "plugin_massocsimport_replayrules" :
         foreach ($data["item"] as $key => $val) {
            if ($val == 1) {
               PluginMassocsimportNotimported::computerImport(array('id'=>$key));
            }
         }
         break;

      case "plugin_massocsimport_delete" :
         $plugin = new Plugin();
         if ($plugin->isActivated("uninstall")) {
            foreach ($data["item"] as $key => $val) {
               if ($val == 1) {
                  $notimport->deleteNotImportedComputer($key);
               }
            }
         }
         break;
   }
}


function plugin_massocsimport_addSelect($type, $id, $num) {

   $searchopt = &Search::getOptions($type);

   $table = $searchopt[$id]["table"];
   $field = $searchopt[$id]["field"];

   $out = "`$table`.`$field` AS ITEM_$num,
               `$table`.`ocsid` AS ocsid,
               `$table`.`ocsservers_id` AS ocsservers_id, ";

   if ($num == 0) {
      switch ($type) {
         case 'PluginMassocsimportNotimported' :
            return $out;

         case 'PluginMassocsimportDetail' :
            $out .= "`$table`.`plugin_massocsimport_threads_id`,
                     `$table`.`threadid`, ";
            return $out;
      }
      return "";
   }
}


function plugin_massocsimport_giveItem($type, $id, $data, $num) {
   global $CFG_GLPI, $DB, $LANG;

   $searchopt = &Search::getOptions($type);

   $table = $searchopt[$id]["table"];
   $field = $searchopt[$id]["field"];

   switch ("$table.$field") {
      case "glpi_plugin_massocsimport_details.action" :
         $detail = new PluginMassocsimportDetail();
         return $detail->giveActionNameByActionID($data["ITEM_$num"]);

      case "glpi_plugin_massocsimport_notimported.reason" :
         return PluginMassocsimportNotimported::getReason($data["ITEM_$num"]);

      case "glpi_plugin_massocsimport_details.rules_id":
         $detail = new PluginMassocsimportDetail();
         $detail->getFromDB($data['id']);
         return PluginMassocsimportNotimported::getRuleMatchedMessage($detail->fields['rules_id']);

      default:
        return "";
   }
   return '';
}

function plugin_massocsimport_searchOptionsValues($params = array()) {
   switch($params['searchoption']['field']) {
      case "action":
         PluginMassocsimportDetail::showActions($params['name'],$params['value']);
         return true;
   }
   return false;
}


function plugin_headings_actions_massocsimport($item) {

   switch (get_Class($item)) {
      case 'OcsServer' :
         return array (1 => "plugin_headings_massocsimport");
   }
   return false;
}


function plugin_get_headings_massocsimport($item, $withtemplate) {
   return false;
}


function plugin_headings_massocsimport($item, $withtemplate = 0) {

   switch (get_class($item)) {
      case 'OcsServer' :
         $conf = new PluginMassocsimportConfig();
         $conf->showOcsReportsConsole($item->getField('id'));
         break;
   }
}


function plugin_massocsimport_install() {
   global $DB;

   //Upgrade process if needed
   if (TableExists("glpi_plugin_mass_ocs_import")) { //1.1 ou 1.2
      if (!FieldExists('glpi_plugin_mass_ocs_import_config','warn_if_not_imported')) { //1.1
         plugin_massocsimport_upgrade11to12();
      }
   }
   if (TableExists("glpi_plugin_mass_ocs_import")) { //1.2 because if before
      plugin_massocsimport_upgrade121to13();
   }
   if (TableExists("glpi_plugin_massocsimport")) { //1.3 ou 1.4
      if (FieldExists('glpi_plugin_massocsimport','ID')) { //1.3
         plugin_massocsimport_upgrade13to14();
      }
   }
   if (TableExists('glpi_plugin_massocsimport_threads')
         && !FieldExists('glpi_plugin_massocsimport_threads','not_unique_machines_number')) {
         plugin_massocsimport_upgrade14to15();
   }
   if (!TableExists('glpi_plugin_massocsimport_threads')) { //not installed

      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_massocsimport_threads` (
                  `id` int(11) NOT NULL auto_increment,
                  `threadid` int(11) NOT NULL default '0',
                  `entities_id` int(11) NOT NULL DEFAULT 0,
                  `rules_id` TEXT,
                  `start_time` datetime default NULL,
                  `end_time` datetime default NULL,
                  `status` int(11) NOT NULL default '0',
                  `error_msg` text NOT NULL,
                  `imported_machines_number` int(11) NOT NULL default '0',
                  `synchronized_machines_number` int(11) NOT NULL default '0',
                  `failed_rules_machines_number` int(11) NOT NULL default '0',
                  `linked_machines_number` int(11) NOT NULL default '0',
                  `notupdated_machines_number` int(11) NOT NULL default '0',
                  `not_unique_machines_number` int(11) NOT NULL default '0',
                  `link_refused_machines_number` int(11) NOT NULL default '0',
                  `total_number_machines` int(11) NOT NULL default '0',
                  `ocsservers_id` int(11) NOT NULL default '1',
                  `processid` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  KEY `end_time` (`end_time`),
                  KEY `process_thread` (`processid`,`threadid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());


      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_massocsimport_configs` (
                  `id` int(11) NOT NULL auto_increment,
                  `thread_log_frequency` int(11) NOT NULL default '10',
                  `is_displayempty` int(1) NOT NULL default '1',
                  `import_limit` int(11) NOT NULL default '0',
                  `ocsservers_id` int(11) NOT NULL default '-1',
                  `delay_refresh` int(11) NOT NULL default '0',
                  `allow_ocs_update` tinyint(1) NOT NULL default '0',
                  `comment` text,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());

      $query = "INSERT INTO `glpi_plugin_massocsimport_configs`
                     (`id`,`thread_log_frequency`,`is_displayempty`,`import_limit`,`ocsservers_id`)
                VALUES (1, 2, 1, 0,-1);";
      $DB->query($query) or die($DB->error());


      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_massocsimport_details` (
                  `id` int(11) NOT NULL auto_increment,
                  `entities_id` int(11) NOT NULL default '0',
                  `plugin_massocsimport_threads_id` int(11) NOT NULL default '0',
                  `rules_id` TEXT,
                  `threadid` int(11) NOT NULL default '0',
                  `ocsid` int(11) NOT NULL default '0',
                  `computers_id` int(11) NOT NULL default '0',
                  `action` int(11) NOT NULL default '0',
                  `process_time` datetime DEFAULT NULL,
                  `ocsservers_id` int(11) NOT NULL default '1',
                  PRIMARY KEY (`id`),
                  KEY `end_time` (`process_time`),
                  KEY `process_thread` (`plugin_massocsimport_threads_id`,`threadid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die($DB->error());

      $query = "INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`)
                VALUES ('PluginMassocsimportNotimported', 2, 1, 0),
                       ('PluginMassocsimportNotimported', 3, 2, 0),
                       ('PluginMassocsimportNotimported', 4, 3, 0),
                       ('PluginMassocsimportNotimported', 5, 4, 0),
                       ('PluginMassocsimportNotimported', 6, 5, 0),
                       ('PluginMassocsimportNotimported', 7, 6, 0),
                       ('PluginMassocsimportNotimported', 8, 7, 0),
                       ('PluginMassocsimportNotimported', 9, 8, 0),
                       ('PluginMassocsimportNotimported', 10, 9, 0),
                       ('PluginMassocsimportDetail', 5, 1, 0),
                       ('PluginMassocsimportDetail', 2, 2, 0),
                       ('PluginMassocsimportDetail', 3, 3, 0),
                       ('PluginMassocsimportDetail', 4, 4, 0),
                       ('PluginMassocsimportDetail', 6, 5, 0),
                       ('PluginMassocsimportDetail', 80, 6, 0)";
      $DB->query($query) or die($DB->error());


      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_massocsimport_notimported` (
                  `id` INT( 11 ) NOT NULL  auto_increment,
                  `entities_id` int(11) NOT NULL default '0',
                  `rules_id` TEXT,
                  `comment` text NULL,
                  `ocsid` INT( 11 ) NOT NULL DEFAULT '0',
                  `ocsservers_id` INT( 11 ) NOT NULL ,
                  `ocs_deviceid` VARCHAR( 255 ) NOT NULL ,
                  `useragent` VARCHAR( 255 ) NOT NULL ,
                  `tag` VARCHAR( 255 ) NOT NULL ,
                  `serial` VARCHAR( 255 ) NOT NULL ,
                  `name` VARCHAR( 255 ) NOT NULL ,
                  `ipaddr` VARCHAR( 255 ) NOT NULL ,
                  `domain` VARCHAR( 255 ) NOT NULL ,
                  `last_inventory` DATETIME ,
                  `reason` INT( 11 ) NOT NULL ,
                  PRIMARY KEY ( `id` ),
                  UNIQUE KEY `ocs_id` (`ocsservers_id`,`ocsid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());


      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_massocsimport_servers` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `ocsservers_id` int(11) NOT NULL DEFAULT '0',
                  `max_ocsid` int(11) DEFAULT NULL,
                  `max_glpidate` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `ocsservers_id` (`ocsservers_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());

      $query = "SELECT id " .
               "FROM `glpi_notificationtemplates` " .
               "WHERE `itemtype`='PluginMassocsimportNotimported'";
      $result=$DB->query($query);
      if (!$DB->numrows($result)) {

         //Add template
         $query = "INSERT INTO `glpi_notificationtemplates` " .
                  "VALUES(NULL, 'Computers not imported', 'PluginMassocsimportNotimported', NOW(), '', NULL);";
         $DB->query($query) or die($DB->error());
         $templates_id = $DB->insert_id();
         $query = "INSERT INTO `glpi_notificationtemplatetranslations` " .
                  "VALUES(NULL, $templates_id, '', '##lang.notimported.action## : ##notimported.entity##'," .
                  " '\r\n\n##lang.notimported.action## :&#160;##notimported.entity##\n\n" .
                  "##FOREACHnotimported##&#160;\n##lang.notimported.reason## : ##notimported.reason##\n" .
                  "##lang.notimported.name## : ##notimported.name##\n" .
                  "##lang.notimported.deviceid## : ##notimported.deviceid##\n" .
                  "##lang.notimported.tag## : ##notimported.tag##\n##lang.notimported.serial## : ##notimported.serial## \r\n\n" .
                  " ##notimported.url## \n##ENDFOREACHnotimported## \r\n', '&lt;p&gt;##lang.notimported.action## :&#160;##notimported.entity##&lt;br /&gt;&lt;br /&gt;" .
                  "##FOREACHnotimported##&#160;&lt;br /&gt;##lang.notimported.reason## : ##notimported.reason##&lt;br /&gt;" .
                  "##lang.notimported.name## : ##notimported.name##&lt;br /&gt;" .
                  "##lang.notimported.deviceid## : ##notimported.deviceid##&lt;br /&gt;" .
                  "##lang.notimported.tag## : ##notimported.tag##&lt;br /&gt;" .
                  "##lang.notimported.serial## : ##notimported.serial##&lt;/p&gt;\r\n&lt;p&gt;&lt;a href=\"##notimported.url##\"&gt;" .
                  "##notimported.url##&lt;/a&gt;&lt;br /&gt;##ENDFOREACHnotimported##&lt;/p&gt;');";
         $DB->query($query) or die($DB->error());

         $query = "INSERT INTO `glpi_notifications`
                   VALUES (NULL, 'Computers not imported', 0, 'PluginMassocsimportNotimported', 'not_imported',
                           'mail',".$templates_id.", '', 1, 1, NOW());";
         $DB->query($query) or die($DB->error());
      }
   }

   $cron = new CronTask;
   if (!$cron->getFromDBbyName('PluginMassocsimportThread','CleanOldThreads')) {
      // creation du cron - param = duree de conservation
      CronTask::Register('PluginMassocsimportThread', 'CleanOldThreads', HOUR_TIMESTAMP,
                         array('param' => 24));
   }

   return true;
}


function plugin_massocsimport_upgrade11to12() {
   global $DB;


   $migration = new Migration(12);

   // plugin tables
   if (!TableExists("glpi_plugin_mass_ocs_import_config")) {
      $query = "CREATE TABLE `glpi_plugin_mass_ocs_import_config` (
                  `ID` int(11) NOT NULL,
                  `enable_logging` int(1) NOT NULL default '1',
                  `thread_log_frequency` int(4) NOT NULL default '10',
                  `display_empty` int(1) NOT NULL default '1',
                  `delete_frequency` int(4) NOT NULL default '0',
                  `import_limit` int(11) NOT NULL default '0',
                  `default_ocs_server` int(11) NOT NULL default '-1',
                  `delay_refresh` varchar(4) NOT NULL default '0',
                  `delete_empty_frequency` int(4) NOT NULL default '0',
                  PRIMARY KEY  (`ID`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());

      $query = "INSERT INTO `glpi_plugin_mass_ocs_import_config`
                     (`ID`, `enable_logging`, `thread_log_frequency`, `display_empty`,
                      `delete_frequency`, `delete_empty_frequency`, `import_limit`,
                      `default_ocs_server` )
                VALUES (1, 1, 5, 1, 2, 2, 0,-1)";

      $DB->query($query) or die($DB->error());
   }

   $migration->addField("glpi_plugin_mass_ocs_import_config", "warn_if_not_imported", 'integer');
   $migration->addField("glpi_plugin_mass_ocs_import_config", "not_imported_threshold", 'integer');

   $migration->executeMigration();
}


function plugin_massocsimport_upgrade121to13() {
   global $DB;

   $migration = new Migration(13);

   if (TableExists("glpi_plugin_mass_ocs_import_config")) {
      $tables = array (
            "glpi_plugin_massocsimport_servers"      => "glpi_plugin_mass_ocs_import_servers",
            "glpi_plugin_massocsimport"              => "glpi_plugin_mass_ocs_import",
            "glpi_plugin_massocsimport_config"       => "glpi_plugin_mass_ocs_import_config",
            "glpi_plugin_massocsimport_not_imported" => "glpi_plugin_mass_ocs_import_not_imported");

      foreach ($tables as $new => $old) {
         if (!TableExists($new)) {
            $query = "RENAME TABLE `$old`
                      TO `$new`;";
            $DB->query($query) or die($DB->error());
         }
      }

      $migration->changeField("glpi_plugin_massocsimport", "process_id", "process_id",
                              "BIGINT( 20 ) NOT NULL DEFAULT '0'");

      $migration->addField("glpi_plugin_massocsimport_config", "comments", 'text');

      $migration->addField("glpi_plugin_massocsimport", "noupdate_machines_number", 'integer');

      if (!TableExists("glpi_plugin_massocsimport_details")) {
         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_massocsimport_details` (
                     `ID` int(11) NOT NULL auto_increment,
                     `process_id` bigint(10) NOT NULL default '0',
                     `thread_id` int(4) NOT NULL default '0',
                     `ocs_id` int(11) NOT NULL default '0',
                     `glpi_id` int(11) NOT NULL default '0',
                     `action` int(11) NOT NULL default '0',
                     `process_time` datetime DEFAULT NULL,
                     `ocs_server_id` int(4) NOT NULL default '1',
                     PRIMARY KEY  (`ID`),
                     KEY `end_time` (`process_time`)
                   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      }

      //Add fields to the default view
      $query = "INSERT INTO `glpi_displayprefs` (`itemtype`, `num`, `rank`, `users_id`)
                VALUES (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 2, 1, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 3, 2, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 4, 3, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 5, 4, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 6, 5, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 7, 6, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 8, 7, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 8, 7, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 10, 9, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 5, 1, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 2, 2, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 3, 3, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 4, 4, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 6, 5, 0)";
      $DB->query($query);// or die($DB->error());

      $drop_fields = array (//Was not used, debug only...
                            "glpi_plugin_massocsimport_config" => "warn_if_not_imported",
                            "glpi_plugin_massocsimport_config" => "not_imported_threshold",
                            //Logging must always be enable !
                            "glpi_plugin_massocsimport_config" => "enable_logging",
                            "glpi_plugin_massocsimport_config" => "delete_empty_frequency");

      foreach ($drop_fields as $table => $field) {
         $migration->dropField($table, $field);
      }
   }

   $migration->executeMigration();
}


function plugin_massocsimport_upgrade13to14() {

   $migration = new Migration(14);

   $migration->renameTable("glpi_plugin_massocsimport", "glpi_plugin_massocsimport_threads");
   $migration->changeField("glpi_plugin_massocsimport_threads", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_threads", "thread_id", "threadid", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_threads", "status", "status", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_threads", "ocs_server_id", "ocsservers_id",
                           'integer', array('value' => 1));
   $migration->changeField("glpi_plugin_massocsimport_threads", "process_id", "processid",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_threads", "noupdate_machines_number",
                           "notupdated_machines_number", 'integer');
   $migration->addKey("glpi_plugin_massocsimport_threads", array("processid", "threadid"),
                      "process_thread");


   $migration->renameTable("glpi_plugin_massocsimport_config", "glpi_plugin_massocsimport_configs");
   $migration->dropField("glpi_plugin_massocsimport_configs", "delete_frequency");
   $migration->dropField("glpi_plugin_massocsimport_configs", "enable_logging");
   $migration->dropField("glpi_plugin_massocsimport_configs", "delete_empty_frequency");
   $migration->dropField("glpi_plugin_massocsimport_configs", "warn_if_not_imported");
   $migration->dropField("glpi_plugin_massocsimport_configs", "not_imported_threshold");
   $migration->changeField("glpi_plugin_massocsimport_configs", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_configs", "thread_log_frequency",
                           "thread_log_frequency", 'integer', array('value' => 10));
   $migration->changeField("glpi_plugin_massocsimport_configs", "display_empty", "is_displayempty",
                           'int(1) NOT NULL default 1');
   $migration->changeField("glpi_plugin_massocsimport_configs", "default_ocs_server",
                           "ocsservers_id", 'integer', array('value' => -1));
   $migration->changeField("glpi_plugin_massocsimport_configs", "delay_refresh", "delay_refresh",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_configs", "comments", "comment", 'text');


   $migration->changeField("glpi_plugin_massocsimport_details", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_details", "process_id",
                           "plugin_massocsimport_threads_id", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_details", "thread_id", "threadid", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_details", "ocs_id", "ocsid", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_details", "glpi_id", "computers_id",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_details", "ocs_server_id",
                           "ocsservers_id", 'integer', array('value' => 1));
   $migration->addKey("glpi_plugin_massocsimport_details",
                      array("plugin_massocsimport_threads_id", "threadid"), "process_thread");


   $migration->renameTable("glpi_plugin_massocsimport_not_imported",
                           "glpi_plugin_massocsimport_notimported");
   $migration->changeField("glpi_plugin_massocsimport_notimported", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_notimported", "ocs_id", "ocsid", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_notimported", "ocs_server_id", "ocsservers_id",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_notimported", "deviceid", "ocs_deviceid",
                           'string');


   $migration->changeField("glpi_plugin_massocsimport_servers", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_servers", "ocs_server_id", "ocsservers_id",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_servers", "max_ocs_id", "max_ocsid",
                           'int(11) DEFAULT NULL');
   $migration->changeField("glpi_plugin_massocsimport_servers", "max_glpi_date", "max_glpidate",
                           'datetime DEFAULT NULL');

   $migration->executeMigration();
}


function plugin_massocsimport_upgrade14to15() {
   global $DB, $LANG;

   $migration = new Migration(15);

   $migration->addField("glpi_plugin_massocsimport_threads", "not_unique_machines_number", '' .
                        'integer');
   $migration->addField("glpi_plugin_massocsimport_threads", "link_refused_machines_number",
                        'integer');
   $migration->addField("glpi_plugin_massocsimport_threads", "entities_id", 'integer');
   $migration->addField("glpi_plugin_massocsimport_threads", "rules_id", 'text');

   $migration->addField("glpi_plugin_massocsimport_configs", "allow_ocs_update", 'bool');

   $migration->addField("glpi_plugin_massocsimport_notimported", "reason", 'integer');

   $query = "INSERT INTO `glpi_displaypreferences`
                    (`itemtype`, `num`, `rank`, `users_id`)
             VALUES ('PluginMassocsimportNotimported', 10, 9, 0)";
   $DB->query($query)
   or die("1.5 insert into glpi_displaypreferences " . $LANG['update'][90] . $DB->error());

   $migration->addField("glpi_plugin_massocsimport_notimported", "serial", 'string',
                        array('value' => ''));
   $migration->addField("glpi_plugin_massocsimport_notimported", "comment", "TEXT NOT NULL");
   $migration->addField("glpi_plugin_massocsimport_notimported", "rules_id", 'text');
   $migration->addField("glpi_plugin_massocsimport_notimported", "entities_id", 'integer');

   $migration->addField("glpi_plugin_massocsimport_details", "entities_id", 'integer');
   $migration->addField("glpi_plugin_massocsimport_details", "rules_id", 'text');

   $query = "SELECT id " .
            "FROM `glpi_notificationtemplates` " .
            "WHERE `itemtype`='PluginMassocsimportNotimported'";
   $result = $DB->query($query);
   if (!$DB->numrows($result)) {

      //Add template
      $query = "INSERT INTO `glpi_notificationtemplates` " .
               "VALUES(NULL, 'Computers not imported', 'PluginMassocsimportNotimported', NOW(), '', '');";
      $DB->query($query) or die($DB->error());
      $templates_id = $DB->insert_id();
      $query = "INSERT INTO `glpi_notificationtemplatetranslations` " .
               "VALUES(NULL, $templates_id, '', '##lang.notimported.action## : ##notimported.entity##'," .
               " '\r\n\n##lang.notimported.action## :&#160;##notimported.entity##\n\n" .
               "##FOREACHnotimported##&#160;\n##lang.notimported.reason## : ##notimported.reason##\n" .
               "##lang.notimported.name## : ##notimported.name##\n" .
               "##lang.notimported.deviceid## : ##notimported.deviceid##\n" .
               "##lang.notimported.tag## : ##notimported.tag##\n##lang.notimported.serial## : ##notimported.serial## \r\n\n" .
               " ##notimported.url## \n##ENDFOREACHnotimported## \r\n', '&lt;p&gt;##lang.notimported.action## :&#160;##notimported.entity##&lt;br /&gt;&lt;br /&gt;" .
               "##FOREACHnotimported##&#160;&lt;br /&gt;##lang.notimported.reason## : ##notimported.reason##&lt;br /&gt;" .
               "##lang.notimported.name## : ##notimported.name##&lt;br /&gt;" .
               "##lang.notimported.deviceid## : ##notimported.deviceid##&lt;br /&gt;" .
               "##lang.notimported.tag## : ##notimported.tag##&lt;br /&gt;" .
               "##lang.notimported.serial## : ##notimported.serial##&lt;/p&gt;\r\n&lt;p&gt;&lt;a href=\"##infocom.url##\"&gt;" .
               "##notimported.url##&lt;/a&gt;&lt;br /&gt;##ENDFOREACHnotimported##&lt;/p&gt;');";
      $DB->query($query) or die($DB->error());

      $query = "INSERT INTO `glpi_notifications`
                VALUES (NULL, 'Computers not imported', 0, 'PluginMassocsimportNotimported', 'not_imported',
                        'mail',".$templates_id.", '', 1, 1, NOW());";
      $DB->query($query) or die($DB->error());
   }
   $migration->executeMigration();
}


function plugin_massocsimport_uninstall() {
   global $DB;

   $tables = array ("glpi_plugin_mass_ocs_import",
                    "glpi_plugin_massocsimport",
                    "glpi_plugin_massocsimport_threads",
                    "glpi_plugin_mass_ocs_import_servers",
                    "glpi_plugin_massocsimport_servers",
                    "glpi_plugin_mass_ocs_import_config",
                    "glpi_plugin_massocsimport_config",
                    "glpi_plugin_massocsimport_configs",
                    "glpi_plugin_mass_ocs_import_not_imported",
                    "glpi_plugin_massocsimport_not_imported",
                    "glpi_plugin_massocsimport_notimported",
                    "glpi_plugin_massocsimport_details");

   foreach ($tables as $table) {
      $query = "DROP TABLE IF EXISTS `$table`;";
      $DB->query($query) or die($DB->error());
   }

   $query = "DELETE
             FROM `glpi_displaypreferences`
             WHERE `itemtype` IN ('PluginMassocsimportNotimported', 'PluginMassocsimportDetail')";
   $DB->query($query) or die($DB->error());

   $query = "DELETE FROM `glpi_alerts` WHERE `itemtype`='PluginMassocsimportNotimported'";
   $DB->query($query) or die($DB->error());

   $notification = new Notification();
   foreach (getAllDatasFromTable($notification->getTable(),"`itemtype`='PluginMassocsimportNotimported'") as $data) {
      $notification->delete($data);
   }
   $template = new NotificationTemplate();
   foreach (getAllDatasFromTable($template->getTable(),"`itemtype`='PluginMassocsimportNotimported'") as $data) {
      $template->delete($data);
   }

   $cron = new CronTask;
   if ($cron->getFromDBbyName('PluginMassocsimportThread','CleanOldThreads')) {
      // creation du cron - param = duree de conservation
      CronTask::Unregister('massocsimport');
   }

}
?>