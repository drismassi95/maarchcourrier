<?php

/*
*   Copyright 2008-2012 Maarch
*
*   This file is part of Maarch Framework.
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
*   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief Contains the admin standard controller page
*
* @file
* @author Arnaud Veber
* @author Laurent Giovannoni
* @date $date$
* @version $Revision$
* @ingroup core
*/

//TODO: management of errors

$coreTools = new core_tools();
$coreTools->load_lang();

$mode = 'list';
//retrieve the controller parameters
if (isset($_REQUEST['mode']) && !empty($_REQUEST['mode'])) {
    $mode = $_REQUEST['mode'];
}
if (isset($_REQUEST['object']) && !empty($_REQUEST['object'])) {
    $object = $_REQUEST['object'];
} else {
    echo _OBJECT_MANDATORY;
}
if (isset($_REQUEST['objectId']) && !empty($_REQUEST['objectId'])) {
    $objectId = $_REQUEST['objectId'];
}
$isApps = false;
if (isset($_REQUEST['admin']) && !empty($_REQUEST['admin'])) {
    $isApps = true;
    //if empty this is an object in the apps
    $viewLocation = 'apps/' . $_SESSION['config']['app_id'] . '/admin/' . $_REQUEST['admin'];
    //test if the user is allowed to acces the admin service
    $coreTools->test_admin('admin_' . $object, 'apps');
} elseif (isset($_REQUEST['module']) && !empty($_REQUEST['module'])) {
    //the module parameter gives the module name
    $viewLocation = 'modules/' . $_REQUEST['module'];
    //test if the user is allowed to acces the admin service
    $coreTools->test_admin('admin_' . $object, $object);
} else {
    echo _VIEW_LOCATION_MANDATORY;
}

//INCLUDES
//TODO: voir avec Cyril pour inclure l'object adéquat et sa XSD
//CRUDL CASES
switch ($mode) {
    case 'create' :
        displayCreate();
        break;
    case 'read' :
        $state = displayRead($docserverId);
        break;
    case 'update' :
        $state = displayUpdate($docserverId);
        break;
    case 'delete' :
        doDelete($docserverId);
        break;
    case 'list' :
        require_once 'apps' . DIRECTORY_SEPARATOR
            . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR . 'class'
            . DIRECTORY_SEPARATOR . 'class_list_show.php';
        $docserversList = displayList();
        break;
    //TODO: PROCESS IT LIKE PARTICULAR CASES OF UPDATE
    case 'allow' :
        doEnable($docserverId);
    case 'ban' :
        doDisable($docserverId);
}

locationBarManagement($mode, $object, $isApps);

//TODO: MAYBE PUT IT ON TOOLS CLASS
/**
 * Management of the location bar
 */
function locationBarManagement($mode, $object, $isApps)
{
    $pageName = 'admin_standard_page_controller';
    $pageLabels = array(
        'add' => _ADDITION,
        'up' => _MODIFICATION,
        'list' => _LIST,
    );
    $pageIds = array(
        'add'   => $object . '_add',
        'up'    => $object . '_up',
        'list'  => $object . '_list',
    );
    $init = false;
    if (isset($_REQUEST['reinit']) && $_REQUEST['reinit'] == 'true') {
        $init = true;
    }
    $level = '';
    if (isset($_REQUEST['level']) && ($_REQUEST['level'] == 2
        || $_REQUEST['level'] == 3 || $_REQUEST['level'] == 4
        || $_REQUEST['level'] == 1)
    ) {
        $level = $_REQUEST['level'];
    }
    if($isApps) {
        $pagePath = $_SESSION['config']['businessappurl'] . 'index.php?page='
            . $pageName . '&admin=' . $object . '&mode=' . $mode;
    } else {
        $pagePath = $_SESSION['config']['businessappurl'] . 'index.php?page='
            . $pageName . '&module=' . $object . '&mode=' . $mode;
    }
    $pageLabel = $pageLabels[$mode];
    $pageId = $pageIds[$mode];
    $coreTools = new core_tools();
    $coreTools->manage_location_bar($pagePath, $pageLabel, $pageId, $init, $level);
}

/**
 * Initialize session parameters for list display
 */
function displayList()
{
    //TODO: VOIR AVEC CYRIL COMMENT CHARGER UN OBJET DE LISTE
}
