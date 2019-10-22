<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ResourceFolderModel
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace CustomField\models;

use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class ResourceCustomFieldModel
{
    public static function get(array $aArgs)
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        $customFields = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['resources_custom_fields'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);

        return $customFields;
    }

    public static function create(array $args)
    {
        ValidatorModel::notEmpty($args, ['res_id', 'custom_field_id', 'value']);
        ValidatorModel::intVal($args, ['res_id', 'custom_field_id']);
        ValidatorModel::stringType($args, ['value']);

        DatabaseModel::insert([
            'table'         => 'resources_custom_fields',
            'columnsValues' => [
                'res_id'            => $args['res_id'],
                'custom_field_id'   => $args['custom_field_id'],
                'value'             => $args['value'],
            ]
        ]);

        return true;
    }

    public static function delete(array $args)
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'resources_custom_fields',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}