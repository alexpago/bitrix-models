<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Console\Generate;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\SystemException;
use Pago\Bitrix\Models\Console\Generate\Models\HlBlock;
use Pago\Bitrix\Models\Console\Generate\Models\Iblock;
use Pago\Bitrix\Models\Console\Generate\Models\Table;
use Pago\Bitrix\Models\Console\Traits\ConsoleBaseMethods;
use Pago\Bitrix\Models\Console\Traits\ConsoleMessage;
use Pago\Bitrix\Models\Helpers\Helper;
use Pago\Bitrix\Models\Helpers\TableModelHelper;

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

        match (is_string($this->method) ? strtolower($this->method) : null) {
            // Генерация инфоблоков
            'iblock', 'ib' => call_user_func(function () {
                $this->generateIModel();
            }),
            // Генерация highload блоков
            'hlblock', 'highloadblock', 'hb', 'hl' => call_user_func(function () {
                $this->generateHlModel();
            }),
            // Генерация таблиц
            'table' => call_user_func(function () {
                $this->generateTableModel();
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
     * @return void
     * @throws SystemException
     */
    public function generateTableModel(): void
    {
        $message = 'Введите название таблицы через пробел для генерации модели.' . PHP_EOL;
        $message .= 'Для выхода введите "q".';
        do {
            $this->info($message);
            $input = explode(' ', strtolower($this->question('Ввод')));

            foreach ($input as $tableName) {
                if (! $tableName) {
                    continue;
                }
                if (! TableModelHelper::instance()->checkTableExists($tableName)) {
                    $this->error(sprintf('Таблица %s не найдена', $tableName));
                    continue;
                }
                $generate = (new Table($tableName))->generateModel();
                if ($generate->success) {
                    $this->creationSuccess($generate, $tableName);
                } else {
                    $this->creationError($generate, $tableName);
                }
                $this->creationWarnings($generate);
            }

            $input = strtolower($this->question('Продолжить y/n?'));
        } while (! ('n' === $input || 'q' === $input));
    }

    /**
     * Генерация highload
     * @return void
     * @throws SystemException
     */
    public function generateHlModel(): void
    {
        $lists = HighloadBlockTable::query()
            ->setSelect([
                'ID',
                'NAME',
                'TABLE_NAME'
            ])
            ->fetchAll();
        $lists = array_combine(
            array_column($lists, 'ID'),
            array_values($lists)
        );
        $message = 'Введите идентификаторы highload блоков через пробел для генерации модели.' . PHP_EOL;
        $message .= 'или all для всех. Для выхода введите "q".';
        do {
            $this->info($message);
            foreach ($lists as $entity) {
                $this->info(sprintf(
                    '%d - %s, TABLE_NAME: %s',
                    (int)$entity['ID'],
                    $entity['NAME'],
                    $entity['TABLE_NAME']
                ));
            }
            $input = explode(' ', strtolower($this->question('Ввод')));
            if ($input && in_array('all', $input)) {
                $input = array_keys($lists);
            }

            foreach ($input as $hlId) {
                $hlId = (int)$hlId;
                if (! $hlId) {
                    continue;
                }
                $generate = (new HlBlock($hlId))->generateModel();
                if ($generate->success) {
                    $this->creationSuccess($generate, $hlId);
                } else {
                    $this->creationError($generate, $hlId);
                }
                $this->creationWarnings($generate);
            }

            $input = strtolower($this->question('Продолжить y/n?'));
        } while (! ('n' === $input || 'q' === $input));
    }

    /**
     * Генерация инфоблоков
     * @return void
     * @throws SystemException
     */
    public function generateIModel(): void
    {
        $lists = IblockTable::query()
            ->setSelect([
                'ID',
                'IBLOCK_TYPE_ID',
                'NAME',
                'CODE',
                'API_CODE',
                'VERSION'
            ])
            ->setFilter([
                'ACTIVE' => 'Y'
            ])
            ->fetchAll();
        $lists = array_combine(
            array_column($lists, 'ID'),
            array_values($lists)
        );
        $message = 'Введите идентификаторы инфоблоков через пробел для генерации модели.' . PHP_EOL;
        $message .= 'или all для всех. Для выхода введите "q".';
        do {
            $this->info($message);
            foreach ($lists as $iblock) {
                $this->info(sprintf(
                    '%d - %s CODE: %s, API_CODE: %s',
                    (int)$iblock['ID'],
                    $iblock['NAME'],
                    $iblock['CODE'] ?: '-',
                    $iblock['API_CODE'] ?: '-'
                ));

                if (! $iblock['CODE']) {
                    $this->warning('WARNING: Не заполнен символьный код. Рекомендуется указать его');
                }
            }

            $input = explode(' ', strtolower($this->question('Ввод')));
            if ($input && in_array('all', $input)) {
                $input = array_keys($lists);
            }
            foreach ($input as $iblockId) {
                $iblockId = (int)$iblockId;
                if (! $iblockId) {
                    continue;
                }
                $generateModel = new Iblock($iblockId);
                $generate = $generateModel->generateModel();
                if ($generate->success) {
                    $this->creationSuccess($generate, $iblockId);
                } else {
                    $this->creationError($generate, $iblockId);
                }
                $this->creationWarnings($generate);

                // Генерация API_CODE при его отсутствии
                if ($generate->success && empty($lists[$iblockId]['API_CODE'])) {
                    $this->warning('У инфоблока отсутствует обязательное значение API_CODE. Устанавливаю API_CODE');
                    if ($generateModel->setApiCode()) {
                        $this->success('API_CODE успешно установлен');
                    } else {
                        $this->success('Ошибка установки API_CODE');
                    }
                }
            }
            $input = strtolower($this->question('Продолжить y/n?'));
        } while (! ('n' === $input || 'q' === $input));
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
}
