<?php namespace Kasperworks;

use ReflectionClass;


/**
 * Relationship types enum
 */
enum RelationType
{
    case HAS_MANY;
    case BELONGS_TO;
    case HAS_ONE;
    case MANY_TO_MANY;
}

/**
 * Relationship loader class
 */
class RelationshipLoader
{
    public function __construct(
        protected string $relatedClass,
        protected RelationType $type,
        protected string $foreignKey,
        protected string $localKey,
        protected Model $parent
    ) {}

    public function get(): mixed
    {
        $reflection = new ReflectionClass($this->parent);
        $localKeyProp = $reflection->getProperty($this->localKey);
        $localKeyProp->setAccessible(true);
        $localKeyValue = $localKeyProp->getValue($this->parent);

        return match($this->type) {
            RelationType::HAS_MANY => $this->relatedClass::query()
                ->where($this->foreignKey, '=', $localKeyValue)
                ->get(),
            RelationType::BELONGS_TO => $this->relatedClass::find($localKeyValue),
            default => null
        };
    }
}
