<?php

require_once __DIR__ . '/Database.php';

class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    protected function currentTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }
}
