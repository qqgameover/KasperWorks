<?php namespace Kasperworks;

use PDO;
use Kasperworks\Poworm;

class QueryBuilder
{
    protected static ?PDO $db = null;
    protected string $table;
    protected array $selectColumns = ["*"];
    protected array $whereClauses = [];
    protected array $bindings = [];
    protected ?string $orderBy = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected ?string $groupBy = null;
    protected array $joins = [];

    public function __construct(string $table)
    {
        if (self::$db == null) {
            self::$db = Poworm::getInstance()->db;
        }
        $this->table = $table;
    }

    public function select(array $columns = ["*"]): self
    {
        $this->selectColumns = $columns;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {

        $allowedOperators = ["=", "!=", ">", "<", ">=", "<=", "LIKE", "IN", "NOT IN"];
        if(!in_array($operator, $allowedOperators, true)){
            throw new \Exception("Invalid operator in WHERE clause: $operator");
        }

        $this->whereClauses[] = sprintf("%s %s ?", $column, $operator);
        $this->bindings[] = $value;
        return $this;
    }

    public function whereRaw(string $whereClause, array $bindings = []): self
    {
        $this->whereClauses[] = $whereClause;
        $this->bindings = [...$this->bindings, ...$bindings]; // Optimized merge
        return $this;
    }

    public function orderBy(string $column, string $direction = "ASC"): self
    {
        $this->orderBy = sprintf("%s %s", $column, strtoupper($direction));
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function innerJoin(string $table, string $on): self
    {
        $this->joins[] = "INNER JOIN $table ON $on";
        return $this;
    }

    public function leftJoin(string $table, string $on): self
    {
        $this->joins[] = "LEFT JOIN $table ON $on";
        return $this;
    }

    public function get(): array
    {
        $sql = sprintf(
            "SELECT %s FROM %s %s %s %s %s %s %s",
            implode(", ", $this->selectColumns),
            $this->table,
            implode(" ", $this->joins),
            !empty($this->whereClauses)
                ? "WHERE " . implode(" AND ", $this->whereClauses)
                : "",
            $this->orderBy ? "ORDER BY {$this->orderBy}" : "",
            $this->groupBy ? $this->groupBy : "",
            $this->limit ? "LIMIT {$this->limit}" : "",
            $this->offset ? "OFFSET {$this->offset}" : ""
        );

        $stmt = self::$db->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $this->limit(1);
        return $this->get()[0] ?? null;
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = rtrim(str_repeat("?, ", count($columns)), ", ");
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(", ", $columns),
            $placeholders
        );

        $stmt = self::$db->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) self::$db->lastInsertId();
    }

    public function batchInsert(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys(reset($data));
        $placeholders = rtrim(
            str_repeat(
                "(" . str_repeat("?, ", count($columns) - 1) . "?), ",
                count($data)
            ),
            ", "
        );
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $this->table,
            implode(", ", $columns),
            $placeholders
        );

        $stmt = self::$db->prepare($sql);
        $stmt->execute(array_merge(...array_map("array_values", $data)));

        return $stmt->rowCount() > 0;
    }

    public function update(array $data, array $where): bool
    {
        if (empty($where)) {
            throw new \Exception("Cannot update without a WHERE condition.");
        }

        $setClause = implode(", ", array_map(fn($col) => "$col = ?", array_keys($data)));

        $whereClauses = [];
        $bindings = array_values($data);

        if (count($where) !== 3) {
            throw new \Exception("Invalid WHERE condition format. Expected: ['column', 'operator', 'value']");
        }

        [$column, $operator, $value] = $where;
        $allowedOperators = ["=", "!=", ">", "<", ">=", "<=", "LIKE", "IN", "NOT IN"];
        if (!in_array($operator, $allowedOperators, true)) {
            throw new \Exception("Invalid operator in WHERE clause: $operator");
        }

        $whereClauses[] = "$column $operator ?";
        $bindings[] = $value;

        $whereClause = implode(" AND ", $whereClauses);

        $sql = sprintf("UPDATE %s SET %s WHERE %s", $this->table, $setClause, $whereClause);

        var_dump($sql);

        $stmt = self::$db->prepare($sql);
        return $stmt->execute($bindings);
    }

    public function pluck(string $column, ?string $key = null): array
    {
        $this->select([$column]);

        if ($key) {
            $this->selectColumns[] = $key;
        }

        $results = $this->get();

        if ($key) {
            return array_column($results, $column, $key);
        }

        return array_column($results, $column);
    }

    public function groupBy(string ...$columns): self
    {
        $this->groupBy = "GROUP BY " . implode(", ", $columns);
        return $this;
    }


    public function delete(): bool
    {
        if (empty($this->whereClauses)) {
            throw new \Exception("Delete operation requires at least one WHERE condition to prevent mass deletion.");
        }

        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $this->table,
            implode(" AND ", $this->whereClauses)
        );

        $stmt = self::$db->prepare($sql);
        return $stmt->execute($this->bindings);
    }


}
