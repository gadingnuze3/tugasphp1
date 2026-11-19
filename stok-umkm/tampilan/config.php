<?php
// Set zona waktu ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database Lokal (XAMPP / Laragon)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "inventory_umkm";

// Membuat koneksi menggunakan MySQLi
$conn = new mysqli($host, $user, $pass, $db);

// Cek jika terjadi error pada koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// Set karakter encoding agar simbol dan teks tersimpan aman
$conn->set_charset("utf8mb4");
?>