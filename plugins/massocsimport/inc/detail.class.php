<?php
/*
 * @version $Id: detail.class.php 143 2012-06-29 07:37:51Z remi $
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

class PluginMassocsimportDetail extends CommonDBTM {


   function getSearchOptions() {
      global $LANG;

      $tab = array ();
      $tab['common'] = $LANG["massocsimport"]["common"][23];

      $tab[1]['table']     = $this->getTable();
      $tab[1]['field']     = 'ocsid';
      $tab[1]['linkfield'] = 'ocsid';
      $tab[1]['name']      = $LANG['ocsng'][45];
      $tab[1]['datatype']  = 'integer';

      $tab[2]['table']         = 'glpi_ocsservers';
      $tab[2]['field']         = 'name';
      $tab[2]['linkfield']     = 'ocsservers_id';
      $tab[2]['name']          = $LANG['ocsng'][29];
      $tab[2]['searchtype']    = array('equals','contains');
      $tab[2]['datatype']      = 'itemlink';
      $tab[2]['itemlink_type'] = 'OcsServer';
      $tab[2]['searchtype']    = 'equals';

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'process_time';
      $tab[3]['linkfield'] = '';
      $tab[3]['name']      = $LANG["massocsimport"]["common"][31];
      $tab[3]['datatype']  = 'datetime';

      $tab[4]['table']      = $this->getTable();
      $tab[4]['field']      = 'action';
      $tab[4]['linkfield']  = 'action';
      $tab[4]['name']       = $LANG['rulesengine'][11];
      $tab[4]['searchtype'] = 'equals';

      $tab[5]['table']     = 'glpi_plugin_massocsimport_threads';
      $tab[5]['field']     = 'processid';
      $tab[5]['linkfield'] = 'plugin_massocsimport_threads_id';
      $tab[5]['name']      = $LANG["massocsimport"]["common"][32];
      $tab[5]['datatype']  = 'integer';

      $tab[6]['table']         = 'glpi_computers';
      $tab[6]['field']         = 'name';
      $tab[6]['linkfield']     = 'computers_id';
      $tab[6]['name']          = $LANG['help'][25];
      $tab[6]['datatype']      = 'itemlink';
      $tab[6]['itemlink_type'] = 'Computer';

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'threadid';
      $tab[7]['linkfield'] = 'threadid';
      $tab[7]['name']      = $LANG["massocsimport"]["common"][9];
      $tab[7]['datatype']  = 'integer';

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'rules_id';
      $tab[8]['linkfield'] = '';
      $tab[8]['name']      = $LANG["massocsimport"]["common"][39];
      $tab[8]['datatype']  = 'text';

      $tab[80]['table']       = 'glpi_entities';
      $tab[80]['field']       = 'completename';
      $tab[80]['name']        = $LANG['entity'][0];
      $tab[80]['searchtype']  = 'equals';

      return $tab;
   }


   function logProcessedComputer ($ocsid, $ocsservers_id, $action, $threadid, $threads_id) {

      $input["ocsid"] = $ocsid;
      if (isset($action["rule_matched"])) {
         $input["rules_id"] = json_encode($action['rule_matched']);
      } else {
         $input["rules_id"] = "";
      }
      $input["threadid"]                        = $threadid;
      $input["plugin_massocsimport_threads_id"] = $threads_id;
      $input["ocsservers_id"]                   = $ocsservers_id;
      $input["action"]                          = $action['status'];

      if (isset($action["entities_id"])) {
         $input["entities_id"] = $action['entities_id'];
      } else {
         $input['entities_id'] = 0;
      }
      if (isset($action['computers_id'])) {

         $comp = new Computer();
         if ($comp->getFromDB($action['computers_id'])) {
            $input['computers_id'] = $comp->getID();
            $input['entities_id']  = $comp->getEntityID();
         }
      }
      $input["process_time"] = date("Y-m-d H:i:s");

      $this->add($input);
   }


   static function deleteThreadDetailsByProcessID($threads_id) {
      global $DB;

      $detail = new self();
      foreach ($DB->request($detail->getTable(),
                            array('plugin_massocsimport_threads_id' => $threads_id)) as $data) {
         $detail->delete($data);
      }
   }


   static function giveActionNameByActionID($action) {

      $actions = self::getActions();
      if (isset($actions[$action])) {
         return $actions[$action];
      }
      return '';
   }


   static function getActions() {
      global $LANG;

      return array(OcsServer::COMPUTER_FAILED_IMPORT => $LANG["massocsimport"]["common"][18],
                   OcsServer::COMPUTER_IMPORTED      => $LANG["massocsimport"]["common"][5],
                   OcsServer::COMPUTER_LINKED        => $LANG["massocsimport"]["common"][19],
                   OcsServer::COMPUTER_NOTUPDATED    => $LANG["massocsimport"]["common"][20],
                   OcsServer::COMPUTER_SYNCHRONIZED  => $LANG["massocsimport"]["common"][6],
                   OcsServer::COMPUTER_NOT_UNIQUE    => $LANG["massocsimport"]["common"][33],
                   OcsServer::COMPUTER_LINK_REFUSED  => $LANG["massocsimport"]["common"][37]);
   }


   static function showActions($name, $value=0) {

      $actions = self::getActions();
      Dropdown::showFromArray($name,$actions, array('value' => $value));
   }

}
?>