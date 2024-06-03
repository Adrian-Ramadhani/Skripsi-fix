<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../class/absensi.php';

$database = new Database();
$db = $database->getConnection();

$item = new Absensi($db);
$item->uid = isset($_GET['uid']) ? $_GET['uid'] : die('wrong structure!');
	
if($item->createData()){
	// create array
	$data_arr = array(
		"waktu" => $item->waktu,
		"nama" => $item->nama,
		"uid" => $item->uid,
		"status" =>  $item->status
	);
	http_response_code(200);
	echo json_encode($data_arr);
} else{
	http_response_code(404);
	echo json_encode("Failed!");
}
?>