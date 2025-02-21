<?php namespace Kasperworks\Attributes;

#[\Attribute]
class Index
{
    public function __construct(public string $column) {}
}
