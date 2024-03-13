<?php

namespace FpDbTest;

use Exception;

/**
 * Трейт предоставляет набор утилит для форматирования значений SQL-запросов.
 */
trait SQLFormatter
{
    /**
     * Форматирует значение для использования в SQL-запросе в зависимости от его типа.
     *
     * @param mixed $value Значение, которое необходимо форматировать.
     * @return string Форматированное значение, готовое к вставке в SQL-запрос.
     */
    protected function formatValue(mixed $value): string
    {
        return match (true) {
            is_null($value) => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string)$value,
            default => "'" . $this -> addSlashesToValue(
                $this -> escapeString((string)$value)) . "'",
        };
    }

    /**
     * Экранирует специальные символы в строке для использования в SQL-запросе.
     *
     * @param string $value Строка, которую необходимо экранировать.
     * @return string Экранированная строка.
     */
    protected function addSlashesToValue(string $value): string {
        return addcslashes($value, "'");
    }

    /**
     * Форматирует массив для использования в SQL-запросе.
     * Отличает ассоциативные массивы для части SET и обычные массивы для оператора IN.
     *
     * @param array $array Массив значений для форматирования.
     * @return string Строка значений, отформатированная для SQL.
     * @throws Exception
     */
    protected function formatArray(array $array): string {
        if (empty($array)) {
            throw new Exception("Array for formatting cannot be empty");
        }

        return $this->isAssoc($array) ? $this->formatAssocArray($array) : $this->formatValueList($array);
    }

    /**
     * Форматирует ассоциативный массив для части SET в SQL-запросе UPDATE.
     *
     * @param array $array Ассоциативный массив значений [поле => значение].
     * @return string Строка для части SET запроса.
     * @throws Exception
     */
    protected function formatAssocArray(array $array): string {
        return implode(", ", array_map(function ($column, $val) {
            return $this->formatIdentifier($column) . " = " . $this->formatValue($val);
        }, array_keys($array), $array));
    }

    /**
     * Форматирует список значений для использования в операторе IN SQL-запроса.
     *
     * @param array $array Массив значений.
     * @return string Строка значений, разделённых запятой.
     */
    protected function formatValueList(array $array): string {
        return implode(", ", array_map([$this, 'formatValue'], $array));
    }

    /**
     * Проверяет, является ли массив ассоциативным.
     *
     * @param array $arr Проверяемый массив.
     * @return bool Возвращает true, если массив ассоциативный.
     */
    protected function isAssoc(array $arr): bool {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Форматирует идентификатор (например, имя столбца или таблицы) для использования в SQL-запросе.
     *
     * @param string $identifier Идентификатор для форматирования.
     * @return string Форматированный идентификатор.
     * @throws Exception
     */
    protected function formatIdentifier(string $identifier): string
    {
        if (trim($identifier) === '') {
            throw new Exception("Identifier for formatting cannot be empty.");
        }

        return "`" . str_replace("`", "``", $identifier) . "`";
    }

    /**
     * Экранирует строку с использованием функции real_escape_string объекта mysqli.
     *
     * @param string $value Строка для экранирования.
     * @return string Экранированная строка.
     */
    protected function escapeString(string $value): string
    {
        return $this->mysqli->real_escape_string($value);
    }

    /**
     * Форматирует массив имен полей в строку, разделенную запятыми, для использования в SQL-запросе.
     *
     * @param array $fields Массив имен полей.
     * @return string Строка имен полей, разделенная запятыми.
     */
    protected function formatFieldArray(array $fields): string {
        $escapedFields = array_map(function($field) {
            return "`" . str_replace("`", "``", $field) . "`";
        }, $fields);
        return implode(", ", $escapedFields);
    }
}
