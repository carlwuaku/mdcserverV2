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
     * @var string $searchString the string being searched. can be a comma-separated string
     * @var int $limit the number of rows to return
     * @var int $offset the offset or page, where pagination is needed
     * @return BaseBuilder an object that can be used in chain queries like ->join() or ->select()
     */
    public function search(string $searchString): BaseBuilder
    {
        $words = explode(',', $searchString);
        $builder = $this->db->table($this->table);
        //if no search fields were defined, fall back to allowed fields. whatever the case, 
        $fields = $this->searchFields ?? $this->allowedFields;
        foreach ($words as $word) {
            $likeCondition = '';
            foreach ($fields as $column) {
                //append the table name to the column if it doesn't already have it appended. if there's a dot, leave it.
                //so that if we add joined tables it doesn't add the wrong table names
                $columnName = str_contains($column,".") ? $column : $this->table .".". $column;
                $likeCondition .= "{$columnName} LIKE '%{$word}%' OR ";
            }
            $likeCondition = rtrim($likeCondition, ' OR ');
            $builder->where("({$likeCondition})");
        }
        return $builder;
    }
}