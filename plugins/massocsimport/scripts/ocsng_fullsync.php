<?php
/*
 * @version $Id: ocsng_fullsync.php 135 2011-11-08 11:34:36Z remi $
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Contributor: Goneri Le Bouder <goneri@rulezlan.org>
// Contributor: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

# Converts cli parameter to web parameter for compatibility
if (isset ($_SERVER["argv"]) && !isset ($argv)) {
   $argv = $_SERVER["argv"];
}
if ($argv) {
   for ($i = 1 ; $i < count($argv) ; $i++) {
      $it            = explode("=", $argv[$i], 2);
      $it[0]         = preg_replace('/^--/', '', $it[0]);
      $_GET[$it[0]]  = $it[1];
   }
}

// Can't run on MySQL replicate
$USEDBREPLICATE         = 0;
$DBCONNECTION_REQUIRED  = 1;

// MASS IMPORT for OCSNG
define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/config/based_config.php");
include (GLPI_ROOT . "/inc/includes.php");

$_SESSION["glpicronuserrunning"] = $_SESSION["glpiname"]= 'massocsimport';
// Check PHP Version - sometime (debian) cli version != module version
if (phpversion() < "5") {
   die("PHP version " . phpversion() . " - " . $LANG["install"][9] . "\n\n");
}
// Chech Memory_limit - sometine cli limit (php-cli.ini) != module limit (php.ini)
$mem = Toolbox::getMemoryLimit();
if ($mem > 0 && $mem < 64 * 1024 * 1024) {
   die("PHP memory_limit=$mem - " . $LANG["install"][88] . "\n\n");
}

//Check if plugin is installed
$plugin = new Plugin();
if (!$plugin->isInstalled("massocsimport")) {
   echo $LANG['crontask'][61]."\n";
   exit (1);
}

if (!$plugin->isActivated("massocsimport")) {
   echo $LANG['crontask'][61]."\n";
   exit (1);
}

$thread_nbr    = '';
$threadid      = '';
$ocsservers_id = -1;
$fields        = array ();

//Get script configuration
$config     = new PluginMassocsimportConfig();
$notimport  = new PluginMassocsimportNotimported();
$config->getFromDB(1);

if (!isset ($_GET["ocs_server_id"]) || $_GET["ocs_server_id"] == '') {
   $ocsservers_id = $config->fields["ocsservers_id"];
} else {
   $ocsservers_id = $_GET["ocs_server_id"];
}

if (isset ($_GET["managedeleted"]) && $_GET["managedeleted"] == 1) {
   //echo "=====================================================\n";
   //echo "\tClean old Not Imported machine list (" . $notimport->cleanNotImported($ocsservers_id) . ")\n";

   if ($ocsservers_id != -1) {
      FirstPass($ocsservers_id);
   } else {
      echo "\tManage delete items in all OCS server\n";
      echo "=====================================================\n";

      //Import from all the OCS servers
      $query = "SELECT `id`, `name`
                FROM `glpi_ocsservers` WHERE `is_active`='1'";
      $result = $DB->query($query);

      echo "=====================================================\n";
      while ($ocsservers = $DB->fetch_array($result)) {
         FirstPass($ocsservers["id"]);
      }
      echo "=====================================================\n";
   }

} else { // not managedeleted
   if (isset ($_GET["thread_nbr"]) || isset ($_GET["thread_id"])) {
      if (!isset($_GET["thread_id"]) || $_GET["thread_id"] > $_GET["thread_nbr"]
                  || $_GET["thread_id"] <= 0) {
         echo ("Threadid invalid: threadid must be between 1 and thread_nbr\n\n");
         exit (1);
      }

      $thread_nbr = $_GET["thread_nbr"];
      $threadid = $_GET["thread_id"];

      echo "=====================================================\n";
      echo "\tThread #$threadid : starting ($threadid/$thread_nbr)\n";
   } else {
      $thread_nbr = -1;
      $threadid   = -1;
   }

   //Get the script's process identifier
   if (isset ($_GET["process_id"])) {
      $fields["processid"] = $_GET["process_id"];
   }
   $thread = new PluginMassocsimportThread();

   //Prepare datas to log in db
   $fields["start_time"]                   = date("Y-m-d H:i:s");
   $fields["threadid"]                     = $threadid;
   $fields["status"]                       = PLUGIN_MASSOCSIMPORT_STATE_STARTED;
   $fields["ocsservers_id"]                = $ocsservers_id;
   $fields["imported_machines_number"]     = 0;
   $fields["synchronized_machines_number"] = 0;
   $fields["not_unique_machines_number"]   = 0;
   $fields["failed_rules_machines_number"] = 0;
   $fields["notupdated_machines_number"]   = 0;
   $fields["not_unique_machines_number"]   = 0;
   $fields["linked_machines_number"]       = 0;
   $fields["link_refused_machines_number"] = 0;
   $fields["total_number_machines"]        = 0;
   $fields["error_msg"]                    = '';

   $tid          = $thread->add($fields);
   $fields["id"] = $tid;
   if ($ocsservers_id != -1) {
      $result = SecondPass($tid,$ocsservers_id, $thread_nbr, $threadid, $fields, $config);
      if ($result) {
         $fields = $result;
      }
   } else {
      //Import from all the OCS servers
      $query = "SELECT `id`, `name`
                FROM `glpi_ocsservers` WHERE `is_active` = '1'";
      $res = $DB->query($query);

      while ($ocsservers = $DB->fetch_array($res)) {
         $result = SecondPass($tid,$ocsservers["id"], $thread_nbr, $threadid,
                              $fields, $config);
         if ($result) {
            $fields = $result;
         }
      }
   }

   //Write in db all the informations about this thread
   $fields["total_number_machines"] = $fields["imported_machines_number"]
                                      + $fields["synchronized_machines_number"]
                                      + $fields["failed_rules_machines_number"]
                                      + $fields["linked_machines_number"]
                                      + $fields["notupdated_machines_number"]
                                      + $fields["not_unique_machines_number"]
                                      + $fields["link_refused_machines_number"];
   $fields["end_time"]              = date("Y-m-d H:i:s");
   $fields["status"]                = PLUGIN_MASSOCSIMPORT_STATE_FINISHED;
   $fields["error_msg"]             = "";
   $thread->update($fields);

   echo "\tThread #" . $threadid . " : done !!\n";
   echo "=====================================================\n";
}

//Send notifications if needed
PluginMassocsimportNotimported::sendAlert();


function FirstPass($ocsservers_id) {
   global $DB, $DBocs;

   if (OcsServer::checkOCSconnection($ocsservers_id)) {
      // Compute lastest new computer
      $query  = "SELECT MAX(`ID`)
                 FROM `hardware`";
      $max_id = 0;
      if ($result = $DBocs->query($query)) {
         if ($DBocs->numrows($result) > 0) {
            $max_id = $DBocs->result($result, 0, 0);
         }
      }

      // Compute lastest synchronization date
      $query    = "SELECT MAX(`last_ocs_update`)
                   FROM `glpi_ocslinks`
                   WHERE `ocsservers_id` = '$ocsservers_id'";

      $max_date = "0000-00-00 00:00:00";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            if ($DB->result($result, 0, 0) != '') {
               $max_date = $DB->result($result, 0, 0);
            }
         }
      }

      // Store result for second pass (multi-thread)
      $server                  = new PluginMassocsimportServer();
      $fields["max_ocsid"]     = $max_id;
      $fields["max_glpidate"]  = $max_date;
      $fields["ocsservers_id"] = $ocsservers_id;

      if ($server->getFromDB($ocsservers_id)) {
         $fields["id"] = $server->fields["id"];
         $server->update($fields);
      } else {
         $fields["id"] = $server->add($fields);
      }

      // Handle ID changed or PC deleted in OCS.
      $cfg_ocs = OcsServer::getConfig($ocsservers_id);
      echo "\tManage delete items in OCS server #$ocsservers_id: \"" . $cfg_ocs["name"] . "\"\n";
      OcsServer::manageDeleted($ocsservers_id);
   } else {
      echo "*** Can't connect to OCS server #$ocsservers_id ***";
   }
}


function SecondPass($threads_id, $ocsservers_id, $thread_nbr, $threadid, $fields, $config) {

   $server  = new PluginMassocsimportServer();
   $cfg_ocs = OcsServer::getConfig($ocsservers_id);

   if (!$server->getFromDB($ocsservers_id)) {
      echo "\tThread #" . $threadid . " : cannot get server information : " . $cfg_ocs["name"] . "\n\n";
      return false;
   }

   if (!OcsServer::checkOCSconnection($ocsservers_id)) {
      echo "\tThread #" . $threadid . " : cannot contact server : " . $cfg_ocs["name"] . "\n\n";
      return false;
   }
   return plugin_massocsimport_importFromOcsServer($threads_id,$cfg_ocs, $server, $thread_nbr,
                                                   $threadid, $fields, $config);
}


function plugin_massocsimport_importFromOcsServer($threads_id,$cfg_ocs, $server, $thread_nbr,
                                                  $threadid, $fields, $config) {
   global $DBocs;

   echo "\tThread #" . $threadid . " : import computers from server: '" . $cfg_ocs["name"] . "'\n";

   $where_multi_thread = '';
   $limit              = "";
   if ($thread_nbr != -1 && $threadid != -1 && $thread_nbr > 1) {
      $where_multi_thread = " AND `ID` % $thread_nbr = " . ($threadid -1);
   }
   if ($config->fields["import_limit"] > 0) {
      $limit = " LIMIT " . $config->fields["import_limit"];
   }

   $query_ocs = "SELECT `ID`
                 FROM `hardware`
                 INNER JOIN `accountinfo` ON (`hardware`.`ID` = `accountinfo`.`HARDWARE_ID`)
                 WHERE ((CHECKSUM&" . intval($cfg_ocs["checksum"]) . ") > 0
                        OR `LASTDATE` > '" . $server->fields["max_glpidate"] . "')
                       AND TIMESTAMP(`LASTDATE`) < (NOW()-180)
                       AND `ID` <= " . intval($server->fields["max_ocsid"]);

   if (!empty ($cfg_ocs["tag_limit"])) {
      $splitter = explode("$", $cfg_ocs["tag_limit"]);
      if (count($splitter)) {
         $query_ocs .= " AND `accountinfo`.`TAG` IN ('" . $splitter[0] . "'";
         for ($i = 1 ; $i < count($splitter) ; $i++) {
            $query_ocs .= ",'" . $splitter[$i] . "'";
         }
         $query_ocs .= ")";
      }
   }
   $query_ocs .= "$where_multi_thread
                  $limit";

   $result_ocs = $DBocs->query($query_ocs);
   $nb         = $DBocs->numrows($result_ocs);
   echo "\tThread #$threadid : $nb computer(s)\n";

   $fields["total_number_machines"] += $nb;

   $thread     = new PluginMassocsimportThread();
   $notimport  = new PluginMassocsimportNotimported();
   for ($i = 0 ; $data = $DBocs->fetch_array($result_ocs) ; $i++) {
      if ($i == $config->fields["thread_log_frequency"]) {
         $fields["status"] = PLUGIN_MASSOCSIMPORT_STATE_RUNNING;
         $thread->update($fields);
         $i = 0;
      }

      echo ".";
      $entities_id = 0;
      $action      = OcsServer::processComputer($data["ID"], $cfg_ocs["id"], 1);
      OcsServer::manageImportStatistics($fields, $action['status']);

      switch ($action['status']) {
         case OcsServer::COMPUTER_NOT_UNIQUE :
         case OcsServer::COMPUTER_FAILED_IMPORT :
         case OcsServer::COMPUTER_LINK_REFUSED:
            $notimport->logNotImported($cfg_ocs["id"], $data["ID"],$action);
            break;

         default:
            $notimport->cleanNotImported($cfg_ocs["id"], $data["ID"]);
         //Log detail
         $detail = new PluginMassocsimportDetail();
         $detail->logProcessedComputer($data["ID"],$cfg_ocs["id"], $action, $threadid, $threads_id);
            break;
      }

   }
   return $fields;
}
?>