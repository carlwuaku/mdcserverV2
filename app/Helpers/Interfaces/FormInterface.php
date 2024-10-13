<?php
namespace App\Helpers\Interfaces;

/**
 * this interface helps to define the way in which tabular data is displayed for data retrieved for a certain model.
 * it let the model define the columns to be displayed, and how they are labelled.
 */
interface FormInterface
{
    public function getFormFields(): array;


}