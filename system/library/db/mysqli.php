<?php

namespace DB;

final class MySQLi {
    private $connection;

    public function __construct($hostname, $username, $password, $database, $port = '3306') {
        $this->connection = new \mysqli($hostname, $username, $password, $database, $port);

        if ($this->connection->connect_errno) {
            throw new \Exception('Error: ' . $this->connection->connect_errno . '<br />Error No: ' . $this->connection->errno);
        }

        $this->connection->set_charset("utf8");
        $this->connection->query("SET SQL_MODE = ''");
    }

    public function query($sql) {

        if(\Config::get('debug.sql')) {
            $start_time = microtime(true);

            $query = $this->connection->query($sql);

            $msec = number_format(microtime(true) - $start_time, 4, '.', ',') . " msec";

            $output = date("Y-m-d h:i:s"). " \t";
            $output .= $msec . " \t";
            $output .= debug_backtrace()[1]['file'].":".debug_backtrace()[1]['line'] . " \t";

            $output .= trim(preg_replace('/\s\s+/', ' ', $sql)) . " \n";

            if(!is_dir(DIR_LOGS)) {
                mkdir(DIR_LOGS, \Config::get('directory_permission', 0775), true);
            }

            if (!file_exists(DIR_LOGS . 'mysql_queries.txt')) {
                touch(DIR_LOGS . 'mysql_queries.txt');
            }

            $file = fopen(DIR_LOGS . 'mysql_queries.txt', 'a');

            fwrite($file, $output);

            fclose($file);

        } else {
            $query = $this->connection->query($sql);
        }

        if (!$this->connection->errno) {
            if ($query instanceof \mysqli_result) {
                $result = new \stdClass();
                $result->num_rows = $query->num_rows;
                $result->rows = [];

                while ($row = $query->fetch_assoc()) {
					$result->rows[] = $row;
                }

                $result->row = isset($result->rows[0]) ? $result->rows[0] : [];

                $query->close();

                return $result;
            } else {
                return true;
            }
        } else {
            throw new \Exception('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
        }
    }

    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }

    public function countAffected() {
        return $this->connection->affected_rows;
    }

    public function getLastId() {
        return $this->connection->insert_id;
    }

    public function connected() {
        return $this->connection->ping();
    }

    public function __destruct() {
        $this->connection->close();
    }

}