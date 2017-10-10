<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CategoryOldUrl extends Model
{
    /** @var  $_validator Validator */
    private $_validator;

    // Правила валидации
    private $_rules = [
        'category_id'   => ['required', 'integer'],
        'category_code' => ['required', 'max:9', 'alpha_num', 'regex:/^([D|C|S]\d{8})$/i'],
        'url'           => 'required'
    ];

    protected $table = 'categories_old_urls';
    protected $fillable = ['category_id', 'url'];
    protected $with = ['category'];

    // Виртуальный атрибут для представления id категории в виде кода
    public $category_code;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->_createValidator();
    }

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Виртуальный код категории
    /**
     * @return null|string
     */
    public function getCategoryCode()
    {
        return !empty($this->category_code) ? $this->category_code : Category::rawId2Code($this->category_id);
    }

    /**
     * @param $value string
     */
    public function setCategoryCode($value)
    {
        $this->category_code = strtoupper($value);
        $this->attributes['category_id'] = Category::code2RawId($value);
    }

    private function _createValidator()
    {
        $this->_validator = Validator::make([], $this->_rules);
    }

    // Проверка валидности модели
    public function isValid()
    {
        return $this->_validator->setData($this->toArray())->passes();
    }

    /**
     * Ошибки валидации
     * @return \Illuminate\Support\MessageBag
     */
    public function getValidationErrors()
    {
        return $this->_validator->errors();
    }

    public function fill(array $attributes)
    {
        if (isset($attributes['category_code'])) {
            $this->setCategoryCode($attributes['category_code']);
        }

        return parent::fill($attributes);
    }
}
