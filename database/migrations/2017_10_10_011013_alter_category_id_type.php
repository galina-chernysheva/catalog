<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use \App\Category;

class AlterCategoryIdType extends Migration
{
    public $categoryTable = 'categories';
    public $urlTable = 'categories_old_urls';

    public function up()
    {
        $idPrefixIntEquals = Category::$idPrefixIntEqual;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // конвертация id существующих записей
        DB::update(
            "UPDATE {$this->categoryTable}
                SET
                    id = REPLACE(id, ?, ?),
                    id = REPLACE(id, ?, ?),
                    id = REPLACE(id, ?, ?)
            ", [
                Category::D_LEVEL, $idPrefixIntEquals[Category::D_LEVEL],
                Category::C_LEVEL, $idPrefixIntEquals[Category::C_LEVEL],
                Category::S_LEVEL, $idPrefixIntEquals[Category::S_LEVEL]
            ]
        );

        DB::update(
            "UPDATE {$this->categoryTable}
                SET
                    parent_id = REPLACE(parent_id, ?, ?),
                    parent_id = REPLACE(parent_id, ?, ?),
                    parent_id = REPLACE(parent_id, ?, ?)
                WHERE parent_id IS NOT NULL
            ", [
                Category::D_LEVEL, $idPrefixIntEquals[Category::D_LEVEL],
                Category::C_LEVEL, $idPrefixIntEquals[Category::C_LEVEL],
                Category::S_LEVEL, $idPrefixIntEquals[Category::S_LEVEL]
            ]
        );

        DB::update(
            "UPDATE {$this->urlTable}
                SET
                    category_id = REPLACE(category_id, ?, ?),
                    category_id = REPLACE(category_id, ?, ?),
                    category_id = REPLACE(category_id, ?, ?)
            ", [
                Category::D_LEVEL, $idPrefixIntEquals[Category::D_LEVEL],
                Category::C_LEVEL, $idPrefixIntEquals[Category::C_LEVEL],
                Category::S_LEVEL, $idPrefixIntEquals[Category::S_LEVEL]
            ]
        );

        // Изменение типа
        Schema::table($this->categoryTable, function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
        });
        Schema::table($this->urlTable, function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex($this->urlTable . '_category_id_foreign');
        });

        DB::statement("ALTER TABLE {$this->categoryTable} MODIFY COLUMN id INT");
        DB::statement("ALTER TABLE {$this->categoryTable} MODIFY COLUMN parent_id INT");
        DB::statement("ALTER TABLE {$this->urlTable} MODIFY COLUMN category_id INT");

        Schema::table($this->categoryTable, function (Blueprint $table) {
            $table->index('parent_id');
            $table->foreign('parent_id')
                ->references('id')->on($this->categoryTable)
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table($this->urlTable, function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')->on($this->categoryTable)
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $idPrefixIntEquals = Category::$idPrefixIntEqual;
        $replaceCond = "(CASE LEFT(%s, 1) WHEN ? THEN ? WHEN ? THEN ? ELSE ? END)";

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Изменение типа
        Schema::table($this->categoryTable, function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
        });
        Schema::table($this->urlTable, function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex($this->urlTable . '_category_id_foreign');
        });

        DB::statement("ALTER TABLE {$this->categoryTable} MODIFY COLUMN id VARCHAR(9)");
        DB::statement("ALTER TABLE {$this->categoryTable} MODIFY COLUMN parent_id VARCHAR(9)");
        DB::statement("ALTER TABLE {$this->urlTable} MODIFY COLUMN category_id VARCHAR(9)");

        Schema::table($this->categoryTable, function (Blueprint $table) {
            $table->index('parent_id');
            $table->foreign('parent_id')
                ->references('id')->on($this->categoryTable)
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        Schema::table($this->urlTable, function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')->on($this->categoryTable)
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        // обратная конвертация id
        DB::update(
            "UPDATE {$this->categoryTable} SET id = CONCAT(" . sprintf($replaceCond, 'id') . ", RIGHT(id, 8))",
            [
                $idPrefixIntEquals[Category::D_LEVEL], Category::D_LEVEL,
                $idPrefixIntEquals[Category::C_LEVEL], Category::C_LEVEL,
                Category::S_LEVEL
            ]
        );

        DB::update(
            "UPDATE {$this->categoryTable} 
                SET parent_id = CONCAT(" . sprintf($replaceCond, 'parent_id') . ", RIGHT(parent_id, 8))
                WHERE parent_id IS NOT NULL",
            [
                $idPrefixIntEquals[Category::D_LEVEL], Category::D_LEVEL,
                $idPrefixIntEquals[Category::C_LEVEL], Category::C_LEVEL,
                Category::S_LEVEL
            ]
        );

        DB::update(
            "UPDATE {$this->urlTable} 
                SET category_id = CONCAT(" . sprintf($replaceCond, 'category_id') . ", RIGHT(category_id, 8))",
            [
                $idPrefixIntEquals[Category::D_LEVEL], Category::D_LEVEL,
                $idPrefixIntEquals[Category::C_LEVEL], Category::C_LEVEL,
                Category::S_LEVEL
            ]
        );

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
