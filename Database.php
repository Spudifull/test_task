<?php

namespace FpDbTest;

use Exception;
use mysqli;
use FpDbTest\SQLFormatter;
use Override;

/**
 * Класс для работы с базой данных и формирования SQL-запросов.
 */
readonly class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    use SQLFormatter;

    /**
     * Конструктор класса Database.
     *
     * @param mysqli $mysqli
     */
    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * Формирует SQL-запрос из шаблона и массива аргументов.
     *
     * @param string $query Шаблон SQL-запроса с плейсхолдерами.
     * @param array $args Массив аргументов для подстановки в запрос.
     * @return string Сформированный SQL-запрос.
     * @throws Exception Если отсутствует аргумент для подстановки.
     */
    #[Override]
    public function buildQuery(string $query, array $args = []): string
    {
        if (trim($query) === '') {
            throw new Exception("SQL query template cannot be empty");
        }

        $query = $this->processConditionalBlocks($query, $args);

        return $this->processParameters($query, $args);
    }

    /**
     * Обрабатывает условные блоки в SQL-шаблоне.
     *
     * @param string $query SQL-шаблон с условными блоками.
     * @param array $args Массив аргументов для проверки условий.
     * @return string SQL-шаблон с обработанными условными блоками.
     */
    private function processConditionalBlocks(string $query, array $args): string
    {
        $skipValue = $this->skip();
        return preg_replace_callback('/{([^{}]*)}/', function ($matches) use ($args, $skipValue) {
            if (in_array($skipValue, $args, true)) {
                return '';
            }
            return $matches[1];
        }, $query);
    }

    /**
     * Обрабатывает плейсхолдеры в SQL-шаблоне, подставляя значения из массива аргументов.
     *
     * @param string $query SQL-шаблон с плейсхолдерами.
     * @param array $args Массив аргументов для подстановки.
     * @return string SQL-запрос с подставленными значениями.
     * @throws Exception
     */
    private function processParameters(string $query, array $args): string
    {
        $index = 0;
        return preg_replace_callback(
            '/\?([dfa#s])?/',
            function ($matches) use (&$args, &$index) {
                if (!isset($args[$index])) {
                    throw new Exception("Missing argument at index {$index}");
                }

            $value = $args[$index++];
            $type = $matches[1] ?? $this->determineType($value);

            if ($type === 'SQL_SKIP_BLOCK') {
                throw new Exception(
                    "SQL_SKIP_BLOCK placeholder found but is not supported directly in method."
                );
            }
            return $this->formatValueByType($value, $type);
        }, $query
        );
    }

    /**
     * Определяет тип переданного значения для соответствия типам плейсхолдеров SQL.
     *
     * @param mixed $value Значение, для которого нужно определить тип.
     * @return string Код типа значения ('d' для int, 'f' для float, 'a' для array, 's' для string и др.).
     */
    private function determineType(mixed $value): string
    {
        return match (true) {
            is_null($value) => 'NULL',
            is_bool($value) => 'b',
            is_int($value) => 'd',
            is_float($value) => 'f',
            is_array($value) => 'a',
            default => 's',
        };
    }

    /**
     * Форматирует значение в соответствии с указанным типом для включения в SQL-запрос.
     *
     * Эта функция принимает значение и его тип, затем возвращает значение, отформатированное
     * для безопасного использования в SQL-запросе. Включает в себя обработку специфических
     * типов, таких как NULL, числовые типы, массивы и строки, а также идентификаторы.
     *
     * @param mixed $value Значение для форматирования.
     * @param string $type Тип значения, основанный на однобуквенном коде (определённом в determineType).
     * @return string Форматированное для SQL значение.
     * @throws Exception Если передан неизвестный тип значения.
     */
    protected function formatValueByType(mixed $value, string $type): string {
        return match ($type) {
            'd' => is_null($value) ? 'NULL' : (string)(int)$value,
            'f' => is_null($value) ? 'NULL' : (string)(float)$value,
            'a' => $this->formatArray($value),
            '#' => $this->formatFieldArray(is_array($value) ? $value : [$value]),
            default => $this->formatValue($value),
        };
    }

    /**
     * Возвращает специальное значение для пропуска условного блока в SQL-запросе.
     *
     * @return string Специальное значение для пропуска блока.
     */
    #[Override]
    public function skip(): string
    {
        return 'SKIP_THIS_BLOCK';
    }
}