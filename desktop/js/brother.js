
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


/*
 * Fonction permettant l'affichage des commandes dans l'équipement
 */
function addCmdToTable(_cmd) {
  if (!isset(_cmd))
    var _cmd = {configuration: {}};

  if (!isset(_cmd.configuration))
    _cmd.configuration = {};

  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td class="hidden-xs"><span class="cmdAttr" data-l1key="id"></span></td>';
  tr += '<td>';
  tr += '<div class="input-group">';
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">';
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>';
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>';
  tr += '</div>';
  tr += '</td>';
  tr += '<td>';
  tr += '<span class="type disabled" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += '</td>';
  tr += '<td>';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> ';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> ';
  tr += '<div style="margin-top:7px;">';
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
  tr += '</div>';
  tr += '</td>';
  tr += '<td style="text-align:right"><span class="cmdAttr" data-l1key="htmlstate"></span></td>';
  tr += '<td style="text-align:center"><input class="cmdAttr form-control input-sm disabled" data-l1key="logicalId" style="width:70%;display:inline-block" placeholder="{{Logical ID}}"><br></td>'
  tr += '<td>';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
  }
  tr += '</td>';
  tr += '</tr>';

  document.querySelector('#table_cmd tbody').insertAdjacentHTML('beforeend', tr);
  let last = document.querySelector('#table_cmd tbody tr:last-child');
  // last.setJeeValues(_cmd, '.cmdAttr')
  $(last).setValues(_cmd, '.cmdAttr');

  if (isset(_cmd.type)) {
    last.querySelector('.cmdAttr[data-l1key="type"]').value = init(_cmd.type);
  }

  // jeedom.cmd.changeType(last, init(_cmd.subType));
  jeedom.cmd.changeType($(last), init(_cmd.subType));
}
