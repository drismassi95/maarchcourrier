<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class ResControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $resController = new \Resource\controllers\ResController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $data = [
            [
                'column'    => 'subject',
                'value'     => 'Breaking News : Superman is alive - PHP unit',
                'type'      => 'string',
            ],
            [
                'column'    => 'type_id',
                'value'     => 102,
                'type'      => 'integer',
            ],
            [
                'column'    => 'typist',
                'value'     => 'LLane',
                'type'      => 'string',
            ],
            [
                'column'    => 'dest_user',
                'value'     => 'bbain',
                'type'      => 'string',
            ],
            [
                'column'    => 'priority',
                'value'     => 'poiuytre1357nbvc',
                'type'      => 'string',
            ]
        ];

        $aArgs = [
            'collId'        => 'letterbox_coll',
            'table'         => 'res_letterbox',
            'status'        => 'NEW',
            'encodedFile'   => $encodedFile,
            'fileFormat'    => 'txt',
            'data'          => $data
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->createRes($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        self::$id = $responseBody->resId;
        $this->assertInternalType('int', self::$id);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id]);

        $this->assertInternalType('array', $res);

        $this->assertSame('Breaking News : Superman is alive - PHP unit', $res['subject']);
        $this->assertSame(null, $res['title']);
        $this->assertSame(null, $res['description']);
        $this->assertSame(102, $res['type_id']);
        $this->assertSame('txt', $res['format']);
        $this->assertSame('NEW', $res['status']);
        $this->assertSame('LLane', $res['typist']);
        $this->assertSame(null, $res['destination']);
    }

    public function testCreateExt()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        
        $data = [
            [
                'column'    => 'category_id',
                'value'     => 'incoming',
                'type'      => 'string',
            ]
        ];

        $aArgs = [
            'resId' => self::$id,
            'data'  => $data
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->createExt($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        
        $this->assertSame(true, $responseBody->status);

        $ext = \Resource\models\ResModel::getExtById(['resId' => self::$id, 'select' => ['category_id']]);

        $this->assertSame('incoming', $ext['category_id']);

        $data = [
            [
                'column'    => 'category_id',
                'value'     => 'incoming',
                'type'      => 'string',
            ]
        ];

        $aArgs = [
            'resId' => self::$id,
            'data'  => $data
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->createExt($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Document already exists in mlb_coll_ext', $responseBody->errors);


        $aArgs = [
            'resId' => self::$id,
            'data'  => null
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->createExt($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Bad Request', $responseBody->errors);
    }

    public function testGetFileContent()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $resController->getFileContent($request, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);

        $response     = $resController->getFileContent($request, new \Slim\Http\Response(), ['resId' => -2]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Document does not exist', $responseBody->errors);
    }

    public function testGetThumbnailContent()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $resController->getThumbnailContent($request, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);

        $response     = $resController->getThumbnailContent($request, new \Slim\Http\Response(), ['resId' => -2]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);
    }

    public function testUpdateStatus()
    {
        $resController = new \Resource\controllers\ResController();

        //  UPDATE STATUS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'resId'         => [self::$id],
            'status'        => 'EVIS'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->updateStatus($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('success', $responseBody->success);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id]);
        $this->assertInternalType('array', $res);
        $this->assertSame('EVIS', $res['status']);

        //  UPDATE WITHOUT STATUS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'resId'         => [self::$id]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->updateStatus($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id]);
        $this->assertInternalType('array', $res);
        $this->assertSame('COU', $res['status']);
    }

    public function testUpdateExternalInfos()
    {
        $resController = new \Resource\controllers\ResController();

        //  UPDATE STATUS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        //ALL OK
        $aArgs = [
                'externalInfos' => [
                    [
                        'res_id'        => self::$id,
                        'external_id'   => "BB981212IIYZ",
                        'external_link' => "https://publik.nancy.fr/res/BB981212BB65"
                    ]
                ],
                'status'        => "GRCSENT"
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame('success', $responseBody->success);

        // EXTERNAL INFOS EMPTY AND RES ID IS NOT INTEGER
        $aArgs = [
            'externalInfos' => [
                    [
                        'res_id'        => "res_id",
                        'external_id'   => "",
                        'external_link' => ""
                    ]
                ],
            'status'        => "GRCSENT"

        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame('Bad Request: invalid res_id', $responseBody->errors);

        // DOCUMENT DOES NOT EXIST
        $aArgs = [
            'externalInfos' => [
                        [
                            'res_id'        => 123456789,
                            'external_id'   => "BB981212IIYZ",
                            'external_link' => "https://publik.nancy.fr/res/BB981212BB65"
                        ]
                    ],
            'status'        => 'GRCSENT'
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame(_DOCUMENT_NOT_FOUND, $responseBody->errors);

        //MISSING STATUS
        $aArgs = [
                'externalInfos' => [
                    [
                        'res_id'        => self::$id,
                        'external_id'   => "BB981212IIYZ",
                        'external_link' => "https://publik.nancy.fr/res/BB981212BB65"
                    ]
                ],
                'status'        => null
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame('Bad Request : status is empty', $responseBody->errors);

        //MISSING EXTERNAL INFOS
        $aArgs = [
            'externalInfos' => null,
            'status'        => "GRCSENT"
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame('Bad Request : externalInfos is empty', $responseBody->errors);
    }

    public function testGetList()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'select'        => 'res_id',
            'clause'        => '1=1',
            'withFile'      => true,
            'orderBy'       => ['res_id'],
            'limit'         => 1
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->getList($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $arr_res = $responseBody->resources;
        $this->assertNotNull($arr_res[0]->fileBase64Content);
        $this->assertInternalType('int', $arr_res[0]->res_id);

        $aArgs = [
            'select'        => 'res_id',
            'clause'        => '1=1',
            'withFile'      => false,
            'orderBy'       => ['res_id'],
            'limit'         => 1
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->getList($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $arr_res = $responseBody->resources;
        $this->assertSame(null, $arr_res[0]->fileBase64Content);
        $this->assertInternalType('int', $arr_res[0]->res_id);

        $aArgs = [
            'select'        => '',
            'clause'        => '1=1',
            'withFile'      => false,
            'orderBy'       => ['res_id'],
            'limit'         => 1
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->getList($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame("Bad Request: select is not valid", $responseBody->errors);

        $aArgs = [
            'select'        => 'res_id',
            'clause'        => '',
            'withFile'      => false,
            'orderBy'       => ['res_id'],
            'limit'         => 1
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->getList($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame("Bad Request: clause is not valid", $responseBody->errors);
    }

    public function testDelete()
    {
        //  DELETE
        \Resource\models\ResModel::delete(['resId' => self::$id]);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id]);
        $this->assertInternalType('array', $res);
        $this->assertSame('DEL', $res['status']);
    }

    public function testCreateMultipleDocument()
    {
        $resController = new \Resource\controllers\ResController();

        $aNewDocument = [
            1 => [
                102,
                'poiuytre1357nbvc',
                'NEW'
            ],
            2 => [
                103,
                'poiuytre1379nbvc',
                'COU'
            ],
            3 => [
                104,
                'poiuytre1391nbvc',
                'ENVDONE'
            ]
        ];

        foreach ($aNewDocument as $key => $value) {
            $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
            $request        = \Slim\Http\Request::createFromEnvironment($environment);
    
            $fileContent = file_get_contents('test/unitTests/samples/test.txt');
            $encodedFile = base64_encode($fileContent);
    
            $data = [
                [
                    'column'    => 'subject',
                    'value'     => $key . ' Breaking News : 12345 Superman is alive - PHP unit',
                    'type'      => 'string',
                ],
                [
                    'column'    => 'type_id',
                    'value'     => $value[0],
                    'type'      => 'integer',
                ],
                [
                    'column'    => 'typist',
                    'value'     => 'LLane',
                    'type'      => 'string',
                ],
                [
                    'column'    => 'dest_user',
                    'value'     => 'bbain',
                    'type'      => 'string',
                ],
                [
                    'column'    => 'priority',
                    'value'     => $value[1],
                    'type'      => 'string',
                ],
                [
                    'column'    => 'destination',
                    'value'     => 'PJS',
                    'type'      => 'string',
                ]
            ];
    
            $aArgs = [
                'collId'        => 'letterbox_coll',
                'table'         => 'res_letterbox',
                'status'        => $value[2],
                'encodedFile'   => $encodedFile,
                'fileFormat'    => 'txt',
                'data'          => $data
            ];
    
            $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
            $response     = $resController->createRes($fullRequest, new \Slim\Http\Response());
            $responseBody = json_decode((string)$response->getBody());
            $newId = $responseBody->resId;
            $this->assertInternalType('int', $newId);
            if ($key < 2) {
                $GLOBALS['resources'][] = $newId;
            }

            $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
            $request        = \Slim\Http\Request::createFromEnvironment($environment);
            
            $data = [
                [
                    'column'    => 'category_id',
                    'value'     => 'incoming',
                    'type'      => 'string',
                ]
            ];
    
            $aArgs = [
                'resId' => $newId,
                'data'  => $data
            ];
    
            $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
            $response     = $resController->createExt($fullRequest, new \Slim\Http\Response());
            $responseBody = json_decode((string)$response->getBody());
            
            $this->assertSame(true, $responseBody->status);

            $ext = \Resource\models\ResModel::getExtById(['resId' => $newId, 'select' => ['category_id']]);
    
            $this->assertSame('incoming', $ext['category_id']);
        }
    }
}
