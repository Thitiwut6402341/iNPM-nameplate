<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Grammars\Grammar;

// use Ramsey\Uuid\Uuid;

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
        Grammar::macro('typeRole_types', function () {
            return 'role_types';
        });
        DB::unprepared("drop function if exists uuid_v4;create or replace function uuid_v4()
        returns uuid
        language plpgsql
        as $$
        declare
            gen_uuid uuid;
        begin
            gen_uuid := uuid_in(overlay(overlay(md5(random()::text || ':' || random()::text) placing '4' from 13) placing to_hex(floor(random()*(11-8+1) + 8)::int)::text from 17)::cstring);
            return gen_uuid;
        end; $$;");
        // DB::unprepared("drop type if exists role_types;");
        // DB::unprepared("create type role_types as enum ('admin', 'general');");
        DB::unprepared("drop type if exists role_types;create type role_types as enum ('admin', 'general');");
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary()->default(DB::raw('uuid_v4()'));
            $table->string('name', 255);
            $table->string('username', 255)->nullable(false);
            // $table->string('password', 255)->default('');
            $table->addColumn('role_types', 'role');
            $table->boolean('is_actived')->nullable();
            $table->timestamp('login_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
