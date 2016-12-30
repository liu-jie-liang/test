<?php

// $redis = new Redis();
// $redis->connect('127.0.0.1', 6379);
// $key = 'a';
// $redis->incr($key);
// $tmsKey = $key . $redis->get($key);
// $redis->expire($tmsKey, 3600);
// echo $tmsKey;


$dsn = 'mysql:host=192.168.60.59;port=5200;dbname=lsh_tms_66;charset=utf8';
$user = 'root';
$passwd = '172008jie';
$options = [
	PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8',
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

$pdo = null;
$stmt = null;

try {
	$pdo = new pdo($dsn, $user, $passwd, $options);

} catch (PDOException $pe) {
	echo $pe->getMessage();
}

$stmt = $pdo->prepare("select order_id,warehouse_id,`status`,FROM_UNIXTIME(ordered_at, '%Y-%m-%d %h:%i:%s'),container,remote_flag,show_flag,time_area_id from order_head where warehouse_id='DC10' and remote_flag=1 order by ordered_at desc");
$stmt->execute();
$remoteOrderList = $stmt->fetchAll();
$remoteOrderIdList = array_column($remoteOrderList, 'order_id');

$stmt = $pdo->prepare("select order_id,warehouse_id,`status`,FROM_UNIXTIME(ordered_at, '%Y-%m-%d %h:%i:%s'),container,remote_flag,show_flag,time_area_id from order_head where warehouse_id='DC10' and show_flag=0 and remote_flag=1 order by ordered_at desc");
$stmt->execute();
$notShowOrderList = $stmt->fetchAll();
$notShowOrderIdList = array_column($notShowOrderList, 'order_id');

$stmt = $pdo->prepare("select * from order_head where remote_flag=1 and `status`=1");
$stmt->execute();
$remoteOrderList2 = $stmt->fetchAll();
foreach ($remoteOrderList2 as $index => $order) {
	$orderId = $order['order_id'];
	$stmt = $pdo->prepare("select * from order_wave_detail where `status`=1 and order_id='$orderId'");
	$stmt->execute();
	$orderWaveDetail = $stmt->fetchAll();
	if (!empty($orderWaveDetail)) {
		if ($order['show_flag'] != 0) {
			echo "submitted remote order show_flag is not 0, error, order_id: $orderId\r\n";
		}
		//echo "submitted remote : $orderId\r\n";
		//$stmt = $pdo->prepare("update order_head set show_flag=0 where order_id='$orderId'");
		//$stmt->execute();
	} else {
		if ($order['show_flag'] != 1) {
                        echo "unsubmitted remote order show_flag is not 1, error, order_id: $orderId\r\n";
                }
		//echo "unsubmitted remote : $orderId\r\n";
		//$stmt = $pdo->prepare("update order_head set show_flag=0 where order_id='$orderId'");
		//$stmt->execute();
	}
}

$redis = new Redis();
$redis->connect('192.168.60.59', 6381);

$warehouseId = 'DC10';
$newestPlanKey = 'tms:newestwaveplan:info:$warehouseId';

$planId = $redis->get($newestPlanKey);
$wavePlanKey = "tms:waveplan:$planId";
$waveNotPlanKey = "tms:wavenotplan:$planId";

$waveNotPlanOrderIds = $redis->zrange($waveNotPlanKey, 0, -1);
foreach ($waveNotPlanOrderIds as $index => $waveOrderId) {
	if (in_array($waveOrderId, $remoteOrderIdList)) {
		//echo "not plan and remote : $waveOrderId\r\n";
	}
	if (in_array($waveOrderId, $notShowOrderIdList)) {
		//echo "not plan and not show : $waveOrderId\r\n";
	}
}

$waveIds = $redis->zrange($wavePlanKey, 0, -1);

foreach ($waveIds as $index => $waveId) {
	$key = "tms:wave:$waveId";
	$tmsWaveOrderList = $redis->zrange($key, 0, -1);
	foreach ($tmsWaveOrderList as $key => $waveOrderId) {
		if (in_array($waveOrderId, $remoteOrderIdList)) {
			//echo "plan and remote : $waveOrderId\r\n";
		}
		if (in_array($waveOrderId, $notShowOrderIdList)) {
			//echo "plan and not show : $waveOrderId\r\n";
		}
	}
}
