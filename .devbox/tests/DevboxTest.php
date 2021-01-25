<?php

use PHPUnit\Framework\TestCase;

class DevboxTest extends TestCase
{

    public function testExtrapolateEnv()
    {
        $_ENV = [
            'DB_NAME' => 'database',
            'DB_USER' => 'username',
            'DB_PASS' => 'password',
        ];

        $input = 'name = $(DB_NAME), user = $(DB_USER), pass = $(DB_PASS)';
        $expected = 'name = database, user = username, pass = password';

        self::assertEquals(
            $expected,
            \Devbox\Devbox::extrapolateEnv($input)
        );
    }
}
