<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Grammars\Grammar;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'pgsql';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Grammar::macro('typeStatus_types', function () {
            return 'status_types';
        });
        DB::unprepared("drop type if exists status_types;");
        DB::unprepared("create type status_types as enum ('usable', 'unusable', 'broken');");
        // DB::unprepared("drop type if exists status_types;create type status_types as enum ('usable', 'unusable', 'broken');");
        Schema::create('devices', function (Blueprint $table) {
            $table->string('serial_no')->primary()->nullable(false);
            $table->string('device_name', 255)->nullable(false);
            $table->addColumn('status_types', 'status')->default('unusable');
            $table->string('remarks', 255)->default('');
            $table->decimal('battery', 8, 2)->min(0)->max(100)->nullable();
            $table->string('ip_address', 15)->nullable();
            $table->timestamp('last_actived_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices');
    }
};
