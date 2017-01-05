<?php

class Db
{
    protected static $db;
    
    protected $table_pre;
    protected $table_name;
    protected $table;

    public function __construct() {
        $this->table_pre = '';
        $this->table_name = '';
        $this->table = '';
    }
    
    protected function __clone() {}
    
    public function __callStatic($name, $args) {
        // 避免在执行sql语句之前连接数据库，提高性能
        if (!empty(self::$db)) {
            return call_user_func_array(array(self::db(), $name), $args);
        } else {
            throw new Yaf_Exception("db static call error");
        }
    }
    
    protected static function db() {
        if (empty(self::$db)) {
            $config = Yaf_Application::app()->getConfig();
            $type = $config->db->type;
            $driver = $config->db->driver;
            if ($type == 'mysql') {
                if ($driver == 'pdo') {
                    self::$db = new Driver_Pdo();
                } else if ($driver == 'mysql') {
                    self::$db = new Driver_Mysql();
                } else {
                    self::$db = new Driver_Mysqli();
                }
            }
            return self::$db;
        }
    }

    public function table($table = '', $pre = true)
    {
        if (!empty($table)) {
            if ($pre) {
                $this->table = $this->quote_field($this->table_pre . $table);
            } else {
                $this->table = $this->quote_field($table);
            }
        } else {
            $this->table = $this->quote_field($this->table_pre . $this->table_name);
        }
        return $this->table;
    }
    
    public function tableName() {
        return $this->table_name;
    }

    public function insert($data, $replace = false)
    {
        $sql = $this->implode($data);
        $cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
        return $this->query("$cmd " . $this->table() . " SET $sql");
    }

    public function delete($condition)
    {
        if (empty($condition)) {
            // 不允许全表删除
            return false;
        } elseif (is_array($condition)) {
            if (count($condition) == 2 && !empty($condition['where']) && !empty($condition['arg'])) {
                $where = $this->format($condition['where'], $condition['arg']);
            } else {
                $where = $this->implode($condition, 'and');
            }
        } else {
            $where = $condition;
        }
        $sql = "DELETE FROM " . $this->table() . " WHERE $where";
        return $this->query($sql);
    }

    public function update($data, $condition, $low_priority = false)
    {
        $sql = $this->implode($data);
        if (empty($sql)) {
            return false;
        }
        $cmd = "UPDATE " . ($low_priority ? 'LOW_PRIORITY ' : '');
        if (empty($condition)) {
            // 不允许全表更新
            return false;
        } elseif (is_array($condition)) {
            $where = $this->implode($condition, 'and');
        } else {
            $where = $condition;
        }
        return $this->query("$cmd " . $this->table() . " SET $sql WHERE $where");
    }

    public function select($condition = '1', $fields = '*', $limit = '', $order = '')
    {
        if (empty($condition)) {
            // 允许全表查询
            $where = '1';
        } elseif (is_array($condition)) {
            $where = $this->implode($condition, 'and');
        } else {
            $where = $condition;
        }
        if (empty($fields)) {
            $columns = '*';
        } elseif (is_array($fields)) {
            $columns = $this->implode_field($fields);
        } else {
            $columns = $fields;
        }
        $table = $this->table();
        return $this->query("SELECT $columns FROM $table WHERE $where $order $limit");
    }
    
    public function fetch_all($condition = '1', $fields = '*', $limit = '', $order = '', $result_type = MYSQLI_ASSOC)
    {
        $query = $this->select($condition, $fields, $limit, $order);
        return self::db()->fetch_all($query, $result_type);
    }
    
    public function fetch_array($condition = '1', $fields = '*', $limit = '', $order = '', $result_type = MYSQLI_ASSOC)
    {
        $query = $this->select($condition, $fields, $limit, $order);
        return self::db()->fetch_array($query, $result_type);
    }
    
    public function fetch_assoc($condition = '1', $fields = '*', $limit = '', $order = '')
    {
        $query = $this->select($condition, $fields, $limit, $order);
        return self::db()->fetch_assoc($query);
    }
    
    public function fetch_row($condition = '1', $fields = '*', $limit = '', $order = '')
    {
        $query = $this->select($condition, $fields, $limit, $order);
        return self::db()->fetch_row($query);
    }

    public function query($sql, $arg = array())
    {
        if (!empty($arg)) {
            if (is_array($arg)) {
                $sql = $this->format($sql, $arg);
            }
        }

        self::checkquery($sql);

        $ret = self::db()->query($sql);
        $sql = trim($sql);
        $cmd = trim(strtoupper(substr($sql, 0, stripos($sql, ' '))));
        if ($cmd === 'SELECT') {

        } elseif ($cmd === 'UPDATE' || $cmd === 'DELETE') {
            $ret = self::db()->affected_rows();
        } elseif ($cmd === 'INSERT') {
            $ret = self::db()->insert_id();
        }
        return $ret;
    }

    // sql语句安全性检测
    public static function checkquery($sql)
    {
        return SqlSafeCheck::checkquery($sql);
    }

    // 实现escape_string功能的替代方法
    public function mysql_escape_mimic($inp)
    {
        if (is_array($inp)) {
            return array_map(__METHOD__, $inp);
        }
        if (!empty($inp) && is_string($inp)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
        }
        return $inp;
    }

    public function limit($limit, $start = 0)
    {
        $limit = intval($limit) > 0 ? intval($limit) : 0;
        $start = intval($start) > 0 ? intval($start) : 0;
        if ($limit > 0) {
            if ($start > 0) {
                return " limit $start, $limit";
            } else {
                return " limit $limit";
            }
        }
        return '';
    }

    public function order($field, $order = 'asc')
    {
        if (empty($field)) {
            return '';
        }
        $order = strtoupper($order) == 'asc' || empty($order) ? 'asc' : 'desc';
        return $this->quote_field($field) . ' ' . $order;
    }

    public function quote_value($str, $clean_array = false)
    {
        if (is_string($str)) {
            return '\'' . $this->mysql_escape_mimic($str) . '\'';
        }
        if (is_int($str) or is_float($str)) {
            return '\'' . $str . '\'';
        }
        if (is_array($str)) {
            // 将数组中的数组元素全部重置为空字符串
            if ($clean_array === false) {
                foreach ($str as &$v) {
                    $v = $this->quote_value($v, true);
                }
                return $str;
            } else {
                return '\'\'';
            }
        }
        if (is_bool($str)) {
            return $str ? '1' : '0';
        }
        return '\'\'';
    }

    public function quote_field($field)
    {
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                $field[$k] = $this->quote_field($v);
            }
        } else {
        	$field = trim($field);
            if (strpos($field, '`') !== false) {
                $field = str_replace('`', '', $field);
            }
            $field = '`' . $this->mysql_escape_mimic($field) . '`';
        }
        return $field;
    }

    public function field_value($field, $val, $glue = '=')
    {
        $field = $this->quote_field($field);

        $glue = trim($glue);
        if (is_array($val)) {
            $glue = $glue == 'notin' ? 'notin' : 'in';
        } else {
            if ($glue == 'in') {
                $glue = '=';
            }
        }

        switch ($glue) {
            case '=':
                return $field . $glue . $this->quote_value($val);
                break;
            case '-':
            case '+':
                return $field . '=' . $field . $glue . $this->quote_value(strval($val));
                break;
            case '|':
            case '&':
            case '^':
                return $field . '=' . $field . $glue . $this->quote_value($val);
                break;
            case '>':
            case '<':
            case '<>':
            case '<=':
            case '>=':
                return $field . $glue . $this->quote_value($val);
                break;

            case 'like':
                return $field . ' like ' . $this->quote_value($val);
                break;
            case 'regexp':
                return $field . ' regexp ' . $this->quote_value($val);
                break;
            case 'find':
                return 'find_in_set(' . $this->quote_value($val) . ',' . $field . ')';
                break;

            case 'in':
            case 'notin':
                $val = implode(',', $this->quote_value($val));
                return $field . ($glue == 'notin' ? ' not' : '') . ' in(' . $val . ')';
                break;

            default:
                throw new Yaf_Exception("sql glue error: $glue");
        }
    }

    public function implode($array, $glue = ',', $gum = '=')
    {
        $sql = '';
        $glue = trim($glue);
        foreach ($array as $k => $v) {
            $field_value = $this->field_value($k, $v, $gum);
            switch ($glue) {
                case ',':
                case 'and':
                case 'or':
                    $sql .= !empty($sql) ? " $glue " . $field_value : $field_value;
                    break;

                default:
                    throw new Yaf_Exception("sql glue error: $glue");
            }
        }
        return $sql;
    }
    
    public function implode_field($fields){
    	$sql = '';
    	$fields = $this->quote_field($fields);
    	return implode(',', $fields);
    }

    public function format($sql, $arg)
    {
        $count = substr_count($sql, '%');
        if (!$count) {
            return $sql;
        } elseif ($count > count($arg)) {
            throw new Yaf_Exception("sql args error: $sql");
        }

        $len = strlen($sql);
        $i = $find = 0;
        $ret = '';
        while ($i <= $len && $find < $count) {
            if ($sql{$i} == '%') {
                $next = $sql{$i + 1};
                if ($next == 't') {
                    $ret .= $this->table($arg[$find]);
                } elseif ($next == 's') {
                    $ret .= $this->quote_value(is_array($arg[$find]) ? serialize($arg[$find]) : strval($arg[$find]));
                } elseif ($next == 'f') {
                    $ret .= sprintf('%F', $arg[$find]);
                } elseif ($next == 'd') {
                    $ret .= intval($arg[$find]);
                } elseif ($next == 'i') {
                    $ret .= $arg[$find];
                } elseif ($next == 'n') {
                    if (!empty($arg[$find])) {
                        $ret .= is_array($arg[$find]) ? implode(',', $this->quote_value($arg[$find])) : $this->quote_value($arg[$find]);
                    } else {
                        $ret .= '0';
                    }
                } else {
                    $ret .= $this->quote_value($arg[$find]);
                }
                $i++;
                $find++;
            } else {
                $ret .= $sql{$i};
            }
            $i++;
        }
        if ($i < $len) {
            $ret .= substr($sql, $i);
        }
        return $ret;
    }
}

