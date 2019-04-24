<?php
# UserDao.php
namespace j\db;

use j\db\test;

class UserDao extends Dao{

    public function getTable(){
        $table =  test\table('xyz_adminuser');
        return $table;
    }

}