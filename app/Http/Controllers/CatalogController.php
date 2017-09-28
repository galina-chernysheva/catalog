<?php

namespace App\Http\Controllers;

use App\Category;
use App\CategoryOldUrl;

class CatalogController extends Controller
{
    /**
     * Вывод дерева каталога
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('catalog.index', [
            'name'          => 'Каталог',
            'categories'    => Category::with('children')->whereNull('parent_id')->orderBy('id')->get()
        ]);
    }

    /**
     * Вывод категории каталога
     * Категория ищется по актуальному или старому url, актуальный имеет приоритет
     * @param $url string
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    /* NOTE: 301 редирект кэшируется браузером.
     * Это может привести  к некорректному редиректу в случае, когда в старых url появляется ссылка-дубликат,
     * связанная с другой категорией (очень частный случай)
     *
     * Пример:
     * 0.   Создаём D00000001 category-1, D00000002 category-2, D00000003 category-3
     * 1.   Переименование D00000001 category-1 => category-4, old url: category-1
     * 2.   Переименование D00000002 category-2 => category-5, old url: category-2
     * Промежуточный итог: D00000001 category-4, D00000002 category-5, D00000003 category-3
     * 3.   Заходим на category-2 - редирект на D00000002 category-5
     * 4.   Заходим на category-1 - редирект на D00000001 category-4
     * 5.   Заходим на category-5 - страница D00000002 category-5
     * 6.   Переименование D00000003 category-3 => category-1, old url: category-3
     * Промежуточный итог: D00000001 category-4, D00000002 category-5, D00000003 category-1
     * 7.   Переименование D00000003 category-1 => category-2, old url: category-1
     * Промежуточный итог: D00000001 category-4, D00000002 category-5, D00000003 category-2
     * 8.   Заходим на category-2 - страница D00000003 category-2
     *      (корректно, не редирект на D00000002 category-5, потому что есть новый url == category-2, он имеет приоритет)
     * 9(!) Заходим на category-1 - ожидаем редирект на категорию последнего old url == category-1,
     *      т.е. D00000003 category-2. Получаем закэшированный редирект на D00000001 category-4
     */
    public function category($url)
    {
        $category = Category::where('url', $url)->first();

        if (empty($category)) {
            $oldUrl = CategoryOldUrl::with('category')->where('url', $url)->orderByDesc('updated_at')->first();
            $category = !empty($oldUrl) ? $oldUrl->category : null;
        }

        if (empty($category)) {
            abort(404);
        } elseif ($category->url != $url) {
            return redirect($category->getFullUrl(), 301);
        }

        return view('catalog.category', [
            'category'  => $category
        ]);
    }
}
