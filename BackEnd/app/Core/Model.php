<?php

namespace Core;

class Model
{
    protected $db;

    public function __construct()
    {
        $this->db = require __DIR__ . '/../../config/database.php'; // Gọi file database
    }
}
