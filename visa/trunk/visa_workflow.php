<?php
/*
*    Copyright 2008,2009 Maarch
*
*  This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief   Action : proceed visa workflow
*
* Permet de récupérer l'état de visa en cours et de transmettre à la personne suivante devant viser
*
* @file
* @author Nicolas Couture <couture@docimsol.com>
* @date $date$
* @version $Revision$
* @ingroup apps
*/

/**
* $confirm  bool true
*/
 $confirm = true;

/**
* $etapes  array Contains only one etap, the status modification
*/
 $etapes = array('empty_error');
 
 require_once "modules" . DIRECTORY_SEPARATOR . "visa" . DIRECTORY_SEPARATOR
			. "class" . DIRECTORY_SEPARATOR
			. "class_modules_tools.php";
 
 
 function manage_empty_error($arr_id, $history, $id_action, $label_action, $status)
{
	$_SESSION['action_error'] = '';
	$result = '';
	$coll_id = "letterbox_coll";
	$res_id = $arr_id[0];
	require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_security.php");
	$sec = new security();
	$table = $sec->retrieve_table_from_coll($coll_id);
	
	$circuit_visa = new visa();
	$workflow = $circuit_visa->getVisaWorkflow($res_id, $coll_id);
	$current_step = $circuit_visa->getCurrentVisaStep($res_id, $table);
	if (isset($workflow[$current_step+2])){
		$newStep=$current_step+1;
		$circuit_visa->query("UPDATE res_letterbox SET current_visa=". $newStep ." WHERE res_id = $res_id");
		
		$circuit_visa->query("UPDATE circuit_visa SET date_visa = CURRENT_TIMESTAMP WHERE res_id = $res_id AND vis_user='".$_SESSION['user']['UserId']."'");
	}
	else {
		$newStep=$current_step+1;
		$circuit_visa->query("UPDATE res_letterbox SET current_visa=". $newStep ." WHERE res_id = $res_id");
		
		$circuit_visa->query("UPDATE circuit_visa SET date_visa = CURRENT_TIMESTAMP WHERE res_id = $res_id AND vis_user='".$_SESSION['user']['UserId']."'");
		
		$circuit_visa->query("UPDATE res_letterbox SET status='ESIG' WHERE res_id = $res_id");
	}
		
	return array('result' => $res_id.'#', 'history_msg' => '');
}

?>
