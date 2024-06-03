<?php
date_default_timezone_set('Asia/Jakarta');
class Absensi{
	// Connection
	private $conn;

	// Table
	private $db_table = "data_absen";
	private $db_table1 = "data_pengurus";
	private $db_table2 = "data_invalid";

	// Columns
	public $id;
	public $tanggal;
	public $waktu;
	public $uid;
	public $status;
	public $last_status;
	public $nama;

	// Db connection
	public function __construct($db){
		$this->conn = $db;
	}

	// CREATE
	public function createData(){
	//1. Cek user terdaftar
		$sqlQuery = "SELECT * FROM ". $this->db_table1 ." WHERE uid = :uid LIMIT 0,1";
		$stmt = $this->conn->prepare($sqlQuery);
		$stmt->bindParam(":uid", $this->uid);
		$stmt->execute();
		if($stmt->errorCode() == 0) {
			while(($dataRow = $stmt->fetch(PDO::FETCH_ASSOC)) != false) {
				$this->nama = $dataRow['nama'];
			}
		} else {
			$errors = $stmt->errorInfo();
			echo($errors[2]);
		}
		$itemCount = $stmt->rowCount();
		
		if($itemCount > 0){
			//UID terdaftar -> cek status terakhir
			$sqlQuery = "SELECT data_absen.id, data_absen.uid, data_absen.status, data_pengurus.nama 
						FROM ". $this->db_table .", ". $this->db_table1 ."
						WHERE data_absen.id = (SELECT MAX(data_absen.id) 
						FROM ". $this->db_table ." WHERE data_absen.uid = :uid) 
						AND data_pengurus.uid= :uid";
			$stmt = $this->conn->prepare($sqlQuery);
			$stmt->bindParam(":uid", $this->uid);
			$stmt->execute();
			$itemCount = $stmt->rowCount();
			if($itemCount > 0){
				//error handling
				if($stmt->errorCode() == 0) {
					while(($dataRow = $stmt->fetch(PDO::FETCH_ASSOC)) != false) {
						$this->last_status = $dataRow['status'];
						$this->nama = $dataRow['nama'];
						//echo($this->last_status);
					}
				} else {
					$errors = $stmt->errorInfo();
					echo($errors[2]);
				}
			}else{
				$this->last_status ="OUT";
			}
			
			//set status
			if ($this->last_status == "IN"){
				$this->status = "OUT";
			}else{
				$this->status= "IN";
			}
			//Insert Data to data_absen	
			$sqlQuery = "INSERT INTO ". $this->db_table ."
					SET	waktu = :waktu, uid = :uid, status = :now_status";
						
			$this->waktu = date("H:i:s");
			
			$stmt = $this->conn->prepare($sqlQuery);
		
			// sanitize
			$this->uid=htmlspecialchars(strip_tags($this->uid));
		
			// bind data
			$stmt->bindParam(":uid", $this->uid);
			$stmt->bindParam(":now_status", $this->status);
			$stmt->bindParam(":waktu", $this->waktu);
		
			if($stmt->execute()){
			   return true;
			}
			return false;
		}
		else{
			//UID tidak terdaftar
			$this->status= "INVALID";
			$this->nama ="Invalid";
			
			//Insert Data to data_invalid	
			$sqlQuery = "INSERT INTO
						". $this->db_table2 ."
					SET
						waktu = :waktu,
						uid = :uid, 
						status = :now_status";
			$this->waktu = date("H:i:s");
			
			$stmt = $this->conn->prepare($sqlQuery);
		
			// sanitize
			$this->uid=htmlspecialchars(strip_tags($this->uid));
		
			// bind data
			$stmt->bindParam(":uid", $this->uid);
			$stmt->bindParam(":now_status", $this->status);
			$stmt->bindParam(":waktu", $this->waktu);
		
			if($stmt->execute()){
			   return true;
			}
			return false;
			
		}
		
	}
}
?>