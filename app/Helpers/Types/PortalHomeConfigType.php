<?php
namespace App\Helpers\Types;



class Action
{
    public string $label;
    public string $icon;
    public string $type;
    public string $linkProp;
    public string $url;
    public array $urlParams;

    /**
     * a list of criteria which must all be met for the alert to be shown
     * @var CriteriaType[]
     */
    public array $criteria;

    public function __construct(
        string $label = '',
        string $icon = '',
        string $type = '',
        string $linkProp = '',
        string $url = '',
        array $urlParams = [],
        array $criteria = []
    ) {
        $this->label = $label;
        $this->icon = $icon;
        $this->type = $type;
        $this->linkProp = $linkProp;
        $this->url = $url;
        $this->urlParams = $urlParams;
        $this->criteria = $criteria;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'icon' => $this->icon,
            'type' => $this->type,
            'linkProp' => $this->linkProp,
            'url' => $this->url,
            'urlParams' => $this->urlParams,
            'criteria' => $this->criteria
        ];
    }

    public static function fromArray(array $data): self
    {
        $alertCriteria = [];
        if (isset($data['criteria']) && is_array($data['criteria'])) {
            foreach ($data['criteria'] as $criteriaData) {
                $alertCriteria[] = CriteriaType::fromArray($criteriaData);
            }
        }
        return new self(
            $data['label'] ?? '',
            $data['icon'] ?? '',
            $data['type'] ?? '',
            $data['linkProp'] ?? '',
            $data['url'] ?? '',
            $data['urlParams'] ?? [],
            $alertCriteria
        );
    }
}

class Alert
{
    public string $message;
    public string $type;

    /**
     * a list of criteria which must all be met for the alert to be shown
     * @var CriteriaType[]
     */
    public array $criteria;

    public function __construct(
        string $message = '',
        string $type = '',
        array $criteria = []
    ) {
        $this->message = $message;
        $this->type = $type;
        $this->criteria = $criteria;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'criteria' => $this->criteria
        ];
    }

    public static function fromArray(array $data): self
    {
        /**
         * @var CriteriaType[]
         */
        $alertCriteria = [];
        if (isset($data['criteria']) && is_array($data['criteria'])) {
            foreach ($data['criteria'] as $criteriaData) {
                $alertCriteria[] = CriteriaType::fromArray($criteriaData);
            }
        }
        return new self(
            $data['message'] ?? '',
            $data['type'] ?? '',
            $alertCriteria
        );
    }
}

class DataPoint
{

    public string $dataSource;
    public string $apiUrl;
    public string $message;
    public string $type;

    /**
     * a list of criteria which must all be met for the alert to be shown
     * @var CriteriaType[]
     */
    public array $criteria;

    public function __construct(
        string $dataSource = '',
        string $apiUrl = '',
        string $message = '',
        string $type = '',
        array $criteria = []
    ) {
        $this->dataSource = $dataSource;
        $this->apiUrl = $apiUrl;
        $this->message = $message;
        $this->type = $type;
        $this->criteria = $criteria;
    }

    public function toArray(): array
    {
        return [
            'dataSource' => $this->dataSource,
            'apiUrl' => $this->apiUrl,
            'message' => $this->message,
            'type' => $this->type,
            'criteria' => $this->criteria
        ];
    }

    public static function fromArray(array $data): self
    {
        /**
         * @var CriteriaType[]
         */
        $alertCriteria = [];
        if (isset($data['criteria']) && is_array($data['criteria'])) {
            foreach ($data['criteria'] as $criteriaData) {
                $alertCriteria[] = CriteriaType::fromArray($criteriaData);
            }
        }
        return new self(
            $data['dataSource'] ?? '',
            $data['apiUrl'] ?? '',
            $data['message'] ?? '',
            $data['type'] ?? '',
            $alertCriteria
        );
    }
}

class PortalHomeConfigType
{
    public string $title;
    public string $image;
    public string $icon;
    /**
     * links to be included
     * @var DataPoint[]
     */
    public array $dataPoints;
    /**
     * links to be included
     * @var Action[]
     */
    public array $actions;
    public string $description;

    /**
     * @var Alert[]
     */
    public array $alerts;

    public function __construct(
        string $title = '',
        string $image = '',
        string $icon = '',
        array $actions = [],
        string $description = '',
        array $alerts = [],
        array $dataPoints = []
    ) {
        $this->title = $title;
        $this->image = $image;
        $this->icon = $icon;
        $this->actions = $actions;
        $this->description = $description;
        $this->alerts = $alerts;
        $this->dataPoints = $dataPoints;
    }



    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'image' => $this->image,
            'icon' => $this->icon,
            'actions' => array_map(fn($action) => $action->toArray(), $this->actions),
            'description' => $this->description,
            'alerts' => array_map(fn($alert) => $alert->toArray(), $this->alerts),

        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    public static function fromArray(array $data): self
    {
        $actions = [];
        if (isset($data['actions']) && is_array($data['actions'])) {
            foreach ($data['actions'] as $actionData) {
                $actions[] = Action::fromArray($actionData);
            }
        }

        $alerts = [];
        if (isset($data['alerts']) && is_array($data['alerts'])) {
            foreach ($data['alerts'] as $alertData) {
                $alerts[] = Alert::fromArray($alertData);
            }
        }
        $dataPoints = [];
        if (isset($data['dataPoints']) && is_array($data['dataPoints'])) {
            foreach ($data['dataPoints'] as $dataPointData) {
                $dataPoints[] = DataPoint::fromArray($dataPointData);
            }
        }

        return new self(
            $data['title'] ?? '',
            $data['image'] ?? '',
            $data['icon'] ?? '',
            $actions,
            $data['description'] ?? '',
            $alerts,
            $dataPoints
        );
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if ($data === null) {
            throw new \InvalidArgumentException('Invalid JSON provided');
        }
        return self::fromArray($data);
    }
}
