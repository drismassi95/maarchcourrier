<?php
/**
* File : change_doctype.php
*
* Script called by an ajax object to process the document type change during
* indexing (index_mlb.php)
*
* @package  maarch
* @version 1
* @since 10/2005
* @license GPL v3
* @author  Claire Figueras  <dev@maarch.org>
* @author  Cyril Vazquez  <dev@maarch.org>
*/
require_once 'modules/entities/class/class_manage_listdiff.php';

$db = new dbquery();
$core = new core_tools();
$core->load_lang();
$diffList = new diffusion_list();
$origin = $_REQUEST['origin'];

if (empty($_REQUEST['origin'])) {
    $_SESSION['error'] = _ORIGIN . ' ' . _UNKNOWN;
    echo "{status : 2, error_txt : '" . addslashes($_SESSION['error']) . "'}";
    exit();
}

if ((! isset($_REQUEST['objectType']) || empty($_REQUEST['objectType']))
    && $_REQUEST['load_from_model'] == 'true')
{
    if ($_REQUEST['mandatory'] <> 'none') {
        $_SESSION['error'] = _OBJECT_TYPE . ' ' . _IS_EMPTY;
    }
    $_SESSION[$origin]['diff_list'] = array();
    echo "{status : 1, error_txt : '" . addslashes($_SESSION['error']) . "'}";
    exit();
} 

if ((! isset($_REQUEST['objectId']) || empty($_REQUEST['objectId']))
    && $_REQUEST['load_from_model'] == 'true'
) {
    // if ($_REQUEST['mandatory'] <> 'none') {
    //     $_SESSION['error'] = _ENTITY_ID . ' ' . _IS_EMPTY;
    // }
    $_SESSION[$origin]['diff_list'] = array();
    echo "{status : 1, error_txt : '" . addslashes($_SESSION['error']) . "'}";
    exit();
}

if (empty($_REQUEST['collId']) && $_REQUEST['load_from_model'] == 'true') {
    $_SESSION['error'] = _COLL_ID . ' ' . _IS_EMPTY;
    echo "{status : 2, error_txt : '" . addslashes($_SESSION['error']) . "'}";
    exit();
}
$onlyCC = false;


if( $core->test_service('add_copy_in_process', 'entities', false) && $_REQUEST['origin'] == 'process'){
    $onlyCC = true;
}

if($_REQUEST['origin'] == 'indexing'){
    $onlyCC = false;
}

$objectType = $_REQUEST['objectType'];
$objectId = $_REQUEST['objectId'];

// Get listmodel_parameters
$_SESSION[$origin]['difflist_type'] = $diffList->get_difflist_type($objectType);

if ($_REQUEST['load_from_model'] == 'true') {
    $_SESSION[$origin]['diff_list'] = $diffList->get_listmodel($objectType, $objectId);
    $_SESSION[$origin]['diff_list']['difflist_type'] = $_SESSION[$origin]['diff_list']['object_type'];
}

if ($objectId <> '') {
    $_SESSION[$origin]['difflist_object']['object_id'] = $objectId;
    if ($objectType == 'entity_id') {
        $db->connect();
        $query = "select entity_label from entities where entity_id = '" . $objectId . "'";
        $db->query($query);
        $res = $db->fetch_object();
        if ($res->entity_label <> '') {
            $_SESSION[$origin]['difflist_object']['object_label'] = $res->entity_label;
        }
    }
}


$content = '';
if (! $onlyCC) {
    if (isset($_SESSION['validStep']) && $_SESSION['validStep'] == 'ok') {
        $content .= "";
    } else {
        //$content .= '<h2>' . _LINKED_DIFF_LIST . ' : </h2>';
    }
}
if(!empty($_SESSION[$origin]['diff_list'])) {
    $roles = $diffList->list_difflist_roles();
    $difflist = $_SESSION[$origin]['diff_list'];
    
    # Get content from buffer of difflist_display 
    ob_start();
    require_once 'modules/entities/difflist_display.php';
    $content .= str_replace(array("\r", "\n", "\t"), array("", "", ""), ob_get_contents());
    ob_end_clean();   

    $labelButton = _MODIFY_LIST;
    $arg = '&mode=up';
} else {
    $content .= '<p>' . _NO_DIFF_LIST_ASSOCIATED . '</p>';
    $labelButton = _CREATE_LIST;
    $arg = '&mode=add';
}
if ($onlyCC) {
    $arg .= '&only_cc';
}

if($origin != 'process'){
    $content_standard = '<center><b>' . _DIFF_LIST . '</b> | ';
    $content_standard .= '<span class="button" >';
    $content_standard .= '<i class="fa fa-edit fa-2x" title="'.$labelButton.'"></i>'
             . '<a href="javascript://" onclick="window.open(\''
             . $_SESSION['config']['businessappurl'] . 'index.php?display=true'
             . '&module=entities&page=manage_listinstance&origin=' . $origin . $arg
             . '\', \'\', \'scrollbars=yes,menubar=no,toolbar=no,status=no,'
             . 'resizable=yes,width=1280,height=800,location=no\');"><small>'
             . $labelButton . '</small></a>';
    $content_standard .= '</span></center>';
}else{
    $content_standard .= '<h2 style="margin:0;">' . _DIFF_LIST . ' : </h2>';
    $content_standard .= '<br/>';
    $content_standard .= '<span class="button" >';
    $content_standard .= '<i class="fa fa-pencil" title="'.$labelButton.'"></i>'
             . '<a href="javascript://" onclick="window.open(\''
             . $_SESSION['config']['businessappurl'] . 'index.php?display=true'
             . '&module=entities&page=manage_listinstance&origin=' . $origin . $arg
             . '\', \'\', \'scrollbars=yes,menubar=no,toolbar=no,status=no,'
             . 'resizable=yes,width=1280,height=800,location=no\');"> '
             . $labelButton . '</a>';
    $content_standard .= '</span>';
}
echo "{status : 0, div_content : '" . addslashes($content_standard . $content . '<br>') 
    . "', div_content_action : '" . addslashes($content) . "'}";
exit();
