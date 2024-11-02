<?php
// process_wallet.php
include 'function.php'; // Pastikan path ini benar

// Pastikan data dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil parameter dari request
    $channel = isset($_POST['channel']) ? trim($_POST['channel']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';
    $expiryMinutes = 30; // Default waktu 30 menit
    $viewName = 'Mr. Zyn'; // Sesuaikan jika perlu
    $projectName = isset($_POST['projectName']) ? trim($_POST['projectName']) : ''; // Ambil nama proyek

    // Validasi input
    if (empty($channel) || empty($amount) || empty($phoneNumber) || empty($projectName)) {
        echo "<p style='color: red;'>Error: Data tidak lengkap.</p>";
        exit;
    }

    if ($amount <= 0) {
        echo "<p style='color: red;'>Error: Nominal harus lebih besar dari 0.</p>";
        exit;
    }

    // Panggil fungsi untuk membuat pembayaran E-Wallet
    $response = createEwalletPayment($channel, $amount, $phoneNumber, $expiryMinutes, $viewName, $projectName);

    // Tampilkan respons dan hasilnya
    handleEwalletResponse($response);
} else {
    echo "<p style='color: red;'>Error: Metode request tidak valid.</p>";
}
?>
