<?php

namespace j\db\mapper;

use j\db\mapper\dataType\Json;
use j\db\mapper\dataType\DateTime;
use PHPUnit\Framework\TestCase;

class DataMapperTest extends TestCase{

    function testDataTypeFactory(){
        $f = DataTypeFactory::getInstance();
        $this->assertTrue($f->get('dateTime') instanceof DateTime);
        $this->assertTrue($f->get('json') instanceof Json);
    }

    function testMapper(){
        $defines = [
            'attrs' => ['json', ['charset' => 'gbk']],
            'tags' => 'splitArray',
            'array' => 'array',

            'create' => ['dateTime', [
                'int' => true,
                'autoCreate' => true,
                'format' => 'Y-m-d'
            ]],
            'update' => ['dateTime', [
                'autoCreate' => true,
                'format' => 'Y-m-d'
            ]],
            'age' => [
                'int',
                ['max' => 100, 'min' => 10]
            ],
            'size' => [
                'int',
                ['max' => 100, 'min' => 10]
            ],
            'weight' => ['int'],
            'tags1' => ['splitArray'],
        ];
        $mapper = new DataMapper($defines);

        $data = [
            'tags' => ['keyword1', 'keyword2'],
            'attrs' => [
                'spec' => 1,
                'value' => 2,
                'desc' => 'it is a test',
                'name' => 'gbkÖÐÎÄ'
            ],
            'array' => [
                'title' => 'test',
                'nodes' => [
                    1, 2, 3
                ]
            ],
            'create' => '2014-01-01',
            'update' => null,
            'age' => 8,
            'size' => 120,
            'weight' => 'invalid int',
            'tags1' => null,
        ];
        $dataStore = $mapper->store($data);

        $this->assertTrue(is_string($dataStore['attrs']));
        $this->assertEquals(
            $dataStore['create'],
            strtotime($data['create'])
        );

        $dataRestore = $mapper->restore($dataStore);
        $data['update'] = date('Y-m-d');
        $data['age'] = 10;
        $data['size'] = 100;
        $data['weight'] = 0;
        $data['tags1'] = [];
        $this->assertEquals($dataRestore, $data);
    }
}