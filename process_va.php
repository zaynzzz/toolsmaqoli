<?php
// process_va.php
include 'function.php'; // Pastikan path ini benar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;
    $projectName = isset($_POST['projectName']) ? trim($_POST['projectName']) : '';
    $channel = isset($_POST['channel']) ? trim($_POST['channel']) : '';

    // Validasi input yang dibutuhkan
    if (!$amount || !$projectName || !$channel) {
        echo "<p style='color: red;'>Error: Data tidak lengkap. Pastikan amount, projectName, dan channel diisi.</p>";
        exit;
    }

    if ($amount <= 0) {
        echo "<p style='color: red;'>Error: Nominal harus lebih besar dari 0.</p>";
        exit;
    }

    // Pastikan createVa didefinisikan di function.php
    $response = createVa($amount, 30, $projectName, $channel); // Sesuaikan nilai waktu kedaluwarsa sesuai kebutuhan

    // Tampilkan respons
    handleVaResponse($response);
} else {
    echo "<p style='color: red;'>Error: Metode request tidak valid.</p>";
}
?>
