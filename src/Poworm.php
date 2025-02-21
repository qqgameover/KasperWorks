<?php namespace KasperWorks;

use \PDO;
use \PDOException;
use Dotenv\Dotenv;

/**
* Poworm class
* @internal
* @package KasperWorks
* @version 1.0.0
* Responsible for creating a PDO instance and returning it as a singleton.
*/
class Poworm
{
    public readonly \PDO $db;
    protected static ?self $instance = null;

    private function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            // Load .env file
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();

            // Retrieve environment variables
            $server = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'test';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host=$server;dbname=$dbname;charset=$charset";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset",
            ];

            try {
                $pdo = new \PDO($dsn, $username, $password, $options);
                self::$instance = new self($pdo);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
