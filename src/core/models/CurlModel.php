<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Curl Model
 * @author dev@maarch.org
 */

namespace SrcCore\models;

class CurlModel
{
    public static function exec(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['curlCallId']);
        ValidatorModel::stringType($aArgs, ['curlCallId']);
        ValidatorModel::arrayType($aArgs, ['bodyData']);
        ValidatorModel::boolType($aArgs, ['noAuth']);

        $curlConfig = CurlModel::getConfigByCallId(['curlCallId' => $aArgs['curlCallId']]);
        if (empty($curlConfig)) {
            return [];
        }

        $opts = [
            CURLOPT_URL => $curlConfig['url'],
            CURLOPT_HTTPHEADER => [
                'accept:application/json',
                'content-type:application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
        ];
        if (empty($aArgs['noAuth'])) {
            $opts[CURLOPT_HTTPHEADER][] = 'Authorization: Basic ' . base64_encode($curlConfig['user']. ':' .$curlConfig['password']);
        }

        if ($curlConfig['method'] == 'POST' || $curlConfig['method'] == 'PUT') {
            $opts[CURLOPT_POSTFIELDS] = json_encode($aArgs['bodyData']);
        }
        if ($curlConfig['method'] == 'POST') {
            $opts[CURLOPT_POST] = true;
        } elseif ($curlConfig['method'] == 'PUT' || $curlConfig['method'] == 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = $curlConfig['method'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $rawResponse = curl_exec($curl);

        return json_decode($rawResponse, true);
    }

    public static function execSOAP(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['xmlPostString', 'url']);
        ValidatorModel::stringType($aArgs, ['xmlPostString', 'url', 'soapAction']);
        ValidatorModel::arrayType($aArgs, ['options']);

        $opts = [
            CURLOPT_URL             => $aArgs['url'],
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $aArgs['xmlPostString'],
            CURLOPT_HTTPHEADER      => [
                'content-type:text/xml;charset="utf-8"',
                'accept:text/xml',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Content-length: ' . strlen($aArgs['xmlPostString']),
            ]
        ];

        if (!empty($aArgs['soapAction'])) {
            $opts[CURLOPT_HTTPHEADER][] = "SOAPAction: \"{$aArgs['soapAction']}\"";
        }
        if (!empty($aArgs['options'])) {
            foreach ($aArgs['options'] as $key => $option) {
                $opts[$key] = $option;
            }
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $rawResponse = curl_exec($curl);

        return ['response' => simplexml_load_string($rawResponse), 'infos' => curl_getinfo($curl)];
    }

    public static function getConfigByCallId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['curlCallId']);
        ValidatorModel::stringType($aArgs, ['curlCallId']);

        $curlConfig = [];

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/curlCall.xml']);
        if ($loadedXml) {
            $curlConfig['user']     = (string)$loadedXml->user;
            $curlConfig['password'] = (string)$loadedXml->password;
            foreach ($loadedXml->call as $call) {
                if ((string)$call->id == $aArgs['curlCallId']) {
                    $curlConfig['url']      = (string)$call->url;
                    $curlConfig['method']   = strtoupper((string)$call->method);
                    if (!empty($call->file)) {
                        $curlConfig['file'] = (string)$call->file->key;
                    }
                    if (!empty($call->data)) {
                        $curlConfig['data'] = [];
                        foreach ($call->data as $data) {
                            $curlConfig['data'][(string)$data->key] = (string)$data->value;
                        }
                    }
                    if (!empty($call->rawData)) {
                        $curlConfig['rawData'] = [];
                        foreach ($call->rawData as $data) {
                            $curlConfig['rawData'][(string)$data->key] = (string)$data->value;
                        }
                    }
                    if (!empty($call->return)) {
                        $curlConfig['return'] = (string)$call->return;
                    }
                }
            }
        }

        return $curlConfig;
    }

    public static function isEnabled(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['curlCallId']);
        ValidatorModel::stringType($aArgs, ['curlCallId']);

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/curlCall.xml']);
        if ($loadedXml) {
            foreach ($loadedXml->call as $call) {
                if ((string)$call->id == $aArgs['curlCallId']) {
                    if (!empty((string)$call->enabled) && (string)$call->enabled == 'true') {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
