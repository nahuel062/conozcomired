<?php
/*
 * @version $Id: notificationtargetnotimported.class.php 134 2011-11-08 09:46:40Z remi $
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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class PluginMassocsimportNotificationTargetNotImported extends NotificationTarget {


   function getEvents() {
      global $LANG;

      return array ('not_imported' => $LANG["massocsimport"]["common"][18]);
   }


   function getDatasForTemplate($event,$options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      $this->datas['##notimported.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                         $options['entities_id']);

      foreach($options['notimported'] as $id => $item) {
         $tmp = array();

         $tmp['##notimported.name##']     = $item['name'];
         $tmp['##notimported.serial##']   = $item['serial'];
         $tmp['##notimported.entity##']   = Dropdown::getDropdownName('glpi_entities',
                                                                      $options['entities_id']);
         $tmp['##notimported.ocsid##']    = $item['ocsid'];
         $tmp['##notimported.deviceid##'] = $item['ocs_deviceid'];
         $tmp['##notimported.tag##']      = $item['tag'];
         $tmp['##notimported.ocsserver##'] = Dropdown::getDropdownName('glpi_ocsservers',
                                                                       $item['ocsid']);
         $tmp['##notimported.reason##'] = PluginMassocsimportNotimported::getReason($item['reason']);

         $url = $CFG_GLPI["url_base"]."/index.php?redirect=plugin_massocsimport_".$item['id'];
         $tmp['##notimported.url##'] = urldecode($url);

         $this->datas['notimported'][] = $tmp;
      }
      $this->getTags();

      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {
      global $LANG;

      $tags = array('notimported.id'           => 'ID',
                    'notimported.url'          => $LANG['document'][33],
                    'notimported.tag'          => $LANG['ocsconfig'][39],
                    'notimported.name'         => $LANG['common'][16],
                    'notimported.action'       => $LANG["massocsimport"]["common"][18],
                    'notimported.ocsid'        => 'ID OCS',
                    'notimported.deviceid'     => $LANG["massocsimport"]["common"][22],
                    'notimported.reason'       => $LANG["massocsimport"]["common"][34],
                    'notimported.serial'       => $LANG['common'][19]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'=>$tag,'label'=>$label,
                                   'value'=>true));
      }
      asort($this->tag_descriptions);
   }
}
?>