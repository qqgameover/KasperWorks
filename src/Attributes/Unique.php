<?php namespace Kasperworks\Attributes;

#[\Attribute]
class Unique
{
    public function __construct(public string $column) {}
}
