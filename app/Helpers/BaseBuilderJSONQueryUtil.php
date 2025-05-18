<?php

namespace App\Helpers;

use CodeIgniter\Database\BaseBuilder;

/**
 * BaseBuilderJSONQueryTrait
 * 
 * Adds JSON query capabilities to CodeIgniter 4 Base Builder using JSON_EXTRACT
 * it's meant to be used with BaseBuilder, constructed from models, not directly. 
 */
class BaseBuilderJSONQueryUtil
{
    /**
     * @var \CodeIgniter\Database\ConnectionInterface
     */
    private static $db;
    public function __construct()
    {

    }
    /**
     * Query a JSON field for equality
     * @param BaseBuilder $builder
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path (e.g. '$.field' or '$.nested.field')
     * @param mixed $value Value to compare against
     * @param boolean $asString Whether to cast to string 
     * @return BaseBuilder
     */
    public static function whereJson(BaseBuilder $builder, string $jsonColumn, string $path, $value, bool $asString = true)
    {
        $field = self::buildJsonExtract($jsonColumn, $path, $asString);
        $builder->where($field, $value);
        return $builder;
    }

    /**
     * Query a JSON field with a custom operator
     * @param \CodeIgniter\Database\BaseBuilder $builder
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param string $operator Comparison operator (=, >, <, etc)
     * @param mixed $value Value to compare against
     * @param boolean $asString Whether to cast to string
     * @return BaseBuilder
     */
    public static function whereJsonCompare(BaseBuilder $builder, string $jsonColumn, string $path, string $operator, $value, bool $asString = true)
    {
        $field = self::buildJsonExtract($jsonColumn, $path, asString: $asString);
        $builder->where("{$field} {$operator}", $value, true);
        return $builder;
    }

    /**
     * Search for a value within a JSON string field (LIKE operator)
     *@param BaseBuilder $builder
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param string $value Value to search for
     * @param string $position Position of wildcard ('both', 'before', 'after', 'none')
     * @return BaseBuilder
     */
    public static function likeJson(BaseBuilder $builder, string $jsonColumn, string $path, string $value, string $position = 'both')
    {
        $field = self::buildJsonExtract($jsonColumn, $path, true);
        $builder->like($field, $value, $position, false);
        return $builder;
    }

    /**
     * Check if a JSON field contains a specific value
     *@param BaseBuilder $builder
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path for the array
     * @param mixed $value Value to check for
     * @return BaseBuilder
     */
    public static function whereJsonContains(BaseBuilder $builder, string $jsonColumn, string $path, $value)
    {
        $db = db_connect();
        $jsonValue = json_encode($value);
        $escapedValue = $db->escape($jsonValue);
        $extractedPath = self::buildJsonExtract($jsonColumn, $path, true);

        $sql = "JSON_CONTAINS({$extractedPath}, {$escapedValue})";

        $builder->where($sql, 1, false);
        return $builder;
    }

    /**
     * Check if a JSON path exists
     *@param BaseBuilder $builder
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path to check
     * @param boolean $mustExist Whether the path must exist (true) or must not exist (false)
     * @return BaseBuilder
     */
    public static function whereJsonExists(BaseBuilder $builder, string $jsonColumn, string $path, bool $mustExist = true)
    {
        $db = db_connect();
        $escapedPath = $db->escape($path);
        $value = $mustExist ? 1 : 0;

        $sql = "JSON_CONTAINS_PATH(`{$jsonColumn}`, 'one', {$escapedPath})";
        $builder->where($sql, $value, true);
        return $builder;
    }

    /**
     * Order results by a JSON field
     *@param BaseBuilder $builder
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param string $direction Order direction ('ASC' or 'DESC')
     * @param boolean $asString Whether to cast to string
     * @return BaseBuilder
     */
    public static function orderByJson(BaseBuilder $builder, string $jsonColumn, string $path, string $direction = 'ASC', bool $asString = true)
    {
        $field = self::buildJsonExtract($jsonColumn, $path, $asString);
        $builder->orderBy($field, $direction);
        return $builder;
    }

    /**
     * Select a specific JSON field
     * @param BaseBuilder $builder
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param string $alias Optional alias for the selected field
     * @param boolean $asString Whether to cast to string
     * @return BaseBuilder
     */
    public static function selectJson(BaseBuilder $builder, string $jsonColumn, string $path, string $alias = null, bool $asString = true)
    {
        $field = self::buildJsonExtract($jsonColumn, $path, $asString);

        if ($alias !== null) {
            $field .= " AS `{$alias}`";
        }

        $builder->select($field, false);
        return $builder;
    }

    /**
     * Build a JSON_EXTRACT expression with optional string casting
     *
     * @param string $jsonColumn Name of the JSON column
     * @param string $path JSON path
     * @param boolean $asString Whether to cast to string
     * @return string SQL expression
     */
    protected static function buildJsonExtract(string $jsonColumn, string $path, bool $asString = true)
    {
        $db = db_connect();
        //make sure the path contains the $. sign
        if (strpos($path, '$.') !== 0) {
            $path = '$.' . $path;
        }
        // Escape the JSON path
        $escapedPath = $db->escape($path);


        $extract = "JSON_EXTRACT(`{$jsonColumn}`, {$escapedPath})";

        if ($asString) {
            // Cast JSON value as string to handle string comparisons properly
            return "JSON_UNQUOTE({$extract})";
        }

        return $extract;
    }

    /**
     * Filter records by multiple JSON conditions
     * @param BaseBuilder $builder
     * @param string $jsonColumn Name of the JSON column
     * @param array $conditions Array of ['path' => '$.field', 'value' => 'value', 'operator' => '=']
     * @param string $joinType How to join conditions ('AND' or 'OR')
     * @param boolean $asString Whether to cast to string
     * @return BaseBuilder
     */
    public static function whereJsonMultiple(BaseBuilder $builder, string $jsonColumn, array $conditions, string $joinType = 'AND', bool $asString = true)
    {
        $builder->groupStart();

        $first = true;
        foreach ($conditions as $condition) {
            $path = $condition['path'] ?? null;
            $value = $condition['value'] ?? null;
            $operator = $condition['operator'] ?? '=';

            if ($path === null || $value === null) {
                continue;
            }

            // $method = $first ? 'where' : strtolower($joinType);
            $field = self::buildJsonExtract($jsonColumn, $path, $asString);

            // $this->$method("{$field} {$operator}", $value, false);
            if ($first) {
                $builder->where("{$field} {$operator}", $value, false);
            } else {
                $builder->groupStart();
                $builder->where("{$field} {$operator}", $value, false);
                $builder->groupEnd();
            }
            $first = false;
        }

        $builder->groupEnd();
        return $builder;
    }
}