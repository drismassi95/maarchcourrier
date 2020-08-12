<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*/

/**
 * @brief Registered Number Range Model
 * @author dev@maarch.org
 */

namespace RegisteredMail\models;

use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class RegisteredNumberRangeModel
{
    public static function get(array $args = [])
    {
        ValidatorModel::arrayType($args, ['select']);

        return DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['registered_number_range']
        ]);
    }

    public static function getById(array $args)
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);
        ValidatorModel::arrayType($args, ['select']);

        $site = DatabaseModel::select([
            'select' => empty($args['select']) ? ['*'] : $args['select'],
            'table'  => ['registered_number_range'],
            'where'  => ['id = ?'],
            'data'   => [$args['id']]
        ]);

        if (empty($site[0])) {
            return [];
        }

        return $site[0];
    }

    public static function create(array $args)
    {
        ValidatorModel::notEmpty($args, ['type', 'rangeStart', 'rangeEnd', 'siteId']);
        ValidatorModel::stringType($args, ['type']);
        ValidatorModel::intVal($args, ['rangeStart', 'rangeEnd', 'siteId']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'registered_number_range_id_seq']);

        DatabaseModel::insert([
            'table'         => 'registered_number_range',
            'columnsValues' => [
                'id'                      => $nextSequenceId,
                'type'                    => $args['type'],
                'tracking_account_number' => $args['trackingAccountNumber'],
                'range_start'             => $args['rangeStart'],
                'range_end'               => $args['rangeEnd'],
                'creator'                 => $args['id'],
                'site_id'                 => $args['siteId']
            ]
        ]);

        return $nextSequenceId;
    }

    public static function update(array $args)
    {
        ValidatorModel::notEmpty($args, ['where']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'registered_number_range',
            'set'   => empty($args['set']) ? [] : $args['set'],
            'where' => $args['where'],
            'data'  => empty($args['data']) ? [] : $args['data']
        ]);

        return true;
    }

    public static function delete(array $args)
    {
        ValidatorModel::notEmpty($args, ['where', 'data']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'registered_number_range',
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}