<?php
// Nama file untuk menyimpan pesan
$file = 'messages.txt';
date_default_timezone_set("Asia/Bangkok");

// Fungsi untuk mendapatkan alamat IP pengguna
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Tentukan IP pengirim yang sedang aktif (ini adalah IP pengguna yang mengirim pesan)
$activeIP = getUserIP();

// Jika form dikirim, simpan pesan ke file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $anon = "Anonymous";
        $ip = $activeIP;
        $timestamp = date('Y-m-d H:i:s');
        $entry = "{$anon} ({$ip}) - {$timestamp} : {$message}" . PHP_EOL;
        file_put_contents($file, $entry, FILE_APPEND);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


// Baca semua pesan dari file
$messages = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];

// Daftar emote besar (1000 emote)
$emotes = [
    '😊', '😂', '😍', '😎', '😢', '😁', '😜', '😇', '😏', '😋', '😐', '🤔', '😡', '😈',
    '😱', '😷', '🤩', '😭', '😵', '😻', '🤗', '😈', '😜', '😝', '😒', '🧐', '🥳', '💀', '🤡',
    '🤑', '😺', '🙀', '🙉', '👹', '💩', '🐱', '🐭', '🐰', '🐻', '🐼', '🐯', '🐨', '🐸', '🐒', 
    '🦄', '🐗', '🐴', '🐍', '🦓', '🦒', '🦧', '🦔', '🐉', '🦁', '🦢', '🦄', '🐝', '🦋', '🐞', 
    '🐌', '🐜', '🦋', '🐧', '🐤', '🐣', '🦀', '🐞', '🦋', '🦇', '🦗', '🦓', '🐬', '🐋', '🦏', 
    '🦈', '🐙', '🐢', '🐚', '🐚', '🐠', '🐡', '🦀', '🐧', '🐚', '🐠', '🐡', '🦓', '🦀', '🐜', 
    '🦖', '🦒', '🦣', '🦘', '🦥', '🦦', '🦨', '🦩', '🦦', '🦒', '🐦', '🦚', '🦜', '🦝', 
    '🦣', '🦑', '🦪', '🦭', '🐋', '🐬', '🐠', '🐟', '🐡', '🐢', '🐍', '🐸', '🦄', '🐴', '🦉', 
    '🐅', '🐆', '🦁', '🦓', '🐃', '🐂', '🐄', '🦃', '🦅', '🦄', '🦧', '🐺', '🐕', '🐦', '🐥',
    // Anda bisa menambahkan lebih banyak emote sesuai kebutuhan
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anonymous Chat</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #1e1e2f;
            color: #fff;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            padding: 20px;
            text-align: center;
            background-color: #29293d;
            border-bottom: 1px solid #444;
            font-size: 1.5em;
        }
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-height: 500px;
        }
        .chat-box {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #29293d;
        }
        .form-container {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background-color: #29293d;
            border-top: 1px solid #444;
        }
        .form-container input[type="text"] {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            margin-bottom: 10px;
            color: #fff;
            background-color: #444;
        }
        .form-container button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            background-color: #4caf50;
            color: #fff;
            cursor: pointer;
        }
        /* Pesan yang dikirim oleh pengguna aktif */
        .message {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #444;
            border-radius: 5px;
        }
        .message.active {
            background-color: #4caf50;
            align-self: flex-end;
        }
        /* Modal emote */
        .emote-modal {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #29293d;
            border-top: 1px solid #444;
        }
        .emote-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 1.5em;
        }
        .emote-buttons button {
            background: none;
            border: none;
            cursor: pointer;
        }
    </style>
    <script>
        // Fungsi untuk membuka modal emote di bawah tombol Send
        function toggleEmoteModal() {
            const modal = document.getElementById("emoteModal");
            modal.style.display = (modal.style.display === "block") ? "none" : "block";
        }

        // Fungsi untuk menambahkan emoji ke kolom input
        function insertEmote(emote) {
            const messageInput = document.getElementById('message');
            messageInput.value += emote;
            messageInput.focus();
        }
    </script>
</head>
<body>
    <div class="header">Anonymous Chat</div>
    <div class="chat-container">
    <div class="chat-box" id="chatBox">
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No messages yet. Apa yang ada di pikiran Anda?</p>
        <?php endif; ?>
    </div>
        <form method="POST" class="form-container">
            <input type="text" name="message" id="message" placeholder="Type your message..." required>
            <button type="button" onclick="toggleEmoteModal()">😊 Emote</button>
        </form>

        <!-- Modal untuk Emote -->
        <div id="emoteModal" class="emote-modal">
            <div class="emote-buttons">
                <?php foreach ($emotes as $emote): ?>
                    <button type="button" onclick="insertEmote('<?= htmlspecialchars($emote) ?>')"><?= $emote ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
    // Fungsi untuk scroll ke bawah
    function scrollToBottom() {
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Scroll ke bawah setelah DOM selesai dimuat
    document.addEventListener('DOMContentLoaded', scrollToBottom);

    // Scroll ke bawah setelah form dikirim
    document.querySelector('form').addEventListener('submit', function () {
        setTimeout(scrollToBottom, 10); // Beri jeda kecil untuk memastikan pesan baru dimuat
    });
</script>
<script>
        // Fungsi untuk membuka modal emote di bawah tombol Send
        function toggleEmoteModal() {
            const modal = document.getElementById("emoteModal");
            modal.style.display = (modal.style.display === "block") ? "none" : "block";
        }

        // Fungsi untuk menambahkan emoji ke kolom input
        function insertEmote(emote) {
            const messageInput = document.getElementById('message');
            messageInput.value += emote;
            messageInput.focus();
        }

        // Fungsi untuk mengirim pesan melalui AJAX
        function sendMessage() {
            const messageInput = document.getElementById('message');
            const message = messageInput.value.trim();

            if (message) {
                fetch('send_message.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        'message': message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        messageInput.value = ''; // Clear input
                        loadMessages(); // Load latest messages
                    }
                });
            }
        }

        // Fungsi untuk memuat pesan dari server
        function loadMessages() {
            const chatBox = document.getElementById('chatBox');
            fetch('update_messages.php')
                .then(response => response.json())
                .then(messages => {
                    chatBox.innerHTML = '';
                    messages.forEach(msg => {
                        const div = document.createElement('div');
                        div.classList.add('message');
                        div.textContent = msg;
                        chatBox.appendChild(div);
                    });
                    chatBox.scrollTop = chatBox.scrollHeight; // Scroll ke bawah otomatis
                });
        }

        // Scroll ke bawah otomatis
        function scrollToBottom() {
            const chatBox = document.getElementById('chatBox');
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Memuat pesan baru setiap 2 detik
        setInterval(loadMessages, 1000);

        // Fokus otomatis pada input saat DOM selesai dimuat
        document.addEventListener('DOMContentLoaded', scrollToBottom);
    </script>

</body>

</html>
