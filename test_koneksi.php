<?php
$host = "localhost";
$port = "5432";
$dbname = "postgres";
$user = "postgres";
$password = "!@#"; // ganti sesuai password saat install PostgreSQL 16

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if ($conn) {
    echo "Koneksi ke PostgreSQL berhasil!";
} else {
    echo "Koneksi gagal.";
}