<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Seda Controller
* @author dev@maarch.org
*/

namespace ExportSeda\controllers;

use Attachment\models\AttachmentModel;
use Doctype\models\DoctypeModel;
use Email\models\EmailModel;
use Entity\models\EntityModel;
use Folder\models\FolderModel;
use Group\controllers\PrivilegeController;
use Note\models\NoteModel;
use Resource\controllers\ResController;
use Resource\controllers\ResourceListController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use User\models\UserModel;

class SedaController
{
    public function checkSendToRecordManagement(Request $request, Response $response, array $aArgs)
    {
        $body = $request->getParsedBody();

        if (!Validator::arrayType()->notEmpty()->validate($body['resources'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body resources is empty or not an array']);
        }

        $errors = ResourceListController::listControl(['groupId' => $aArgs['groupId'], 'userId' => $aArgs['userId'], 'basketId' => $aArgs['basketId'], 'currentUserId' => $GLOBALS['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $body['resources'] = array_slice($body['resources'], 0, 500);
        if (!ResController::hasRightByResId(['resId' => $body['resources'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $firstResource = $body['resources'][0];

        $resource = ResModel::getById(['resId' => $firstResource, 'select' => ['destination', 'type_id', 'subject', 'linked_resources']]);
        if (empty($resource)) {
            return $response->withStatus(400)->withJson(['errors' => 'resource does not exists']);
        } elseif (empty($resource['destination'])) {
            return $response->withStatus(400)->withJson(['errors' => 'resource has no destination', 'lang' => 'noDestination']);
        }

        $doctype = DoctypeModel::getById(['id' => $resource['type_id'], 'select' => ['description', 'retention_rule', 'retention_final_disposition']]);
        if (empty($doctype['retention_rule']) || empty($doctype['retention_final_disposition'])) {
            return $response->withStatus(400)->withJson(['errors' => 'retention_rule or retention_final_disposition is empty for doctype', 'lang' => 'noRetentionInfo']);
        }
        $entity = EntityModel::getByEntityId(['entityId' => $resource['destination'], 'select' => ['producer_service', 'entity_label']]);
        if (empty($entity['producer_service'])) {
            return $response->withStatus(400)->withJson(['errors' => 'producer_service is empty for this entity', 'lang' => 'noProducerService']);
        }

        $sedaXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/export_seda/xml/config.xml']);
        if (empty($sedaXml->CONFIG->senderOrgRegNumber)) {
            return $response->withStatus(400)->withJson(['errors' => 'No senderOrgRegNumber found in config.xml (export_seda)']);
        }

        $date = new \DateTime();

        $return = [
            'data' => [
                'entity' => [
                    'label'               => $entity['entity_label'],
                    'producerService'     => $entity['producer_service'],
                    'senderArchiveEntity' => (string)$sedaXml->CONFIG->senderOrgRegNumber
                ],
                'doctype' => [
                    'label'                     => $doctype['description'],
                    'retentionRule'             => $doctype['retention_rule'],
                    'retentionFinalDisposition' => $doctype['retention_final_disposition']
                ],
                'slipInfo' => [
                    'slipId'    => $GLOBALS['login'] . '-' . $date->format('Ymd-His'),
                    'archiveId' => 'archive_' . $firstResource
                ]
            ],
            'archiveUnits' => [
                [
                    'id'               => 'letterbox_' . $firstResource,
                    'label'            => $resource['subject'],
                    'type'             => 'mainDocument',
                    'descriptionLevel' => 'Item'
                ]
            ]
        ];

        $attachments = AttachmentModel::get([
            'select'  => ['res_id', 'title'],
            'where'   => ['res_id_master = ?', 'status not in (?)', 'attachment_type not in (?)'],
            'data'    => [$firstResource, ['DEL', 'OBS', 'TMP'], ['signed_response']],
            'orderBy' => ['modification_date DESC']
        ]);
        foreach ($attachments as $attachment) {
            $return['archiveUnits'][] = [
                'id'               => 'attachment_' . $attachment['res_id'],
                'label'            => $attachment['title'],
                'type'             => 'attachment',
                'descriptionLevel' => 'Item'
            ];
        }

        $notes = NoteModel::get(['select' => ['note_text', 'id'], 'where' => ['identifier = ?'], 'data' => [$firstResource]]);
        foreach ($notes as $note) {
            $return['archiveUnits'][] = [
                'id'               => 'note_' . $note['id'],
                'label'            => $note['note_text'],
                'type'             => 'note',
                'descriptionLevel' => 'Item'
            ];
        }

        $emails = EmailModel::get([
            'select'  => ['object', 'id'],
            'where'   => ['document->>\'id\' = ?', 'status = ?'],
            'data'    => [$firstResource, 'SENT'],
            'orderBy' => ['send_date desc']
        ]);
        foreach ($emails as $email) {
            $return['archiveUnits'][] = [
                'id'               => 'note_' . $email['id'],
                'label'            => $email['object'],
                'type'             => 'email',
                'descriptionLevel' => 'Item'
            ];
        }

        $return['archiveUnits'][] = [
            'id'               => 'summarySheet_' . $firstResource,
            'label'            => 'Fiche de liaison',
            'type'             => 'summarySheet',
            'descriptionLevel' => 'Item'
        ];

        $linkedResourcesIds = json_decode($resource['linked_resources'], true);
        if (!empty($linkedResourcesIds)) {
            $linkedResources = [];
            $linkedResources = ResModel::get([
                'select' => ['res_id', 'alt_identifier'],
                'where'  => ['res_id in (?)'],
                'data'   => [$linkedResourcesIds]
            ]);
            $return['additionalData']['linkedResources'] = array_column($linkedResources, 'alt_identifier');
        }

        $entities = UserModel::getEntitiesById(['id' => $aArgs['userId'], 'select' => ['entities.id']]);
        $entities = array_column($entities, 'id');

        if (empty($entities)) {
            $entities = [0];
        }

        $folders = FolderModel::getWithEntitiesAndResources([
            'select' => ['DISTINCT(folders.id)', 'folders.label'],
            'where'  => ['res_id = ?', '(entity_id in (?) OR keyword = ?)', 'folders.public = TRUE'],
            'data'   => [$firstResource, $entities, 'ALL_ENTITIES']
        ]);
        foreach ($folders as $folder) {
            $return['additionalData']['folders'][] = [
                'id'               => 'folder_' . $folder['id'],
                'label'            => $folder['label'],
                'type'             => 'folder',
                'descriptionLevel' => 'Item'
            ];
        }

        $archivalAgreements       = SedaController::getArchivalAgreements([
            'configXml'           => $sedaXml,
            'senderArchiveEntity' => (string)$sedaXml->CONFIG->senderOrgRegNumber,
            'producerService'     => $entity['producer_service']
        ]);
        if (!empty($archivalAgreements['error'])) {
            return $response->withStatus(400)->withJson(['errors' => 'ArchivalAgreements error : ' . $archivalAgreements['error']]);
        }
        $recipientArchiveEntities = SedaController::getRecipientArchiveEntities(['configXml' => $sedaXml, 'archivalAgreements' => $archivalAgreements['archivalAgreements']]);
        if (!empty($recipientArchiveEntities['error'])) {
            return $response->withStatus(400)->withJson(['errors' => 'ArchivalEntities error : ' . $recipientArchiveEntities['error']]);
        }

        $return['archivalAgreements']       = $archivalAgreements['archivalAgreements'];
        $return['recipientArchiveEntities'] = $recipientArchiveEntities['archiveEntities'];
        
        return $response->withJson($return);
    }

    public function getRecipientArchiveEntities($args = [])
    {
        $archiveEntities = [];
        if (strtolower((string)$args['configXml']->CONFIG->sae) == 'maarchrm') {
            $curlResponse = CurlModel::execSimple([
                'url'     => rtrim((string)$args['configXml']->CONFIG->urlSAEService, '/') . '/organization/organization/Byrole/archiver',
                'method'  => 'GET',
                'cookie'  => 'LAABS-AUTH=' . urlencode((string)$args['configXml']->CONFIG->token),
                'headers' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'User-Agent: ' . (string)$args['configXml']->CONFIG->userAgent
                ]
            ]);

            if (!empty($curlResponse['errors'])) {
                return ['error' => 'Error during processing in getRecipientArchiveEntities : ' . $curlResponse['errors']];
            }

            $archiveEntitiesAllowed = array_column($args['archivalAgreements'], 'archiveEntityRegNumber');

            $archiveEntities[] = [
                'id'    => "",
                'label' => null
            ];
            foreach ($curlResponse['response'] as $retentionRule) {
                if (in_array($retentionRule['registrationNumber'], $archiveEntitiesAllowed)) {
                    $archiveEntities[] = [
                        'id'    => $retentionRule['registrationNumber'],
                        'label' => $retentionRule['displayName']
                    ];
                }
            }
        } else {
            foreach ($args['configXml']->externalSAE as $value) {
                if ((string)$value->id == (string)$args['configXml']->CONFIG->sae) {
                    foreach ($value->archiveEntities->archiveEntity as $rule) {
                        $archiveEntities[] = [
                            'id'    => (string)$rule->id,
                            'label' => (string)$rule->label
                        ];
                    }
                    break;
                }
            }
        }

        return ['archiveEntities' => $archiveEntities];
    }

    public function getArchivalAgreements($args = [])
    {
        $archivalAgreements = [];
        if (strtolower((string)$args['configXml']->CONFIG->sae) == 'maarchrm') {
            $curlResponse = CurlModel::execSimple([
                'url'     => rtrim((string)$args['configXml']->CONFIG->urlSAEService, '/') . '/medona/archivalAgreement/Index',
                'method'  => 'GET',
                'cookie'  => 'LAABS-AUTH=' . urlencode((string)$args['configXml']->CONFIG->token),
                'headers' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'User-Agent: ' . (string)$args['configXml']->CONFIG->userAgent
                ]
            ]);

            if (!empty($curlResponse['errors'])) {
                return ['error' => 'Error during processing in getArchivalAgreements : ' . $curlResponse['errors']];
            }

            $producerService = SedaController::getProducerServiceInfo(['configXml' => $args['configXml'], 'producerServiceName' => $args['producerService']]);
            if (!empty($producerService['errors'])) {
                return ['error' => 'Error during processing in getArchivalAgreements producer service info : ' . $curlResponse['errors']];
            } elseif (empty($producerService['producerServiceInfo'])) {
                return ['error' => 'ProducerService does not exists in MaarchRM'];
            }

            $archivalAgreements[] = [
                'id'    => "",
                'label' => null
            ];
            foreach ($curlResponse['response'] as $retentionRule) {
                if ($retentionRule['depositorOrgRegNumber'] == $args['senderArchiveEntity'] && in_array($producerService['producerServiceInfo']['orgId'], $retentionRule['originatorOrgIds'])) {
                    $archivalAgreements[] = [
                        'id'            => $retentionRule['reference'],
                        'label'         => $retentionRule['name'],
                        'archiveEntityRegNumber' => $retentionRule['archiverOrgRegNumber']
                    ];
                }
            }
        } else {
            foreach ($args['configXml']->externalSAE as $value) {
                if ((string)$value->id == (string)$args['configXml']->CONFIG->sae) {
                    foreach ($value->archivalAgreements->archivalAgreement as $rule) {
                        $archivalAgreements[] = [
                            'id'    => (string)$rule->id,
                            'label' => (string)$rule->label
                        ];
                    }
                    break;
                }
            }
        }

        return ['archivalAgreements' => $archivalAgreements];
    }

    public function getProducerServiceInfo($args = [])
    {
        $curlResponse = CurlModel::execSimple([
            'url'     => rtrim((string)$args['configXml']->CONFIG->urlSAEService, '/') . '/organization/organization/Search?term=' . $args['producerServiceName'],
            'method'  => 'GET',
            'cookie'  => 'LAABS-AUTH=' . urlencode((string)$args['configXml']->CONFIG->token),
            'headers' => [
                'Accept: application/json',
                'Content-Type: application/json',
                'User-Agent: ' . (string)$args['configXml']->CONFIG->userAgent
            ]
        ]);

        if (!empty($curlResponse['errors'])) {
            return ['error' => $curlResponse['errors']];
        }

        return ['producerServiceInfo' => $curlResponse['response'][0]];
    }

    public function getRetentionRules(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_architecture', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $sedaXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/export_seda/xml/config.xml']);
        if (empty($sedaXml->CONFIG->sae)) {
            return $response->withStatus(400)->withJson(['errors' => 'No SAE found in config.xml (export_seda)']);
        }

        $retentionRules = [];
        if (strtolower((string)$sedaXml->CONFIG->sae) == 'maarchrm') {
            $curlResponse = CurlModel::execSimple([
                'url'     => rtrim((string)$sedaXml->CONFIG->urlSAEService, '/') . '/recordsManagement/retentionRule/Index',
                'method'  => 'GET',
                'cookie'  => 'LAABS-AUTH=' . urlencode((string)$sedaXml->CONFIG->token),
                'headers' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'User-Agent: ' . (string)$sedaXml->CONFIG->userAgent
                ]
            ]);

            if (!empty($curlResponse['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Error during processing in getRetentionRules : ' . $curlResponse['errors']]);
            }

            $retentionRules[] = [
                'id'    => "",
                'label' => null
            ];
            foreach ($curlResponse['response'] as $retentionRule) {
                $retentionRules[] = [
                    'id'    => $retentionRule['code'],
                    'label' => $retentionRule['label']
                ];
            }
        } else {
            foreach ($sedaXml->externalSAE as $value) {
                if ((string)$value->id == (string)$sedaXml->CONFIG->sae) {
                    foreach ($value->retentionRules->retentionRule as $rule) {
                        $retentionRules[] = [
                            'id'    => (string)$rule->id,
                            'label' => (string)$rule->label
                        ];
                    }
                    break;
                }
            }
        }

        return $response->withJson(['retentionRules' => $retentionRules]);
    }
}