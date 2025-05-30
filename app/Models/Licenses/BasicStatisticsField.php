<?php

namespace App\Models\Licenses;
class BasicStatisticsField
{
    public $label;
    public $name;
    public $type;
    public $data;

    public $xAxisLabel;
    public $yAxisLabel;

    public $valueProperty;
    public $labelProperty;

    public function __construct($label, $name, $type, $xAxisLabel = '', $yAxisLabel = '', $valueProperty = '', $labelProperty = '')
    {
        $this->label = $label;
        $this->name = $name;
        $this->type = $type;
        $this->xAxisLabel = $xAxisLabel;
        $this->yAxisLabel = $yAxisLabel;
        $this->valueProperty = $valueProperty;
        $this->labelProperty = $labelProperty;
    }
}