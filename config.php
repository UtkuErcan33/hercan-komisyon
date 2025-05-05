<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'hercan_komisyon');

// Veritabanına bağlanma
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Bağlantıyı kontrol et
if($conn === false){
    die("HATA: Veritabanına bağlanılamadı. " . mysqli_connect_error());
}
?> 