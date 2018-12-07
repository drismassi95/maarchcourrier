<?php
require 'modules/templates/class/templates_controler.php';
require_once 'core/class/class_db.php';
require_once 'core/class/class_db_pdo.php';
$db = new Database();
$stmt = $db->query("select destination from res_view_letterbox where res_id = ? ", array($_SESSION['doc_id']));
if ($stmt->rowCount() > 0) {
        $res = $stmt->fetchObject();
        $destination_entity = $res->destination;
}
$templatesControler = new templates_controler();
$templates = array();
if (isset($_SESSION['destination_entity']) && !empty($_SESSION['destination_entity'])) {
 $templates = $templatesControler->getAllTemplatesForProcess($_SESSION['destination_entity']);
} else {
	$templates = $templatesControler->getAllTemplatesForProcess($destination_entity);
}
$frmStr ="";
$frmStr .= '<option value="">'._CHOOSE_MODEL.'</option>';  
for ($i=0;$i<count($templates);$i++) {
	if (!file_exists($templates[$i]['FILE'])) {
		$disabled = 'disabled="disabled"';

		$title = "title=\""._FILE." ".$templates[$i]['FILE']." "._NOT_AVAILABLE."\"";
	} else {
		$disabled = '';
		$title = "";
	}
    if ($templates[$i]['TYPE'] == 'OFFICE' 
    	&& ($templates[$i]['TARGET'] == 'attachments' || $templates[$i]['TARGET'] == '') 
    	&& ($templates[$i]['ATTACHMENT_TYPE'] == $_REQUEST['attachment_type'] || $templates[$i]['ATTACHMENT_TYPE'] == 'all')) {
	       	$frmStr .= '<option '.$title.' '. $disabled .' value="'. functions::xssafe($templates[$i]['ID']).'">';
	        $frmStr .= functions::xssafe($templates[$i]['LABEL']);
	        	$frmStr .= '</option>';
    }
}

echo $frmStr;
