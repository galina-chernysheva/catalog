<?php

namespace App\Console\Commands;

use App\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\JsonFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\Storage;

class ImportCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:catalog {file? : Имя/путь JSON-файла со структурой каталога}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт каталога из JSON-файла';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->validateFile($data);
        if (empty($data)) {
            $this->info('Нет данных для импорта');
        } else {
            $total = 0;
            $totalSucced = 0;
            $this->processCategories($data, $total, $totalSucced, null);
            $this->info("Импорт категорий завершён: успешно импортировано {$totalSucced} категорий из {$total}");
        }
    }

    /**
     * @inheritdoc
     */
    public function getArguments()
    {
        $args = parent::getArguments();
        $args[] = [
            'file', InputArgument::OPTIONAL, 'Имя/путь JSON-файла со структурой каталога',
            Storage::disk('import')->get('catalog.json')
        ];
        return $args;
    }

    /**
     * Вывод в консоль и логирование
     * @inheritdoc
     */
    public function line($string, $style = null, $verbosity = null)
    {
        parent::line($string, $style, $verbosity);
        if (in_array($style, ['info', 'error', 'warning'])) {
            Log::$style($string);
        }
    }

    /**
     * Проверка существования и формата файла
     * @param $data array
     */
    protected function validateFile(&$data)
    {
        $filename = $this->argument('file');

        if (!empty($filename)) {
            if (!Storage::disk('project')->exists($filename))
                $this->error('Не найден файл для импорта: ' . $filename);
            $file = Storage::disk('project')->get($filename);
        } else {
            $filename = 'import/catalog.json';
            if (!Storage::disk('import')->exists('catalog.json')) {
                $this->error('Не найден файл для импорта: ' . $filename);
            }
            $file = Storage::disk('import')->get('catalog.json');
            $this->info('Параметр file не задан. Для импорта будет использоваться файл по-умолчанию: ' . $filename);
        }

        $data = json_decode($file, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Некорректный JSON файл');
        }
    }

    /**
     * Сохранение категорий
     * @param array $data
     * @param $total integer
     * @param $totalSucced integer
     * @param string|null $parentId
     */
    protected function processCategories($data, &$total, &$totalSucced, $parentId = null)
    {
        foreach ($data as $categoryData) {
            $total++;

            $id = isset($categoryData['id']) ? $categoryData['id'] : null;
            if ($parentId)
                $categoryData['parent_id'] = $parentId;
            try {
                $category = Category::updateOrCreate(['id' => $id], $categoryData);
                $totalSucced++;
                if (isset($categoryData['children'])) {
                    $this->processCategories($categoryData['children'], $total, $totalSucced, $category->id);
                }
            } catch (\Exception $e) {
                $this->error('Ошибка сохранения категории' . ($id ? " c ID = {$id}" : '' ) . ': ' . $e->getMessage());
            }
        }
    }
}
