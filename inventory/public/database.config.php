<?php
$SERVER_NAME = getenv('SERVER_NAME') ?: getenv('MYSQLHOST') ?: 'localhost';
$USERNAME    = getenv('DB_USERNAME') ?: getenv('MYSQLUSER') ?: 'root';
$PASSWORD    = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
$DB_NAME     = getenv('DB_NAME')     ?: getenv('MYSQLDATABASE') ?: 'railway';
