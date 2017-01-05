<?php

final class Driver_Mysqli
{
    private $config;
    private $curlink;
    private $version;
    private $inTransaction;

    public function __construct()
    {
        $this->config = Yaf_Application::app()->getConfig();
        $this->connect();
        $this->version = $this->version();
        $this->inTransaction = false;
        $this->autocommit(true);
    }

    public function connect()
    {
        $link = new mysqli();
        if (! $link->real_connect($this->config->db->host, $this->config->db->user, $this->config->db->pass, $this->config->db->db, 
            $this->config->db->port, null, MYSQLI_CLIENT_COMPRESS)) {
            $this->halt($link->connect_error, $link->connect_errno, json_encode($this->config->db));
        } else {
            $this->curlink = $link;
            $this->set_charset($this->config->db->charset);
        }
    }

    public function query($sql)
    {
        if (!$this->ping()) {
            $this->connect();
        }
        $this->escape_string($sql);
        if (($query = $this->curlink->query($sql)) === false) {
            if (in_array($this->errno(), array(2006, 2013))) {
                $this->connect();
                return $this->curlink->query($sql);
            } else {
                $this->halt($this->error(), $this->errno(), $sql);
            }
        }
        return $query;
    }

    public function fetch_all($query, $result_type = MYSQLI_ASSOC)
    {
        return $query->fetch_all($result_type);
    }

    public function fetch_array($query, $result_type = MYSQLI_ASSOC)
    {
        return $query->fetch_array($result_type);
    }

    public function fetch_assoc($query)
    {
        return $query->fetch_assoc();
    }

    public function fetch_row($query)
    {
        return $query->fetch_row();
    }

    public function free_result($query)
    {
        return $query->free();
    }

    public function num_rows($query)
    {
        return $query->num_rows;
    }

    public function affected_rows()
    {
        return $this->curlink->affected_rows;
    }
    
    public function autocommit($mode) {
        return $this->curlink->autocommit($mode);
    }
    
    public function beginTransaction() {
        if ($this->inTransaction) {
            return true;
        }
        $bool = $this->curlink->begin_transaction();
        $this->autocommit(false);
        $this->inTransaction = true;
        return $bool;
    }

    public function close()
    {
        return $this->curlink->close();
    }
    
    public function commit() {
        if (!$this->inTransaction) {
            throw new Yaf_Exception("sql commit error: commit not in transaction");
        }
        $bool = $this->curlink->commit();
        $this->autocommit(true);
        $this->inTransaction = false;
        return $bool;
    }
    
    public function errno() {
        return $this->curlink->errno;
    }
    
    public function error() {
        return $this->curlink->error;
    }
    
    public function version() {
        $version = $this->curlink->server_version;
        $mainVersion = $version / 10000;
        $minorVersion = $version / 100;
        $subVersion = $version - $mainVersion * 10000 - $minorVersion * 100;
        return "$mainVersion.$minorVersion.$subVersion";
    }
    
    public function compareVersion($version) {
        $versions = explode('.', trim($version));
        $version = $versions[0] * 10000 + $versions[1] * 100;
        if ($versions[2]) {
            $version += $versions[2];
        }
        return $this->curlink->server_version >= $version;
    }

    public function insert_id()
    {
        return ($id = $this->curlink->insert_id) > 0 ? $id : $this->fetch_row($this->query("SELECT last_insert_id()"))[0];
    }
    
    public function ping() {
        return $this->curlink->ping();
    }
    
    public function rollback() {
        if (!$this->inTransaction) {
            throw new Yaf_Exception("sql rollback error: rollback not in transaction");
        }
        $bool = $this->curlink->rollback();
        $this->autocommit(true);
        $this->inTransaction = false;
        return $bool;
    }

    public function escape_string($str)
    {
        return $this->curlink->real_escape_string($str);
    }
    
    public function set_charset($charset) {
        return $this->curlink->set_charset($charset);
    }
    
    public function halt($error, $errno, $sql) {
        $message = <<<MESSAGE
        sql error:
            $error
            $errno
            $sql
MESSAGE;
        throw new Yaf_Exception($message);
    }
}

