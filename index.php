<?php

$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=sysbench;charset=utf8';
$user = 'root';
$passwd = '172008jie';
$options = [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8;',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

$pdo = null;
$stmt = null;

try {
        $pdo = new pdo($dsn, $user, $passwd, $options);

} catch (PDOException $pe) {
	throw $pe;
}

$pdo->beginTransaction();
try {
	$stmt = $pdo->prepare("select * from sbtest where id = 1 for update");
	$stmt->execute();
	$result = $stmt->fetchAll();

} catch (Exception $e) {
	$pdo->rollBack();
	throw $e;
}
$pdo->commit();

echo json_encode($result);
