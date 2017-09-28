<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public $table = 'categories';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->string('id', 9);
            $table->string('parent_id', 9)->nullable();
            $table->string('title', 255);
            $table->text('url');

            $table->primary('id');
            $table->index('parent_id');

            $table->foreign('parent_id')
                ->references('id')->on($this->table)
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists($this->table);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
