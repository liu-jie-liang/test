<?php

class SqlSafeCheck
{

    protected static $checkcmd = array('SEL' => 1, 'UPD' => 1, 'INS' => 1, 'REP' => 1, 'DEL' => 1);
    protected static $config = array(
    	'dfunction' => array('load_file','hex','substring','if','ord','char'),
//     	'daction' => array('@','intooutfile','intodumpfile','unionselect','(select', 'unionall', 'uniondistinct'),
    	'daction' => array('@'),
    	'dnote' => array('/*','*/','#','--','"'),
    	'dlikehex' => 1,
    	'afullnote' => 0,
	);

    public static function checkquery($sql)
    {
        if (Yaf_Application::app()->getConfig()->db->sql_safe_check) {
            $check = 1;
            $cmd = strtoupper(substr(trim($sql), 0, 3));
            if (isset(self::$checkcmd[$cmd])) {
                $check = self::_safe_check($sql);
            } elseif (substr($cmd, 0, 2) === '/*') {
                $check = -1;
            }
            if ($check < 1) {
                throw new Yaf_Exception("sql safe check error: $sql");
            }
        }
        return true;
    }

    private static function _safe_check($sql)
    {
        $sql = str_replace(array('\\\\', '\\\'', '\\"', '\'\''), '', $sql);
        $mark = $clean = '';
        if (strpos($sql, '/') === false && strpos($sql, '#') === false && strpos($sql, '-- ') === false && strpos($sql, '@') === false && strpos($sql, '`') === false) {
            $clean = preg_replace("/'(.+?)'/s", '', $sql);
        } else {
            $len = strlen($sql);
            $mark = $clean = '';
            for ($i = 0; $i < $len; $i++) {
                $str = $sql[$i];
                switch ($str) {
                    case '`':
                        if (!$mark) {
                            $mark = '`';
                            $clean .= $str;
                        } elseif ($mark == '`') {
                            $mark = '';
                        }
                        break;
                    case '\'':
                        if (!$mark) {
                            $mark = '\'';
                            $clean .= $str;
                        } elseif ($mark == '\'') {
                            $mark = '';
                        }
                        break;
                    case '/':
                        if (empty($mark) && $sql[$i + 1] == '*') {
                            $mark = '/*';
                            $clean .= $mark;
                            $i++;
                        } elseif ($mark == '/*' && $sql[$i - 1] == '*') {
                            $mark = '';
                            $clean .= '*';
                        }
                        break;
                    case '#':
                        if (empty($mark)) {
                            $mark = $str;
                            $clean .= $str;
                        }
                        break;
                    case "\n":
                        if ($mark == '#' || $mark == '--') {
                            $mark = '';
                        }
                        break;
                    case '-':
                        if (empty($mark) && substr($sql, $i, 3) == '-- ') {
                            $mark = '-- ';
                            $clean .= $mark;
                        }
                        break;

                    default:
                        break;
                }
                $clean .= $mark ? '' : $str;
            }
        }

        if (strpos($clean, '@') !== false) {
            return '-3';
        }

        $clean = preg_replace("/[^a-z0-9_\-\(\)#\*\/\"]+/is", "", strtolower($clean));

        if (self::$config['afullnote']) {
            $clean = str_replace('/**/', '', $clean);
        }

        if (is_array(self::$config['dfunction'])) {
            foreach (self::$config['dfunction'] as $fun) {
                if (strpos($clean, $fun . '(') !== false)
                    return '-1';
            }
        }

        if (is_array(self::$config['daction'])) {
            foreach (self::$config['daction'] as $action) {
                if (strpos($clean, $action) !== false)
                    return '-3';
            }
        }

        if (self::$config['dlikehex'] && strpos($clean, 'like0x')) {
            return '-2';
        }

        if (is_array(self::$config['dnote'])) {
            foreach (self::$config['dnote'] as $note) {
                if (strpos($clean, $note) !== false)
                    return '-4';
            }
        }

        return 1;
    }
}

