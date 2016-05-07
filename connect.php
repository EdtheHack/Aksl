<?php
class BrainDB {
    private static $db_conn = NULL;

    private function __construct(){

    }

    public static function init(){
        if (!isset(self::$db_conn)){
            self::$db_conn = new PDO('mysql:host=localhost;dbname=Aksl', 'root');
        }
    }

    public static function getConnection(){
        if (isset(self::$db_conn)){
            return self::$db_conn;
        } else {
            return null;
        }
    }
}
?>