<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Convert PDF Controller
 * @author dev@maarch.org
 */

namespace Convert\controllers;


use Attachment\models\AttachmentModel;
use Convert\models\AdrModel;
use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Parameter\models\ParameterModel;
use Resource\models\ResModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;

class ConvertPdfController
{
    public static function convert(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['collId', 'resId']);
        ValidatorModel::stringType($aArgs, ['collId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::boolType($aArgs, ['isVersion']);

        $resource = AttachmentModel::getById(['id' => $aArgs['resId'], 'isVersion' => $aArgs['isVersion'], 'select' => ['docserver_id', 'path', 'filename']]);
        
        if (empty($resource)) {
            return ['errors' => '[ConvertPdf] Resource does not exist'];
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $resource['docserver_id'], 'select' => ['path_template']]);

        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => '[ConvertPdf] Docserver does not exist'];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $resource['path']) . $resource['filename'];

        if (!file_exists($pathToDocument)) {
            return ['errors' => '[ConvertPdf] Document does not exist on docserver'];
        }

        $docInfo = pathinfo($pathToDocument);

        $ext = pathinfo($pathToDocument, PATHINFO_EXTENSION);
        $tmpPath = CoreConfigModel::getTmpPath();
        $fileNameOnTmp = rand() . $docInfo["filename"];

        copy($pathToDocument, $tmpPath.$fileNameOnTmp.'.'.$docInfo["extension"]);

        $command = "unoconv -f pdf " . escapeshellarg($tmpPath.$fileNameOnTmp.'.'.$docInfo["extension"]);

        
        exec('export HOME=/tmp && '.$command, $output, $return);

        if (!file_exists($tmpPath.$fileNameOnTmp.'.pdf')) {
            return ['errors' => '[ConvertPdf]  Conversion failed ! '. implode(" ", $output)];
        }
        
        $storeResult = DocserverController::storeResourceOnDocServer([
            'collId'    => $aArgs['collId'],
            'fileInfos' => [
                'tmpDir'        => $tmpPath,
                'tmpFileName'   => $fileNameOnTmp . '.pdf',
            ],
            'docserverTypeId'   => 'CONVERT'
        ]);

        if (!empty($storeResult['errors'])) {
            return ['errors' => "[ConvertPdf] {$storeResult['errors']}"];
        }

        AdrModel::createAttachAdr([
            'resId'         => $aArgs['resId'],
            'isVersion'     => $aArgs['isVersion'],
            'type'          => 'PDF',
            'docserverId'   => $storeResult['docserver_id'],
            'path'          => $storeResult['destination_dir'],
            'filename'      => $storeResult['file_destination_name'],
        ]);

        return true;
    }
}
