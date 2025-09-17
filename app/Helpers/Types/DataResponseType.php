<?php
namespace App\Helpers\Types;

/**
 * @template T
 */
class DataResponseType
{
    /**
     * a list of results
     * @var T[]
     */
    public array $data;
    public int $total;

    /**
     * a list of column names to display. these are the column keys in $data. they'll be displayed in the UI in order in which they are added
     * @var string[]
     */
    public array $displayColumns;

    /**
     * a list of form fields to use as filters in the UI
     * @var FormFieldType[]
     */
    public array $columnFilters;

    public function __construct(
        array $data = [],
        int $total = 0,
        array $displayColumns = [],
        array $columnFilters = []
    ) {
        $this->data = $data;
        $this->total = $total;
        $this->displayColumns = $displayColumns;
        $this->columnFilters = $columnFilters;
    }

    /**
     * Create instance from array
     * 
     * @template U
     * @param array{data: U[], total: int, displayColumns: string[], columnFilters: FormFieldType[]} $array
     * @return DataResponseType<U>
     */
    public static function fromArray(array $array): self
    {
        $instance = new self();
        $instance->data = $array['data'] ?? [];
        $instance->total = $array['total'] ?? 0;
        $instance->displayColumns = $array['displayColumns'] ?? [];
        $instance->columnFilters = $array['columnFilters'] ?? [];

        return $instance;
    }

    /**
     * Create instance from JSON string
     * 
     * @template U
     * @param string $json
     * @return DataResponseType<U>
     * @throws \JsonException
     */
    public static function fromJson(string $json): self
    {
        $array = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($array);
    }

    /**
     * Create instance with typed data transformation
     * 
     * @template U
     * @param array $rawData
     * @param int $total
     * @param string[] $displayColumns
     * @param FormFieldType[] $columnFilters
     * @param callable(mixed): U|null $transformer Optional transformer function to convert raw data to typed objects
     * @return DataResponseType<U>
     */
    public static function create(
        array $rawData,
        int $total,
        array $displayColumns,
        array $columnFilters,
        ?callable $transformer = null
    ): self {
        $instance = new self();
        $instance->data = $transformer ? array_map($transformer, $rawData) : $rawData;
        $instance->total = $total;
        $instance->displayColumns = $displayColumns;
        $instance->columnFilters = $columnFilters;

        return $instance;
    }

    /**
     * Convert to array
     * 
     * @return array{data: T[], total: int, displayColumns: string[], columnFilters: FormFieldType[]}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'total' => $this->total,
            'displayColumns' => $this->displayColumns,
            'columnFilters' => $this->columnFilters,
        ];
    }

    /**
     * Convert to JSON string
     * 
     * @param int $flags JSON encoding flags
     * @return string
     * @throws \JsonException
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * Create empty instance
     * 
     * @template U
     * @param string[] $displayColumns
     * @param FormFieldType[] $columnFilters
     * @return DataResponseType<U>
     */
    public static function empty(array $displayColumns = [], array $columnFilters = []): self
    {
        return self::create([], 0, $displayColumns, $columnFilters);
    }

    /**
     * Check if response has data
     * 
     * @return bool
     */
    public function hasData(): bool
    {
        return !empty($this->data);
    }

    /**
     * Get the count of data items
     * 
     * @return int
     */
    public function getDataCount(): int
    {
        return count($this->data);
    }

    /**
     * Map data to a new type
     * 
     * @template U
     * @param callable(T): U $mapper
     * @return DataResponseType<U>
     */
    public function map(callable $mapper): self
    {
        $newInstance = new self();
        $newInstance->data = array_map($mapper, $this->data);
        $newInstance->total = $this->total;
        $newInstance->displayColumns = $this->displayColumns;
        $newInstance->columnFilters = $this->columnFilters;

        return $newInstance;
    }

    /**
     * Filter data items
     * 
     * @param callable(T): bool $predicate
     * @return DataResponseType<T>
     */
    public function filter(callable $predicate): self
    {
        $newInstance = new self();
        $newInstance->data = array_filter($this->data, $predicate);
        $newInstance->total = count($newInstance->data); // Update total to reflect filtered count
        $newInstance->displayColumns = $this->displayColumns;
        $newInstance->columnFilters = $this->columnFilters;

        return $newInstance;
    }

    /**
     * Get first data item
     * 
     * @return T|null
     */
    public function first()
    {
        return $this->data[0] ?? null;
    }

    /**
     * Get last data item
     * 
     * @return T|null
     */
    public function last()
    {
        return end($this->data) ?: null;
    }
}