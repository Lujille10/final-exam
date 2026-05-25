<?php
// Mimics the "accounts" table in your database
class Account {
    public $id       = "";
    public $username = "";
    public $password = "";
    public $created_at = "";

    function __construct($username, $password, $created_at = "", $id = "") {
        $this->id         = $id;
        $this->username   = $username;
        $this->password   = $password;
        $this->created_at = $created_at;
    }
}
