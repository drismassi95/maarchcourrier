<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief User Model
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Models;

use Core\Controllers\HistoryController;
use Entities\Models\EntityModel;

class UserModelAbstract
{
    public static function get(array $aArgs = [])
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data']);

        $aUsers = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data']
        ]);

        return $aUsers;
    }

    public static function getById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $aUser = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users'],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        if (empty($aUser)) {
            return [];
        }

        return $aUser[0];
    }

    public static function create(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['user']);
        ValidatorModel::notEmpty($aArgs['user'], ['userId', 'firstname', 'lastname']);
        ValidatorModel::stringType($aArgs['user'], ['userId', 'firstname', 'lastname', 'mail', 'initials', 'thumbprint', 'phone', 'changePassword']);

        DatabaseModel::insert([
            'table'         => 'users',
            'columnsValues' => [
                'user_id'           => $aArgs['user']['userId'],
                'firstname'         => $aArgs['user']['firstname'],
                'lastname'          => $aArgs['user']['lastname'],
                'mail'              => $aArgs['user']['mail'],
                'phone'             => $aArgs['user']['phone'],
                'initials'          => $aArgs['user']['initials'],
                'thumbprint'        => $aArgs['user']['thumbprint'],
                'enabled'           => 'Y',
                'status'            => 'OK',
                'change_password'   => empty($aArgs['user']['changePassword']) ? 'Y' : $aArgs['user']['changePassword'],
                'loginmode'         => 'standard',
                'password'          => SecurityModel::getPasswordHash('maarch')
            ]
        ]);

        return true;
    }

    public static function update(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'user']);
        ValidatorModel::notEmpty($aArgs['user'], ['firstname', 'lastname']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs['user'], ['firstname', 'lastname', 'mail', 'initials', 'thumbprint', 'phone', 'enabled']);

        DatabaseModel::update([
            'table'     => 'users',
            'set'       => [
                'firstname'     => $aArgs['user']['firstname'],
                'lastname'      => $aArgs['user']['lastname'],
                'mail'          => $aArgs['user']['mail'],
                'phone'         => $aArgs['user']['phone'],
                'initials'      => $aArgs['user']['initials'],
                'enabled'       => $aArgs['user']['enabled'],
                'thumbprint'    => $aArgs['user']['thumbprint']
            ],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        return true;
    }

    public static function delete(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        DatabaseModel::update([
            'table'     => 'users',
            'set'       => [
                'status'    => 'DEL',
            ],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        return true;
    }

    public static function getByUserId(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $aUser = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users'],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        if (empty($aUser)) {
            return [];
        }

        return $aUser[0];
    }

    public static function getByEntities(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['entities']);
        ValidatorModel::arrayType($aArgs, ['entities']);

        $aUsers = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users, users_entities'],
            'where'     => ['users.user_id = users_entities.user_id', 'users_entities.entity_id in (?)'],
            'data'      => [$aArgs['entities']]
        ]);

        return $aUsers;
    }

    public static function getByEmail(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['mail']);
        ValidatorModel::stringType($aArgs, ['mail']);

        $aUser = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users'],
            'where'     => ['mail = ? and status = ?'],
            'data'      => [$aArgs['mail'], 'OK'],
            'limit'     => 1
        ]);

        return $aUser;
    }

    public static function updatePassword(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'password']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['password']);

        DatabaseModel::update([
            'table'     => 'users',
            'set'       => [
                'password'  => SecurityModel::getPasswordHash($aArgs['password'])
            ],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        return true;
    }

    public static function resetPassword(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        DatabaseModel::update([
            'table'     => 'users',
            'set'       => [
                'password'  => SecurityModel::getPasswordHash('maarch')
            ],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        return true;
    }

    public static function createSignature(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userSerialId', 'signatureLabel', 'signaturePath', 'signatureFileName']);
        ValidatorModel::stringType($aArgs, ['signatureLabel', 'signaturePath', 'signatureFileName']);
        ValidatorModel::intVal($aArgs, ['userSerialId']);

        DatabaseModel::insert([
            'table'         => 'user_signatures',
            'columnsValues' => [
                'user_serial_id'        => $aArgs['userSerialId'],
                'signature_label'       => $aArgs['signatureLabel'],
                'signature_path'        => $aArgs['signaturePath'],
                'signature_file_name'   => $aArgs['signatureFileName']
            ]
        ]);

        return true;
    }

    public static function updateSignature(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['signatureId', 'userSerialId', 'label']);
        ValidatorModel::stringType($aArgs, ['label']);
        ValidatorModel::intVal($aArgs, ['signatureId', 'userSerialId']);

        DatabaseModel::update([
            'table'     => 'user_signatures',
            'set'       => [
                'signature_label'   => $aArgs['label']
            ],
            'where'     => ['user_serial_id = ?', 'id = ?'],
            'data'      => [$aArgs['userSerialId'], $aArgs['signatureId']]
        ]);

        return true;
    }

    public static function deleteSignature(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['signatureId', 'userSerialId']);
        ValidatorModel::intVal($aArgs, ['signatureId', 'userSerialId']);

        DatabaseModel::delete([
            'table'     => 'user_signatures',
            'where'     => ['user_serial_id = ?', 'id = ?'],
            'data'      => [$aArgs['userSerialId'], $aArgs['signatureId']],
        ]);

        return true;
    }

    public static function createEmailSignature(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId', 'title', 'htmlBody']);
        ValidatorModel::stringType($aArgs, ['userId', 'title', 'htmlBody']);

        DatabaseModel::insert([
            'table'         => 'users_email_signatures',
            'columnsValues' => [
                'user_id'   => $aArgs['userId'],
                'title'     => $aArgs['title'],
                'html_body' => $aArgs['htmlBody']
            ]
        ]);

        return true;
    }

    public static function updateEmailSignature(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id','userId', 'title', 'htmlBody']);
        ValidatorModel::stringType($aArgs, ['userId', 'title', 'htmlBody']);
        ValidatorModel::intVal($aArgs, ['id']);

        DatabaseModel::update([
            'table'     => 'users_email_signatures',
            'set'       => [
                'title'     => $aArgs['title'],
                'html_body' => $aArgs['htmlBody'],
            ],
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$aArgs['userId'], $aArgs['id']]
        ]);

        return true;
    }

    public static function deleteEmailSignature(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        DatabaseModel::delete([
            'table'     => 'users_email_signatures',
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$aArgs['userId'], $aArgs['id']]
        ]);

        return true;
    }

    public static function getSignaturesById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $aReturn = DatabaseModel::select([
            'select'    => ['id', 'user_serial_id', 'signature_label', 'signature_path', 'signature_file_name'],
            'table'     => ['user_signatures'],
            'where'     => ['user_serial_id = ?'],
            'data'      => [$aArgs['id']],
            'order_by'  => ['id']
        ]);

        $docserver = [];
        if (!empty($aReturn)) {
            $docserver = DocserverModel::getByTypeId(['docserver_type_id' => 'TEMPLATES', 'select' => ['path_template']]);
        }

        if (!file_exists($docserver['path_template'])) {
            return [];
        }
        $tmpPath = CoreConfigModel::getTmpPath();
        $urlTmpPath = str_replace('rest/', '', \Url::coreurl()) . 'apps/maarch_entreprise/tmp/';
        foreach($aReturn as $key => $value) {
            $pathToSignature = $docserver['path_template'] . str_replace('#', '/', $value['signature_path']) . $value['signature_file_name'];

            $extension = explode('.', $pathToSignature);
            $extension = $extension[count($extension) - 1];
            $fileNameOnTmp = 'tmp_file_' . $aArgs['id'] . '_' . rand() . '.' . strtolower($extension);
            $filePathOnTmp = $tmpPath . $fileNameOnTmp;
            if (file_exists($pathToSignature) && copy($pathToSignature, $filePathOnTmp)) {
                $aReturn[$key]['pathToSignatureOnTmp'] = $urlTmpPath . $fileNameOnTmp;
            } else {
                $aReturn[$key]['pathToSignatureOnTmp'] = '';
            }
            $aReturn[$key]['pathToSignature'] = $pathToSignature;

            unset($aReturn[$key]['signature_path'], $aReturn[$key]['signature_file_name']);
        }

        return $aReturn;
    }

    public static function getSignatureWithSignatureIdById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'signatureId']);
        ValidatorModel::intVal($aArgs, ['id','signatureId']);

        $aReturn = DatabaseModel::select([
            'select'    => ['id', 'user_serial_id', 'signature_label'],
            'table'     => ['user_signatures'],
            'where'     => ['user_serial_id = ?', 'id = ?'],
            'data'      => [$aArgs['id'], $aArgs['signatureId']],
        ]);

        return $aReturn[0];
    }

    public static function getEmailSignaturesById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users_email_signatures'],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']],
            'order_by'  => ['id']
        ]);

        return $aReturn;
    }

    public static function getEmailSignatureWithSignatureIdById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId', 'signatureId']);
        ValidatorModel::stringType($aArgs, ['userId']);
        ValidatorModel::intVal($aArgs, ['signatureId']);

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users_email_signatures'],
            'where'     => ['user_id = ?', 'id = ?'],
            'data'      => [$aArgs['userId'], $aArgs['signatureId']],
        ]);

        return $aReturn[0];
    }

    public static function getLabelledUserById(array $aArgs = [])
    {
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['userId']);

        if (!empty($aArgs['id'])) {
            $rawUser = UserModel::getById(['id' => $aArgs['id'], 'select' => ['firstname', 'lastname']]);
        } elseif (!empty($aArgs['userId'])) {
            $rawUser = UserModel::getByUserId(['userId' => $aArgs['userId'], 'select' => ['firstname', 'lastname']]);
        }

        $labelledUser = '';
        if (!empty($rawUser)) {
            $labelledUser = $rawUser['firstname']. ' ' .$rawUser['lastname'];
        }

        return $labelledUser;
    }

    public static function getCurrentConsigneById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);


        $aReturn = DatabaseModel::select([
            'select'    => ['process_comment'],
            'table'     => ['listinstance'],
            'where'     => ['res_id = ?', 'process_date is null', 'item_mode in (?)'],
            'data'      => [$aArgs['resId'], ['visa', 'sign']],
            'order_by'  => ['listinstance_id ASC'],
            'limit'     => 1
        ]);

        if (empty($aReturn[0])) {
            return '';
        }

        return $aReturn[0]['process_comment'];
    }

    public static function getPrimaryGroupById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);


        $aGroup = DatabaseModel::select([
            'select'    => ['usergroup_content.group_id', 'usergroups.group_desc'],
            'table'     => ['usergroup_content, usergroups'],
            'where'     => ['usergroup_content.group_id = usergroups.group_id', 'usergroup_content.user_id = ?', 'usergroup_content.primary_group = ?'],
            'data'      => [$aArgs['userId'], 'Y']
        ]);

        if (empty($aGroup[0])) {
            return [];
        }

        return $aGroup[0];
    }

    public static function getPrimaryEntityByUserId(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $aEntity = DatabaseModel::select([
            'select'    => ['users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity'],
            'table'     => ['users_entities, entities'],
            'where'     => ['users_entities.entity_id = entities.entity_id', 'users_entities.user_id = ?', 'users_entities.primary_entity = ?'],
            'data'      => [$aArgs['userId'], 'Y']
        ]);

        if (empty($aEntity[0])) {
            return [];
        }

        return $aEntity[0];
    }

    public static function getGroupsByUserId(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $aGroups = DatabaseModel::select([
            'select'    => ['usergroup_content.group_id', 'usergroups.group_desc', 'usergroup_content.primary_group', 'usergroup_content.role', 'security.maarch_comment'],
            'table'     => ['usergroup_content, usergroups, security'],
            'where'     => ['usergroup_content.group_id = usergroups.group_id', 'usergroup_content.user_id = ?','usergroups.group_id = security.group_id'],
            'data'      => [$aArgs['userId']]
        ]);

        return $aGroups;
    }

    public static function getEntitiesById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $aEntities = DatabaseModel::select([
            'select'    => ['users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity'],
            'table'     => ['users_entities, entities'],
            'where'     => ['users_entities.entity_id = entities.entity_id', 'users_entities.user_id = ?'],
            'data'      => [$aArgs['userId']],
            'order_by'  => ['users_entities.primary_entity DESC']
        ]);

        return $aEntities;
    }

    public static function getServicesById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $aServices = DatabaseModel::select([
            'select'    => ['usergroups_services.service_id'],
            'table'     => ['usergroup_content, usergroups_services'],
            'where'     => ['usergroup_content.group_id = usergroups_services.group_id', 'usergroup_content.user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        return $aServices;
    }

    public static function activateAbsenceById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        DatabaseModel::update([
            'table'     => 'users',
            'set'       => [
                'status'    => 'ABS'
            ],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        return true;
    }

    public static function desactivateAbsenceById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);

        DatabaseModel::delete([
            'table'     => 'user_abs',
            'where'     => ['user_abs = ?'],
            'data'      => [$user['user_id']]
        ]);

        DatabaseModel::update([
            'table'     => 'users',
            'set'       => [
                'status'    => 'OK'
            ],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        return true;
    }

    public static function hasGroup(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'groupId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['groupId']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        $groups = UserModel::getGroupsByUserId(['userId' => $user['user_id']]);
        foreach ($groups as $value) {
            if ($value['group_id'] == $aArgs['groupId']) {
                return true;
            }
        }

        return false;
    }

    public static function addGroup(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'groupId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['groupId', 'role']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        DatabaseModel::insert([
            'table'         => 'usergroup_content',
            'columnsValues' => [
                'user_id'       => $user['user_id'],
                'group_id'      => $aArgs['groupId'],
                'role'          => $aArgs['role'],
                'primary_group' => 'Y'
            ]
        ]);

        $groupInfos = GroupModel::getById(['groupId' => $aArgs['groupId']]);

        HistoryController::add([
            'tableName' => 'users',
            'recordId'  =>$user['user_id'],
            'eventType' => 'GROUP ADD',
            'eventId'   => 'groupadded',
            'info'       =>$_SESSION['user']['UserId'].' '._ADDED_USER.' '.$user['user_id'].' '._IN_GROUP.' '.$groupInfos['group_desc']
        ]);

        return true;
    }

    public static function updateGroup(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'groupId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['groupId', 'role']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        DatabaseModel::update([
            'table'     => 'usergroup_content',
            'set'       => [
                'role'      => $aArgs['role']
            ],
            'where'     => ['user_id = ?', 'group_id = ?'],
            'data'      => [$user['user_id'], $aArgs['groupId']]
        ]);

        return true;
    }

    public static function deleteGroup(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'groupId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['groupId']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        DatabaseModel::delete([
            'table'     => 'usergroup_content',
            'where'     => ['group_id = ?', 'user_id = ?'],
            'data'      => [$aArgs['groupId'], $user['user_id']]
        ]);

        $groupInfos = GroupModel::getById(['groupId' => $aArgs['groupId']]);

        HistoryController::add([
            'tableName' => 'users',
            'recordId'  =>$user['user_id'],
            'eventType' => 'GROUP DELETED',
            'eventId'   => 'groupdeleted',
            'info'       =>$_SESSION['user']['UserId'].' '._REMOVED_USER.' '.$user['user_id'].' '._FROM_GROUP.' '.$groupInfos['group_desc']
        ]);

        return true;
    }

    public static function hasEntity(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'entityId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['entityId']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        $entities = UserModel::getEntitiesById(['userId' => $user['user_id']]);
        foreach ($entities as $value) {
            if ($value['entity_id'] == $aArgs['entityId']) {
                return true;
            }
        }

        return false;
    }

    public static function addEntity(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'entityId', 'primaryEntity']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['entityId', 'role', 'primaryEntity']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        DatabaseModel::insert([
            'table'         => 'users_entities',
            'columnsValues' => [
                'user_id'           => $user['user_id'],
                'entity_id'         => $aArgs['entityId'],
                'user_role'         => $aArgs['role'],
                'primary_entity'    => $aArgs['primaryEntity']
            ]
        ]);

        $entityInfos = EntityModel::getByID(['entityId' => $aArgs['entityId']]);

        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $user['user_id'],
            'eventType' => 'ENTITY ADD',
            'eventId'   => 'entityadded',
            'info'       => $_SESSION['user']['UserId'].' '._ADDED_USER.' '.$user['user_id'].' '._IN_ENTITY.' '.$entityInfos['entity_label']
        ]);

        return true;
    }

    public static function updateEntity(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'entityId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['entityId', 'role']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        DatabaseModel::update([
            'table'     => 'users_entities',
            'set'       => [
                'user_role'      => $aArgs['role']
            ],
            'where'     => ['user_id = ?', 'entity_id = ?'],
            'data'      => [$user['user_id'], $aArgs['entityId']]
        ]);

        return true;
    }

    public static function updatePrimaryEntity(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'entityId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['entityId']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        $entities = EntityModel::getByUserId(['userId' => $user['user_id']]);
        foreach ($entities as $entity) {
            if ($entity['primary_entity'] == 'Y') {
                DatabaseModel::update([
                    'table'     => 'users_entities',
                    'set'       => [
                        'primary_entity'    => 'N'
                    ],
                    'where'     => ['user_id = ?', 'entity_id = ?'],
                    'data'      => [$user['user_id'], $entity['entity_id']]
                ]);
            }
        }

        DatabaseModel::update([
            'table'     => 'users_entities',
            'set'       => [
                'primary_entity'    => 'Y'
            ],
            'where'     => ['user_id = ?', 'entity_id = ?'],
            'data'      => [$user['user_id'], $aArgs['entityId']]
        ]);

        return true;
    }

    public static function reassignPrimaryEntity(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $entities = EntityModel::getByUserId(['userId' => $aArgs['userId']]);
        if (!empty($entities[0])) {
            DatabaseModel::update([
                'table'     => 'users_entities',
                'set'       => [
                    'primary_entity'    => 'Y'
                ],
                'where'     => ['user_id = ?', 'entity_id = ?'],
                'data'      => [$aArgs['userId'], $entities[0]['entity_id']]
            ]);
        }

        return true;
    }

    public static function deleteEntity(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'entityId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['entityId']);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        DatabaseModel::delete([
            'table'     => 'users_entities',
            'where'     => ['entity_id = ?', 'user_id = ?'],
            'data'      => [$aArgs['entityId'], $user['user_id']]
        ]);

        $entityInfos=EntityModel::getByID(['entityId' => $aArgs['entityId']]);

        HistoryController::add([
            'tableName' => 'users',
            'recordId'  =>$user['user_id'],
            'eventType' => 'ENTITY DELETE',
            'eventId'   => 'entitydeleted',
            'info'       =>$_SESSION['user']['UserId'].' '._REMOVED_USER.' '.$user['user_id'].' '._FROM_ENTITY.' '.$entityInfos['entity_label']
        ]);

        return true;
    }

    public static function updateBasketColor(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'groupId', 'basketId', 'color']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['groupId', 'basketId', 'color']);

        $isPresent = DatabaseModel::select([
            'select'    => ['1'],
            'table'     => ['users_baskets'],
            'where'     => ['user_serial_id = ?', 'group_id = ?', 'basket_id = ?'],
            'data'      => [$aArgs['id'], $aArgs['groupId'], $aArgs['basketId']]
        ]);

        if (empty($isPresent)) {
            DatabaseModel::insert(
                [
                    'table'         => 'users_baskets',
                    'columnsValues' => [
                        'user_serial_id'    => $aArgs['id'],
                        'basket_id'         => $aArgs['basketId'],
                        'group_id'          => $aArgs['groupId'],
                        'color'             => $aArgs['color']
                    ]
                ]
            );
        } else {
            DatabaseModel::update([
                'table'     => 'users_baskets',
                'set'       => [
                    'color'    => $aArgs['color']
                ],
                'where'     => ['user_serial_id = ?', 'group_id = ?', 'basket_id = ?'],
                'data'      => [$aArgs['id'], $aArgs['groupId'], $aArgs['basketId']]
            ]);
        }

        return true;
    }

    public static function eraseBasketColor(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'groupId', 'basketId']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['groupId', 'basketId']);

        DatabaseModel::delete(
            [
                'table' => 'users_baskets',
                'where' => ['user_serial_id = ?', 'group_id = ?', 'basket_id = ?'],
                'data'  => [$aArgs['id'], $aArgs['groupId'], $aArgs['basketId']]
            ]
        );

        return true;
    }


}
