#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <LiquidCrystal_I2C.h>
#include <ArduinoJson.h>

// Inisialisasi LCD I2C
LiquidCrystal_I2C lcd(0x27, 16, 2); // Alamat I2C 0x27 untuk LCD 16x2

#define RST_PIN         16   // pin-D0       
#define SS_PIN          0    // pin-D3
byte buzzer = 15;            // pin D8
MFRC522 mfrc522(SS_PIN, RST_PIN);   // Buat instance MFRC522

const char* ssid = "KUPER";    // SSID Wifi Anda
const char* password = "lawanpkii";   // Password Wifi
String server_addr= "192.168.1.16";  // Alamat server Anda atau IP komputer

byte readCard[4];   
uint8_t successRead;    
String UIDCard;

void setup() {
  pinMode(buzzer, OUTPUT);
  Serial.begin(115200);                                         // Inisialisasi komunikasi serial dengan PC
  SPI.begin();                                                  // Inisialisasi bus SPI
  mfrc522.PCD_Init();                                           // Inisialisasi kartu MFRC522
  Serial.println(F("Baca data UID pada MIFARE PICC:"));    // Menunjukkan bahwa siap membaca
  TampilkanDetailPembaca();                                     // Menampilkan detail PCD - detail Pembaca Kartu MFRC522
  
  lcd.init();                                                   // Inisialisasi LCD
  lcd.backlight();                                              // Nyalakan lampu latar LCD
  
  // intro saja
  lcd.clear();
  lcd.setCursor(0, 0);  
  lcd.print("ABSENSI");
  lcd.setCursor(0, 1);  
  lcd.print("PENGURUS BEM");
  delay(2000); 
  KoneksikanWIFI(); 
  delay(2000);
}

void loop() { 
  lcd.clear(); 
  lcd.setCursor(0, 0);
  lcd.print("Tempel ID Card!!");
  successRead = dapatkanID();
}

uint8_t dapatkanID() {
  // Bersiap untuk Membaca PICCs
  if ( ! mfrc522.PICC_IsNewCardPresent()) { 
    return 0;
  }
  if ( ! mfrc522.PICC_ReadCardSerial()) {   
    return 0;
  }
  UIDCard ="";
  Serial.println(F("UID PICC yang Dipindai:"));
   
  for ( uint8_t i = 0; i < mfrc522.uid.size; i++) {  
    UIDCard += String(mfrc522.uid.uidByte[i], HEX);
  }
  UIDCard.toUpperCase(); // Kapital
  Serial.print("UID:");
  Serial.println(UIDCard);
  Serial.println(F("**Akhir Pembacaan**"));
  digitalWrite(buzzer, HIGH); delay(200);
  digitalWrite(buzzer, LOW); delay(200);
  digitalWrite(buzzer, HIGH); delay(200);
  digitalWrite(buzzer, LOW);
  
  simpanData(); // simpan data ke DB
  delay(2000); 
  
  mfrc522.PICC_HaltA(); // Berhenti membaca
  return 1;
}

void simpanData(){
  KoneksikanWIFI(); // cek koneksi wifi
  WiFiClient client;
  String address, pesan, nama_depan;
  
  // sesuaikan dengan alamat server Anda (alamat IP komputer) dan direktori aplikasi Anda
  address = "http://"+server_addr+"/absensi/webapi/api/create.php?uid="+UIDCard;
  
  HTTPClient http;  
  http.begin(client, address);
  int httpCode = http.GET();        // Kirim permintaan GET
  String payload; 
  Serial.print("Respon: "); 
  if (httpCode > 0) {               // Periksa kode pengembalian    
      payload = http.getString();   // Dapatkan payload respons permintaan
      payload.trim();               // hilangkan karakter \n
      if( payload.length() > 0 ){
         Serial.println(payload + "\n");
      }
  }
  http.end();   // Tutup koneksi  

  const size_t kapasitas = JSON_OBJECT_SIZE(4) + 70; // simulasi data JSON Anda https://arduinojson.org/v6/assistant/
  DynamicJsonDocument doc(kapasitas);
      
  // Deserialisasi dokumen JSON
  DeserializationError error = deserializeJson(doc, payload);
  
  // Uji jika parsing berhasil.
  if (error) {
    Serial.print(F("deserializeJson() gagal: "));
    Serial.println(error.c_str());
    return;
  }
  
  const char* waktu_res = doc["waktu"];
  String nama_res = doc["nama"]; 
  const char* uid_res = doc["uid"]; 
  String status_res = doc["status"]; 

  for(int i = 0; i < nama_res.length(); i++){
    if(nama_res.charAt(i) == ' '){
      nama_depan = nama_res.substring(0, i);
      break;
    }
  }
  
  lcd.clear();
  
  // Cetak Data 
  if (status_res == "INVALID"){
    pesan = "Siapa kamu?";
    lcd.setCursor(0, 0); lcd.print(pesan);
    lcd.setCursor(0, 1); lcd.print(uid_res);
  } else {
    if (status_res == "IN"){
      pesan = "Selamat Datang!";
    } else {
      pesan = "Sampai Jumpa!";
    }
    lcd.setCursor(0, 0); lcd.print(pesan);
    lcd.setCursor(0, 1); lcd.print(nama_depan);
  }
  delay(3000);
}

void KoneksikanWIFI(){
  if(WiFi.status() != WL_CONNECTED){
    Serial.print("Mencoba menghubungkan ke SSID: ");
    Serial.println(ssid);
    WiFi.begin(ssid, password);
    int i = 0;
    while(WiFi.status() != WL_CONNECTED){ 
      Serial.print(".");
      lcd.clear();
      lcd.setCursor(0, 0); lcd.print("Menghubungkan ...");
      delay(1000); 
      ++i;
      if (i == 30){
        i = 0;
        Serial.println("\n Gagal Menghubungkan.");
        break;
      }    
    }
    Serial.println("\n Terhubung!"); 
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("Terhubung!");
    delay(2000);
  }
}

void TampilkanDetailPembaca() {
  // Dapatkan versi perangkat lunak MFRC522
  byte v = mfrc522.PCD_ReadRegister(mfrc522.VersionReg);
  Serial.print(F("Versi Perangkat Lunak MFRC522: 0x"));
  Serial.print(v, HEX);
  if (v == 0x91)
    Serial.print(F(" = v1.0"));
  else if (v == 0x92)
    Serial.print(F(" = v2.0"));
  else
    Serial.print(F(" (tidak diketahui), mungkin kloningan cina?"));
  Serial.println("");
  // Ketika 0x00 atau 0xFF dikembalikan, kemungkinan komunikasi gagal
  if ((v == 0x00) || (v == 0xFF)) {
    Serial.println(F("PERINGATAN: Kegagalan komunikasi, apakah MFRC522 terhubung dengan benar?"));
    Serial.println(F("SISTEM DIHENTIKAN: Periksa koneksi."));
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("Kegagalan Kom.");
    lcd.setCursor(0, 1); lcd.print("Periksa Koneksi.");
    while (true); // tidak melanjutkan lebih jauh
  }
}