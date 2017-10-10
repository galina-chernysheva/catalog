<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Пути
Route::get('/', 'CatalogController@index');
Route::get('catalog/{url}', 'CatalogController@category')->where('url', '|[a-z0-9_/-]+|i');


// "Хлебные крошки"
// Главная (Каталог)
Breadcrumbs::register('catalog', function ($breadcrumbs) {
    $breadcrumbs->push('Каталог', url('/'));
});
// Категория
Breadcrumbs::register('category', function ($breadcrumbs, $category) {
    /**
     * @var $category App\Category
     */
    if ($category->parent_id) {
        $breadcrumbs->parent('category', $category->parent);
    } else {
        $breadcrumbs->parent('catalog');
    }

    $breadcrumbs->push($category->title, $category->getFullUrl());
});
