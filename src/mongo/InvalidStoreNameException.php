<?php

namespace j\mongo;

use Exception;

class InvalidStoreNameException extends Exception{
    protected $message = '无效存储名称';
}
