<?php
$SERVER_NAME = getenv('DB_HOST') ?: 'localhost';
$USERNAME    = getenv('DB_USERNAME') ?: 'root';
$PASSWORD    = getenv('DB_PASSWORD') ?: '';
$DB_NAME     = getenv('DB_NAME') ?: 'railway';
