<?php

namespace dbtesttask;

use Exception;
use mysqli;

require_once __DIR__ . '/DatabaseInterface.php';

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        return $this->formatQuery($query, $args);
    }

    public function skip()
    {
        return null;
    }

    private function formatQuery(string $query, array $args): string
    {
        $formattedArgs = [];

        foreach ($args as $arg) {
            if (is_array($arg)) {
                $formattedArgs[] = $this->formatArrayArg($arg);
            } elseif ($arg === $this->skip()) {
                return '';
            } else {
                $formattedArgs[] = $this->formatScalarArg($arg);
            }
        }

        return vsprintf($query, $formattedArgs);
    }

    private function formatArrayArg(array $arg): string
    {
        $formattedValues = [];

        foreach ($arg as $key => $value) {
            $formattedValues[] = is_int($key) ? $this->formatScalarArg($value) : "`$key` = " . $this->formatScalarArg($value);
        }

        return implode(', ', $formattedValues);
    }

    private function formatScalarArg($arg): string
    {
        if (is_null($arg)) {
            return 'NULL';
        } elseif (is_bool($arg)) {
            return $arg ? '1' : '0';
        } elseif (is_numeric($arg)) {
            return strval($arg);
        } else {
            return "'" . $this->mysqli->real_escape_string($arg) . "'";
        }
    }
}
