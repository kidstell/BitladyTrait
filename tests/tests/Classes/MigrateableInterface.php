<?php
namespace Kidstell\Bitlady\Tests\Classes;


interface MigrateableInterface{

    public function dbUp();
    public function dbDown();
    public function seed();
    public function getTable();
}