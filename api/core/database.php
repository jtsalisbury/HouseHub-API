<?php
    class Database {
        public $link;

        public function __construct($host, $db, $user, $pass) {
            try {
                $this->link = new PDO("mysql:host=" . $host . ";dbname=" . $db, $user, $pass);
                $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("internal db error");
            }
        }

        public function getLink() {
            return $this->link;
        }

        public function __destruct() {
            $this->link = null;
        }
    }

?>