<?php
// function.php

/**
 * Fungsi untuk mengambil kredensial berdasarkan nama proyek.
 *
 * @param string $projectName Nama proyek yang dipilih.
 * @return array|null Mengembalikan array dengan 'api_key' dan 'api_token' atau null jika tidak ditemukan.
 */
function getCredentials($projectName) {
    $credentials = include 'credentials.php';
    return $credentials[$projectName] ?? null;
}

/**
 * Fungsi untuk menghasilkan reference unik.
 *
 * @return string Reference ID unik.
 */
function generateReference() {
    return uniqid('ref_', true);
}

/**
 * Fungsi untuk membuat signature HMAC SHA512.
 *
 * @param string $key API Key.
 * @param array $body Data yang dikirim ke API.
 * @param string $token API Token.
 * @return string Signature.
 */
function signhash($key, $body, $token) {
    $data = $key . json_encode($body);

    return hash_hmac('sha512', $data, $token);
}

/**
 * Fungsi untuk mendapatkan nama acak dari API randomuser.me.
 *
 * @return string Nama acak atau 'defaultName' jika gagal.
 */
function getRandomName() {
    $apiUrl = 'https://randomuser.me/api/';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return 'defaultName';
    }
    
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['results'][0]['name'])) {
        $name = $data['results'][0]['name'];
        return $name['first'] . ' ' . $name['last'];
    }
    return 'defaultName';
}

/**
 * Fungsi untuk membuat pembayaran QRIS.
 *
 * @param float $amount Jumlah pembayaran.
 * @param int $expiryMinutes Waktu kedaluwarsa dalam menit.
 * @param string $projectName Nama proyek untuk mengambil kredensial.
 * @return string JSON string respons dari API atau error.
 */
function createQris($amount, $expiryMinutes, $projectName) {
    $credentials = getCredentials($projectName);
    if (!$credentials) {
        return json_encode(['success' => false, 'message' => 'Invalid project name or credentials not found.']);
    }

    // Pastikan URL API QRIS benar
    $url = 'https://api.cronosengine.com/api/qris';
    $key = $credentials['api_key'];
    $token = $credentials['api_token'];

    $viewName = getRandomName();

    $body = [
        "reference" => generateReference(),
        "amount" => $amount,
        "expiryMinutes" => $expiryMinutes,
        "viewName" => $viewName,
        "additionalInfo" => [
            "callback" => "https://api-prod.mitrapayment.com/api/callback/cronos/notify"
        ]
    ];

    $signature = signhash($key, $body, $token);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "On-Key: $key",
        "On-Token: $token",
        "On-Signature: $signature",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return json_encode(['success' => false, 'message' => 'cURL Error: ' . $error_msg]);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        curl_close($ch);
        return json_encode(['success' => false, 'message' => 'API Error: HTTP Status Code ' . $http_code]);
    }

    curl_close($ch);

    return $response; // Asumsikan respons adalah string JSON
    
}

/**
 * Fungsi untuk menangani respons QRIS dan menampilkan QR Code.
 *
 * @param string $response Respons dari API.
 */
function handleQrisResponse($response) {
    $responseData = json_decode($response, true);
   
    if ($responseData === null) {
        echo "<p style='color: red;'>Error: Respons tidak valid dari API.</p>";
        return;
    }

    if (isset($responseData['responseCode']) && $responseData['responseCode'] == 200) {
        $reffId = $responseData['responseData']['id'] ?? 'N/A';
        $amount = $responseData['responseData']['amount'] ?? 0;
        $imageBase64 = $responseData['responseData']['qris']['image'] ?? '';

        echo "<h2>QRIS Payment Generated Successfully</h2>";
        echo "<p>Reff ID: " . htmlspecialchars($reffId) . "</p>";
        echo "<p>Amount: Rp " . number_format($amount, 0, ',', '.') . "</p>";

        if ($imageBase64) {
            echo "<h3>QR Code:</h3>";
            echo "<img src='$imageBase64' alt='QR Code' class='qris-image'>"; // Apply the qris-image class here
        } else {
            echo "<p style='color: red;'>Error: QR Code tidak tersedia.</p>";
        }
    } else {
        $errorMessage = $responseData['responseMessage'] ?? 'Terjadi kesalahan.';
        echo "<p style='color: red;'>Error: " . htmlspecialchars($errorMessage) . "</p>";
    }
}

/**
 * Fungsi untuk membuat pembayaran Virtual Account.
 *
 * @param float $amount Jumlah pembayaran.
 * @param int $expiryMinutes Waktu kedaluwarsa dalam menit.
 * @param string $projectName Nama proyek untuk mengambil kredensial.
 * @param string $bankCode Kode bank untuk VA.
 * @return string JSON string respons dari API atau error.
 */
function createVa($amount, $expiryMinutes, $projectName, $bankCode) {
    $credentials = getCredentials($projectName);
    if (!$credentials) {
        return json_encode(['success' => false, 'message' => 'Invalid project name or credentials not found.']);
    }

    // Pastikan URL API VA benar
    $url = 'https://api.cronosengine.com/api/virtual-account';
    $key = $credentials['api_key'];
    $token = $credentials['api_token'];

    $body = [
        "bankCode" => $bankCode,
        "singleUse" => true,
        "type" => "ClosedAmount",
        "reference" => generateReference(),
        "amount" => $amount,
        "expiryMinutes" => $expiryMinutes,
        "viewName" => 'Mr. Gentur',
        "additionalInfo" => [
            "callback" => "http://your-site-callback.com/notify"
        ]
    ];

    $signature = signhash($key, $body, $token);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "On-Key: $key",
        "On-Token: $token",
        "On-Signature: $signature",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return json_encode(['success' => false, 'message' => 'cURL Error: ' . curl_error($ch)]);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        curl_close($ch);
        return json_encode(['success' => false, 'message' => 'API Error: HTTP Status Code ' . $http_code]);
    }

    curl_close($ch);
    return $response; // Asumsikan respons adalah string JSON
}
/**
 * Fungsi untuk menangani respons Virtual Account dan menampilkan hasilnya.
 *
 * @param string $response Respons dari API.
 */
function handleVaResponse($response) {
    $responseData = json_decode($response, true);

    if ($responseData === null) {
        echo "<p style='color: red;'>Error: Respons tidak valid dari API.</p>";
        return;
    }

    if (isset($responseData['responseCode']) && $responseData['responseCode'] == 200) {
        $reffId = $responseData['responseData']['id'] ?? 'N/A';
        $amount = $responseData['responseData']['amount'] ?? 0;
        $totalAmount = $responseData['responseData']['totalAmount'] ?? 0;
        $fee = $responseData['responseData']['fee'] ?? 0;
        $vaNumber = $responseData['responseData']['virtualAccount']['vaNumber'] ?? 'N/A';
        $bankCode = $responseData['responseData']['virtualAccount']['bankCode'] ?? 'N/A';

        // Output respons
        echo "<h2>Virtual Account Payment Generated Successfully</h2>";
        echo "<p>Reff ID: " . htmlspecialchars($reffId) . "</p>";
        echo "<p>VA Number: " . htmlspecialchars($vaNumber) . "</p>";

        // Menampilkan informasi amount dan total
        echo "<p>Amount: Rp " . number_format($amount, 0, ',', '.') . "</p>";
        echo "<p>Fee: Rp " . number_format($fee, 0, ',', '.') . "</p>";
        echo "<p>Total Amount: Rp " . number_format($totalAmount, 0, ',', '.') . "</p>";

        // Menentukan nama bank berdasarkan bankCode
        $bankName = 'N/A';
        $logoUrl = ''; // Variable untuk logo bank
        switch ($bankCode) {
            case '008':
                $bankName = 'Mandiri';
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/4/43/Bank_Mandiri_logo.svg'; // Logo Mandiri
                break;
            case '014':
                $bankName = 'BCA';
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Bank_Central_Asia_logo.svg'; // Logo BCA
                break;
            case '002':
                $bankName = 'BRI';
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/d/d1/Bank_Rakyat_Indonesia_logo.svg'; // Logo BRI
                break;
            case '009':
                $bankName = 'BNI';
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/7/7d/Logo_Bank_Negara_Indonesia.svg'; // Logo BNI
                break;
            case '013':
                $bankName = 'Permata';
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/id/8/85/Logo_Bank_Permata.svg'; // Logo Permata
                break;
            case '011':
                $bankName = 'Danamon';
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/en/e/e6/Danamon_Bank_Logo.svg'; // Logo Danamon
                break;
            case '022':
                $bankName = 'CIMB';
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/a/aa/CIMB_Logo.svg'; // Logo CIMB
                break;
            case '153':
                $bankName = 'Sahabat Sampoerna';
                $logoUrl = 'https://upload.wikimedia.org/wikipedia/commons/4/4b/Logo_Bank_Sahabat_Sampoerna.png'; // Logo Sahabat Sampoerna
                break;
        }

        // Menampilkan nama bank
        echo "<p>Bank: " . htmlspecialchars($bankName) . "</p>";

        // Menampilkan logo bank
        if ($logoUrl) {
            echo "<img src='$logoUrl' alt='$bankName Logo' style='width: 100px; height: auto; margin-top: 10px;'>";
        }
    } else {
        $errorMessage = $responseData['responseMessage'] ?? 'Terjadi kesalahan.';
        echo "<p style='color: red;'>Error: " . htmlspecialchars($errorMessage) . "</p>";
    }
}

?>
