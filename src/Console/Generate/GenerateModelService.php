<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate;

use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Console\Generate\Models\HlBlock;
use Pago\Bitrix\Models\Console\Generate\Models\Iblock;
use Pago\Bitrix\Models\Console\Generate\Models\Table;
use Pago\Bitrix\Models\Console\Traits\ConsoleBaseMethods;
use Pago\Bitrix\Models\Console\Traits\ConsoleMessage;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\HlModelHelper;
use Pago\Bitrix\Models\Helpers\TableModelHelper;
use Pago\Bitrix\Models\Models\HlblockTable;
use Pago\Bitrix\Models\Models\IblockTable;

/**
 * Консольные команды генерация модели
 */
final class GenerateModelService
{
    use ConsoleMessage;
    use ConsoleBaseMethods;

    /**
     * @var array
     */
    private array $inputArguments;

    /**
     * @var array
     */
    private array $arguments;

    /**
     * @var string|null
     */
    private ?string $method;

    /**
     * @param array $arguments
     * @throws SystemException
     */
    public function __construct(array $arguments)
    {
        // Подключаем базовые модули highload, iblock
        Helper::includeBaseModules();
        // Фильтруем входящие данные
        $this->inputArguments = $arguments;
        array_shift($arguments);
        $this->arguments = $this->getArguments($arguments);
        // Считываем метод переданный при вызове (тип генерации модели)
        $this->method = $this->getMethodFromInputArguments($arguments);

        // Переданные идентификаторы в аргументах
        $elements = null;
        if (count($this->arguments) > 1) {
            $elements = array_slice(array_keys($this->arguments), 1);
        }

        match (is_string($this->method) ? strtolower($this->method) : null) {
            // Генерация инфоблоков
            'iblock', 'ib' => call_user_func(function () use ($elements) {
                $this->generateIModel($elements);
            }),
            // Генерация highload блоков
            'hlblock', 'highloadblock', 'hb', 'hl' => call_user_func(function () use ($elements) {
                $this->generateHlModel($elements);
            }),
            // Генерация таблиц
            'table' => call_user_func(function () use ($elements) {
                $this->generateTableModel($elements);
            }),
            default => call_user_func(function () {
                $this->hello();
            }),
        };
    }

    /**
     * Базовое сообщение открытия в командной строке
     * @return void
     */
    public function hello(): void
    {
        $this->success('Добро пожаловать в генератор моделей');
        $this->info('Выберите тип модель для генерации и введите его. Доступны: iblock, hlblock, table');
        $this->info('Пример: php ' . $this->inputArguments[0] . ' iblock');
    }

    /**
     * Генерация таблиц
     * @param array|null $inputNames
     * @return void
     * @throws SystemException
     */
    public function generateTableModel(array|null $inputNames = null): void
    {
        if (! $inputNames) {
            $message = 'Введите название таблицы через пробел для генерации модели.' . PHP_EOL;
            $message .= 'Для выхода введите "q".';

            $this->info($message);
            $inputNames = explode(' ', strtolower($this->question('Ввод')));
        }

        // Вывод всех таблиц для выбора
        foreach ($inputNames as $tableName) {
            if (! $tableName) {
                continue;
            }
            if (!TableModelHelper::instance()->checkTableExists($tableName)) {
                $this->error(sprintf('Таблица %s не найдена', $tableName));
                continue;
            }
            $generateModel = new Table(
                tableName: $tableName,
                path: $this->getArgument('path'),
                namespace: $this->getArgument('namespace')
            );
            $generate = $generateModel->generateModel();
            if ($generate->success) {
                // Заменим имя таблицы на CamelCase, так как указано имя таблицы
                $generate->name = Helper::snakeToCamelCase($generate->name, true);
                $this->creationSuccess($generate, $tableName);
            } else {
                $this->creationError($generate, $tableName);
            }
            $this->creationWarnings($generate);
        }
    }

    /**
     * Генерация highload
     * @param array|null $inputIds
     * @return void
     * @throws SystemException
     */
    public function generateHlModel(array|null $inputIds = null): void
    {
        $hls = HlblockTable::query()->get();

        // Запрашиваем ID или all если нужно создать для всех
        if (! $inputIds) {
            $message = 'Введите идентификаторы highload блоков через пробел для генерации модели.' . PHP_EOL;
            $message .= 'или all для всех. Для выхода введите "q".';

            // Вывод всех справочников для выбора
            $this->info($message);
            foreach ($hls as $hl) {
                $this->info(sprintf(
                    '%d - %s, TABLE_NAME: %s',
                    $hl->ID,
                    $hl->NAME,
                    $hl->TABLE_NAME
                ));
            }
            $inputIds = explode(' ', strtolower($this->question('Ввод')));
        }
        if ($inputIds && in_array('all', $inputIds)) {
            $inputIds = array_map(function (HlblockTable $hls) {
                return $hls->ID;
            }, $hls);
        }
        // Создаем модели для каждого указанного справочника
        foreach ($inputIds as $hlId) {
            $hlId = (int)$hlId;
            if (! $hlId) {
                continue;
            }
            // Проверка существования HL
            if (! HlModelHelper::getHlCodeById($hlId)) {
                $this->warning(sprintf('Справочник %d не найден', $hlId));
                continue;
            }
            $generateModel = new HlBlock(
                id: $hlId,
                path: $this->getArgument('path'),
                namespace: $this->getArgument('namespace')
            );
            $generate = $generateModel->generateModel();
            if ($generate->success) {
                $this->creationSuccess($generate, $hlId);
            } else {
                $this->creationError($generate, $hlId);
            }
            $this->creationWarnings($generate);
        }
    }

    /**
     * Генерация инфоблоков
     * @param array|null $inputIds
     * @return void
     * @throws SystemException
     */
    public function generateIModel(array|null $inputIds = null): void
    {
        $iblocks = IblockTable::query()->get();
        // Запрашиваем ID или all если нужно создать для всех
        if (! $inputIds) {
            $message = 'Введите идентификаторы инфоблоков через пробел для генерации модели.' . PHP_EOL;
            $message .= 'или all для всех. Для выхода введите "q".';
            $this->info($message);

            // Вывод всех инфоблоков для выбора
            foreach ($iblocks as $iblock) {
                $this->info(sprintf(
                    '%d - %s CODE: %s, API_CODE: %s',
                    $iblock->ID,
                    $iblock->NAME,
                    $iblock->CODE ?: '-',
                    $iblock->API_CODE ?: '-'
                ));

                if (! $iblock->CODE) {
                    $this->warning('WARNING: Не заполнен символьный код. Рекомендуется указать его');
                }
            }

            $inputIds = explode(' ', strtolower($this->question('Ввод')));
        }
        if ($inputIds && in_array('all', $inputIds)) {
            $inputIds = array_map(function (IblockTable $iblock) {
                return $iblock->ID;
            }, $iblocks);
        }
        // Создаем модели для каждого указанного инфоблока
        foreach ($inputIds as $iblockId) {
            $iblock = IblockTable::query()->whereId($iblockId)->first();
            if (! $iblock) {
                continue;
            }
            $generateModel = new Iblock(
                id: $iblock->ID,
                path: $this->getArgument('path'),
                namespace: $this->getArgument('namespace')
            );
            $generate = $generateModel->generateModel();
            if ($generate->success) {
                $this->creationSuccess($generate, $iblockId);
            } else {
                $this->creationError($generate, $iblockId);
            }
            $this->creationWarnings($generate);

            // Генерация API_CODE при его отсутствии
            if ($generate->success && ! $iblock->API_CODE) {
                $this->warning('У инфоблока отсутствует обязательное значение API_CODE. Устанавливаю API_CODE');
                if ($generateModel->setApiCode()) {
                    $this->success('API_CODE успешно установлен');
                } else {
                    $this->success('Ошибка установки API_CODE');
                }
            }
        }
    }

    /**
     * Предупреждения при создании модели
     * @param GenerateResult $result
     * @return void
     */
    private function creationWarnings(GenerateResult $result): void
    {
        if (count($result->warnings)) {
            $this->warning('Внимание:');
        }
        foreach ($result->warnings as $warning) {
            $this->warning('- ' . $warning);
        }
    }

    /**
     * Сообщение успешного создания модели
     * @param GenerateResult $result
     * @param int|string $id
     * @return void
     */
    private function creationSuccess(GenerateResult $result, int|string $id): void
    {
        $this->success(
            sprintf(
                'Успешно создана модель %s - %s\%s',
                $id,
                $result->namespace,
                $result->name
            )
        );
        $this->success('Файл: ' . $result->path);
    }

    /**
     * Сообщение ошибки создания модели
     * @param GenerateResult $result
     * @param int|string $id
     * @return void
     */
    private function creationError(GenerateResult $result, int|string $id): void
    {
        $this->warning(
            sprintf(
                'Ошибка создания модели %s - %s\%s',
                $id,
                $result->namespace,
                $result->name
            )
        );
        $this->warning('Файл: ' . $result->path);
    }

    /**
     * Получить аргумент
     * @param string $argument
     * @param string|null $default
     * @return string|null
     */
    private function getArgument(string $argument, ?string $default = null): ?string
    {
        return $this->arguments[$argument] ?? $default;
    }
}
