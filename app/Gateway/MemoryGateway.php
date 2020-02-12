<?php

namespace App\Gateway;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class MemoryGateway extends Migration
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
            DB::table($tableName)
                ->insert([
                    'remember' => $note
                ]);
        } else {
            $this->up($tableName);
            $this->rememberThis($tableName, $note);
        }
        return "Noted.";
    }

    public function count(string $tableName)
    {
        if (Schema::hasTable($tableName)) {
            return DB::table($tableName)->count();
        }
        return 0;
    }

    // Memory
    function getMemory(string $tableName, int $id)
    {
        $memory = DB::table($tableName)
            ->where('id', $id)
            ->first();
        //$memory = $this->getRowNumber($tableName, $num, "SELECT *");
        // $memory = DB::select("SELECT * FROM \"$tableName\" WHERE id = 2");

        if ($memory) {
            // return (array) $memory;
            return $memory = "test";
        } else {
            $id++;
            $this->getMemory($tableName, $id);
        }
    }

    function forgetMemory(string $tableName, int $id)
    {
        // DB::table($tableName)
        // ->where('id', $id)
        // ->delete();

        $this->getRowNumber($tableName, $id, "DELETE");
        return "Forgetted";
    }

    function getRowNumber($tableName, $num, $option)
    {
        $user = DB::select("WITH temp AS
        (
        SELECT *, ROW_NUMBER() OVER(ORDER BY id) AS number
        from \"$tableName\"
        ), temp2 AS (
        SELECT *
        FROM temp
        WHERE number = $num
        )
        $option FROM \"$tableName\"
        WHERE id IN (SELECT id FROM temp2);
        ");
        return $user;
    }
}
