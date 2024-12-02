<?php
$file = 'messages.txt';

$messages = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];

echo json_encode($messages);
?>
