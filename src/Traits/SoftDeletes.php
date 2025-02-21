<?php namespace Kasperworks\Traits;

trait SoftDeletes
{
    /**
     * Modify the query to exclude soft-deleted records by default.
     *
     * @return \Kasperworks\QueryBuilder
     */
    public static function query(): \Kasperworks\QueryBuilder
    {
        return (new \Kasperworks\QueryBuilder(static::getTable()))
            ->where(static::getTable() . ".is_deleted_flag", "=", 0);
    }

    /**
     * Allow including soft-deleted records.
     *
     * @return \Kasperworks\QueryBuilder
     */
    public static function withTrashed(): \Kasperworks\QueryBuilder
    {
        return new \Kasperworks\QueryBuilder(static::getTable());
    }

    /**
     * Find a record that is not soft deleted.
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        return static::query()->where("id", "=", $id)->first();
    }

    /**
     * Find a record including soft-deleted ones.
     *
     * @param int $id
     * @return array|null
     */
    public static function findWithTrashed(int $id): ?array
    {
        return static::withTrashed()->where("id", "=", $id)->first();
    }

    /**
     * Soft delete a record.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        return static::withTrashed()->where("id", "=", $id)->update(["is_deleted_flag" => 1]);
    }

    /**
     * Restore a soft-deleted record.
     *
     * @param int $id
     * @return bool
     */
    public static function restore(int $id): bool
    {
        return static::withTrashed()->where("id", "=", $id)->update(["is_deleted_flag" => 0]);
    }

    /**
     * Permanently delete a record.
     *
     * @param int $id
     * @return bool
     */
    public static function forceDelete(int $id): bool
    {
        return static::withTrashed()->where("id", "=", $id)->delete();
    }
}
