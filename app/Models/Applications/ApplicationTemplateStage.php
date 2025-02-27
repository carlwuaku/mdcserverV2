<?php

namespace App\Models\Applications;
class ApplicationTemplateStage
{

    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $description;
    /**
     * @var array
     */
    public $allowedTransitions;
    /**
     * @var array{"type":string,"config":object{"template":string,"subject":string,"endpoint":string,"method":string,"recipient_field":string}
     */
    public $actions;
}
