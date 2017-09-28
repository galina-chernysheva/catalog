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
        'category_id'   => ['max:9', 'alpha_num', 'regex:/^([D|C|S]\d{8})$/i'],
        'url'           => 'required'
    ];

    protected $table = 'categories_old_urls';
    protected $fillable = ['category_id', 'url'];
    protected $with = ['category'];

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
}
