<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    /**
     * test rule
     */
    public function testRule()
    {
        $value = '123456';
        $value = 'abc4567';
        $value = 'abcdef';
        $value = '123abc';
        $value = '12345';

        // 正则：6-16位 大小写字母或数字
        $reg = '/^[A-Za-z0-9]{6,16}$/';

        $result = preg_match($reg, $value) ? true : false;

        $this->assertTrue($result, 'message');
    }
}
