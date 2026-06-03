<?php
    class Database
    {
        private static ?PDO $connection = null;

        public static function connect(): PDO
        {
            if (self::$connection === null) {
                $config = require __DIR__ . '/../../mate_config/database.php';

                $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";

                try {
                    self::$connection = new PDO($dsn, $config['username'], $config['password']);
                    self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    die('Database connection failed.');
                }
            }

            return self::$connection;
        }
    }