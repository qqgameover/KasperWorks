<?php namespace Kasperworks\Attributes;

#[\Attribute]
class ForeignKey
{
    public function __construct(
        public string $column,
        public string $referencedTable,
        public string $referencedColumn
    ) {}
}
