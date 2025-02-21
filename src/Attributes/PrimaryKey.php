<?php namespace Kasperworks\Attributes;

#[\Attribute]
class PrimaryKey
{
    public function __construct(public string $column) {}
}
