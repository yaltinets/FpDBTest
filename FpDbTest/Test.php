<?php

use dbtesttask\Database;
use dbtesttask\DatabaseTest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;

spl_autoload_register(function ($class) {
    $a = array_slice(explode('\\', $class), 1);
    if (!$a) {
        throw new Exception();
    }
    $filename = implode('/', [__DIR__, ...$a]) . '.php';
    require_once $filename;
});

class DatabaseTestWithMock extends TestCase
{
    /** @var MockObject */
    private $mysqliMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->mysqliMock = $this->getMockBuilder(mysqli::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testBuildQuery(): void
    {
        $mysqli = $this->mysqliMock;

        $mysqli->expects($this->any())
            ->method('real_escape_string')
            ->willReturnCallback(function ($string) {
                return addslashes($string);
            });

        $db = new Database($mysqli);
        $test = new DatabaseTest($db);

        $results = [];

        $results[] = $db->buildQuery('SELECT name FROM users WHERE user_id = 1');

        $results[] = $db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        );

        $results[] = $db->buildQuery(
            'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
            [['name', 'email'], 2, true]
        );

        $results[] = $db->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null]]
        );

        foreach ([null, true] as $block) {
            $results[] = $db->buildQuery(
                'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                ['user_id', [1, 2, 3], $block ?? $db->skip()]
            );
        }

        $correct = [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
            'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1',
            'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
        ];

        $this->assertEquals($correct, $results);
    }

    public static function suite(): TestSuite
    {
        return new TestSuite(self::class);
    }
}

$test = new DatabaseTestWithMock();
$test->run();

exit('OK');
