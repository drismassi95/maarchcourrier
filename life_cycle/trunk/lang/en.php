<?php
/*
 *
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

/*********************** ADMIN ***********************************/
if (!defined('_LIFE_CYCLE'))  define('_LIFE_CYCLE', 'Life cycle');
if (!defined('_ADMIN_LIFE_CYCLE_SHORT'))  define('_ADMIN_LIFE_CYCLE_SHORT', ' Life cycle administration');
if (!defined('_ADMIN_LIFE_CYCLE'))  define('_ADMIN_LIFE_CYCLE', ' Administration des cycles de vie des ressources num&eacute;riques');
if (!defined('_ADMIN_LIFE_CYCLE_DESC'))  define('_ADMIN_LIFE_CYCLE_DESC', 'Administration des cycles de vie des ressources num&eacute;riques.');

if (!defined('_MANAGE_LC_CYCLES'))  define('_MANAGE_LC_CYCLES', 'Life cycle management ("lc_cycles")');
if (!defined('_MANAGE_LC_CYCLE_STEPS'))  define('_MANAGE_LC_CYCLE_STEPS', 'life cycle steps management("lc_cycles_steps")');
if (!defined('_MANAGE_LC_POLICIES'))  define('_MANAGE_LC_POLICIES', 'Life cycle policies management ("lc_policies")');

if (!defined('_MANAGE_DOCSERVERS'))  define('_MANAGE_DOCSERVERS', 'Docservers management ("docservers")');
if (!defined('_MANAGE_DOCSERVERS_LOCATIONS'))  define('_MANAGE_DOCSERVERS_LOCATIONS', 'Docservers locations management ("docserver_locations")');
if (!defined('_MANAGE_DOCSERVER_TYPES'))  define('_MANAGE_DOCSERVER_TYPES', 'Docservers types management  ("docserver_types")');

if (!defined('_ADMIN_DOCSERVERS'))  define('_ADMIN_DOCSERVERS', ' Docservers administration');


/*****************CYCLE_STEPS************************************/
if (!defined('_LC_CYCLE_STEP'))  define('_LC_CYCLE_STEP', 'Life cycle step');
if (!defined('_LC_CYCLE_STEPS_LIST'))  define('_LC_CYCLE_STEPS_LIST', 'Life cycle step list');
if (!defined('_ALL_LC_CYCLE_STEPS'))  define('_ALL_LC_CYCLE_STEPS', 'View all');
if (!defined('_POLICY_ID'))  define('_POLICY_ID', 'Policy ID');
if (!defined('_CYCLE_STEP_ID'))  define('_CYCLE_STEP_ID', 'Life cycle step ID ("lc_cycle_steps")');
if (!defined('_CYCLE_STEP_DESC'))  define('_CYCLE_STEP_DESC', 'Life cycle step description');
if (!defined('_STEPT_OPERATION'))  define('_STEP_OPERATION', 'Action on a life cycle step');
if (!defined('_IS_ALLOW_FAILURE'))  define('_IS_ALLOW_FAILURE', 'Allow failure flag');
if (!defined('_PREPROCESS_SCRIPT'))  define('_PREPROCESS_SCRIPT', 'Before process script');
if (!defined('_POSTPROCESS_SCRIPT'))  define('_POSTPROCESS_SCRIPT', 'After process script');
if (!defined('_LC_CYCLE_STEP_ADDITION'))  define('_LC_CYCLE_STEP_ADDITION', 'Add a life cycle step');
if (!defined('_LC_CYCLE_STEP_UPDATED'))  define('_LC_CYCLE_STEP_UPDATED', 'Life cycle step updated');
if (!defined('_LC_CYCLE_STEP_ADDED'))  define('_LC_CYCLE_STEP_ADDED', 'Life cycle step added');
if (!defined('_LC_CYCLE_STEP_DELETED'))  define('_LC_CYCLE_STEP_DELETED', 'Life cycle step deleted');
if (!defined('_IS_MUST_COMPLETE'))  define('_IS_MUST_COMPLETE', 'IS_MUST_COMPLETE');


/****************CYCLES*************************************/
if (!defined('_CYCLE_ID'))  define('_CYCLE_ID', 'Life cycle ID');
if (!defined('_LC_CYCLE_ID'))  define('_LC_CYCLE_ID', 'Life cycle ID');
if (!defined('_SEQUENCE_NUMBER'))  define('_SEQUENCE_NUMBER', 'sequence number');
if (!defined('_LC_CYCLE'))  define('_LC_CYCLE', 'Life cycle');
if (!defined('_CYCLE_DESC'))  define('_CYCLE_DESC', 'Life cycle description');
if (!defined('_VALIDATION_MODE'))  define('_VALIDATION_MODE', 'Validation mode');
if (!defined('_ALL_LC_CYCLES'))  define('_ALL_LC_CYCLES', 'View all');
if (!defined('_LC_CYCLES_LIST'))  define('_LC_CYCLES_LIST', 'Life cycle list');
if (!defined('_SEQUENCE_NUMBER'))  define('_SEQUENCE_NUMBER', 'Sequence number');
if (!defined('_LC_CYCLE_ADDITION'))  define('_LC_CYCLE_ADDITION', 'Add a life cycle');
if (!defined('_LC_CYCLE_ADDED'))  define('_LC_CYCLE_ADDED', 'Life cycle added');
if (!defined('_LC_CYCLE_UPDATED'))  define('_LC_CYCLE_UPDATED', 'Life cycle updated');
if (!defined('_LC_CYCLE_DELETED'))  define('_LC_CYCLE_DELETED', 'Life cycle deleted');



/***************DOCSERVERS TYPES*************************************/
if (!defined('_DOCSERVER_TYPE_ID'))  define('_DOCSERVER_TYPE_ID', 'Docserver type ID ');
if (!defined('_DOCSERVER_TYPE'))  define('_DOCSERVER_TYPE', 'Docserver type');
if (!defined('_DOCSERVER_TYPES_LIST'))  define('_DOCSERVER_TYPES_LIST', 'Docserver type list ');
if (!defined('_ALL_DOCSERVER_TYPES'))  define('_ALL_DOCSERVER_TYPES', 'View all');
if (!defined('_DOCSERVER_TYPE_LABEL'))  define('_DOCSERVER_TYPE_LABEL', 'Docserver type label ');
if (!defined('_IS_COMPRESSED'))  define('_IS_COMPRESSED', 'Compressed');
//if (!defined('_IS_META'))  define('_IS_META', 'Contient des métadonnées');
if (!defined('_DOCSERVER_TYPE_ADDITION'))  define('_DOCSERVER_TYPE_ADDITION', 'Add a docserver type ');
if (!defined('_COMPRESS_MODE'))  define('_COMPRESS_MODE', 'Compression mode');
if (!defined('_META_TEMPLATE'))  define('_META_TEMPLATE', 'Meta template');
if (!defined('_SIGNATURE_MODE'))  define('_SIGNATURE_MODE', 'Signature mode');
if (!defined('_CONTAINER_MAX_NUMBER'))  define('_CONTAINER_MAX_NUMBER', 'Container max number');
if (!defined('_DOCSERVER_TYPE_MODIFICATION'))  define('_DOCSERVER_TYPE_MODIFICATION', 'Docserver type modification');
if (!defined('_DOCSERVER_TYPE_ADDED'))  define('_DOCSERVER_TYPE_ADDED', 'Docserver type added ');


/***************DOCSERVERS*************************************/
if (!defined('_DOCSERVER_ID'))  define('_DOCSERVER_ID', 'Docserver ID');
if (!defined('_DEVICE_LABEL'))  define('_DEVICE_LABEL', 'Device label ');
if (!defined('_SIZE_FORMAT'))  define('_SIZE_FORMAT', 'Size format ');
if (!defined('_SIZE_LIMIT'))  define('_SIZE_LIMIT', 'Size limit');
if (!defined('_ACTUAL_SIZE'))  define('_ACTUAL_SIZE', 'Actual size');
if (!defined('_DOCSERVER_LOCATIONS'))  define('_DOCSERVER_LOCATIONS', 'Docserver locations ');
if (!defined('_DOCSERVER_MODIFICATION'))  define('_DOCSERVER_MODIFICATION', 'Docserver modification');
if (!defined('_DOCSERVER_ADDITION'))  define('_DOCSERVER_ADDITION', 'Add a docserver');
if (!defined('_DOCSERVERS_LIST'))  define('_DOCSERVERS_LIST', 'Docservers list ');
if (!defined('_ALL_DOCSERVERS'))  define('_ALL_DOCSERVERS', 'View all ');
if (!defined('_DOCSERVER'))  define('_DOCSERVER', 'a docserver');
if (!defined('_COLL_ID'))  define('_COLL_ID', 'Collection ID');
if (!defined('_PERCENTAGE_FULL'))  define('_PERCENTAGE_FULL', 'Filling percentage');
if (!defined('_IS_LOGGED'))  define('_IS_LOGGED', 'Is logged');
if (!defined('_IS_CONTAINER'))  define('_IS_CONTAINER', 'Is contained');
if (!defined('_LOG_TEMPLATE'))  define('_LOG_TEMPLATE', 'Templates for resources logged');
if (!defined('_IS_SIGNED'))  define('_IS_SIGNED', 'Is signed');
if (!defined('_SIGNATURE_MODE'))  define('_SIGNATURE_MODE', 'Signature mode');





/************DOCSERVER LOCATIONS******************************/
if (!defined('_DOCSERVER_LOCATION_ADDITION'))  define('_DOCSERVER_LOCATION_ADDITION', 'Add a docserver location');
if (!defined('_ALL_DOCSERVER_LOCATIONS'))  define('_ALL_DOCSERVER_LOCATIONS', 'View all');
if (!defined('_DOCSERVER_LOCATIONS_LIST'))  define('_DOCSERVER_LOCATIONS_LIST', 'Docserver location list');
if (!defined('_DOCSERVER_LOCATION'))  define('_DOCSERVER_LOCATION', 'a docserver location');
if (!defined('_IPV4'))  define('_IPV4', 'IPv4 Address');
if (!defined('_IPV6'))  define('_IPV6', 'IPv6 Address');
if (!defined('_NET_DOMAIN'))  define('_NET_DOMAIN', 'Net domain');
if (!defined('_DOCSERVER_LOCATION_ID'))  define('_DOCSERVER_LOCATION_ID', 'Docserver location ID');
if (!defined('_MASK'))  define('_MASK', 'Mask');



/*************CYCLE POLICIES*************************************/
if (!defined('_LC_POLICY'))  define('_LC_POLICY', 'Life cycle policy');
if (!defined('_POLICY_NAME'))  define('_POLICY_NAME', 'Policy name');
if (!defined('_LC_POLICY_ID'))  define('_LC_POLICY_ID', 'Policy ID');
if (!defined('_LC_POLICY_NAME'))  define('_LC_POLICY_NAME', 'Life cycle policy name');
if (!defined('_POLICY_DESC'))  define('_POLICY_DESC', 'Policy description');
if (!defined('_LC_POLICY_ADDITION'))  define('_LC_POLICY_ADDITION', 'Add a life cycle policy');
if (!defined('_LC_POLICIES_LIST'))  define('_LC_POLICIES_LIST', 'Life cycle policy list');
if (!defined('_ALL_LC_POLICIES'))  define('_ALL_LC_POLICIES', 'View all');
if (!defined('_LC_POLICY_UPDATED'))  define('_LC_POLICY_UPDATED', 'Life cycle policy updated');
if (!defined('_LC_POLICY_ADDED'))  define('_LC_POLICY_ADDED', 'Life cycle policy added');
if (!defined('_LC_POLICY_DELETED'))  define('_LC_POLICY_DELETED', 'Life cycle policy deleted');
?>
