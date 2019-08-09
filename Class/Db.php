<?php

class Db {

    private static $instance;

    protected function __construct() {
        
    }

    protected function __clone() {
        
    }

    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function __callStatic($method, $args) {
        echo "__callStatic, method $method, args $args";
    }

    public static function getPdo() {
        if (empty(self::$instance)) {
            $opt = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            try {
                self::$instance = new PDO("mysql:host=localhost;dbname=test_samson; charset=UTF8", 'test_samson', '123456', $opt);
            } catch (PDOException $e) {
                die('Ошибка подключения к БД: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function preExec(string $sql, array $arr = []) {
        if (empty(self::$instance)) {
            self::getPdo();
        }
        $stmt = self::$instance->prepare($sql);
        $stmt->execute($arr);
        return $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function someBusinessLogic() {
        
    }

}

//$Pdo=Db::getPdo();
//$res = Db::preExec($sql);
