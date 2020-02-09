<?php

namespace App\Gateway;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableGateway extends Migration
{
    public function up(string $tableName)
    {
        Schema::create($tableName, function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(string $tableName)
    {
        Schema::drop($tableName);
    }
}
