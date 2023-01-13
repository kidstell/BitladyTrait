<?php

namespace Kidstell\Bitlady\Tests\Classes;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Kidstell\Bitlady\Tests\Classes\MigrateableInterface;

class LaravelApp{
    public function init($migrateableObjects=[])
    {
        $this->migrateableObjects = $migrateableObjects;
        $this->readEnvFile();
        $this->getDbConnection();
        $this->migrateTestModels();

        return $this;
    }

    public function migrateTestModels()
    {
        foreach ($this->migrateableObjects as $obj) {
            $o = "\\".$obj;
            $this->migrateForTest(new $obj);
        }

        return $this;
    }

    public function rollbackTestModels()
    {
        foreach ($this->migrateableObjects as $obj) {
            $o = "\\".$obj;
            $this->rollbackMigrationForTest(new $obj);
        }

        return $this;
    }

    public function getDbConnection()
    {
        $capsule = new Capsule;
        file_put_contents($_ENV['DB_DATABASE'], '');
        $capsule->addConnection([
            "driver" => $_ENV['DB_DRIVER'],
            "host" => $_ENV['DB_HOST']??'',
            "database" => $_ENV['DB_DATABASE'],
            "username" => $_ENV['DB_USER']??'',
            "password" => $_ENV['DB_PASS']??'',
            'prefix' => '',
            // 'foreign_key_constraints' => $_ENV['DB_FOREIGN_KEYS'],
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->capsule = $capsule;
        return $this;
    }

    public function readEnvFile()
    {
        $a = \Dotenv\Dotenv::class;
        $dotenv = Dotenv::createImmutable(realpath(__DIR__.'/../..'));
        $dotenv->safeLoad();
        $this->dotenv = $dotenv;
        // print_r($_ENV);
        return $this;
    }

    public function migrateForTest(MigrateableInterface $modelObject)
    {
        Capsule::schema()->dropIfExists($modelObject->getTable());
        $modelObject->dbUp();
        $modelObject->seed();
        return $this;
    }

    public function rollbackMigrationForTest(MigrateableInterface $modelObject)
    {
        $modelObject->dbDown();
        return $this;
    }

    public function __destruct()
    {
        // $this->rollbackTestModels();
    }
}
