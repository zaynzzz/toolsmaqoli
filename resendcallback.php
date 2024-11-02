<?php
// function.php

/**
 * Fungsi untuk mengambil kredensial berdasarkan nama proyek.
 *
 * @param string $projectName Nama proyek yang dipilih.
 * @return array|null Mengembalikan array dengan 'api_key' dan 'api_token' atau null jika tidak ditemukan.
 */
function getCredentials($projectName) {
    // Mengimpor kredensial dari file credentials.php
    $credentials = include 'credentials.php';
    
    if (isset($credentials[$projectName])) {
        return $credentials[$projectName];
    } else {
        return null;
    }
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
    $signature = hash_hmac('sha512', $data, $token);
    return $signature;
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
 * Fungsi untuk membuat QRIS.
 *
 * @param float $amount Jumlah pembayaran.
 * @param int $expiryMinutes Waktu kedaluwarsa dalam menit.
 * @param string $projectName Nama proyek untuk mengambil kredensial.
 * @return array|string Respons dari API atau array kesalahan.
 */
function createQris($amount, $expiryMinutes, $projectName) {
    $credentials = getCredentials($projectName);
    if (!$credentials) {
        return ['success' => false, 'message' => 'Invalid project name or credentials not found.'];
    }

    $url = 'https://api.cronosengine.com/api/qris';
    $key = $credentials['api_key'];
    $token = $credentials['api_token'];

    // Dapatkan nama acak
    $viewName = getRandomName();

    // Data yang akan dikirim
    $body = [
        "reference" => generateReference(),
        "amount" => $amount,
        "expiryMinutes" => $expiryMinutes,
        "viewName" => $viewName,
        "additionalInfo" => [
            "callback" => "https://api-prod.mitrapayment.com/api/callback/cronos/notify"
        ]
    ];

    // Membuat signature
    $signature = signhash($key, $body, $token);

    // Inisialisasi cURL
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

    // Eksekusi request dan ambil response
    $response = curl_exec($ch);

    // Cek jika ada error
    if(curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'message' => 'cURL Error: ' . $error_msg];
    }

    // Cek HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        curl_close($ch);
        return ['success' => false, 'message' => 'API Error: HTTP Status Code ' . $http_code];
    }

    // Tutup koneksi cURL
    curl_close($ch);

    return $response;
}

/**
 * Fungsi untuk menangani respons QRIS dan menampilkan QR Code.
 *
 * @param string $response Respons dari API.
 * @return void
 */
function handleQrisResponse($response) {
    // Decode respons JSON
    $responseData = json_decode($response, true);

    if ($responseData === null) {
        echo "<p style='color: red;'>Error: Respons tidak valid dari API.</p>";
        return;
    }
    if (isset($responseData['responseCode']) && $responseData['responseCode'] == 200) {
        // Ambil reff id, amount, dan image base64 dari respons
        $reffId = isset($responseData['responseData']['id']) ? $responseData['responseData']['id'] : 'N/A';
        $amount = isset($responseData['responseData']['amount']) ? $responseData['responseData']['amount'] : 0;
        $imageBase64 = isset($responseData['responseData']['qris']['image']) ? $responseData['responseData']['qris']['image'] : '';

        // Tampilkan informasi QRIS
        echo "<h2>QRIS Payment Generated Successfully</h2>";
        echo "<p>Reff ID: " . htmlspecialchars($reffId) . "</p>";
        echo "<p>Amount: Rp " . number_format($amount, 0, ',', '.') . "</p>";

        // Tampilkan QR code yang sudah di-generate dengan kelas CSS untuk styling
        if ($imageBase64) {
            echo "<h3>QR Code:</h3>";
            echo "<img src='$imageBase64' alt='QR Code' class='qris-image'>";
        } else {
            echo "<p style='color: red;'>Error: QR Code tidak tersedia.</p>";
        }
    } else {
        $errorMessage = isset($responseData['responseMessage']) ? htmlspecialchars($responseData['responseMessage']) : 'Terjadi kesalahan.';
        echo "<p style='color: red;'>Error: " . $errorMessage . "</p>";
    }
}

/**
 * Fungsi untuk membuat pembayaran E-Wallet.
 *
 * @param string $channel Channel E-Wallet (e.g., ovo, gopay).
 * @param float $amount Jumlah pembayaran.
 * @param string $phoneNumber Nomor telepon pengguna.
 * @param int $expiryMinutes Waktu kedaluwarsa dalam menit.
 * @param string $viewName Nama tampilan (view name).
 * @param string $projectName Nama proyek untuk mengambil kredensial.
 * @return array|string Respons dari API atau array kesalahan.
 */
function createEwalletPayment($channel, $amount, $phoneNumber, $expiryMinutes, $viewName, $projectName) {
    $credentials = getCredentials($projectName);
    if (!$credentials) {
        return ['success' => false, 'message' => 'Invalid project name or credentials not found.'];
    }

    $url = 'https://api.cronosengine.com/api/e-wallet';
    $key = $credentials['api_key'];
    $token = $credentials['api_token'];

    // Data yang akan dikirim
    $body = [
        "reference" => generateReference(),
        "phoneNumber" => $phoneNumber,
        "channel" => $channel,
        "amount" => $amount,
        "expiryMinutes" => $expiryMinutes,
        "viewName" => $viewName,
        "additionalInfo" => [
            "callback" => "http://your-site-callback.com/notify",
            "successRedirectUrl" => "http://redirect-after-success.com"
        ]
    ];

    // Membuat signature
    $signature = signhash($key, $body, $token);

    // Inisialisasi cURL
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

    // Eksekusi request dan ambil response
    $response = curl_exec($ch);

    // Cek jika ada error
    if(curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'message' => 'cURL Error: ' . $error_msg];
    }

    // Cek HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        curl_close($ch);
        return ['success' => false, 'message' => 'API Error: HTTP Status Code ' . $http_code];
    }

    // Tutup koneksi cURL
    curl_close($ch);

    // Decode respons JSON
    $responseData = json_decode($response, true);

    if ($responseData === null) {
        return ['success' => false, 'message' => 'Invalid JSON response from API.'];
    }

    if (isset($responseData['responseCode']) && $responseData['responseCode'] == 200) {
        // Ambil data yang diperlukan
        $refId = isset($responseData['responseData']['id']) ? $responseData['responseData']['id'] : null;
        $urlPayment = isset($responseData['responseData']['eWallet']['url']) && !empty($responseData['responseData']['eWallet']['url']) ? $responseData['responseData']['eWallet']['url'] : null;
        return [
            'success' => true,
            'refId' => $refId,
            'channel' => strtoupper($channel),
            'amount' => $amount,
            'phoneNumber' => $phoneNumber,
            'url' => $urlPayment // Tambahkan URL jika tersedia
        ];
    } else {
        $message = isset($responseData['responseMessage']) ? htmlspecialchars($responseData['responseMessage']) : 'Terjadi kesalahan.';
        return ['success' => false, 'message' => $message];
    }
}
/**
 * Fungsi untuk menangani respons E-Wallet dan menampilkan pesan sukses.
 *
 * @param array|string $response Respons dari fungsi createEwalletPayment.
 * @return void
 */
function handleEwalletResponse($response) {
    if (is_array($response)) {
        if ($response['success']) {
            $refId = isset($response['refId']) ? htmlspecialchars($response['refId']) : 'N/A';
            $channel = isset($response['channel']) ? htmlspecialchars($response['channel']) : 'N/A';
            $amount = isset($response['amount']) ? $response['amount'] : 0;
            $phoneNumber = isset($response['phoneNumber']) ? htmlspecialchars($response['phoneNumber']) : 'N/A';
            $urlPayment = isset($response['url']) ? htmlspecialchars($response['url']) : null;

            echo "<h3>Pembayaran Berhasil!</h3>";
            echo "<p>Pembayaran melalui <strong>" . $channel . "</strong> sebesar <strong>Rp " . number_format($amount, 0, ',', '.') . "</strong> berhasil dibuat untuk nomor HP: <strong>" . $phoneNumber . "</strong>.</p>";
            echo "<p>Reference ID: <strong>" . $refId . "</strong></p>";

            // Tampilkan URL jika tersedia
            if ($urlPayment) {
                echo "<p>Untuk menyelesaikan pembayaran, silakan klik <a href='" . $urlPayment . "' target='_blank'>di sini</a>.</p>";
            } else {
                // Jika URL tidak tersedia, tampilkan instruksi alternatif
                echo "<p style='color: orange;'>Instruksi pembayaran telah dikirimkan melalui aplikasi E-Wallet Anda.</p>";
                echo "<p>Silakan buka aplikasi E-Wallet Anda dan selesaikan pembayaran dengan Reference ID di atas.</p>";
            }
        } else {
            $message = isset($response['message']) ? htmlspecialchars($response['message']) : 'Terjadi kesalahan.';
            echo "<p style='color: red;'>Error: " . $message . "</p>";
        }
    } else {
        // Jika respons bukan array, tampilkan sebagai teks biasa
        echo "<p>" . htmlspecialchars($response) . "</p>";
    }
}
