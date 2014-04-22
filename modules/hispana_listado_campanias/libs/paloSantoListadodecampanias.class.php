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
  $Id: paloSantoListadodecampañas.class.php,v 1.1 2012-05-03 06:05:25 Juan Pablo Romero jromero@palosanto.com Exp $ */
class paloSantoListadodecampañas{
    var $_DB;
    var $errMsg;

    function paloSantoListadodecampañas(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    /*HERE YOUR FUNCTIONS*/

    function getNumListadodecampañas($filter_field, $filter_value)
    {
        $where    = "";
        $arrParam = null;
        if(isset($filter_field) & $filter_field !=""){
            $where    = "where $filter_field like ?";
            $arrParam = array("$filter_value%");
        }

        // $query   = "SELECT COUNT(*) FROM campania  $where";
	$query   = "SELECT COUNT(*) FROM campania_recarga WHERE status='A'";
        $result=$this->_DB->getFirstRowQuery($query, false);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return 0;
        }
        return $result[0];
    }

    function getListadodecampañas($limit, $offset, $filter_field, $filter_value)
    {
        $where    = "";
        $arrParam = null;
        if(isset($filter_field) & $filter_field !=""){
            $where    = "where $filter_field like ?";
            $arrParam = array("$filter_value%");
        }

        $query   = "SELECT a.id, a.nombre, fecha_inicio, fecha_fin, a.tipo, b.nombre as form 
		    FROM campania AS a, form AS b 
		    WHERE status='A' 
		    AND id_form=b.id
		    $where 
		    ORDER BY a.id desc
		    LIMIT $limit OFFSET $offset";

        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }
        return $result;
    }

    function getListadodecampañasById($id)
    {
        $query = "SELECT * FROM table WHERE id=?";

        $result=$this->_DB->getFirstRowQuery($query, true, array("$id"));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }
    
    function getBasesAgentes($id_campania)
    {
	$arrBasesAgentes = array();

	$query = "SELECT count(*) as cont
		  FROM(select id_campania from campania_base
		  WHERE id_campania=$id_campania
		  AND status='A' UNION
                  select id_campania from campania_recarga
		  WHERE id_campania=$id_campania
		  AND status='A') as x
                  ";
        $result=$this->_DB->getFirstRowQuery($query, true);
	$arrBasesAgentes['bases'] =  $result['cont'];

	$query = "SELECT count(*) as cont
		  FROM campania_agente
		  WHERE id_campania=$id_campania
		  AND status='A'";
        $result=$this->_DB->getFirstRowQuery($query, true);
	$arrBasesAgentes['agentes'] =  $result['cont'];

	return $arrBasesAgentes;
    }
}
?>