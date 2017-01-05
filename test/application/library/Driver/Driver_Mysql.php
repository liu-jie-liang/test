<?php

/**
 * PHP版本小于5.3.0时使用
 * @author liujieliang
 *
 */

final class Driver_Mysql
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
        if (! ($link = mysql_connect($this->config->db->host . ':' . $this->config->db->port, $this->config->db->user, 
            $this->config->db->pass, 1, MYSQL_CLIENT_COMPRESS))) {
            $this->halt(mysql_error(), mysql_errno(), json_encode($this->config->db));
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
        if (($query = mysql_query($sql, $this->curlink)) === false) {
            if (in_array($this->errno(), array(2006, 2013))) {
                $this->connect();
                return mysql_query($sql, $this->curlink);
            } else {
                $this->halt($this->error(), $this->errno(), $sql);
            }
        }
        return $query;
    }
    
    public function fetch_all($query, $result_type = MYSQLI_ASSOC)
    {
        $data = array();
        while (($row = $this->fetch_array($query, $result_type)) !== false) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function fetch_array($query, $result_type = MYSQLI_ASSOC)
    {
        return mysql_fetch_array($query, $result_type);
    }
    
    public function fetch_assoc($query)
    {
        return mysql_fetch_assoc($query);
    }
    
    public function fetch_row($query)
    {
        return mysql_fetch_row($query);
    }
    
    public function free_result($query)
    {
        return mysql_free_result($query);
    }
    
    public function num_rows($query)
    {
        return mysql_num_rows($query);
    }
    
    public function affected_rows()
    {
        return mysql_affected_rows($this->curlink);
    }
    
    public function autocommit($mode) {
        if ($mode) {
            $mode = 1;
        } else {
            $mode = 0;
        }
        return $this->query("set autocommit=$mode");
    }
    
    public function beginTransaction() {
        if ($this->inTransaction) {
            return true;
        }
        $bool = $this->query("start transaction");
        $this->autocommit(false);
        $this->inTransaction = true;
        return $bool;
    }
    
    public function close()
    {
        return mysql_close($this->curlink);
    }
    
    public function commit() {
        if (!$this->inTransaction) {
            throw new Yaf_Exception("sql commit error: commit not in transaction");
        }
        $bool = $this->query("commit");
        $this->autocommit(true);
        $this->inTransaction = false;
        return $bool;
    }
    
    public function errno() {
        return mysql_errno($this->curlink);
    }
    
    public function error() {
        return mysql_error($this->curlink);
    }
    
    public function server_version() {
        $versions = explode('.', preg_match('/[1-9]\.[0-9]\.[0-9]{1,2}/', mysql_get_server_info($this->curlink)));
        $version = $versions[0] * 10000 + $versions[1] * 100;
        if ($versions[2]) {
            $version += $versions[2];
        }
        return $version;
    }
    
    public function version() {
        $version = $this->server_version();
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
        return $this->server_version() >= $version;
    }
    
    public function insert_id()
    {
        return ($id = mysql_insert_id($this->curlink)) > 0 ? $id : $this->fetch_row($this->query("SELECT last_insert_id()"))[0];
    }
    
    public function ping() {
        return mysql_ping($this->curlink);
    }
    
    public function rollback() {
        if (!$this->inTransaction) {
            throw new Yaf_Exception("sql rollback error: rollback not in transaction");
        }
        $bool = $this->query("rollback");
        $this->autocommit(true);
        $this->inTransaction = false;
        return $bool;
    }
    
    public function escape_string($str)
    {
        return mysql_real_escape_string($str, $this->curlink);
    }
    
    public function set_charset($charset) {
        return mysql_set_charset($charset, $this->curlink);
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

