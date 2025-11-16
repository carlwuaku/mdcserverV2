<?php
namespace App\Helpers\Types;

class KeyValueType
{
    public string $key;
    public string $value;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value
        ];
    }

    public static function fromArray(array $data): KeyValueType
    {
        return new KeyValueType($data['key'], $data['value']);
    }
}