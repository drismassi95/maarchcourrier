<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Template Controller
 * @author dev@maarch.org
 */

namespace Template\controllers;

use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Group\models\ServiceModel;
use History\controllers\HistoryController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use Template\models\TemplateAssociationModel;
use Template\models\TemplateModel;
use Attachment\models\AttachmentModel;
use Entity\models\EntityModel;

class TemplateController
{
    const AUTHORIZED_MIMETYPES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessing‌ml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel','application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml‌.slideshow',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.spreadsheet'
    ];

    public function get(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_templates', 'userId' => $GLOBALS['userId'], 'location' => 'templates', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $templates = TemplateModel::get();

        return $response->withJson(['templates' => $templates]);
    }

    public function getDetailledById(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_templates', 'userId' => $GLOBALS['userId'], 'location' => 'templates', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $template = TemplateModel::getById(['id' => $aArgs['id']]);
        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
        }

        $rawLinkedEntities = TemplateAssociationModel::get(['select' => ['value_field'], 'where' => ['template_id = ?'], 'data' => [$template['template_id']]]);
        $linkedEntities = [];
        foreach ($rawLinkedEntities as $rawLinkedEntity) {
            $linkedEntities[] = $rawLinkedEntity['value_field'];
        }
        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => 'superadmin']);
        foreach ($entities as $key => $entity) {
            $entities[$key]['state']['selected'] = false;
            if (in_array($entity['id'], $linkedEntities)) {
                $entities[$key]['state']['selected'] = true;
            }
        }

        $attachmentModelsTmp = AttachmentModel::getAttachmentsTypesByXML();
        $attachmentTypes = [];
        foreach ($attachmentModelsTmp as $key => $value) {
            $attachmentTypes[] = [
                'label' => $value['label'],
                'id'    => $key
            ];
        }

        return $response->withJson([
            'template'          => $template,
            'templatesModels'   => TemplateController::getModels(),
            'attachmentTypes'   => $attachmentTypes,
            'datasources'       => TemplateModel::getDatasources(),
            'entities'          => $entities
        ]);
    }

    public function create(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_templates', 'userId' => $GLOBALS['userId'], 'location' => 'templates', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();
        if (!TemplateController::checkData(['data' => $data])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if ($data['template_type'] == 'OFFICE') {
            if (empty($data['userUniqueId']) && empty($data['uploadedFile'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Template file is missing']);
            }
            if (!empty($data['userUniqueId'])) {
                if (!Validator::stringType()->notEmpty()->validate($data['template_style'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Template style is missing']);
                }
                $explodeStyle = explode(':', $data['template_style']);
                $fileOnTmp = "tmp_file_{$GLOBALS['userId']}_{$data['userUniqueId']}." . strtolower($explodeStyle[0]);
            } else {
                if (empty($data['uploadedFile']['base64']) || empty($data['uploadedFile']['name'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Uploaded file is missing']);
                }
                $fileContent = base64_decode($data['uploadedFile']['base64']);
                $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($fileContent);
                if (!in_array($mimeType, self::AUTHORIZED_MIMETYPES)) {
                    return $response->withStatus(400)->withJson(['errors' => _WRONG_FILE_TYPE]);
                }

                $fileOnTmp = rand() . $data['uploadedFile']['name'];
                $file = fopen(CoreConfigModel::getTmpPath() . $fileOnTmp, 'w');
                fwrite($file, $fileContent);
                fclose($file);
            }

            $storeResult = DocserverController::storeResourceOnDocServer([
                'collId'            => 'templates',
                'docserverTypeId'   => 'TEMPLATES',
                'fileInfos'         => [
                    'tmpDir'            => CoreConfigModel::getTmpPath(),
                    'tmpFileName'       => $fileOnTmp,
                ]
            ]);
            if (!empty($storeResult['errors'])) {
                return $response->withStatus(500)->withJson(['errors' => '[storeResource] ' . $storeResult['errors']]);
            }

            $data['template_path'] = $storeResult['destination_dir'];
            $data['template_file_name'] = $storeResult['file_destination_name'];
        }

        $id = TemplateModel::create($data);
        if (!empty($data['entities']) && is_array($data['entities'])) {
            foreach ($data['entities'] as $entity) {
                TemplateAssociationModel::create(['templateId' => $id, 'entityId' => $entity]);
            }
        }

        HistoryController::add([
            'tableName' => 'templates',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'info'      => _TEMPLATE_ADDED . " : {$data['template_label']}",
            'moduleId'  => 'template',
            'eventId'   => 'templateCreation',
        ]);

        return $response->withJson(['template' => $id]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_templates', 'userId' => $GLOBALS['userId'], 'location' => 'templates', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $template = TemplateModel::getById(['select' => ['template_style', 'template_file_name', 'template_type', 'template_target'], 'id' => $aArgs['id']]);
        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
        }

        $data = $request->getParams();
        $data['template_type'] = $template['template_type'];
        $data['template_target'] = $template['template_target'];
        if (!TemplateController::checkData(['data' => $data])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if ($data['template_type'] == 'OFFICE' && (!empty($data['userUniqueId']) || !empty($data['uploadedFile']))) {
            if (!empty($data['userUniqueId'])) {
                if (!empty($template['template_style']) && !Validator::stringType()->notEmpty()->validate($data['template_style'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Template style is missing']);
                }
                $explodeStyle = explode('.', $data['template_file_name']);
                $fileOnTmp = "tmp_file_{$GLOBALS['userId']}_{$data['userUniqueId']}." . strtolower($explodeStyle[count($explodeStyle) - 1]);
            } else {
                if (empty($data['uploadedFile']['base64']) || empty($data['uploadedFile']['name'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Uploaded file is missing']);
                }
                $fileContent = base64_decode($data['uploadedFile']['base64']);
                $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($fileContent);
                if (!in_array($mimeType, self::AUTHORIZED_MIMETYPES)) {
                    return $response->withStatus(400)->withJson(['errors' => _WRONG_FILE_TYPE]);
                }

                $fileOnTmp = rand() . $data['uploadedFile']['name'];
                $file = fopen(CoreConfigModel::getTmpPath() . $fileOnTmp, 'w');
                fwrite($file, $fileContent);
                fclose($file);
            }

            $storeResult = DocserverController::storeResourceOnDocServer([
                'collId'            => 'templates',
                'docserverTypeId'   => 'TEMPLATES',
                'fileInfos'         => [
                    'tmpDir'            => CoreConfigModel::getTmpPath(),
                    'tmpFileName'       => $fileOnTmp,
                ]
            ]);
            if (!empty($storeResult['errors'])) {
                return $response->withStatus(500)->withJson(['errors' => '[storeResource] ' . $storeResult['errors']]);
            }

            $data['template_path'] = $storeResult['destination_dir'];
            $data['template_file_name'] = $storeResult['file_destination_name'];
        }

        TemplateAssociationModel::delete(['where' => ['template_id = ?'], 'data' => [$aArgs['id']]]);
        if (!empty($data['entities']) && is_array($data['entities'])) {
            foreach ($data['entities'] as $entity) {
                TemplateAssociationModel::create(['templateId' => $aArgs['id'], 'entityId' => $entity]);
            }
        }
        unset($data['uploadedFile']);
        unset($data['userUniqueId']);
        unset($data['entities']);
        TemplateModel::update(['set' => $data, 'where' => ['template_id = ?'], 'data' => [$aArgs['id']]]);

        HistoryController::add([
            'tableName' => 'templates',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _TEMPLATE_UPDATED . " : {$data['template_label']}",
            'moduleId'  => 'template',
            'eventId'   => 'templateModification',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_templates', 'userId' => $GLOBALS['userId'], 'location' => 'templates', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $template = TemplateModel::getById(['select' => ['template_label'], 'id' => $aArgs['id']]);
        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
        }

        TemplateModel::delete(['where' => ['template_id = ?'], 'data' => [$aArgs['id']]]);
        TemplateAssociationModel::delete(['where' => ['template_id = ?'], 'data' => [$aArgs['id']]]);

        HistoryController::add([
            'tableName' => 'templates',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'info'      => _TEMPLATE_DELETED . " : {$template['template_label']}",
            'moduleId'  => 'template',
            'eventId'   => 'templateSuppression',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function duplicate(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_templates', 'userId' => $GLOBALS['userId'], 'location' => 'templates', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $template = TemplateModel::getById(['id' => $aArgs['id']]);

        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template not found']);
        }

        if ($template['template_type'] == 'OFFICE') {
            $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);

            $pathOnDocserver = DocserverController::createPathOnDocServer(['path' => $docserver['path_template']]);
            $docinfo = DocserverController::getNextFileNameInDocServer(['pathOnDocserver' => $pathOnDocserver['pathToDocServer']]);
            $docinfo['fileDestinationName'] .=  '.' . explode('.', $template['template_file_name'])[1];

            $pathToDocumentToCopy = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template['template_path']) . $template['template_file_name'];
            $copyResult = DocserverController::copyOnDocServer([
                'sourceFilePath'             => $pathToDocumentToCopy,
                'destinationDir'             => $docinfo['destinationDir'],
                'fileDestinationName'        => $docinfo['fileDestinationName']
            ]);
            if (!empty($copyResult['errors'])) {
                return $response->withStatus(500)->withJson(['errors' => 'Template duplication failed : ' . $copyResult['errors']]);
            }
            $template['template_path'] = str_replace(str_replace(DIRECTORY_SEPARATOR, '#', $docserver['path_template']), '', $copyResult['copyOnDocserver']['destinationDir']);
            $template['template_file_name'] = $copyResult['copyOnDocserver']['fileDestinationName'];
        }

        $template['template_label'] = 'Copie de ' . $template['template_label'];

        $templateId = TemplateModel::create($template);

        return $response->withJson(['id' => $templateId]);
    }

    public function initTemplates(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_templates', 'userId' => $GLOBALS['userId'], 'location' => 'templates', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $attachmentModelsTmp = AttachmentModel::getAttachmentsTypesByXML();
        $attachmentTypes = [];
        foreach ($attachmentModelsTmp as $key => $value) {
            $attachmentTypes[] = [
                'label' => $value['label'],
                'id'    => $key
            ];
        }

        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => 'superadmin']);
        foreach ($entities as $key => $entity) {
            $entities[$key]['state']['selected'] = false;
        }

        return $response->withJson([
            'templatesModels' => TemplateController::getModels(),
            'attachmentTypes' => $attachmentTypes,
            'datasources'     => TemplateModel::getDatasources(),
            'entities'        => $entities,
        ]);
    }

    public static function getModels()
    {
        $customId = CoreConfigModel::getCustomId();

        $models = [];

        if (is_dir("custom/{$customId}/modules/templates/templates/styles/office/")) {
            $path = "custom/{$customId}/modules/templates/templates/styles/office/";
        } else {
            $path = 'modules/templates/templates/styles/office/';
        }
        $officeModels = scandir($path);
        foreach ($officeModels as $value) {
            if ($value != '.' && $value != '..') {
                $file = explode('.', $value);
                $models[] = [
                    'fileName'  => $file[0],
                    'fileExt'   => strtoupper($file[1]),
                    'filePath'  => $path . $value,
                ];
            }
        }

        if (is_dir("custom/{$customId}/modules/templates/templates/styles/open_document/")) {
            $path = "custom/{$customId}/modules/templates/templates/styles/open_document/";
        } else {
            $path = 'modules/templates/templates/styles/open_document/';
        }
        $openModels = scandir($path);
        foreach ($openModels as $value) {
            if ($value != '.' && $value != '..') {
                $file = explode('.', $value);
                $models[] = [
                    'fileName'  => $file[0],
                    'fileExt'   => strtoupper($file[1]),
                    'filePath'  => $path . $value,
                ];
            }
        }
        if (is_dir("custom/{$customId}/modules/templates/templates/styles/txt/")) {
            $path = "custom/{$customId}/modules/templates/templates/styles/txt/";
        } else {
            $path = 'modules/templates/templates/styles/txt/';
        }

        $txtModels = scandir($path);
        foreach ($txtModels as $value) {
            if ($value != '.' && $value != '..') {
                $file = explode('.', $value);
                $models[] = [
                    'fileName'  => $file[0],
                    'fileExt'   => strtoupper($file[1]),
                    'filePath'  => $path . $value,
                ];
            }
        }

        return $models;
    }

    private static function checkData(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['data']);
        ValidatorModel::arrayType($aArgs, ['data']);

        $availableTypes = ['HTML', 'TXT', 'OFFICE'];
        $data = $aArgs['data'];

        $check = Validator::stringType()->notEmpty()->validate($data['template_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['template_comment']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['template_type']) && in_array($data['template_type'], $availableTypes);

        if ($data['template_type'] == 'HTML' || $data['template_type'] == 'TXT') {
            $check = $check && Validator::stringType()->notEmpty()->validate($data['template_content']);
        }

        return $check;
    }
}
