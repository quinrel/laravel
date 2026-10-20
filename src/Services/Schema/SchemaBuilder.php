<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Schema;

/**
 * A fluent builder for generic Schema.org JSON-LD arrays.
 */
class SchemaBuilder
{
    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    public function __construct(string $type)
    {
        $this->attributes['@context'] = 'https://schema.org';
        $this->attributes['@type'] = $type;
    }

    public static function make(string $type): self
    {
        return new self($type);
    }

    public function set(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Set the ID of the schema.
     */
    public function id(string $id): self
    {
        $this->attributes['@id'] = $id;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        if (count($arguments) > 0) {
            $this->attributes[$name] = $arguments[0];
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function toJson(): string
    {
        return json_encode($this->attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public function toHtml(): string
    {
        return '<script type="application/ld+json">'.$this->toJson().'</script>';
    }
}
