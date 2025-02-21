<?php
namespace Kasperworks;

use ReflectionClass;
use ReflectionProperty;
use ReflectionAttribute;
use Kasperworks\Exceptions\RowNotFoundException;
use Kasperworks\Exceptions\ValidationException;
use Kasperworks\Attributes\{PrimaryKey, ForeignKey, Required, Unique, Index};

function class_basename(string $class): string
{
    return basename(str_replace('\\', '/', $class));

}

abstract class Model
{
    protected static string $table;
    protected static array $protected = ["id", "created_at", "updated_at"];
    protected QueryBuilder $query;
    protected array $relations = [];

    public function __construct()
    {
        $this->query = new QueryBuilder(static::$table);
    }

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::$table);
    }

    public static function find(int $id): ?static
    {
        $primaryKey = static::$primaryKey ?? 'id';
        $record = static::query()->where($primaryKey, "=", $id)->first();
        return $record ? static::hydrate($record) : null;
    }

    /**
     * Define a hasMany relationship
     */
    protected function hasMany(string $relatedClass, ?string $foreignKey = null): RelationshipLoader
    {
        $foreignKey ??= strtolower(class_basename(static::class)) . '_id';
        return new RelationshipLoader(
            $relatedClass,
            RelationType::HAS_MANY,
            $foreignKey,
            'id',
            $this
        );
    }

    /**
     * Define a belongsTo relationship
     */
    protected function belongsTo(string $relatedClass, ?string $foreignKey = null): RelationshipLoader
    {
        $foreignKey ??= strtolower(class_basename($relatedClass)) . '_id';
        return new RelationshipLoader(
            $relatedClass,
            RelationType::BELONGS_TO,
            $foreignKey,
            'id',
            $this
        );
    }

    /**
     * Load a relationship
     */
    public function load(string $relation): static
    {
        if (method_exists($this, $relation)) {
            $this->relations[$relation] = $this->$relation()->get();
        }
        return $this;
    }

    /**
     * Get loaded relation
     */
    public function getRelation(string $relation): mixed
    {
        return $this->relations[$relation] ?? null;
    }

    /**
     * Create a new record with validation
     *
     * @param array<string, mixed> $data
     * @return static
     * @throws ValidationException
     */
    public static function create(array $data): static
    {
        static::validate($data);

        if (!empty(array_intersect(array_keys($data), static::$protected))) {
            throw new \Exception("Cannot create protected fields");
        }

        $id = static::query()->insert($data);
        return static::find($id);
    }

    /**
    * Update the model instance with new data
    * @param array<string, mixed> $data
    * @return static
    * @throws ValidationException
    * @throws \Exception
    * Does not update the object in place, but returns a new instance with updated data
    */
    public function update(array $data): static
    {
        $primaryKey = static::$primaryKey ?? 'id';

        // Ensure the object has a primary key set
        if (!isset($this->$primaryKey)) {
            throw new \Exception("Cannot update: No primary key set on object");
        }

        // Get the current object data as an array
        $existingData = get_object_vars($this);

        // Merge the existing data with the new data (new data overrides old values)
        $mergedData = array_merge($existingData, $data);

        // Validate the merged data (this prevents validation failures due to missing fields)
        static::validate($mergedData);

        // Prevent updates to protected fields
        if (!empty(array_intersect(array_keys($data), static::$protected))) {
            throw new \Exception("Cannot update protected fields");
        }

        static::query()->update($data, [$primaryKey, "=", $this->$primaryKey]);

        // Refresh the object’s data
        $updated = static::find($this->$primaryKey);
        foreach ($updated as $key => $value) {
            $this->$key = $value;
        }

        return $updated;
    }

    /**
    * Static version: Update records in the database without needing an instance.
    * @param array<string, mixed> $data  The data to update.
    * @param array<string, mixed> $where Conditions for updating records.
    * @return bool Returns true if at least one record was updated.
    * @throws \Exception
    */
    public static function updateBy(array $data, array $where): bool
    {
        if (empty($where)) {
            throw new \Exception("Cannot update without a where condition.");
        }

        return static::query()->update($data, $where);
    }


    /**
     * Delete the model instance from the database
     */
    public function delete(): bool {
        $primaryKey = static::$primaryKey ?? 'id';
        return static::query()->where($primaryKey, "=", $this->$primaryKey)->delete();
    }


    /**
     * Validate data against model attributes
     *
     * @throws ValidationException
     */
    protected static function validate(array $data): void
    {
        $reflection = new ReflectionClass(static::class);
        $primaryKey = static::$primaryKey ?? 'id';
        $errors = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();

            // Skip protected fields
            if (in_array($propertyName, static::$protected)) {
                continue;
            }

            // Check Required attribute
            $required = $property->getAttributes(Required::class);
            if (!empty($required) && !isset($data[$propertyName])) {
                $errors[$propertyName][] = "Field is required";
            }

            // Check Unique attribute
            $unique = $property->getAttributes(Unique::class);
            if (!empty($unique) && isset($data[$propertyName])) {
                $exists = static::query()
                    ->where($propertyName, '=', $data[$propertyName])
                    ->where($primaryKey, '!=', $data[$primaryKey] ?? null)
                    ->first();
                if ($exists) {
                    $errors[$propertyName][] = "Value must be unique";
                }
            }

            // Validate foreign keys
            $foreignKey = $property->getAttributes(ForeignKey::class);
            if (!empty($foreignKey) && isset($data[$propertyName])) {
                $attr = $foreignKey[0]->newInstance();
                $relatedClass = $attr->referencedTable;
                $exists = $relatedClass::find($data[$propertyName]);
                if (!$exists) {
                    $errors[$propertyName][] = "Related record does not exist";
                }
            }

            // Type validation
            if (isset($data[$propertyName])) {
                $type = $property->getType();
                if ($type && !static::validateType($data[$propertyName], $type->getName())) {
                    $errors[$propertyName][] = "Invalid type, expected {$type->getName()}";
                }
            }
        }

        if (!empty($errors)) {
            $error_str = "";
            array_walk($errors, function ($value, $key) use (&$error_str) {
                $error_str .= "$key: " . implode(", ", $value) . "\n";
            });
            throw new ValidationException("Validation failed with: $error_str", $errors);
        }
    }

    /**
     * Validate value type
     */
    protected static function validateType(mixed $value, string $type): bool
    {
        return match($type) {
            'int' => is_numeric($value),
            'float' => is_numeric($value),
            'string' => is_string($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            default => true
        };
    }

    /**
     * Hydrate a database record into a model instance with attributes
     */
    protected static function hydrate(array $data): static
    {
        $instance = new static();
        $reflection = new ReflectionClass($instance);

        foreach ($data as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);

                // Handle primary key attribute
                $primaryKey = $property->getAttributes(PrimaryKey::class);
                if (!empty($primaryKey)) {
                    $value = (int) $value;
                }

                $property->setValue($instance, static::castValue($property, $value));
            }
        }

        return $instance;
    }


    /**
    * Cast property value to the correct type if possible
    */
    protected static function castValue(ReflectionProperty $property, mixed $value): mixed
    {
        $type = $property->getType();

        if (!$type || !$type->isBuiltin()) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'array' => is_array($value) ? $value : json_decode($value, true),
            default => $value
        };
    }


    /**
    * Convert the model instance to an associative array.
    *
    * @return array<string, mixed>
    */
    public function toArray(): array
    {
        $array = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $property) {
            $propertyName = $property->getName();

            // Skip protected attributes
            if (in_array($propertyName, static::$protected)) {
                continue;
            }

            // Make private/protected properties accessible
            $property->setAccessible(true);
            $array[$propertyName] = $property->getValue($this);
        }

        return $array;
    }
}
