<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Entity Controller
* @author dev@maarch.org
*/

namespace Entity\controllers;

use Basket\models\BasketModel;
use Core\Models\ServiceModel;
use Core\Models\UserModel;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Entity\models\ListTemplateModel;
use Entity\models\UserEntityModel;
use History\controllers\HistoryController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use Template\models\TemplateModel;

class EntityController
{
    public function get(Request $request, Response $response)
    {
        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($entities as $key => $entity) {
            $entities[$key]['users'] = EntityModel::getUsersById(['id' => $entity['entity_id'], 'select' => ['users.user_id', 'users.firstname', 'users.lastname']]);
        }

        return $response->withJson(['entities' => $entities]);
    }

    public function getById(Request $request, Response $response, array $aArgs)
    {
        $entity = EntityModel::getById(['entityId' => $aArgs['id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        return $response->withJson(['entity' => $entity]);
    }

    public function getDetailledById(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'manage_entities', 'userId' => $GLOBALS['userId'], 'location' => 'entities', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getById(['entityId' => $aArgs['id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $aEntities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($aEntities as $aEntity) {
            if ($aEntity['entity_id'] == $aArgs['id'] && $aEntity['allowed'] == false) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $entity['types'] = EntityModel::getTypes();
        $entity['roles'] = EntityModel::getRoles();
        $listTemplateTypes = ListTemplateModel::getTypes(['select' => ['difflist_type_roles'], 'where' => ['difflist_type_id = ?'], 'data' => ['entity_id']]);
        $rolesForService = empty($listTemplateTypes[0]['difflist_type_roles']) ? [] : explode(' ', $listTemplateTypes[0]['difflist_type_roles']);
        foreach ($entity['roles'] as $key => $role) {
            if (in_array($role['id'], $rolesForService)) {
                $entity['roles'][$key]['available'] = true;
            } else {
                $entity['roles'][$key]['available'] = false;
            }
            if ($role['id'] == 'copy') {
                $entity['roles'][$key]['id'] = 'cc';
            }
        }

        $listTemplates = ListTemplateModel::get([
            'select'    => ['object_type', 'item_id', 'item_type', 'item_mode', 'title', 'description'],
            'where'     => ['object_id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        $entity['listTemplate'] = [];
        foreach ($rolesForService as $role) {
            $role == 'copy' ? $entity['listTemplate']['cc'] = [] : $entity['listTemplate'][$role] = [];
        }
        $entity['visaTemplate'] = [];
        foreach ($listTemplates as $listTemplate) {
            if ($listTemplate['object_type'] == 'entity_id' && !empty($listTemplate['item_id'])) {
                if ($listTemplate['item_type'] == 'user_id') {
                    $entity['listTemplate'][$listTemplate['item_mode']][] = [
                        'type'                  => 'user',
                        'id'                    => $listTemplate['item_id'],
                        'labelToDisplay'        => UserModel::getLabelledUserById(['userId' => $listTemplate['item_id']]),
                        'descriptionToDisplay'  => UserModel::getPrimaryEntityByUserId(['userId' => $listTemplate['item_id']])['entity_label']
                    ];
                } elseif ($listTemplate['item_type'] == 'entity_id') {
                    $entity['listTemplate'][$listTemplate['item_mode']][] = [
                        'type'                  => 'entity',
                        'id'                    => $listTemplate['item_id'],
                        'labelToDisplay'        => EntityModel::getById(['entityId' => $listTemplate['item_id'], 'select' => ['entity_label']])['entity_label'],
                        'descriptionToDisplay'  => ''
                    ];
                }
            }
            if ($listTemplate['object_type'] == 'VISA_CIRCUIT' && !empty($listTemplate['item_id'])) {
                $entity['visaTemplate'][] = [
                    'type'                  => 'user',
                    'mode'                  => $listTemplate['item_mode'],
                    'idToDisplay'           => UserModel::getLabelledUserById(['userId' => $listTemplate['item_id']]),
                    'descriptionToDisplay'  => UserModel::getPrimaryEntityByUserId(['userId' => $listTemplate['item_id']])['entity_label']
                ];
            }
        }

        $children = EntityModel::get(['select' => [1], 'where' => ['parent_entity_id = ?'], 'data' => [$aArgs['id']]]);
        $entity['hasChildren'] = count($children) > 0;
        $documents = ResModel::get(['select' => [1], 'where' => ['destination = ?'], 'data' => [$aArgs['id']]]);
        $entity['documents'] = count($documents);
        $users = EntityModel::getUsersById(['select' => [1], 'id' => $aArgs['id']]);
        $entity['users'] = count($users);
        $templates = TemplateModel::getAssociation(['select' => [1], 'where' => ['value_field = ?', 'what = ?'], 'data' => [$aArgs['id'], 'destination']]);
        $entity['templates'] = count($templates);
        $instances = ListInstanceModel::get(['select' => [1], 'where' => ['item_id = ?', 'item_type = ?'], 'data' => [$aArgs['id'], 'entity_id']]);
        $entity['instances'] = count($instances);
        $redirects = BasketModel::getGroupActionRedirect(['select' => [1], 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        $entity['redirects'] = count($redirects);

        return $response->withJson(['entity' => $entity]);
    }

    public function create(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'manage_entities', 'userId' => $GLOBALS['userId'], 'location' => 'entities', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::stringType()->notEmpty()->validate($data['id']) && preg_match("/^[\w-]*$/", $data['id']) && (strlen($data['id']) < 32);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['entity_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['short_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['entity_type']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $existingEntity = EntityModel::getById(['entityId' => $data['id'], 'select' => [1]]);
        if (!empty($existingEntity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity already exists']);
        }

        EntityModel::create($data);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $data['id'],
            'eventType' => 'ADD',
            'info'      => _ENTITY_CREATION . " : {$data['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entityCreation',
        ]);

        return $response->withJson(['entityId' => $data['id']]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'manage_entities', 'userId' => $GLOBALS['userId'], 'location' => 'entities', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getById(['entityId' => $aArgs['id'], 'select' => [1]]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $aEntities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($aEntities as $aEntity) {
            if ($aEntity['entity_id'] == $aArgs['id'] && $aEntity['allowed'] == false) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $data = $request->getParams();

        $check = Validator::stringType()->notEmpty()->validate($data['entity_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['short_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['entity_type']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        unset($data['entity_id']);
        EntityModel::update(['set' => $data, 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _ENTITY_MODIFICATION . " : {$aArgs['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entityModification',
        ]);

        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($entities as $key => $entity) {
            $entities[$key]['users'] = EntityModel::getUsersById(['id' => $entity['entity_id'], 'select' => ['users.user_id', 'users.firstname', 'users.lastname']]);
        }

        return $response->withJson(['entities' => $entities]);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'manage_entities', 'userId' => $GLOBALS['userId'], 'location' => 'entities', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getById(['entityId' => $aArgs['id'], 'select' => [1]]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $aEntities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($aEntities as $aEntity) {
            if ($aEntity['entity_id'] == $aArgs['id'] && $aEntity['allowed'] == false) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $listTemplates = ListTemplateModel::get(['select' => [1], 'where' => ['object_id = ?'], 'data' => [$aArgs['id']]]);
        $children = EntityModel::get(['select' => [1], 'where' => ['parent_entity_id = ?'], 'data' => [$aArgs['id']]]);
        $documents = ResModel::get(['select' => [1], 'where' => ['destination = ?'], 'data' => [$aArgs['id']]]);
        $users = EntityModel::getUsersById(['select' => [1], 'id' => $aArgs['id']]);
        $templates = TemplateModel::getAssociation(['select' => [1], 'where' => ['value_field = ?', 'what = ?'], 'data' => [$aArgs['id'], 'destination']]);
        $instances = ListInstanceModel::get(['select' => [1], 'where' => ['item_id = ?', 'item_type = ?'], 'data' => [$aArgs['id'], 'entity_id']]);
        $redirects = BasketModel::getGroupActionRedirect(['select' => [1], 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);

        $allowedCount = count($listTemplates) + count($children) + count($documents) + count($users) + count($templates) + count($instances) + count($redirects);
        if ($allowedCount > 0) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity is still used']);
        }

        EntityModel::delete(['where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'info'      => _ENTITY_SUPPRESSION . " : {$aArgs['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entitySuppression',
        ]);

        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($entities as $key => $entity) {
            $entities[$key]['users'] = EntityModel::getUsersById(['id' => $entity['entity_id'], 'select' => ['users.user_id', 'users.firstname', 'users.lastname']]);
        }

        return $response->withJson(['entities' => $entities]);
    }

    public function reassignEntity(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'manage_entities', 'userId' => $GLOBALS['userId'], 'location' => 'entities', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $dyingEntity = EntityModel::getById(['entityId' => $aArgs['id'], 'select' => ['parent_entity_id']]);
        $successorEntity = EntityModel::getById(['entityId' => $aArgs['newEntityId'], 'select' => [1]]);
        if (empty($dyingEntity) || empty($successorEntity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity does not exist']);
        }
        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($entities as $entity) {
            if (($entity['entity_id'] == $aArgs['id'] && $entity['allowed'] == false) || ($entity['entity_id'] == $aArgs['newEntityId'] && $entity['allowed'] == false)) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        //Documents
        ResModel::update(['set' => ['destination' => $aArgs['newEntityId']], 'where' => ['destination = ?', 'status != ?'], 'data' => [$aArgs['id'], 'DEL']]);

        //Users
        $users = UserEntityModel::get(['select' => ['user_id', 'entity_id', 'primary_entity'], 'where' => ['entity_id = ? OR entity_id = ?'], 'data' => [$aArgs['id'], $aArgs['newEntityId']]]);
        $tmpUsers = [];
        $doubleUsers = [];
        foreach ($users as $user) {
            if (in_array($user['user_id'], $tmpUsers)) {
                $doubleUsers[] = $user['user_id'];
            }
            $tmpUsers[] = $user['user_id'];
        }
        foreach ($users as $user) {
            if (in_array($user['user_id'], $doubleUsers)) {
                if ($user['entity_id'] == $aArgs['id'] && $user['primary_entity'] == 'N') {
                    UserEntityModel::delete(['where' => ['user_id = ?', 'entity_id = ?'], 'data' => [$user['user_id'], $aArgs['id']]]);
                } elseif ($user['entity_id'] == $aArgs['id'] && $user['primary_entity'] == 'Y') {
                    UserEntityModel::delete(['where' => ['user_id = ?', 'entity_id = ?'], 'data' => [$user['user_id'], $aArgs['newEntityId']]]);
                }
            }
        }
        UserEntityModel::update(['set' => ['entity_id = ?'], 'where' => ['entity_id = ?'], 'data' => [$aArgs['newEntityId'], $aArgs['id']]]);

        //Entities
        $entities = EntityModel::get(['select' => ['entity_id', 'parent_entity_id'], 'where' => ['parent_entity_id = ?'], 'data' => [$aArgs['id']]]);
        foreach ($entities as $entity) {
            if ($entity['entity_id'] = $aArgs['newEntityId']) {
                EntityModel::update(['set' => ['parent_entity_id' => $dyingEntity['parent_entity_id']], 'where' => ['entity_id = ?'], 'data' => [$aArgs['newEntityId']]]);
            } else {
                EntityModel::update(['set' => ['parent_entity_id' => $aArgs['newEntityId']], 'where' => ['entity_id = ?'], 'data' => [$entity['entity_id']]]);
            }
        }

        //Baskets
        BasketModel::updateGroupActionRedirect(['set' => ['entity_id' => $aArgs['newEntityId']], 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        //ListInstances
        ListInstanceModel::update(['set' => ['item_id' => $aArgs['newEntityId']], 'where' => ['item_id = ?', 'item_type = ?'], 'data' => [$aArgs['id'], 'entity_id']]);
        //ListTemplates
        ListTemplateModel::delete(['where' => ['object_id = ?'], 'data' => [$aArgs['id']]]);
        //Templates
        TemplateModel::updateAssociation(['set' => ['value_field' => $aArgs['newEntityId']], 'where' => ['value_field = ?', 'what = ?'], 'data' => [$aArgs['id'], 'destination']]);


        EntityModel::delete(['where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'info'      => _ENTITY_SUPPRESSION . " : {$aArgs['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entitySuppression',
        ]);

        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($entities as $key => $entity) {
            $entities[$key]['users'] = EntityModel::getUsersById(['id' => $entity['entity_id'], 'select' => ['users.user_id', 'users.firstname', 'users.lastname']]);
        }

        return $response->withJson(['entities' => $entities]);
    }

    public function updateStatus(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'manage_entities', 'userId' => $GLOBALS['userId'], 'location' => 'entities', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getById(['entityId' => $aArgs['id'], 'select' => [1]]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $aEntities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($aEntities as $aEntity) {
            if ($aEntity['entity_id'] == $aArgs['id'] && $aEntity['allowed'] == false) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $data = $request->getParams();
        $check = Validator::stringType()->notEmpty()->validate($data['method']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if ($data['method'] == 'disable') {
            $status = 'N';
        } else {
            $status = 'Y';
        }
        $fatherAndSons = EntityModel::getEntityChildren(['entityId' => $aArgs['id']]);

        EntityModel::update(['set' => ['enabled' => $status], 'where' => ['entity_id in (?)'], 'data' => [$fatherAndSons]]);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _ENTITY_MODIFICATION . " : {$aArgs['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entityModification',
        ]);

        return $response->withJson(['success' => 'success']);
    }
}
