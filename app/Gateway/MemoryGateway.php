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
    function getMemory(string $tableName, $id)
    {
        $memory = $this->db->table($tableName)
        ->where('id', $id)
        ->first();

        if ($memory) {
            return (array) $memory;
        }

        return null;
    }
}
