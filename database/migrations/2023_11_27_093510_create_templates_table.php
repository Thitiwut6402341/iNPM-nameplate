<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Grammars\Grammar;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Grammar::macro('typeFont_weight_types', function () {
            return 'font_weight_types';
        });
        DB::unprepared("drop type if exists font_weight_types;");
        DB::unprepared("create type font_weight_types as enum ('normal', 'black', 'medium','hairline','thin','semibold','extrabold','light','bold');");


        Schema::create('templates', function (Blueprint $table) {
            $table->uuid('template_id') -> primary() -> default(DB::raw('uuid_v4()'));
            $table->string('font_family','255')->nullable(false);
            $table->integer('font_size')->default(14);
            $table->addColumn('font_weight_types','font_weight')->default('normal');
            $table->string('font_color',255)->nullable(false);
            $table->string('background_color',255)->nullable(false);
            $table->timestamp('created_at')->useCurrent()-> nullable();
            $table->timestamp('updated_at')->useCurrent()-> nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('templates');
    }
};
