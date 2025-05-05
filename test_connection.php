<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hercan_komisyon";

// Bağlantıyı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
} else {
    echo "Veritabanına başarıyla bağlanıldı!<br>";
    
    // Veritabanı bilgilerini göster
    echo "<h3>Veritabanı Bilgileri:</h3>";
    echo "Server: " . $servername . "<br>";
    echo "Kullanıcı Adı: " . $username . "<br>";
    echo "Veritabanı Adı: " . $dbname . "<br>";
    
    // Users tablosunu kontrol et
    $sql = "SELECT COUNT(*) as total FROM users";
    $result = $conn->query($sql);
    if ($result) {
        $data = $result->fetch_assoc();
        echo "<br>Users tablosunda " . $data['total'] . " kayıt bulundu.";
    } else {
        echo "<br>Users tablosu bulunamadı veya erişilemiyor.";
    }
}

$conn->close();
?> 