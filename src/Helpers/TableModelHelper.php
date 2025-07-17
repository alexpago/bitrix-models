<?php
declare(strict_types=1);

namespace Pago\Bitrix\Models\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\FloatField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DecimalField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\ObjectField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;

/**
 * Вспомогательные методы для модели таблиц
 */
final class TableModelHelper
{
    /**
     * @var TableModelHelper|null
     */
    private static ?self $instance = null;

    /**
     * Свойства для @see DataManager
     * @var array
     */
    private array $maps = [];

    /**
     * @return self
     */
    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Проверка существования таблицы
     * @param string $tableName
     * @return bool
     */
    public function checkTableExists(string $tableName): bool
    {
        global $DB;
        $check = $DB->Query(sprintf('SHOW TABLES LIKE \'%s\'', $tableName))->Fetch();

        return $check !== false;
    }

    /**
     * Получение свойств getMap для
     * @param string $tableName
     * @param array $casts Предопределение типов полей
     * @return array
     * @throws SystemException @see DataManager
     */
    public function getMap(string $tableName, array $casts = []): array
    {
        if (array_key_exists($tableName, $this->maps)) {
            return $this->maps;
        }
        $map = [];
        foreach ($this->getTableColumns($tableName) as $column) {
            $type = strtolower($casts[$column['name']] ?? $column['type']);
            $mapField = match ($type) {
                'int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint' => new IntegerField($column['name']),
                'float', 'double', 'real' => new FloatField($column['name']),
                'decimal' => new DecimalField($column['name']),
                'boolean', 'bool' => new BooleanField($column['name']),
                'date' => new DateField($column['name']),
                'datetime', 'timestamp' => new DatetimeField($column['name']),
                'varchar', 'char' => new StringField($column['name']),
                'json' => new ArrayField($column['name']),
                'enum' => new EnumField($column['name']),
                'object' => new ObjectField($column['name']),
                default => new TextField($column['name'])
            };
            if ($column['auto_increment']) {
                $mapField->configurePrimary();
            }
            if (null !== $column['size']) {
                $mapField->configureSize($column['size']);
            }
            $map[] = $mapField
                ->configureAutocomplete($column['auto_increment'])
                ->configureNullable($column['nullable'])
                ->configureDefaultValue($column['default']);
        }
        $this->maps[$tableName] = $map;

        return $map;
    }

    /**
     * Получить столбцы таблицы
     * @param string $tableName
     * @return array
     * @throws SystemException
     */
    public function getTableColumns(string $tableName): array
    {
        global $DB;
        $sql = $DB->Query(sprintf('show create table %s', $tableName))->Fetch();
        $createTableSql = $sql['Create Table'] ?? null;
        if (! $createTableSql) {
            throw new SystemException(sprintf('Не получена информация к таблице %s', $tableName));
        }

        return $this->getColumns($createTableSql);
    }

    /**
     * Является ли подключение MySQL
     * @return bool
     */
    public function isMysqlConnect(): bool
    {
        return strtolower(Application::getConnection()->getType()) === 'mysql';
    }

    /**
     * Получить столбцы таблицы
     * @param string $createTableSql
     * @return array
     */
    private function getColumns(string $createTableSql): array
    {
        $columns = [];

        foreach (explode("\n", $createTableSql) as $line) {
            $line = trim($line);

            // Ищем строки, которые определяют колонки
            if (preg_match('/^`(.+?)`\s+(.+?),?$/', $line, $matches)) {
                // Дефолтное значение
                $default = null;
                if (preg_match('/DEFAULT \'(.+)\'/', $line, $defaultMatches)) {
                    $default = $defaultMatches[1];
                }
                $type = explode(' ', $matches[2]);
                $size = preg_replace('/[^0-9]/', '', $type[0]);
                $columns[] = [
                    'name' => $matches[1], // Название колонки
                    'type' => preg_replace('/[^a-z]+/i', '', $type[0]), // Тип данных
                    'size' => $size !== '' ? $size : null,
                    'nullable' => ! str_contains($line, 'NOT NULL'),
                    'default' => str_contains($line, 'DEFAULT NULL') ? null : $default,
                    'auto_increment' => str_contains($line, 'AUTO_INCREMENT'),
                ];
            }
        }

        return $columns;
    }
}
