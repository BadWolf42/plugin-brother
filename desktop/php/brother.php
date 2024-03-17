<?php
if (!isConnect('admin'))
  throw new Exception('{{401 - Accès non autorisé}}');

$plugin = plugin::byId('brother');
sendVarToJS('eqType', $plugin->getId());
?>

<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i><br><span>{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i><br><span>{{Configuration}}</span>
      </div>
    </div>
    <legend><i class="icon kiko-printer"></i> {{Mes Imprimantes}}</legend>
    <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
    <div class="eqLogicThumbnailContainer">
<?php
// Affiche la liste des équipements
foreach (eqLogic::byType($plugin->getId()) as $eqLogic) {
  $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
  echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
  echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
  echo '<br>';
  echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
  echo '</div>';
}
?>
    </div>
  </div>

  <div class="col-xs-12 eqLogic" style="display: none;">
    <div class="input-group pull-right" style="display:inline-flex">
      <span class="input-group-btn">
        <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
        <a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
        <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
        <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
      </span>
    </div>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab"><br/>
        <div style="width: 50%; display:inline-block;">
          <form class="form-horizontal">
            <fieldset>

              <div class="form-group">
                <label class="col-sm-6 control-label">{{Nom de l'imprimante}}</label>
                <div class="col-sm-6">
                  <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                  <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'imprimante}}"/>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-6 control-label" >{{Objet parent}}</label>
                <div class="col-sm-6">
                  <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                    <option value="">{{Aucun}}</option>
<?php
foreach ((jeeObject::buildTree (null, false)) as $object) {
  echo '<option value="' . $object->getId() . '">';
  echo str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber'));
  echo $object->getName() . '</option>';
}
?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-6 control-label">{{Catégorie}}</label>
                <div class="col-sm-6">
<?php
foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
  echo '<label class="checkbox-inline">';
  echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
  echo '</label>';
}
?>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-6 control-label">{{Options}}</label>
                <div class="col-sm-6">
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                </div>
              </div><br>

              <div class="form-group">
                <label class="col-sm-6 control-label">{{Adresse IP / Nom d'hôte}}</label>
                <div class="col-sm-6">
                  <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="brotherAddress" placeholder="Adresse IP / Nom d'hôte"/>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-6 control-label help">{{Technologie de l'imprimante}}</label>
                <div class="col-sm-6">
                  <select id="sel_object_template" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="brotherType">
                    <option value="ink">{{Jet d'encre}}</option>
                    <option value="laser">{{Laser}}</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-6 control-label help">{{Type de l'imprimante}}</label>
                <div class="col-sm-6">
                  <select id="sel_object_template" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="brotherColorType">
                    <option value="1">{{Couleur}}</option>
                    <option value="0">{{Noir & Blanc}}</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-6 control-label help" data-help="{{Cocher la case pour utiliser le template de widget}}">{{Template de widget}}</label>
                <div class="col-sm-6">
                  <input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="brotherWidget"/>
                </div>
              </div>

            </fieldset>
          </form>
        </div>
      </div>

      <div role="tabpanel" class="tab-pane" id="commandtab"><br/>
        <div class="table-responsive">
          <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th class="hidden-xs" style="min-width:50px;width:70px">ID</th>
                <th style="min-width:200px;width:350px">{{Nom}}</th>
                <th style="min-width:70px;width:100px">{{Type}}</th>
                <th style="min-width:260px;width:300px">{{Options}}</th>
                <th style="min-width:135px;text-align:right">{{Etat}}</th>
                <th style="min-width:125px;width:125px;text-align:center">{{Logical ID}}</th>
                <th style="min-width:80px;width:200px">{{Actions}}</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, nom_du_plugin) -->
<?php include_file('desktop', 'brother', 'js', 'brother');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');?>
