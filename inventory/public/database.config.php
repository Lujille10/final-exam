<?php
$SERVER_NAME = getenv('SERVER_NAME') ?: 'localhost';
$USERNAME    = getenv('DB_USERNAME') ?: 'root';   // XAMPP default is root
$PASSWORD    = getenv('DB_PASSWORD') ?: '';        // XAMPP default is empty
$DB_NAME     = getenv('DB_NAME')     ?: 'inventory_db';
