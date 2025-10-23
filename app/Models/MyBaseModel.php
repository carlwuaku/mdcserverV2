<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;
use App\Traits\JsonQueryTrait;
use App\Traits\BaseBuilderJSONQueryTrait;
class MyBaseModel extends Model
{
    use JsonQueryTrait;
    protected $table = "";
    protected $allowedFields = [];


    /**
     * a list of tables to join with the model when getting details/searching. the key is the table name and the value is an array
     * specifying the fields and join condition
     * @var array{fields:string[], joinCondition:string, table:string}
     */
    public $joinSearchFields = [];
    protected $searchFields = [];
    /**
     * perform a search on the table of the model calling the method. the table name and columns are taken from the $table
     *  and $allowedFields properties respectively
     * //assuming a user searches for kofi mensa, akosua kobi, hpa 4041
     *   we have to implode this one too to produce a query like:
     *  where(
     * ( (first_name like 'kofi' or last_name like 'kofi') and (first_name like 'mensa' or last_name like 'mensah') ) or
     * ( (first_name like 'akosua' or last_name like 'akosua') and (first_name like 'kobi' or last_name like 'kobi') ) or
     *((first_name like 'hpa' or last_name like 'hpa') and (first_name like '4041' or last_name like '4041'))
     *)
     * @var string $searchString the string being searched. can be a comma-separated string
     * @var int $limit the number of rows to return
     * @var int $offset the offset or page, where pagination is needed
     * @return BaseBuilder an object that can be used in chain queries like ->join() or ->select()
     */
    public function search(string $searchString): BaseBuilder
    {
        try {
            // Sanitize the search string once
            $searchString = trim($searchString);
            $commaWords = array_filter(array_map('trim', explode(',', $searchString)));

            $builder = $this->db->table($this->table);

            // Prepare fields array
            $fields = [];
            $originalFields = $this->searchFields ?? $this->allowedFields;
            foreach ($originalFields as $originalField) {
                $fields[] = str_contains($originalField, ".") ? $originalField : "$this->table.$originalField";
            }

            // Handle joins if specified
            if (!empty($this->joinSearchFields)) {
                $table = $this->joinSearchFields['table'];
                foreach ($this->joinSearchFields['fields'] as $field) {
                    $fields[] = "$table.$field";
                }
                $builder->join($table, $this->joinSearchFields['joinCondition'], 'left');
            }

            // Build search conditions
            if (!empty($commaWords)) {
                $builder->groupStart(); // Start main group (outermost)

                foreach ($commaWords as $commaIndex => $commaWord) {
                    // For the first comma-separated value, start directly
                    // For subsequent values, OR them with the previous ones
                    if ($commaIndex > 0) {
                        $builder->orGroupStart();
                    } else {
                        $builder->groupStart();
                    }

                    $spaceWords = array_filter(array_map('trim', explode(' ', $commaWord)));

                    foreach ($spaceWords as $spaceIndex => $spaceWord) {
                        // For first word, create a field OR group
                        // For subsequent words, AND them with the previous group
                        if ($spaceIndex === 0) {
                            // Create first term group
                            $builder->groupStart(); // Start the field OR group

                            foreach ($fields as $fieldIndex => $field) {
                                $escapedWord = $this->db->escapeLikeString($spaceWord);

                                if ($fieldIndex === 0) {
                                    $builder->like("LOWER($field)", strtolower($escapedWord), 'both', true, true);
                                } else {
                                    $builder->orLike("LOWER($field)", strtolower($escapedWord), 'both', true, true);
                                }
                            }

                            $builder->groupEnd(); // Close field OR group
                        } else {
                            // For subsequent words, AND a new field OR group
                            $builder->groupStart(); // Start the AND group

                            foreach ($fields as $fieldIndex => $field) {
                                $escapedWord = $this->db->escapeLikeString($spaceWord);

                                if ($fieldIndex === 0) {
                                    $builder->like("LOWER($field)", strtolower($escapedWord), 'both', true, true);
                                } else {
                                    $builder->orLike("LOWER($field)", strtolower($escapedWord), 'both', true, true);
                                }
                            }

                            $builder->groupEnd(); // Close the AND group
                        }
                    }

                    $builder->groupEnd(); // Close the group for this comma-separated term
                }

                $builder->groupEnd(); // Close the main search group
            }

            return $builder;
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            log_message("error", $th->getTraceAsString());
            throw $th;
        }
    }

    public function _search(string $searchString): BaseBuilder
    {
        $words = explode(',', $searchString);
        $builder = $this->db->table($this->table);
        //if no search fields were defined, fall back to allowed fields. whatever the case, 
        $fields = $this->searchFields ?? $this->allowedFields;
        $likeConditionsArray = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) {
                continue;
            }
            $wordlikeConditions = [];
            //split the word by spaces and for each generate the search query and join them with 'and'
            $splitWords = explode(' ', $word);
            foreach ($splitWords as $splitWord) {
                $splitWord = trim($splitWord);
                if (empty($splitWord)) {
                    continue;
                }
                $splitWordLikeConditionsArray = [];
                foreach ($fields as $column) {
                    //append the table name to the column if it doesn't already have it appended. if there's a dot, leave it.
                    //so that if we add joined tables it doesn't add the wrong table names
                    $columnName = str_contains($column, ".") ? $column : $this->table . "." . $column;
                    log_message("info", "column name: $columnName");
                    $splitWordLikeConditionsArray[] = "{$columnName} LIKE '%{$splitWord}%' ";
                    //$splitWorkLikeConditionsArray = ["first_name like 'kofi'"]
                }
                $wordlikeConditions[] = "(" . implode(" or ", $splitWordLikeConditionsArray) . ")";
                //$wordlikeConditions = ["(first_name like 'kofi' or last_name like 'kofi')"]
            }

            $likeConditionsArray[] = "(" . implode(" and ", $wordlikeConditions) . ")";
            //$wordlikeConditions = [(("first_name like 'kofi' or last_name like 'kofi'") and (first_name like 'mensa' or last_name like 'mensah')) ]
        }


        $likeConditions = implode(" or ", $likeConditionsArray);
        //$likeConditions = (("first_name like 'kofi' or last_name like 'kofi'") and (first_name like 'mensa' or last_name like 'mensah')) or ((first_name like 'akosua' or last_name like 'akosua') and (first_name like 'kobi' or last_name like 'kobi') )
        // log_message("info", "$likeConditions");
        $builder->where("({$likeConditions})");
        return $builder;
    }

    /**
     * Retrieves distinct values from a given column in the database.
     *
     * @param string $column The name of the column to retrieve distinct values from.
     * @return array An array of distinct values from the specified column.
     */
    public function getDistinctValues(string $column): array
    {
        $query = $this->builder()->distinct()->select($column);
        return $query->get()->getResultArray();
    }

    /**
     * A function that prepares the given results as an array of values.
     *
     * @param array $results The array of results to be prepared.
     * @return array The array of key-value pairs prepared from the results.
     */
    public function prepResultsAsValuesArray(array $results): array
    {
        $keyValuePairs = [];
        foreach ($results as $value) {
            $keyValuePairs[] = ["key" => $value, "value" => $value];
        }
        return $keyValuePairs;
    }

    /**
     * A function to get distinct values as key-value pairs.
     *
     * @param string $column The column to retrieve distinct values from.
     * @return array
     */
    public function getDistinctValuesAsKeyValuePairs(string $column): array
    {
        $results = $this->getDistinctValues($column); //[[$column=>"value1"], [$column=>"value2"]]
        $oneDimensionalArray = [];
        foreach ($results as $result) {
            $oneDimensionalArray[] = $result[$column];
        }
        //convert the results to a one-dimensional array of key-value pairs
        return $this->prepResultsAsValuesArray($oneDimensionalArray);
    }

    /**
     * A function to get multiple values as key-value pairs.
     * 
     * This function takes in an array of columns to select, an array of key columns and a value column.
     * It then selects the specified columns from the database and loops over the results.
     * For each result, it constructs a key by joining the values of the key columns separated by a dash.
     * It then constructs a key-value pair with the key and the value from the specified value column.
     * The function returns an array of these key-value pairs.
     * 
     * @param array $columns An array of columns to select from the database.
     * @param array $keyColumns An array of columns to use as keys. The values of these columns will be joined by a dash to form the key.
     * @param string $valueColumn The column to use as the value in the key-value pairs.
     * @return array An array of key-value pairs.
     */
    public function getMultipleValuesAsKeyValuePairs(array $keyColumns, string $valueColumn): array
    {
        $query = $this->builder();
        $results = $query->get()->getResultArray();
        $keyValuePairs = [];
        foreach ($results as $value) {
            //for the key, get the values from the keyColumns separated by a dash e.g. "key1 - key2 - key3"
            $keyVals = [];
            foreach ($keyColumns as $keyColumn) {
                $keyVals[] = $value[$keyColumn];
            }
            $key = implode(" - ", $keyVals);
            $keyValuePairs[] = ["key" => $key, "value" => $value[$valueColumn]];
        }
        return $keyValuePairs;
    }

    /**
     * A function to create an array based on the allowedFields property of the model.
     * it creates an array with the keys as the allowed fields and the values extracted for each key from the provided array 
     */
    public function createArrayFromAllowedFields(array $data, bool $fillNull = false): array
    {
        $array = [];
        foreach ($this->allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($data[$field] === null && !$fillNull) {
                    continue;
                }
                $array[$field] = $data[$field] ?? null;
            }
        }
        return $array;
    }

    /**
     * A function to get the counts of rows based on some grouped colums
     *
     * @param array $columns The columns to retrieve counts for.
     * @param string $where The where clause to apply to the query.
     * @return array{form_type:string, count:int, status: string}
     */
    public function getGroupedCounts($columns, $where = ""): array
    {
        //sanitise the columns
        $builder = $this->builder();
        $builder->select(["form_type", ...$columns, "count(*) as count"]);
        if (!empty($where)) {
            $builder->where($where);
        }
        $builder->groupBy($columns);
        return $builder->get()->getResultArray();
    }

    public function getTableName(): string
    {
        return $this->table;
    }
}

class JoinSearchFields
{
    public string $table;
    public array $fields;
    public string $joinCondition;

    public function __construct(string $table, array $fields, string $joinCondition)
    {
        $this->table = $table;
        $this->fields = $fields;
        $this->joinCondition = $joinCondition;
    }
}