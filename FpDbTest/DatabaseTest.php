<?php
namespace dbtesttask;

use Exception;
use mysqli;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/DatabaseInterface.php';
require_once __DIR__ . '/Database.php';

class DatabaseTest extends TestCase
{
    private DatabaseInterface $db;

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new Database($this->createMock(mysqli::class));
    }

    protected function setUp(): void
    {
        $mysqliMock = $this->getMockBuilder(mysqli::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->db = new Database($mysqliMock);
    }

    public function testBuildQuery(): void
    {
        $results = [];

        $results[] = $this->db->buildQuery('SELECT name FROM users WHERE user_id = 1');
        $results[] = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        );
        $results[] = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
            [['name', 'email'], 2, true]
        );
        $results[] = $this->db->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null]]
        );

        foreach ([null, true] as $block) {
            try {
                $skipValue = $this->db->skip();
                $results[] = $this->db->buildQuery(
                    'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                    ['user_id', [1, 2, 3], $block ?? $skipValue]
                );
            } catch (Exception $e) {
                $results[] = 'Skipped query due to exception: ' . $e->getMessage();
            }
        }

        $correct = [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
            'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1',
            'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
        ];

        $expectedVsActual = array_diff_assoc($correct, $results);
        if (!empty($expectedVsActual)) {
            $this->fail("Expected vs Actual:\n" . print_r($expectedVsActual, true));
        }
    }

    public function testSkip(): void
    {
        $this->expectException(Exception::class);
        $this->db->skip();
    }
}
