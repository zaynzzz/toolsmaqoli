<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'];
    $token = $_POST['token'];
    $idsInput = $_POST['ids'];

    // Convert comma-separated IDs into an array
    $ids = array_map('trim', explode(',', $idsInput));

    checkApi($ids, $key, $token);
}

function checkApi($ids, $key, $token) {
    $urlBase = 'https://api.cronosengine.com/api/check/';
    $headers = [
        'On-Key: ' . $key,
        'On-Token: ' . $token,
        'On-Signature: ' . hash_hmac('sha512', $key, $token),
        'Accept: application/json'
    ];

    // Start HTML output
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>API Response</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container mt-5">
            <h2 class="text-center">API Response</h2>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Results</h5>
                    <div class="list-group">';

    foreach ($ids as $id) {
        $ch = curl_init();
        $url = $urlBase . $id . '?resendCallback=true';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            echo '<a href="#" class="list-group-item list-group-item-action text-danger">Error: ' . htmlspecialchars($error_msg) . ' for ID ' . htmlspecialchars($id) . '</a>';
            curl_close($ch);
            continue;
        }
        
        curl_close($ch);
        
        // Decode JSON response
        $res = json_decode($response, true);
        if (isset($res['responseData'])) {
            echo '<a href="https://backoffice.cronosengine.com/transactions?tableSearch=' . htmlspecialchars($res['responseData']['id']) . 
                 '" target="_blank" class="list-group-item list-group-item-action">' . 
                 '<strong>ID:</strong> ' . htmlspecialchars($res['responseData']['id']) . '<br>' . 
                 '<strong>Message:</strong> ' . htmlspecialchars($res['responseMessage']) . 
                 '</a>';
        } else {
            echo '<a href="#" class="list-group-item list-group-item-action text-danger">' . 
                 '<strong></strong> ' . htmlspecialchars($res['responseCode']). ' On this project' . '<br>' . 
                 '<strong>Message:</strong> ' . htmlspecialchars($res['responseMessage']) .
                 '</a>';
        }
    }

    echo '</div>'; // End of list group
    echo '<a href="https://tools.maqoli.com/"><button type="button" class="btn btn-primary mt-3">Back</button></a>'; // Back button
    echo '</div>'; // End of card body
    echo '</div>'; // End of card
    echo '</div>'; // End of container
    echo '</body></html>'; // Close the HTML structure
}
?>
