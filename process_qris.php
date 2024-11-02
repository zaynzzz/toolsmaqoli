<?php
// process_qris.php
include 'function.php'; // Pastikan path ini benar

// Pastikan data dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil parameter dari request
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $expiryMinutes = isset($_POST['expiryMinutes']) ? intval($_POST['expiryMinutes']) : 30;
    $projectName = isset($_POST['projectName']) ? trim($_POST['projectName']) : '';

    // Validasi input
    if (empty($amount) || empty($projectName)) {
        echo "<p style='color: red;'>Error: Data tidak lengkap.</p>";
        exit;
    }

    if ($amount <= 0) {
        echo "<p style='color: red;'>Error: Nominal harus lebih besar dari 0.</p>";
        exit;
    }

    // Panggil fungsi untuk membuat QRIS
    $response = createQris($amount, $expiryMinutes, $projectName);

    // Tampilkan respons dan hasilnya
    handleQrisResponse($response);
} else {
    echo "<p style='color: red;'>Error: Metode request tidak valid.</p>";
}
?>
