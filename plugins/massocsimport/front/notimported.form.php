<?php
/*
 * @version $Id: notimported.form.php 135 2011-11-08 11:34:36Z remi $
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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

$dropdown = new PluginMassocsimportNotimported();

if (isset($_POST['action'])) {
   switch ($_POST['action']) {
      case 'plugin_massocsimport_import':
         $_POST['force'] = true;

      case 'plugin_massocsimport_replayrules':
         if (PluginMassocsimportNotimported::computerImport($_POST)) {
            Html::redirect(Toolbox::getItemTypeSearchURL('PluginMassocsimportNotimported'));
         } else {
            Html::redirect(Toolbox::getItemTypeFormURL('PluginMassocsimportNotimported').'?id='.$_POST['id']);
         }
         break;

      case 'plugin_massocsimport_link':
         $dropdown->linkComputer($_POST);
         Html::redirect(Toolbox::getItemTypeSearchURL('PluginMassocsimportNotimported'));
         break;
   }
}

include (GLPI_ROOT . "/front/dropdown.common.form.php");
?>