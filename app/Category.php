<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Mockery\Exception;

class Category extends Model
{
    /** @var  $_validator Validator */
    private $_validator;

    // Правила валидации
    private $_rules = [
        'code'          => ['required', 'max:9' ,'alpha_num', 'regex:/^([D|C|S]\d{8})$/i'],
        'parent_code'   => ['nullable', 'max:9', 'alpha_num', 'regex:/^([D|C|S]\d{8})$/i'],

        'id'            => ['required', 'integer'],
        'title'         => 'required|max:255',
        'parent_id'     => ['nullable', 'integer'],
        'url'           => ['required']
    ];

    protected $keyType = 'string';
    protected $fillable = ['id', 'parent_id', 'title', 'url'];
    protected $with = ['parent'];
    protected $casts = [
        'id'            => 'integer',
        'title'         => 'string',
        'url'           => 'string',
        'parent_id'     => 'integer'
    ];

    public $incrementing = false;
    public $timestamps = false;

    // Виртуальные атрибуты для представления id категории в виде кода
    /** @var  string */
    public $code;
    public $parent_code;

    // Префиксы уровней вложенности категорий
    const D_LEVEL = 'D';
    const C_LEVEL = 'C';
    const S_LEVEL = 'S';

    // Соответствие префиксов ID для разных уровней вложенности
    private $_idParentChildPrefix = [
        null            => self::D_LEVEL,
        self::D_LEVEL   => self::C_LEVEL,
        self::C_LEVEL   => self::S_LEVEL
    ];

    // Соответствие префиксов ID "искусственным" префиксам на уровне базы
    public static $idPrefixIntEqual = [
        self::D_LEVEL => 1,
        self::C_LEVEL => 2,
        self::S_LEVEL => 3
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

    // Виртуальные коды категории
    /**
     * @return null|string
     */
    public function getCode()
    {
        return !empty($this->code) ? $this->code : self::rawId2Code($this->id);
    }

    /**
     * @param $value string
     */
    public function setCode($value)
    {
        $code = strtoupper($value);
        $this->code = $code;
        $this->attributes['id'] = self::code2RawId($code);
    }

    /**
     * @return null|string
     */
    public function getParentCode()
    {
        return !empty($this->parent_code) ? $this->parent_code : self::rawId2Code($this->parent_id);
    }

    /**
     * @param $value string
     */
    public function setParentCode($value)
    {
        $code = strtoupper($value);
        $this->parent_code = $code;
        $this->attributes['parent_id'] = self::code2RawId($code);
    }

    public function fill(array $attributes)
    {
        if (isset($attributes['code'])) {
            $this->setCode($attributes['code']);
        }
        if (isset($attributes['parent_code'])) {
            $this->setCode($attributes['parent_code']);
        }

        return parent::fill($attributes);
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        $attributes = array_merge($attributes, [
            'code'          => $this->getCode(),
            'parent_code'   => $this->getParentCode()
        ]);
        return $attributes;
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
        $parentCode = $this->getParentCode();

        $parentPrefix = $parentCode ? ucfirst($parentCode[0]) : null;
        if (isset($this->_idParentChildPrefix[$parentPrefix]))
            $prefix = ucfirst($this->_idParentChildPrefix[$parentPrefix]);
        if (empty($prefix))
            // TODO: уточнить текущий и родительский уровень, подсказать правильный вариант
            throw new \Exception("Иерархия категорий такого уровня не поддерживается");


        $prefixEquals = self::$idPrefixIntEqual;
        $intStart = $prefixEquals[$prefix] * pow(10, 8) + 1;
        $intEnd = $intStart + pow(10, 8) - 2;
        $maxId = DB::table($this->getTable())
            ->selectRaw('COALESCE(MAX(id), 0) as max_id')
            ->whereRaw("id BETWEEN ? AND ?", [$intStart, $intEnd])
            ->value('max_id');
        $maxId = $maxId == 0 ? $intStart : $maxId + 1;

        if ($maxId > $intEnd)
            throw new \Exception('На данном уровне иерархии создано максимально возможное количество категорий');

        return $maxId;
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
        $code = $this->getCode();
        $parentCode = $this->getParentCode();

        if ($code) {
            $prefix = ucfirst($code[0]);
            $parentPrefix = $parentCode ? ucfirst($parentCode[0]) : null;
            $allowedNesting = array_flip($this->_idParentChildPrefix);
            return isset($allowedNesting[$prefix]) && ($allowedNesting[$prefix] == $parentPrefix);
        }
        return true;
    }

    /**
     * конвертация кода категории в DB view id
     * @param $value string
     * @return int|null
     */
    public static function code2RawId($value)
    {
        $result = null;
        $prefixEquals = self::$idPrefixIntEqual;

        if (isset($value)) {
            $prefix = ucfirst($value[0]);
            if (array_key_exists($prefix, $prefixEquals)) {
                $result = (int)(str_replace($prefix, $prefixEquals[$prefix], $value));
            }
        }
        return $result;
    }

    /**
     * конвертация DB view id в код категории
     * @param $value integer
     * @return null|string
     */
    public static function rawId2Code($value)
    {
        $result = null;
        $prefixEquals = array_flip(self::$idPrefixIntEqual);

        if (isset($value)) {
            $value .= '';
            $prefix = $value[0];
            if (array_key_exists($prefix, $prefixEquals)) {
                $result = strtoupper($prefixEquals[$prefix] . substr($value, 1));
            }
        }
        return $result;
    }

    /**
     * Формирует дерево из переданных категорий
     * @param $categories Collection
     * @return array
     */
    public static function buildCategoriesTree($categories)
    {
        if ($categories->isEmpty())
            return [];

        $tree = [];
        $idParentIdPairs = [];

        /** @var Category $category */
        foreach ($categories as $category) {
            $id = '' .$category->id;
            $parentId = '' . $category->parent_id;
            $idParentIdPairs[$id] = $parentId;

            $data = $category->toArray();
            $data['url'] = $category->getFullUrl();

            $parents = [];
            if ($parentId)
                $parents[] = $parentId;
            while (isset($idParentIdPairs[$parentId]) && !empty($idParentIdPairs[$parentId])) {
                $parentId = $idParentIdPairs[$parentId];
                $parents[] = $parentId;
            }

            $parents = array_reverse($parents);
            $root = &$tree;

            foreach ($parents as $parent)
                $root = &$root['branch'][$parent];

            if (!isset($root['branch']))
                $root['branch'] = [];
            if (!isset($root['branch'][$id]))
                $root['branch'][$id] = $data;
        }

        return !empty($tree['branch']) ? $tree['branch'] : [];
    }

    /**
     * Возвращает полное дерево каталога
     * @return array
     */
    public static function getFullTree()
    {
        $categories = self::orderByRaw('ISNULL(parent_id) DESC, parent_id, id')->get();
        return self::buildCategoriesTree($categories);
    }

    /**
     * Возвращает ветку из дочерних категорий
     */
    public function getBranchTree()
    {
        $levelPrefix = ucfirst(($this->id . '')[0]);
        $level = array_search((int)$levelPrefix, array_values(self::$idPrefixIntEqual));

        // Если текущая категория находится на последнем уровне иерархии, то детей у неё нет по определению
        if ($level == count(self::$idPrefixIntEqual) - 1)
            return [];

        // Иначе строим запрос на выборку всех детей
        $table = $this->getTable();
        $i = 0;
        $categories = DB::select("
            SELECT *
            FROM {$table}
            WHERE parent_id = ?
            UNION
            SELECT *
            FROM {$table}
            WHERE parent_id IN
                (SELECT id FROM {$table} WHERE parent_id = ?)
        ", [$this->id, $this->id]);

        $categories = self::hydrate($categories);

        $branch = self::buildCategoriesTree($categories);

        if (!empty($branch))
            $branch = $branch[$this->id]['branch'];

        return $branch;
    }
}
