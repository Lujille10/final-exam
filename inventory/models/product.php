<?php
// Mimics the "products" table in your database
class Product {
    public $id          = "";
    public $name        = "";
    public $description = "";
    public $quantity    = 0;
    public $price       = 0.0;
    public $category    = "";
    public $created_at  = "";

    function __construct($name, $description, $quantity, $price, $category, $created_at = "", $id = "") {
        $this->id          = $id;
        $this->name        = $name;
        $this->description = $description;
        $this->quantity    = $quantity;
        $this->price       = $price;
        $this->category    = $category;
        $this->created_at  = $created_at;
    }
}
