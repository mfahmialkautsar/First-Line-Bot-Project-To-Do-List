<?php

namespace App\Gateway;

use Illuminate\Database\ConnectionInterface;

class MemoryGateway
{
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct()
    {
        $this->db = app('db');
    }

    // Memory
    function getMemory(string $tableName)
    {
        $memory = $this->db->table($tableName);

        if ($memory) {
            return (array) $memory;
        }

        return null;
    }
}
