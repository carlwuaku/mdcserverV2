<?php

namespace App\Traits;

/**
 * JSONQueryTrait
 * 
 * Adds JSON query capabilities to CodeIgniter 4 Models using JSON_EXTRACT
 */
trait JSONQueryTrait
{
    /**
     * Query a JSON field for equality
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path (e.g. '$.field' or '$.nested.field')
     * @param mixed $value Value to compare against
     * @param boolean $asString Whether to cast to string 
     * @return $this
     */
    public function whereJson(string $jsonColumn, string $path, $value, bool $asString = true)
    {
        $field = $this->buildJsonExtract($jsonColumn, $path, $asString);
        return $this->where($field, $value);
    }

    /**
     * Query a JSON field with a custom operator
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param string $operator Comparison operator (=, >, <, etc)
     * @param mixed $value Value to compare against
     * @param boolean $asString Whether to cast to string
     * @return $this
     */
    public function whereJsonCompare(string $jsonColumn, string $path, string $operator, $value, bool $asString = true)
    {
        $field = $this->buildJsonExtract($jsonColumn, $path, $asString);
        return $this->where("{$field} {$operator}", $value, false);
    }

    /**
     * Search for a value within a JSON string field (LIKE operator)
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param string $value Value to search for
     * @param string $position Position of wildcard ('both', 'before', 'after', 'none')
     * @return $this
     */
    public function likeJson(string $jsonColumn, string $path, string $value, string $position = 'both')
    {
        $field = $this->buildJsonExtract($jsonColumn, $path, true);
        return $this->like($field, $value, $position, false);
    }

    /**
     * Check if a JSON field contains a specific value
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path for the array
     * @param mixed $value Value to check for
     * @return $this
     */
    public function whereJsonContains(string $jsonColumn, string $path, $value)
    {
        $escapedPath = $this->db->escape($path);
        $jsonValue = json_encode($value);
        $escapedValue = $this->db->escape($jsonValue);
        $extractedPath = "JSON_EXTRACT(`{$jsonColumn}`, {$escapedPath})";

        $sql = "JSON_CONTAINS({$extractedPath}, {$escapedValue})";
        return $this->where($sql, 1, false);
    }

    /**
     * Check if a JSON path exists
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path to check
     * @param boolean $mustExist Whether the path must exist (true) or must not exist (false)
     * @return $this
     */
    public function whereJsonExists(string $jsonColumn, string $path, bool $mustExist = true)
    {
        $escapedPath = $this->db->escape($path);
        $value = $mustExist ? 1 : 0;

        $sql = "JSON_CONTAINS_PATH(`{$jsonColumn}`, 'one', {$escapedPath})";
        return $this->where($sql, $value, false);
    }

    /**
     * Order results by a JSON field
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param string $direction Order direction ('ASC' or 'DESC')
     * @param boolean $asString Whether to cast to string
     * @return $this
     */
    public function orderByJson(string $jsonColumn, string $path, string $direction = 'ASC', bool $asString = true)
    {
        $field = $this->buildJsonExtract($jsonColumn, $path, $asString);
        return $this->orderBy($field, $direction);
    }

    /**
     * Select a specific JSON field
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param string $alias Optional alias for the selected field
     * @param boolean $asString Whether to cast to string
     * @return $this
     */
    public function selectJson(string $jsonColumn, string $path, string $alias = null, bool $asString = true)
    {
        $field = $this->buildJsonExtract($jsonColumn, $path, $asString);

        if ($alias !== null) {
            $field .= " AS `{$alias}`";
        }

        return $this->select($field, false);
    }

    /**
     * Build a JSON_EXTRACT expression with optional string casting
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param boolean $asString Whether to cast to string
     * @return string SQL expression
     */
    protected function buildJsonExtract(string $jsonColumn, string $path, bool $asString = true)
    {
        $escapedPath = $this->db->escape($path);
        $extract = "JSON_EXTRACT(`{$jsonColumn}`, {$escapedPath})";

        if ($asString) {
            // Cast JSON value as string to handle string comparisons properly
            return "JSON_UNQUOTE({$extract})";
        }

        return $extract;
    }

    /**
     * Filter records by multiple JSON conditions
     *
     * @param string $jsonColumn Name of the JSON column
     * @param array $conditions Array of ['path' => '$.field', 'value' => 'value', 'operator' => '=']
     * @param string $joinType How to join conditions ('AND' or 'OR')
     * @param boolean $asString Whether to cast to string
     * @return $this
     */
    public function whereJsonMultiple(string $jsonColumn, array $conditions, string $joinType = 'AND', bool $asString = true)
    {
        $this->groupStart();

        $first = true;
        foreach ($conditions as $condition) {
            $path = $condition['path'] ?? null;
            $value = $condition['value'] ?? null;
            $operator = $condition['operator'] ?? '=';

            if ($path === null || $value === null) {
                continue;
            }

            $method = $first ? 'where' : strtolower($joinType);
            $field = $this->buildJsonExtract($jsonColumn, $path, $asString);

            $this->$method("{$field} {$operator}", $value, false);
            $first = false;
        }

        return $this->groupEnd();
    }
}