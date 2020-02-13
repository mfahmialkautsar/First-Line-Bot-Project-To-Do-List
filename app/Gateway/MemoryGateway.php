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
        Schema::dropIfExists($tableName);
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
        $code = '10008F';
        $bin = hex2bin(str_repeat('0', 8 - strlen($code)) . $code);
        $emoji = mb_convert_encoding($bin, 'UTF-8', 'UTF-32BE');
        return "Noted $emoji";
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

        if ($memory) {
            return (array) $memory;
        }

        return null;
    }

    function forgetMemory(string $tableName, int $id)
    {
        // DB::table($tableName)
        // ->where('id', $id)
        // ->delete();

        $this->getRowNumber($tableName, $id, "DELETE");
        return "Forgetted";
    }

    private function getRowNumber($tableName, $num, $option)
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
