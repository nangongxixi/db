<?php
#DaoTest.php created by stcer@jz at 2017/4/10
namespace j\db;

use j\model\Model;
use PHPUnit\Framework\TestCase;

$_REQUEST['sqltest'] = 'jz';

/**
 * Class DaoTest
 * @package j\db
 */
class DaoTest extends TestCase{

    protected function tearDown(){
        parent::tearDown();
    }

    /**
     * @var UserDao
     */
    protected $dao;
    protected function setUp(){
        parent::setUp();
        $this->dao = new UserDao();
    }

    protected $infoId = 376;

    /**
     * update info
     */
    function testUpdate(){
        $insertId = 0;
        try{
            $data = [
                'username' => "Tester",
                'password' => "123123",
                'usergroup' => 1,
                ];
            $model = new Model($data, true);
            $rs = $insertId = $this->dao->insert($model);
            $this->assertTrue(is_numeric($rs));

            $model = $this->dao->findOne($insertId, [], true);
            $model->username = "Test121";

            $rs = $this->dao->update($model);
            $this->assertTrue($rs == 1);

            $rs = $this->dao->delete($model);
            $this->assertTrue($rs == 1);
        } catch(\Exception $e){
            if($insertId){
                $this->dao->deleteFromId($insertId);
            }
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * @depends testUpdate
     */
    function testFind(){
        $list = $this->dao->find(['_limit' => 1]);
        $this->assertTrue(count($list) == 1);
        var_dump(get_class($list));
        $this->assertTrue($list instanceof \j\db\ResultSet);
        foreach($list as $info){
            var_dump($info);
        }
    }

//    /**
//     * @depends testUpdate
//     */
//    function testFindOne(){
//        $info = $this->dao->findOne(376);
//        $this->assertTrue(is_array($info));
//    }
}

/**
 *
CREATE TABLE `xyz_admin_img` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT NULL,
`img_id` int(11) DEFAULT NULL,
`create_date` datetime DEFAULT NULL,
`enable` int(1) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

CREATE TABLE `xyz_adminuser` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`username` varchar(25) NOT NULL,
`password` varchar(50) NOT NULL,
`usergroup` tinyint(4) NOT NULL DEFAULT '9',
`addrights` mediumtext,
`minusrights` varchar(100) DEFAULT NULL,
`lastLoginAt` datetime DEFAULT NULL,
`lastLoginIp` varchar(20) DEFAULT '0.0.0.0',
`nickname` varchar(20) DEFAULT NULL,
`enable` int(11) DEFAULT '1',
`cdate` datetime DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `name` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=377 DEFAULT CHARSET=gbk;

CREATE TABLE `xyz_adminuser_info` (
`user_id` int(11) NOT NULL,
`address` varchar(45) DEFAULT NULL,
`age` int(11) DEFAULT NULL,
PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `xyz_adminuser_keywords` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`keywords` varchar(45) DEFAULT NULL,
`create_date` varchar(45) DEFAULT NULL,
`uid` int(11) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

CREATE TABLE `xyz_images` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`src` varchar(45) DEFAULT NULL,
`title` varchar(45) DEFAULT NULL,
`width` varchar(45) DEFAULT NULL,
`height` varchar(45) DEFAULT NULL,
`enable` int(11) DEFAULT '1',
PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
 *
 */