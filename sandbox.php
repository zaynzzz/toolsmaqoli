<?php

function sendDisbursement($data) {
    // API URL
    $url = 'https://api.cronosengine.com/api/disburse';

    // API Credentials
    $key = 'CE-TKY2ZLBAI6BCVLJP';
    $token = 'nt0FnQD5NxBdYlt9LSe1jLB8LQrvSOhi';
   
    // Body Data
    $body = [
        "bankCode" => $data['bankCode'],
        "recipientAccount" => $data['recipientAccount'],
        "reference" => $data['reference'],
        "amount" => $data['amount'],
        "additionalInfo" => [
            "callback" => $data['callback']
        ]
    ];

    // Convert body to JSON format
    $bodyJson = json_encode($body);

    // Generate On-Signature using hash_hmac with SHA-512
    $signature = hash_hmac('sha512', $key . $bodyJson, $token);

    // Headers for the request
    $headers = [
        "On-Key: $key",
        "On-Token: $token",
        "On-Signature: $signature",
        "Content-Type: application/json"
    ];

    // Initialize cURL
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Request Error: ' . curl_error($ch);
    } else {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code === 200) {
            echo 'Success: ' . $response;
        } else {
            echo "Failed with HTTP Code $http_code: " . $response;
        }
    }

    // Close cURL session
    curl_close($ch);
}

// Usage with Random Reference Generation
$data = [
    "bankCode" => "022",
    "recipientAccount" => "860011001100",
    "reference" => generateRandomReference(),
    "amount" => 6991290,
    "callback" => "https://api-prod.mitrapayment.com/api/callback/cronos/notify"
];

sendDisbursement($data);

// Function to generate a random reference
function generateRandomReference() {
    return 'MPI-' . bin2hex(random_bytes(5)) . '-' . bin2hex(random_bytes(3)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(4));
}
