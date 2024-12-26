<?php
namespace App\Helpers\Interfaces;

/**
 * this interface helps to define the way in which tabular data is displayed for data retrieved for a certain model.
 * it let the model define the columns to be displayed, and how they are labelled.
 */
interface TableDisplayInterface
{
    public function getDisplayColumns(): array;

    /**
     * this would represent how the fields should be labelled on the front end. the front end would default
     * to replacing dashes and underscores with spaces if a label is not defined for a key. this should only 
     * be done for those fields which are not clearly labelled
     */
    public function getDisplayColumnLabels(): array;


    /** 
     * this should return an array of the columns that should be filtered. each column would have it's type
     * and options
     * 
     * @return array {label:string, name:string, type:string, hint:string, options:array, value:string, required:bool}
     */

    public function getDisplayColumnFilters(): array;
}