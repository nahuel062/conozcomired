<?php
/*
 * @version $Id: ministat.class.php 135 2011-11-08 11:34:36Z remi $
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

// Original Author of file: Remi Collet (Fedora at FamilleCollet dot com)
// Purpose of file: compute simple statistics.
// ----------------------------------------------------------------------

class PluginMassocsimportMiniStat {

   public $Min = 0;
   public $Max = 0;
   public $Tot = 0;
   public $Nb  = 0;


   function Reset() {
      $this->Min = $this->Max = $this->Tot = $this->Nb = 0;
   }


   function GetMinimum () {
      return $this->Min;
   }


   function GetMaximum () {
      return $this->Max;
   }


   function GetTotal () {
      return $this->Tot;
   }


   function GetCount () {
      return $this->Nb;
   }


   function GetAverage () {
      return $this->Nb>0 ? $this->Tot / $this->Nb : 0;
   }


   function AddValue($Value) {

      if ($this->Nb > 0) {
         if ($Value < $this->Min) {
            $this->Min = $Value;
         }
         if ($Value > $this->Max) {
            $this->Max = $Value;
         }
         $this->Tot += $Value;
         $this->Nb++;

      } else {
         $this->Min = $this->Max = $this->Tot = $Value;
         $this->Nb = 1;
      }
   }

}
?>