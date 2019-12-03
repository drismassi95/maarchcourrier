<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Resource Contact Model
* @author dev@maarch.org
*/

namespace Resource\models;

use Contact\models\ContactModel;
use Entity\models\EntityModel;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class ResourceContactModel
{
    public static function getByResId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $aContacts = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['resource_contacts'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']],
        ]);

        return $aContacts;
    }

    public static function get(array $args)
    {
        ValidatorModel::arrayType($args, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($args, ['limit']);

        $contacts = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['resource_contacts'],
            'where'     => empty($args['where']) ? [] : $args['where'],
            'data'      => empty($args['data']) ? [] : $args['data'],
            'order_by'  => empty($args['orderBy']) ? [] : $args['orderBy'],
            'limit'     => empty($args['limit']) ? 0 : $args['limit']
        ]);

        return $contacts;
    }

    public static function getFormattedByResId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $aContacts = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['resource_contacts'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']],
        ]);

        foreach ($aContacts as $key => $aContact) {
            if ($aContact['type'] == 'user') {
                $user = UserModel::getLabelledUserById(['id' => $aContact['item_id']]);
                $aContacts[$key]['format'] = $user;
                $aContacts[$key]['restrictedFormat'] = $user;
            } elseif ($aContact['type'] == 'contact') {
                $contact = ContactModel::getOnView([
                    'select' => [
                        'is_corporate_person', 'lastname', 'firstname', 'address_num', 'address_street', 'address_town', 'address_postal_code',
                        'ca_id', 'society', 'contact_firstname', 'contact_lastname', 'address_country'
                    ],
                    'where' => ['ca_id = ?'],
                    'data' => [$aContact['item_id']]
                ]);
                if (isset($contact[0])) {
                    $contact = AutoCompleteController::getFormattedContact(['contact' => $contact[0]]);
                    $aContacts[$key]['format'] = $contact['contact']['otherInfo'];
                    $aContacts[$key]['restrictedFormat'] = $contact['contact']['contact'];
                }
            } elseif ($aContact['type'] == 'entity') {
                $entity = EntityModel::getById(['id' => $aContact['item_id'], 'select' => ['entity_label']]);
                $aContacts[$key]['format'] = $entity['entity_label'];
                $aContacts[$key]['restrictedFormat'] = $entity['entity_label'];
            }
        }

        return $aContacts;
    }

    public static function create(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['res_id', 'item_id', 'type', 'mode']);
        ValidatorModel::intVal($aArgs, ['res_id', 'item_id']);
        ValidatorModel::stringType($aArgs, ['type', 'mode']);

        DatabaseModel::insert([
            'table'         => 'resource_contacts',
            'columnsValues' => [
                'res_id'    => $aArgs['res_id'],
                'item_id'   => $aArgs['item_id'],
                'type'      => $aArgs['type'],
                'mode'      => $aArgs['mode']
            ]
        ]);

        return true;
    }

    public static function delete(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['where', 'data']);
        ValidatorModel::arrayType($aArgs, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'resource_contacts',
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }
}
