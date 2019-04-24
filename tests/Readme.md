# 常用命令

1.  php phpunit.phar --bootstrap autoload.php j/base/ConfigTest.php 
1.  php phpunit.phar --bootstrap autoload.php --no-configuration --debug  phalcon/AnnotationsTest.php
1.  php phpunit.phar --bootstrap autoload.php --no-configuration --debug --filter testMethod  phalcon/AnnotationsTest.php

--debug
    输出调试信息，例如当一个测试开始执行时输出其名称。
    
--filter
    例 3.2: 过滤器模式例子
    --filter 'TestNamespace\\TestCaseClass::testMethod'
    --filter 'TestNamespace\\TestCaseClass'
    --filter TestNamespace
    --filter TestCaseClass
    --filter testMethod
    --filter '/::testMethod .*"my named data"/'
    --filter '/::testMethod .*#5$/'
    --filter '/::testMethod .*#(5|6|7)$/'

--no-configuration
    忽略当前工作目录下的 phpunit.xml 与 phpunit.xml.dist。

--testsuite
    只运行名称与给定模式匹配的测试套件。
    
    
# 测试技巧

*   测试私有方法
    ~~~
    $method = new ReflectionMethod(UserService::class, 'getModel');
    $method->setAccessible(TRUE);
     $true = $method->invoke($this->service, $this->user->identifier());
    $this->assertEquals(true, $true);
    ~~~
*   [异常测试](http://www.phpunit.cn/manual/current/zh_cn/writing-tests-for-phpunit.html#writing-tests-for-phpunit.exceptions)