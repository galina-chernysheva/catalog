<?php

namespace App\Observers;

use App\Category;

class CategoryObserver
{
    /**
     * Прослушивание события сохранения категории.
     *
     * @param  Category $category
     * @return void
     */
    public function saving(Category $category)
    {
        $category->syncChanges();

        // Генерация нового ID
        if (empty($category->id) && empty($category->code))
            $category->id = $category->genId();

        // Генерация нового url
        if ($category->wasChanged('title') || empty($category->url))
            $category->url = $category->genUrl();

        if (!$category->isValid())
            throw new \Exception($category->getValidationErrors()->toJson());
    }

    /**
     * @param Category $category
     */
    public function saved(Category $category)
    {
        $originState = $category->getOriginal();

        // Сохранение старого url и обновление url подкатегорий
        if ($category->wasChanged('url') && !empty($originState['url'])) {
            $category->oldUrls()->create(['url' => $originState['url']]);

            $category->children()->each(function($child) {
                /** @var $child Category */
                $child->update(['url' => $child->genUrl()]);
            });
        }
    }
}
