<?php

$postmanName = 'api_robo_sou';
$postmanId = "167d7612-92fa-49a9-8533-ac897b175e27";
$postmanSchema = "https://schema.getpostman.com/json/collection/v2.1.0/collection.json";
$url = '{{protocol}}://{{host}}';
$swaggerJson = json_decode(file_get_contents('http://5.183.11.4:82/swagger/v1/swagger.json'), true);

$postmanGetModel = [
    'name' => 'get ',
    'protocolProfileBehavior' => ['strictSSL' => false],
    'request' => [
        'method' => 'GET',
        'header' => [[
            "key" => "Authorization",
            "value" => "Bearer {{token}}",
            "type" => "text"
        ]],
        'url' => [
            'raw' => $url,
            'protocol' => '{{protocol}}',
            'host' => ['{{host}}'],
            'path' => []
        ]
    ],
    'response' => []
];


$postmanPostModel = [
    'name' => 'post authenticate',
    'protocolProfileBehavior' => ['strictSSL' => false],
    'request' => [
        'method' => 'POST',
        'header' => [[
            "key" => "Authorization",
            "value" => "Bearer {{token}}",
            "type" => "text"
        ]],
        'body' => [
            'mode' => 'raw',
            'raw' => "{}",
            'options' => [
                'raw' => ['language' => 'json']
            ]
        ],
        'url' => [
            'raw' => '',
            'protocol' => '{{protocol}}',
            'host' => ['{{host}}'],
            'path' => []
        ]
    ]
];

$postmanJson = [
    'info' => [
        '_postman_id' => $postmanId,
        'name' => $postmanName,
        'schema' => $postmanSchema
    ],
    "item" => []
];

$paths = $swaggerJson['paths'];
$pathsKeys = array_keys($paths);
foreach($pathsKeys as $key => $pathKeys)
{
    if ($paths[$pathKeys]['get'])
    {
        $postmanGetModel['name'] = "$pathKeys";
        $postmanGetModel['request']['url']['raw'] = "$url$pathKeys";
        $path = explode('/', $pathKeys);
        if ($path[0] === '')
        {
            unset($path[0]);
        }
        $pathArr = [];
        foreach ($path as $pathObj)
        {
            $pathArr[] = $pathObj;
        }
        $postmanGetModel['request']['url']['path'] = $pathArr;
        $postmanJson['item'][] = $postmanGetModel;
    }
    if ($paths[$pathKeys]['post'])
    {
        $postmanPostModel['name'] = "$pathKeys";
        $postmanPostModel['request']['url']['raw'] = "$url$pathKeys";
        $path = explode('/', $pathKeys);
        if ($path[0] === '')
        {
            unset($path[0]);
        }
        $pathArr = [];
        foreach ($path as $pathObj)
        {
            $pathArr[] = $pathObj;
        }
        $postmanPostModel['request']['url']['path'] = $pathArr;
        $requestBody = $paths[$pathKeys]['post']['requestBody'];
        $postmanPostModel['request']['body']['raw'] = $requestBody
            ? json_encode($requestBody['content']['application/json']['schema']['$ref'])
            : '';
        $postmanJson['item'][] = $postmanPostModel;
    }
}

$controllers = [];
$j = 0;
foreach($postmanJson['item'] as $i => $postmanItem)
{
    $controller = explode('/', $postmanItem['name'])[1];
    if ($i === 0 || $controller !== $controllers[$j - 1])
    {
        $controllers[] = $controller;
        $j++;
    }
}

function getObjectModel($rawPath, $swaggerJson)
{
    if ($rawPath)
    {
        $rawPath = explode('/', $rawPath)[3];
        if (preg_match('/"/', $rawPath))
        {
            $rawPath = substr($rawPath, 0, -1);
        }
        // $rawPath = str_replace($rawPath, '', '"');
        if ($rawPath !== null)
        {
            foreach($swaggerJson['components']['schemas'] as $model => $schema)
            {
                if ($model === $rawPath)
                {
                    $modelObj = [];
                    foreach($schema['properties'] as $key => $prop)
                    {
                        if ($prop['type'] === 'string' or $prop['type'] === 'integer')
                        {
                            $modelObj[$key] = [
                                'string' => '',
                                'integer' => 0
                            ][$prop['type']];
                            continue;
                        }
                        if ($prop['type'] === 'array') {
                            $modelObj[$key] = [];
                            if ($prop['items']['$ref'])
                            {
                                $modelObj[$key][] = getObjectModel($prop['items']['$ref'], $swaggerJson);
                                continue;
                            }
                            if ($prop['items']['type'])
                            {
                                $modelObj[$key][] = [
                                    'string' => '',
                                    'integer' => 0
                                ][$prop['items']['type']];
                                continue;
                            }
                        }
                        if (!$prop['type'])
                        {
                            $modelObj[$key] = getObjectModel($prop['$ref'], $swaggerJson);
                            continue;
                        }
                    }
                    return $modelObj;
                }
            }
        }
    }
}

$controllers = array_map(function ($controller) use ($postmanJson, $swaggerJson) {
    $folder = ['name' => $controller];
    $items = [];  
    foreach ($postmanJson['item'] as $item)
    {
        $controllerName = explode('/', $item['name'])[1];
        if ($controllerName === $controller)
        {
            $itemNameArr = explode('/', $item['name']);
            unset($itemNameArr[0]);
            unset($itemNameArr[1]);
            $item['name'] = implode('/', $itemNameArr) ?? '/';
            if ($item['request']['method'] === 'POST')
            {
                $rawPath = $item['request']['body']['raw'];
                $item['request']['body']['raw'] = json_encode(getObjectModel($rawPath, $swaggerJson));
            }
            $items[] = $item;
        }
    }
    $folder['item'] = $items;
    return $folder;
}, $controllers);

$postmanJson['item'] = $controllers;


file_put_contents('postman-result.json', json_encode($postmanJson));