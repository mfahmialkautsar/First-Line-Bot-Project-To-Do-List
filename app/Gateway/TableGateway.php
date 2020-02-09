<?php

namespace App\Gateway;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableGateway extends Migration
{
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct()
    {
        $this->db = app('db');
    }

    public function up(string $tableName)
    {
        $user = $this->db->table($tableName);

        if (!$user) {
            Schema::create($tableName, function (Blueprint $table)
            {
                $table->increments('id');
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function down(string $tableName)
    {
        $user = $this->db->table($tableName);
        if ($user) {
            Schema::drop($tableName);
        }
    }
}
