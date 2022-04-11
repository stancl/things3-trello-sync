<?php

namespace App\Trello;

use ArrayAccess;
use Stringable;

class APIResource implements ArrayAccess, Stringable
{
    /** Cache for user methods. */
    protected array $cache = [];

    public function __construct(
        protected array $apiData = []
    ) {}

    public function __get($name)
    {
        return $this->apiData[$name];
    }

    public function __set($name, $value)
    {
        $this->apiData[$name] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->apiData[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->apiData[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->apiData[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->apiData[$offset]);
    }

    public function toArray(): array
    {
        return $this->apiData;
    }

    public function __toString()
    {
        return $this->id;
    }
}
