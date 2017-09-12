<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../core/php/phpSpark.class.php';

class remora extends eqLogic {
  /*     * *************************Attributs****************************** */


  /*     * ***********************Methode static*************************** */


  public static function cron() {
    $elogic = self::byLogicalId('teleinfo', 'remora');
    if ($elogic->getIsEnable() == 1) {
      $elogic->getTeleinfo();
    }
    $elogic = self::byLogicalId('chauffeeau', 'remora');
    if ($elogic->getIsEnable() == 1) {
      $elogic->getCEStatus();
    }
    self::getStatusAll();
  }

  public function getTeleinfo() {
    //https://api.spark.io/v1/devices/[DEVICE_ID]/indexhp?access_token=[ACCESS_TOKEN]
    $elogic = self::byLogicalId('teleinfo', 'remora');
    log::add('remora', 'debug', 'getTeleinfo ');
    if (config::byKey('type', 'remora', 0) == "esp") {
      $addr = config::byKey('addr', 'remora', 0);
      $devAddr = 'http://' . $addr . '/tinfo';
      $devRequest = new com_http($devAddr);
      $devResult = $devRequest->exec();
      $ticall = $devResult;
    } else {
      $accessToken = config::byKey('token', 'remora', 0);
      $deviceid = config::byKey('deviceid', 'remora', 0);
      $spark = new phpSpark();
      $spark->setDebug(false);
      $spark->setDebugType("TXT");
      $spark->setAccessToken($accessToken);
      if($spark->getVariable($deviceid, "tinfo") == true) {
        $obj = $spark->getResult();
        $ticall = $obj['result'];
      } else {
        log::add('remora', 'error', 'Teleinfo Erreur d\'appel ' . $spark->getError()) . ' source ' . $spark->getErrorSource();
        return false;
      }
    }

      $tinfo = json_decode($ticall);
      log::add('remora', 'debug', 'Retour Teleinfo ' . print_r($tinfo,true));
      foreach($tinfo as $key => $value ) {
        log::add('remora', 'debug', 'Retour Teleinfo ' . $key . ' valeur ' . $value);
        $remora = self::byLogicalId('teleinfo', 'remora');
        $cmdlogic = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),$key);
        if (!is_object($cmdlogic)) {
          $cmdlogic = new remoraCmd();
          $cmdlogic->setName($key);
          $cmdlogic->setEqLogic_id($remora->id);
          $cmdlogic->setEqType('remora');
          $cmdlogic->setLogicalId($key);
          $cmdlogic->setType('info');
          if ($remora->id == 'PTEC' || $remora->id == 'OPTARIF') {
            $cmdlogic->setSubType('string');
          } else {
            $cmdlogic->setSubType('numeric');
          }
        }
        $cmdlogic->setConfiguration('value', $value);
        $cmdlogic->save();
        $cmdlogic->event($value);
      }

    return ;
  }

  public function getCEStatus() {
    //https://api.spark.io/v1/devices/[DEVICE_ID]/indexhp?access_token=[ACCESS_TOKEN]

    if (config::byKey('type', 'remora', 0) == "esp") {
      $addr = config::byKey('addr', 'remora', 0);
      $devAddr = 'http://' . $addr . '/relais';
      $devRequest = new com_http($devAddr);
      $devResult = $devRequest->exec();
      $jsonob = json_decode($devResult);
      $result = $jsonob->{'relais'};
    } else {
      $accessToken = config::byKey('token', 'remora', 0);
      $deviceid = config::byKey('deviceid', 'remora', 0);
      $spark = new phpSpark();
      $spark->setDebug(false);
      $spark->setDebugType("TXT");
      $spark->setAccessToken($accessToken);
      if($spark->getVariable($deviceid, "etatrelais") == true) {
        $obj = $spark->getResult();
        $result = $obj['result'];
      } else {
        log::add('remora', 'error', 'Teleinfo Erreur d\'appel ' . $spark->getError()) . ' source ' . $spark->getErrorSource();
        return false;
      }
    }
      log::add('remora', 'debug', 'getCEStatus ' . $result);
        $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($this->getId(),'status');
        $remoraCmd->setConfiguration('value', $result);
        $remoraCmd->save();
        $remoraCmd->event($result);

    return ;
  }

  public function getStatusAll() {
    log::add('remora', 'debug', 'getStatusAll ');
    if (config::byKey('type', 'remora', 0) == "esp") {
      $addr = config::byKey('addr', 'remora', 0);
      $devAddr = 'http://' . $addr . '/fp';
      $devRequest = new com_http($devAddr);
      $devResult = $devRequest->exec();
      $result = json_decode($devResult);
    } else {
      $accessToken = config::byKey('token', 'remora', 0);
      $deviceid = config::byKey('deviceid', 'remora', 0);
      $spark = new phpSpark();
      $spark->setDebug(false);
      $spark->setDebugType("TXT");
      $spark->setAccessToken($accessToken);
      if($spark->getVariable($deviceid, "etatfp") == true) {
        $obj = $spark->getResult();
        $result = $obj['result'];
      } else {
        log::add('remora', 'error', 'Teleinfo Erreur d\'appel ' . $spark->getError()) . ' source ' . $spark->getErrorSource();
        return false;
      }
    }

      log::add('remora', 'debug', 'Retour ' . print_r($result,true));

      $izone = 0;
      while ($izone <= 6) {
        $izone ++;
        if (config::byKey('type', 'remora', 0) == "esp") {
          $fp = "fp$izone";
          $value = $result->{$fp};
        } else {
          $sparkzone = $izone - 1;
          $value = $result[$sparkzone];
        }
        $logical = 'zone' . $izone;
        log::add('remora', 'debug', 'Retour Status zone ' . $izone . ' valeur ' . $value);
        $remora = self::byLogicalId($logical, 'remora');
        $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'status');
        $remoraCmd->setConfiguration('value', $value);
        $remoraCmd->save();
        $remoraCmd->event($value);
      }

      if (config::byKey('type', 'remora', 0) == "esp") {
        $devAddr = 'http://' . $addr . '/delestage';
        $devRequest = new com_http($devAddr);
        $devResult = $devRequest->exec();
        $retour = $devResult[0];
        $json = json_decode($retour);
        $result = $json['niveau'];
      } else {
        if($spark->getVariable($deviceid, "nivdelest") == true) {
          $obj = $spark->getResult();
          $result = $obj['result'];
        } else {
          log::add('remora', 'error', 'Teleinfo Erreur d\'appel ' . $spark->getError()) . ' source ' . $spark->getErrorSource();
          return false;
        }
      }

          log::add('remora', 'debug', 'Retour Status nivdelest valeur ' . $result);
          $remora = self::byLogicalId('global', 'remora');
          $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'nivdelest');
          $remoraCmd->setConfiguration('value', $value);
          $remoraCmd->save();
          $remoraCmd->event($value);
    return ;
  }

  public function remoraCall($zone,$request) {
    log::add('remora', 'debug', 'Recu commande ' . $request . ' vers ' . $zone);
    //curl https://api.spark.io/v1/devices/[DEVICE_ID]/fp -d access_token=[ACCESS_TOKEN] -d params=[ORDRES]
    $params = $zone.$request;

    if (config::byKey('type', 'remora', 0) == "esp") {
      $addr = config::byKey('addr', 'remora', 0);
      $devAddr = 'http://' . $addr . '?setfp=' . $params;
      $devRequest = new com_http($devAddr);
      $devResult = $devRequest->exec();
    } else {
      $accessToken = config::byKey('token', 'remora', 0);
      $deviceid = config::byKey('deviceid', 'remora', 0);
      $spark = new phpSpark();
      $spark->setDebug(false);
      $spark->setDebugType("TXT");
      $spark->setTimeout("5");
      $spark->setAccessToken($accessToken);
      if($spark->callFunction($deviceid, "setfp", $params) == true) {

      } else {
        log::add('remora', 'error', 'Erreur d\'appel ' . $spark->getError()) . ' source ' . $spark->getErrorSource();
        return false;
      }
    }

        $logical = 'zone' . $zone;
        $remora = self::byLogicalId($logical, 'remora');
        $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'status');
        $remoraCmd->setConfiguration('value', $request);
        $remoraCmd->save();
        $remoraCmd->event($request);
  }

  public function remoraRelais($request) {
    log::add('remora', 'debug', 'Recu commande relais vers ' . $request);
    if (config::byKey('type', 'remora', 0) == "esp") {
      $addr = config::byKey('addr', 'remora', 0);
      $devAddr = 'http://' . $addr . '?relais=' . $request;
      $devRequest = new com_http($devAddr);
      $devResult = $devRequest->exec();
    } else {
      $accessToken = config::byKey('token', 'remora', 0);
      $deviceid = config::byKey('deviceid', 'remora', 0);
      $spark = new phpSpark();
      $spark->setDebug(false);
      $spark->setDebugType("TXT");
      $spark->setTimeout("5");
      $spark->setAccessToken($accessToken);
      if($spark->callFunction($deviceid, "relais", $request) == true) {

      } else {
        log::add('remora', 'error', 'Erreur d\'appel ' . $spark->getError()) . ' source ' . $spark->getErrorSource();
        return false;
      }
    }

        $remora = self::byLogicalId('chauffeeau', 'remora');
        $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'status');
        $remoraCmd->setConfiguration('value', $request);
        $remoraCmd->save();
        $remoraCmd->event($request);

  }

  public static function populate() {
    $izone = 1;

    while ($izone <= 7) {
      $logical = 'zone' . $izone;
      $remora = self::byLogicalId($logical, 'remora');
      if (!is_object($remora)) {
        log::add('remora', 'info', 'Equipement n existe pas, création ' . $logical);
        $remora = new remora();
        $remora->setEqType_name('remora');
        $remora->setLogicalId($logical);
        $remora->setName('Zone ' . $izone);
        $remora->setIsEnable(true);
        $remora->save();
        $remora->setLogicalId('zone' . $izone);
        $remora->setConfiguration('zone', $izone);
        $remora->save();
        //log::add('remora', 'info',   print_r($remora,true));
      }
      $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'confort');
      if (!is_object($remoraCmd)) {
        $remoraCmd = new remoraCmd();
        $remoraCmd->setEqLogic_id($remora->getId());
        $remoraCmd->setEqType('remora');
        $remoraCmd->setLogicalId('confort');
        $remoraCmd->setName( 'Confort' );
        $remoraCmd->setConfiguration('request', 'C');
        $remoraCmd->setType('action');
        $remoraCmd->setSubType('other');
        $remoraCmd->setDisplay('generic_type','HEATING_ON');
        $remoraCmd->save();
      }
      $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'arret');
      if (!is_object($remoraCmd)) {
        $remoraCmd = new remoraCmd();
        $remoraCmd->setEqLogic_id($remora->getId());
        $remoraCmd->setEqType('remora');
        $remoraCmd->setLogicalId('arret');
        $remoraCmd->setName( 'Arrêt' );
        $remoraCmd->setConfiguration('request', 'A');
        $remoraCmd->setType('action');
        $remoraCmd->setSubType('other');
        $remoraCmd->setDisplay('generic_type','HEATING_OFF');
        $remoraCmd->save();
      }
      $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'eco');
      if (!is_object($remoraCmd)) {
        $remoraCmd = new remoraCmd();
        $remoraCmd->setEqLogic_id($remora->getId());
        $remoraCmd->setEqType('remora');
        $remoraCmd->setLogicalId('eco');
        $remoraCmd->setName( 'Eco' );
        $remoraCmd->setConfiguration('request', 'E');
        $remoraCmd->setType('action');
        $remoraCmd->setSubType('other');
        $remoraCmd->setDisplay('generic_type','HEATING_OTHER');
        $remoraCmd->save();
      }
      $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'horsgel');
      if (!is_object($remoraCmd)) {
        $remoraCmd = new remoraCmd();
        $remoraCmd->setEqLogic_id($remora->getId());
        $remoraCmd->setEqType('remora');
        $remoraCmd->setLogicalId('horsgel');
        $remoraCmd->setName( 'Hors Gel' );
        $remoraCmd->setConfiguration('request', 'H');
        $remoraCmd->setType('action');
        $remoraCmd->setSubType('other');
        $remoraCmd->setDisplay('generic_type','HEATING_OTHER');
        $remoraCmd->save();
      }
      $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'status');
      if (!is_object($remoraCmd)) {
        $remoraCmd = new remoraCmd();
        $remoraCmd->setName('Statut');
        $remoraCmd->setEqLogic_id($remora->id);
        $remoraCmd->setEqType('remora');
        $remoraCmd->setLogicalId('status');
        $remoraCmd->setType('info');
        $remoraCmd->setSubType('string');
        $remoraCmd->setDisplay('generic_type','HEATING_STATE');
        $remoraCmd->save();
      }
      //incrémentation
      $izone++;
    }

    $logical = 'global';
    $remora = self::byLogicalId($logical, 'remora');
    if (!is_object($remora)) {
      log::add('remora', 'info', 'Equipement n existe pas, création ' . $logical);
      $remora = new remora();
      $remora->setEqType_name('remora');
      $remora->setLogicalId($logical);
      $remora->setName('Global');
      $remora->setConfiguration('zone', '0');
      $remora->setIsEnable(true);
      $remora->save();
    }
    $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'confort');
    if (!is_object($remoraCmd)) {
      $remoraCmd = new remoraCmd();
      $remoraCmd->setEqLogic_id($remora->getId());
      $remoraCmd->setEqType('remora');
      $remoraCmd->setLogicalId('confort');
      $remoraCmd->setName( 'Confort' );
      $remoraCmd->setConfiguration('request', 'C');
      $remoraCmd->setType('action');
      $remoraCmd->setSubType('other');
      $remoraCmd->setDisplay('generic_type','HEATING_ON');
      $remoraCmd->save();
    }
    $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'arret');
    if (!is_object($remoraCmd)) {
      $remoraCmd = new remoraCmd();
      $remoraCmd->setEqLogic_id($remora->getId());
      $remoraCmd->setEqType('remora');
      $remoraCmd->setLogicalId('arret');
      $remoraCmd->setName( 'Arrêt' );
      $remoraCmd->setConfiguration('request', 'A');
      $remoraCmd->setType('action');
      $remoraCmd->setSubType('other');
      $remoraCmd->setDisplay('generic_type','HEATING_OFF');
      $remoraCmd->save();
    }
    $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'eco');
    if (!is_object($remoraCmd)) {
      $remoraCmd = new remoraCmd();
      $remoraCmd->setEqLogic_id($remora->getId());
      $remoraCmd->setEqType('remora');
      $remoraCmd->setLogicalId('eco');
      $remoraCmd->setName( 'Eco' );
      $remoraCmd->setConfiguration('request', 'E');
      $remoraCmd->setType('action');
      $remoraCmd->setSubType('other');
      $remoraCmd->setDisplay('generic_type','HEATING_OTHER');
      $remoraCmd->save();
    }
    $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'horsgel');
    if (!is_object($remoraCmd)) {
      $remoraCmd = new remoraCmd();
      $remoraCmd->setEqLogic_id($remora->getId());
      $remoraCmd->setEqType('remora');
      $remoraCmd->setLogicalId('horsgel');
      $remoraCmd->setName( 'Hors Gel' );
      $remoraCmd->setConfiguration('request', 'H');
      $remoraCmd->setType('action');
      $remoraCmd->setSubType('other');
      $remoraCmd->setDisplay('generic_type','HEATING_OTHER');
      $remoraCmd->save();
    }
    $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'nivdelest');
    if (!is_object($remoraCmd)) {
      $remoraCmd = new remoraCmd();
      $remoraCmd->setName('Niveau Délestage');
      $remoraCmd->setEqLogic_id($remora->id);
      $remoraCmd->setEqType('remora');
      $remoraCmd->setLogicalId('nivdelest');
      $remoraCmd->setType('info');
      $remoraCmd->setSubType('string');
      $remoraCmd->save();
    }

    $logical = 'teleinfo';
    $remora = self::byLogicalId($logical, 'remora');
    if (!is_object($remora)) {
      log::add('remora', 'info', 'Equipement n existe pas, création ' . $logical);
      $remora = new remora();
      $remora->setEqType_name('remora');
      $remora->setLogicalId($logical);
      $remora->setName('Téléinfo');
      $remora->setIsEnable(true);
      $remora->save();
    }

    $logical = 'chauffeeau';
    $remora = self::byLogicalId($logical, 'remora');
    if (!is_object($remora)) {
      log::add('remora', 'info', 'Equipement n existe pas, création ' . $logical);
      $remora = new remora();
      $remora->setEqType_name('remora');
      $remora->setLogicalId($logical);
      $remora->setName('Chauffe-Eau');
      $remora->setConfiguration('chauffeeau', '1');
      $remora->setIsEnable(true);
      $remora->save();
    }
    $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'on');
    if (!is_object($remoraCmd)) {
      $remoraCmd = new remoraCmd();
      $remoraCmd->setEqLogic_id($remora->getId());
      $remoraCmd->setEqType('remora');
      $remoraCmd->setLogicalId('on');
      $remoraCmd->setName( 'Allumer' );
      $remoraCmd->setConfiguration('request', '1');
      $remoraCmd->setType('action');
      $remoraCmd->setSubType('other');
      $remoraCmd->setDisplay('generic_type','ENERGY_ON');
      $remoraCmd->save();
    }
    $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'off');
    if (!is_object($remoraCmd)) {
      $remoraCmd = new remoraCmd();
      $remoraCmd->setEqLogic_id($remora->getId());
      $remoraCmd->setEqType('remora');
      $remoraCmd->setLogicalId('off');
      $remoraCmd->setName( 'Eteindre' );
      $remoraCmd->setConfiguration('request', '0');
      $remoraCmd->setType('action');
      $remoraCmd->setSubType('other');
      $remoraCmd->setDisplay('generic_type','ENERGY_OFF');
      $remoraCmd->save();
    }
    $remoraCmd = remoraCmd::byEqLogicIdAndLogicalId($remora->getId(),'status');
    if (!is_object($remoraCmd)) {
      $remoraCmd = new remoraCmd();
      $remoraCmd->setName('Statut');
      $remoraCmd->setEqLogic_id($remora->id);
      $remoraCmd->setEqType('remora');
      $remoraCmd->setLogicalId('status');
      $remoraCmd->setType('info');
      $remoraCmd->setSubType('string');
      $remoraCmd->setDisplay('generic_type','ENERGY_STATE');
      $remoraCmd->save();
    }

    return true;
  }

}

class remoraCmd extends cmd {
  public function execute($_options = null) {


    switch ($this->getType()) {
      case 'info' :
      return $this->getConfiguration('value');
      break;
      case 'action' :
      $request = $this->getConfiguration('request');
      switch ($this->getSubType()) {
        case 'slider':
        $request = str_replace('#slider#', $value, $request);
        break;
        case 'color':
        $request = str_replace('#color#', $_options['color'], $request);
        break;
        case 'message':
        if ($_options != null)  {
          $replace = array('#title#', '#message#');
          $replaceBy = array($_options['title'], $_options['message']);
          if ( $_options['title'] == '') {
            throw new Exception(__('Le sujet ne peut être vide', __FILE__));
          }
          $request = str_replace($replace, $replaceBy, $request);

        }
        else
        $request = 1;
        break;
        default : $request == null ?  1 : $request;
      }

      $eqLogic = $this->getEqLogic();
      $LogicalID = $this->getLogicalId();

      if ($eqLogic->getConfiguration('chauffeeau') == 1) {
        remora::remoraRelais($request);
      } else {
        $zone = $eqLogic->getConfiguration('zone');
        if ($zone == '0') {
          $izone = 1;
          while ($izone <= 7) {
            remora::remoraCall($izone,$request);
            $izone ++;
          }
        } else {
          remora::remoraCall($zone,$request);
        }
      }

      return $request;
    }
    return true;
  }
}

?>
