<?php

    class Database {
        private $host = "";
        private $db   = "";
        private $user = "";
        private $pass = "";

        public $link;
        

        public function __construct() {
            try {
                $this->link = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db, $this->user, $this->pass);
                $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
                return $this->link;

            } catch (PDOException $e) {
                return false;
            }
        }

        public function __destruct() {
            $this->$link = null;
        }
    }

?>