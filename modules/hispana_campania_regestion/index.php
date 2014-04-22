<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.2.0-25                                               |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2012-07-30 03:07:38 Juan Pablo Romero jromero@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoRegestion.class.php";
    include_once "modules/$module_name/libs/paloSantoCrearcampania.class.php";

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    //actions
    $action = getAction();
    $content = "";

    switch($action){	
	// Ver form con información de la campaña padre para proceder a armar la regestión
	case 'view_edit': 
	    $content = viewFormCrearcampaña($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf);
	    break;

	// Grabar la regestión
	case 'save_edit':
	case 'save_new':
	    saveNewRegestion($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            $content = reportRegestion($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
	    break;
	// Reporte de campañas padres
        default:
            $content = reportRegestion($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function saveNewRegestion($smarty, $module_name, $local_templates_dir, $pDB, $arrConf)
{    
    if($_POST['nombre']!="" && $_POST['values_agentes'] && $_POST['values_bases']!="" && $_POST['values_calltypes']!=""){
	$pRegestion = new paloSantoRegestion($pDB);
	$pRegestion->saveNewRegestion($_POST);
	
	return true;
    }else{
	$smarty->assign("mb_title", _tr("Error"));
	$smarty->assign("mb_message", _tr("Verificar que los campos obligatorios hayan sido llenados."));
	return false;
    }    
}

function reportRegestion($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pRegestion = new paloSantoRegestion($pDB);
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");

    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr("Regestión de campañas"));
    $oGrid->pagingShow(true); // show paging section.

    $oGrid->enableExport();   // enable export.
    $oGrid->setNameFile_Export(_tr("Regestión de campañas"));

    $url = array(
        "menu"         =>  $module_name,
        "filter_field" =>  $filter_field,
        "filter_value" =>  $filter_value);
    $oGrid->setURL($url);

    $arrColumns = array(_tr("Campaña padre"),_tr("Clase de calltypes"),_tr("Acción"));
    $oGrid->setColumns($arrColumns);

    $total   = $pRegestion->getNumRegestion($filter_field, $filter_value);
    $arrData = null;
    if($oGrid->isExportAction()){
        $limit  = $total; // max number of rows.
        $offset = 0;      // since the start.
    }else{
        $limit  = 20;
        $oGrid->setLimit($limit);
        $oGrid->setTotal($total);
        $offset = $oGrid->calculateOffset();
    }

    $arrResult =$pRegestion->getRegestion($limit, $offset, $filter_field, $filter_value);

    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){ 
	    $arrTmp[0] = $value['nombre'];
	    $arrTmp[1] = "<form method='POST' action=\"index.php?menu=$module_name\">";
	    $arrTmp[1] .= "<select name=\"clase_calltype\">
			      <option value=\"Contactado\">Contactado</option>
			      <option value=\"No contactado\">No contactado</option>
			      <option value=\"Agendado\">Agendado</option>
			  </select>";
	    $arrTmp[1] .= "<input type=hidden name=\"action\" value=\"view_edit\">";
	    $arrTmp[1] .= "<input type=hidden name=\"id\" value=$value[id]>";
	    $arrTmp[2]  = "<input type=submit value=\"Regestionar\">";
	    $arrTmp[2] .= "</form>";
            $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);

    //begin section filter
    $oFilterForm = new paloForm($smarty, createFieldFilter());
    $smarty->assign("SHOW", _tr("Show"));
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $content;
}


function viewFormCrearcampaña($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pCrearcampaña = new paloSantoCrearcampaña($pDB);
    $arrFormCrearcampaña = createFieldForm($pDB);
    $oForm = new paloForm($smarty,$arrFormCrearcampaña);

    if(!is_array($arrFormCrearcampaña)){ // Si no es un arreglo, debe haber un error.
	    $smarty->assign("mb_title", "Error");
	    // hago print de un string, no de un Array.
            $smarty->assign("mb_message", "<br>No es posible regestionar la campaña.<br>" . $arrFormCrearcampaña); 
	    return ""; 
    }
    
    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");
    $id     = getParameter("id");
    $smarty->assign("ID", $id); //persistence id with input hidden in tpl

    if($action=="view")
        $oForm->setViewMode();
    else if($action=="view_edit" || getParameter("save_edit"))
        $oForm->setEditMode();
    //end, Form data persistence to errors and other events.

    if($action=="view" || $action=="view_edit"){ // the action is to view or view_edit.
        $dataCrearcampaña = $pCrearcampaña->getCrearcampañaById($id);
        if(is_array($dataCrearcampaña) & count($dataCrearcampaña)>0)		
            $_DATA = $dataCrearcampaña;

        else{
            $smarty->assign("mb_title", _tr("Error get Data"));
            $smarty->assign("mb_message", $pCrearcampaña->errMsg);
        }
    }elseif($_POST['save_edit']!="Editar"){ // si no está en modo view_edit, toca colocar la fecha de hoy en ambos campos
	$_DATA['fecha_inicio'] = date("Y-m-d");
	$_DATA['fecha_fin'] = date("Y-m-d");
    }

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("IMG", "images/list.png");
    $smarty->assign("CAMPANIA_PADRE", $_DATA['nombre']);

    // La regestión hereda todo de la campaña padre.
    $smarty->assign("fecha_inicio", $_DATA['fecha_inicio']);
    $smarty->assign("fecha_fin", $_DATA['fecha_fin']);
    $smarty->assign("id_form", $_DATA['id_form']);
    $smarty->assign("script", $_DATA['script']);

    $_DATA['nombre'] = ""; // Para que aparezca en blanco el input del nombre

    if(isset($_POST['save_edit']) && $_POST['save_edit']=="Editar"){
	$pCrearcampaña->actualizarCampania($_DATA); 
        Header("Location: index.php?menu=hispana_listado_campanias");
    }
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Regestionar campaña"), $_DATA);
    $content = "<form method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function createFieldFilter(){
    $arrFilter = array(
	    "nombre" => _tr("Campaña")
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => _tr("Search"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => "",
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => "")
	    );
    return $arrFormElements;
}

function createFieldForm($pDB)
{
    $pCrearCampaign = new paloSantoCrearcampaña($pDB);

/*
    $arrTmp = $pCrearCampaign->getForms();
    if(count($arrTmp)>0){
	foreach($arrTmp as $form){
	    $arrFormOptions[$form['id']] = $form['nombre'];
	}
    }else{
	return "No existen formularios.";
    }
*/

    /*
    if(getParameter("action")!="view_edit"){      
	$arrTmp = $pCrearCampaign->getBases();
	if(count($arrTmp)>0){
	    foreach($arrTmp as $base){
		$arrBaseOptions[$base['id']] = $base['nombre'];
		}
	    }else{
		return "No existen bases de clientes.";
	    }

	$pDB = new paloDB("sqlite3:////var/www/db/acl.db"); // me cambio temporalmente a Sqlite
	$pCrearCampaign = new paloSantoCrearcampaña($pDB);
	$arrTmp = $pCrearCampaign->getUsuarios("Agente"); // pido los usuarios del rol Agente
	
	if(count($arrTmp)>0){
	    foreach($arrTmp as $base){
		$arrAgentesNoElegidos[$base['name']] = $base['description'] . " - Ext: " . $base['extension'];
	    }
	}else{
	    return "No existen agentes.";
	}
    }else
    */
    if(getParameter("action")=="view_edit"){
	// Bases
	$arrTmp = $pCrearCampaign->getBasesCampania(getParameter('id'));
	
	if(count($arrTmp)>0){
	    foreach($arrTmp as $base){
		$arrBasesElegidas[$base['id']] = $base['nombre'];
		}
	}

	if(sizeof($arrBasesElegidas)>0){
	    $arrTmp = $pCrearCampaign->getOtrasBases($arrBasesElegidas);
	}else{
	    $arrTmp = $pCrearCampaign->getBases();
	}
	
	if(count($arrTmp)>0){
	    foreach($arrTmp as $base){
		$arrBaseOptions[$base['id']] = $base['nombre'];
		}
	}

	//Calltypes
	$arrTmp = $pCrearCampaign->getCalltypes(getParameter('id'),getParameter('clase_calltype'));
	
	if(count($arrTmp)>0){
	    foreach($arrTmp as $calltype){
		$arrCalltypes[$calltype['id']] = $calltype['descripcion'];
		}
	}else{
	    return "No existen calltypes.";
	}

	// Agentes
	$arrTmp = $pCrearCampaign->getAgentesCampania(getParameter('id'));

	if(count($arrTmp)>0){
	    foreach($arrTmp as $agente){
		$arrAgentesElegidos[$agente['id_agente']] = $agente['id_agente'];
		}
	}

	$pDB = new paloDB("sqlite3:////var/www/db/acl.db"); // me cambio temporalmente a Sqlite
	$pCrearCampaign = new paloSantoCrearcampaña($pDB);

	$arrTmp = $pCrearCampaign->getUsuariosElegidos("Agente",$arrAgentesElegidos); // pido los usuarios del rol Agente que estén en arreglo $rrAgentesElegidos

	unset($arrAgentesElegidos);
	if(count($arrTmp)>0){
	    foreach($arrTmp as $agente){
		$arrAgentesElegidos[$agente['name']] = $agente['description'] . " - Ext: " . $agente['extension'];
		}
	}

	if(sizeof($arrAgentesElegidos)>0){
	    $arrTmp = $pCrearCampaign->getUsuariosElegidos("Agente",$arrAgentesElegidos,"not"); // pido los usuarios del rol Agente que estén en arreglo $rrAgentesElegidos
	}else{
	    $arrTmp = $pCrearCampaign->getUsuarios("Agente");
	}

	if(count($arrTmp)>0){
	    foreach($arrTmp as $agente){
		$arrAgentesNoElegidos[$agente['name']] = $agente['description'] . " - Ext: " . $agente['extension'];
		}
	}
    } 

    // por default $arrSelect está vacío, debe tener algo cuando la campaña está siendo editada
    $arrSelected = array();

    $arrFields = array(
            "nombre"   => array(      "LABEL"                  => _tr("Nombre"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "base"   => array(      "LABEL"                  => _tr("Base"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrBaseOptions,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "si",
					    "MULTIPLE"               => true,
					    "SIZE"                   => "10"
                                            ),
	    "bases_elegidas"   => array(      "LABEL"                  => _tr("Base"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrBasesElegidas,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "si",
					    "MULTIPLE"               => true,
					    "SIZE"                   => "10"
                                            ),
            "agente"   => array(      "LABEL"                  => _tr("Agente"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrAgentesNoElegidos,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "si",
					    "MULTIPLE"               => true,
					    "SIZE"                   => "5"
                                            ),
	    "agentes_elegidos"   => array(      "LABEL"                  => _tr("Agente"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrAgentesElegidos,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "si",
					    "MULTIPLE"               => true,
					    "SIZE"                   => "5"
                                            ),
/*
            "fecha_inicio"   => array(      "LABEL"                  => _tr("Fecha Inicio"),
					    "REQUIRED"               => "yes",
					    "INPUT_TYPE"             => "DATE",
					    "INPUT_EXTRA_PARAM"      => array("TIME" => false, "FORMAT" => "%Y-%m-%d"),
					    "VALIDATION_TYPE"        => 'text',

					    "VALIDATION_TYPE"        => 'ereg',
					    "VALIDATION_EXTRA_PARAM" => '^[[:digit:]]{2}[[:space:]]+[[:alpha:]]{3}[[:space:]]+[[:digit:]]{4}$'

                                            ),
            "fecha_fin"   => array(      "LABEL"                  => _tr("Fecha Fin"),
                                            "REQUIRED"               => "no",
					    "REQUIRED"               => "yes",
					    "INPUT_TYPE"             => "DATE",
					    "INPUT_EXTRA_PARAM"      => array("TIME" => false, "FORMAT" => "%Y-%m-%d"),
					    "VALIDATION_TYPE"        => 'text',


					    "VALIDATION_EXTRA_PARAM" => '^[[:digit:]]{2}[[:space:]]+[[:alpha:]]{3}[[:space:]]+[[:digit:]]{4}$'

                                            ),
*/
	    "calltypes"   => array(      "LABEL"                  => _tr("Call types"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrCalltypes,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "si",
					    "MULTIPLE"               => true,
					    "SIZE"                   => "5"
                                            ),
	  "calltypes_elegidos"   => array(      "LABEL"                  => _tr("Call types"),
					    "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrSelected,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "si",
					    "MULTIPLE"               => true,
					    "SIZE"                   => "5"
                                            )
            );
    return $arrFields;
}


function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete")) 
        return "delete";
    else if(getParameter("new_open")) 
        return "view_form";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_edit";
    else if(getParameter("action")=="regestion")
        return "regestion";
    else
        return "report"; //cancel
}

function _pre($array)
{
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}
?>