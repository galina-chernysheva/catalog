<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Category extends Model
{
    /** @var  $_validator Validator */
    private $_validator;

    // Правила валидации
    private $_rules = [
        'id'        => ['required', 'max:9' ,'alpha_num', 'regex:/^([D|C|S]\d{8})$/i'],
        'title'      => 'required|max:255',
        'parent_id' => ['nullable', 'max:9', 'alpha_num', 'regex:/^([D|C|S]\d{8})$/i'],
        'url'       => ['required']
    ];

    protected $keyType = 'string';
    protected $fillable = ['id', 'title', 'parent_id', 'url'];
    protected $with = ['parent'];
    protected $casts = [
        'id'        => 'string',
        'title'      => 'string',
        'url'       => 'string',
        'parent_id' => 'string'
    ];

    public $incrementing = false;
    public $timestamps = false;

    // Соответствие префиксов ID для разных уровней вложенности
    private $_idParentChildPrefix = [
        null    => 'D',
        'D'     => 'C',
        'C'     => 'S'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->_createValidator();
    }

    // Relations
    public function parent()
    {
        return $this->hasOne(Category::class, 'id', 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    public function oldUrls()
    {
        return $this->hasMany(CategoryOldUrl::class, 'category_id', 'id');
    }

    // Полная ссылка на категорию
    public function getFullUrl()
    {
        return url('catalog/' . $this->url);
    }

    private function _createValidator()
    {
        $this->_validator = Validator::make([], $this->_rules);
        $this->_validator->after(function ($validator) {
            if (!$this->isValidCategoryNesting()) {
                // TODO: уточнить текущий и родительский уровень, подсказать правильный вариант
                $validator->errors()->add('id', 'Неверный префикс в ID для данного уровня вложенности');
            }
        });
    }

    // Проверка валидности модели
    public function isValid()
    {
        $table = $this->getTable();
        $this->_validator->setData($this->toArray())->addRules([
            // TODO: почему-то не работают валидаторы unique c ignore
            'id'    => [Rule::unique($table, 'id')->ignore($this->id)->whereNot('id', $this->id)],
            'url'   => [Rule::unique($table, 'url')->ignore($this->id)->whereNot('id', $this->id)]
        ]);

        return $this->_validator->passes();
    }

    /**
     * Ошибки валидации
     * @return \Illuminate\Support\MessageBag
     */
    public function getValidationErrors()
    {
        return $this->_validator->errors();
    }

    // Генерация ID
    public function genId()
    {
        $prefix = null;
        $parentPrefix = $this-> parent_id ? ucfirst($this->parent_id[0]) : null;
        if (isset($this->_idParentChildPrefix[$parentPrefix]))
            $prefix = ucfirst($this->_idParentChildPrefix[$parentPrefix]);
        if (empty($prefix))
            // TODO: уточнить текущий и родительский уровень, подсказать правильный вариант
            throw new \Exception("Иерархия категорий такого уровня не поддерживается");

        $maxId = DB::table($this->getTable())
            ->selectRaw('COALESCE(MAX(CAST(RIGHT(id, 8) AS INT)), 0) as max_id')
            ->whereRaw("UPPER(id) LIKE UPPER(?)", ["{$prefix}%"])
            ->value('max_id');
        $maxId++;

        if ($maxId > pow(10, 8))
            throw new \Exception('На данном уровне иерархии создано максимально возможное количество категорий');

        $newId = $prefix . str_pad($maxId, 8, '0', STR_PAD_LEFT);
        return $newId;
    }

    // Генерация URL
    public function genUrl()
    {
        $url = null;
        if ($this->title) {
            $url = Str::slug($this->title);
            if ($this->parent)
                $url = $this->parent->url . '/' . $url;
        }
        return $url;
    }

    // Кастомный валидатор уровня вложенности категорий
    protected function isValidCategoryNesting()
    {
        if ($this->id) {
            $prefix = ucfirst($this->id[0]);
            $parentPrefix = $this->parent_id ? ucfirst($this->parent_id[0]) : null;
            $allowedNesting = array_flip($this->_idParentChildPrefix);
            return isset($allowedNesting[$prefix]) && ($allowedNesting[$prefix] == $parentPrefix);
        }
        return true;
    }
}
