<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ShippingController
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace Shipping\controllers;

use Entity\models\EntityModel;
use Group\models\ServiceModel;
use History\controllers\HistoryController;
use Respect\Validation\Validator;
use Shipping\models\ShippingModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\PasswordModel;

class ShippingController
{
    public function get(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_shippings', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        return $response->withJson(['shippings' => ShippingModel::get(['id', 'label', 'description', 'options', 'fee', 'entities'])]);
    }

    public function getById(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_shippings', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($aArgs['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'id is not an integer']);
        }

        $shippingInfo = ShippingModel::getById(['id' => $aArgs['id']]);
        if (empty($shippingInfo)) {
            return $response->withStatus(400)->withJson(['errors' => 'Shipping does not exist']);
        }
        
        $shippingInfo['account'] = (array)json_decode($shippingInfo['account']);
        $shippingInfo['account']['password'] = '';
        $shippingInfo['options']  = (array)json_decode($shippingInfo['options']);
        $shippingInfo['fee']      = (array)json_decode($shippingInfo['fee']);
        $shippingInfo['entities'] = (array)json_decode($shippingInfo['entities']);

        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => 'superadmin']);
        foreach ($entities as $key => $entity) {
            $entities[$key]['state']['selected'] = false;
            if (in_array($entity['id'], $shippingInfo['entities'])) {
                $entities[$key]['state']['selected'] = true;
            }
        }
        $shippingInfo['entities'] = $entities;

        return $response->withJson($shippingInfo);
    }

    public function create(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_shippings', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        
        $errors = ShippingController::checkData($body, 'create');
        if (!empty($errors)) {
            return $response->withStatus(400)->withJson(['errors' => $errors]);
        }

        if (!empty($body['account']['password'])) {
            $body['account']['password'] = PasswordModel::encrypt(['password' => $body['account']['password']]);
        }

        $body['options']  = json_encode($body['options']);
        $body['fee']      = json_encode($body['fee']);
        $body['entities'] = json_encode($body['entities']);
        $body['account']  = json_encode($body['account']);
        $id = ShippingModel::create($body);

        HistoryController::add([
            'tableName' => 'shipping',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'eventId'   => 'shippingadd',
            'info'      => _SHIPPING_ADDED . ' : ' . $body['label']
        ]);

        return $response->withJson(['shippingId' => $id]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_shippings', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        $body['id'] = $aArgs['id'];

        $errors = ShippingController::checkData($body, 'update');
        if (!empty($errors)) {
            return $response->withStatus(500)->withJson(['errors' => $errors]);
        }

        if (!empty($body['account']['password'])) {
            $body['account']['password'] = PasswordModel::encrypt(['password' => $body['account']['password']]);
        } else {
            $shippingInfo = ShippingModel::getById(['id' => $aArgs['id'], 'select' => ['account']]);
            $body['account']['password'] = $shippingInfo['account']->password;
        }

        $body['options']  = json_encode($body['options']);
        $body['fee']      = json_encode($body['fee']);
        $body['entities'] = json_encode($body['entities']);
        $body['account']  = json_encode($body['account']);

        ShippingModel::update($body);

        HistoryController::add([
            'tableName' => 'shipping',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'eventId'   => 'shippingup',
            'info'      => _SHIPPING_UPDATED. ' : ' . $body['label']
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_shippings', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($aArgs['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'id is not an integer']);
        }

        $shippingInfo = ShippingModel::getById(['id' => $aArgs['id'], 'select' => ['label']]);
        ShippingModel::delete(['id' => $aArgs['id']]);

        HistoryController::add([
            'tableName' => 'shipping',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'eventId'   => 'shippingdel',
            'info'      => _SHIPPING_DELETED. ' : ' . $shippingInfo['label']
        ]);

        $shippings = ShippingModel::get(['select' => ['id', 'label', 'description', 'options', 'fee', 'entities']]);
        return $response->withJson(['shippings' => $shippings]);
    }

    protected function checkData($aArgs, $mode)
    {
        $errors = [];

        if ($mode == 'update') {
            if (!Validator::intVal()->validate($aArgs['id'])) {
                $errors[] = 'Id is not a numeric';
            } else {
                $shippingInfo = ShippingModel::getById(['id' => $aArgs['id']]);
            }
            if (empty($shippingInfo)) {
                $errors[] = 'Shipping does not exist';
            }
        } else {
            if (!empty($aArgs['account'])) {
                if (!Validator::notEmpty()->validate($aArgs['account']['id']) || !Validator::notEmpty()->validate($aArgs['account']['password'])) {
                    $errors[] = 'account id or password is empty';
                }
            }
        }
           
        if (!Validator::notEmpty()->validate($aArgs['label']) ||
            !Validator::length(1, 64)->validate($aArgs['label'])) {
            $errors[] = 'label is empty or too long';
        }
        if (!Validator::notEmpty()->validate($aArgs['description']) ||
            !Validator::length(1, 255)->validate($aArgs['description'])) {
            $errors[] = 'description is empty or too long';
        }

        if (!empty($aArgs['entities'])) {
            if (!Validator::arrayType()->validate($aArgs['entities'])) {
                $errors[] = 'entities must be an array';
            }
            foreach ($aArgs['entities'] as $entity) {
                $info = EntityModel::getByEntityId(['entityId' => $entity, 'select' => ['id']]);
                if (empty($info)) {
                    $errors[] = $entity . ' does not exists';
                }
            }
        }

        return $errors;
    }

    public function initShipping(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_shippings', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => 'superadmin']);
        foreach ($entities as $key => $entity) {
            $entities[$key]['state']['selected'] = false;
        }

        return $response->withJson([
            'entities' => $entities,
        ]);
    }
}
