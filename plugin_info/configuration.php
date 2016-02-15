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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>


<form class="form-horizontal">
  <div class="form-group">
    <fieldset>

      <div class="form-group">
        <label class="col-lg-4 control-label">{{Type de Remora : }}</label>
        <div class="col-lg-4">
          <select id="select_type" style="margin-top:5px" class="configKey form-control" data-l1key="type">
            <option value="esp">{{Nodemcu}}</option>
            <option value="spark">{{Spark}}</option>
          </select>
        </div>
      </div>

      <div id="champ_ip" class="form-group">
        <label class="col-lg-4 control-label">{{IP du Remora : }}</label>
        <div class="col-lg-4">
          <input class="configKey form-control" data-l1key="addr" style="margin-top:5px" placeholder="ex : 192.168.1.1"/>
        </div>
      </div>

      <div id="champ_id" class="form-group">
        <label class="col-lg-4 control-label">{{Device ID du Remora : }}</label>
        <div class="col-lg-4">
          <input class="configKey form-control" data-l1key="deviceid" style="margin-top:5px" placeholder="ID Spark.io"/>
        </div>
      </div>

      <div id="champ_token" class="form-group">
        <label class="col-lg-4 control-label">{{Token d'accès : }}</label>
        <div class="col-lg-4">
          <input class="configKey form-control" data-l1key="token" style="margin-top:5px" placeholder="ex : 4025"/>
        </div>
      </div>

      <script>

      $( "#select_type" ).change(function() {
        $( "#select_type option:selected" ).each(function() {
          if($( this ).val() == "esp"){
            $("#champ_ip").show();
            $("#champ_id").hide();
            $("#champ_token").hide();
          }
          else {
            $("#champ_ip").hide();
            $("#champ_id").show();
            $("#champ_token").show();
          }
        });
      });

      function remora_postSaveConfiguration(){
        $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/remora/core/ajax/remora.ajax.php", // url du fichier php
        data: {
          action: "postSave",
        },
        dataType: 'json',
        error: function (request, status, error) {
          handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
        if (data.state != 'ok') {
          $('#div_alert').showAlert({message: data.result, level: 'danger'});
          return;
        }
      }
    });
  }


  </script>
</div>
</fieldset>
</form>
