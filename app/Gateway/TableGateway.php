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

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(string $tableName)
    {
        // $user = $this->db->table($tableName);

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->string('remember');
                $table->timestamps();
            });
            // $this->db->select("CREATE TABLE IF NOT EXISTS $tableName
            // (
            //     id serial,
            //     user_id varchar(100) NOT NULL,
            //     display_name varchar(100) NOT NULL,
            //     line_id varchar(50) NULL,
            //     PRIMARY KEY (id)
            // );");
        }
    }

    /**
     * Reverse the migration
     * 
     * @return void
     */
    public function down(string $tableName)
    {
        // $user = $this->db->table($tableName);
        // if ($user) {
        Schema::dropIfExists($tableName);
        // $this->db->select("DROP TABLE IF EXISTS $tableName;");
        // }
    }

    public function rememberThis($tableName, $note)
    {
        if (Schema::hasTable($tableName)) {

            if ($note) {
                $this->db->table($tableName)
                    ->insert([
                        'remember' => $note
                    ]);
            }
        } else {
            $this->up($tableName);
            $this->rememberThis($tableName, $note);
        }
    }

    public function count(string $tableName)
    {
        if (Schema::hasTable($tableName)) {
            return $this->db->table($tableName)->count();
        } else {
            return 0;
        }
    }
}
