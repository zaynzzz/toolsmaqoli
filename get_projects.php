<?php
// get_projects.php

header('Content-Type: application/json');

// Mengimpor kredensial dari file credentials.php
$credentials = include 'credentials.php';

// Mengambil daftar nama proyek
$projectNames = array_keys($credentials);

// Mengembalikan daftar proyek sebagai JSON
echo json_encode($projectNames);
?>
