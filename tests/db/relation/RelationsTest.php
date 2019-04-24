<?php

namespace j\db\relation;

use j\db\ResultSet;
use j\db\UserDao;
use j\di\Container;
use j\model\Model;
use PHPUnit\Framework\TestCase;

/**
 * Class RelationsTest
 * @package j\db\relation
 */
class RelationsTest extends TestCase{

    /**
     * @var UserDao
     */
    protected $dao;
    protected $infoId = 376;
    protected function setUp(){
        parent::setUp();
        $this->dao = new UserDao();
        $this->dao->setContainer(Container::getInstance());
        $this->dao->table->resultAsObject(Model::class);
        $this->dao->relations = new Relations($this->dao);
    }

    public function testHasOne(){
        $relation = new HasOne($this->dao, [
            'table' => 'xyz_adminuser_info',
            'keys' => ['user_id', 'id']
            ]);
        $this->dao->relations->hasOne('detail', $relation);

        $info = $this->dao->findOne($this->infoId);
        $this->assertTrue(is_a($info, Model::class));
        $this->assertTrue(is_array($info['detail']));
    }

    public function testHasMany(){
        $relation = new HasMany($this->dao, [
            'table' => 'xyz_adminuser_keywords',
            'keys' => ['uid', 'id'],
        ]);
        $this->dao->relations->hasMany('keywords', $relation);

        $info = $this->dao->findOne($this->infoId);
        $this->assertTrue(is_a($info, Model::class));
        $this->assertTrue(is_a($info['keywords'], ResultSet::class));
        $this->assertTrue(count($info['keywords']) == 3);
    }

    public function testManyHasMany(){
        $relation = new ManyHasMany($this->dao, [
            'table' => 'xyz_images',
            'keys' => ['id', 'user_id'], // Ŀ������м���ӳ��
            'cond' => ['enable' => 1], // Ŀ����ѯ����
            'viaTable' => [
                'pk' => 'id',
                'table' => 'xyz_admin_img',
                'keys' => ['img_id', 'id'], // �м���������ӳ�䣬
                'on' => ['_order' => ['id', 'desc'], '_limit' => 20] // �����м��ѯ����
            ]
        ]);
        $this->dao->relations->manyHasMany('images', $relation);
        $info = $this->dao->findOne($this->infoId);

        $images = $info->getImages(['_limit' => 10]); // ��̬�����м������

        $this->assertTrue(is_a($info, Model::class));
        $this->assertTrue(is_a($images, ResultSet::class));
        $this->assertTrue($images == 2);
    }
}