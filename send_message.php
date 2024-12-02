<?php
$file = 'messages.txt';
date_default_timezone_set("Asia/Bangkok");

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$activeIP = getUserIP();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $anon = "Anonymous";
        $ip = $activeIP;
        $timestamp = date('Y-m-d H:i:s');
        $entry = "{$anon} ({$ip}) - {$timestamp} : {$message}" . PHP_EOL;
        file_put_contents($file, $entry, FILE_APPEND);
        echo json_encode(['status' => 'success']);
    }
}
?>
