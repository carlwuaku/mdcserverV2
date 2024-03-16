<?php
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class MyBaseModel extends Model
{
    protected $table = "";
    protected $allowedFields = [];
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
    public function search(string $searchString): BaseBuilder{
        //sanitize the search string
        $searchString = $this->db->escapeLikeString($searchString);
        $words = array_map('trim', explode(',', $searchString));
        $builder = $this->db->table($this->table);
        $fields = $this->searchFields ?? $this->allowedFields;
        $conditions = [];

        foreach ($words as $word) {
            if (!empty($word)) {
                $wordlikeConditions = [];
                $splitWords = array_map('trim', explode(' ', $word));
                foreach ($splitWords as $splitWord) {
                    if (!empty($splitWord)) {
                        $splitWordLikeConditionsArray = [];
                        $splitWord = $this->db->escapeLikeString($splitWord);
                        $splitWordConditions = array_map(function ($column) use ($splitWord, &$splitWordLikeConditionsArray) {
                            $columnName = str_contains($column, ".") ? $column : $this->table . "." . $column;
                            $splitWordLikeConditionsArray[] = "{$columnName} LIKE '%{$splitWord}%'";
                        }, $fields);
                        $wordlikeConditions[] = "(" . implode(" or ", $splitWordLikeConditionsArray) . ")";
                    }
                }
                $conditions[] = "(" . implode(" and ", $wordlikeConditions) . ")";

            }
        }

        $likeConditions = implode(" or ", $conditions);
        $builder->where($likeConditions);
        return $builder;
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
        log_message("info", "$likeConditions");
        $builder->where("({$likeConditions})");
        return $builder;
    }
}