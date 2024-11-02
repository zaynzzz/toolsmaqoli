<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Metode Pembayaran</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="style.css" rel="stylesheet" />
    <style>
    </style>
</head>
<body>

    <div class="container">
        <h2>Pilih Metode Pembayaran</h2>
        <div class="payment-method">
            <div id="qris-option" onclick="selectMethod('qris')">
                <img src="https://img.icons8.com/color/48/000000/qr-code.png" alt="QRIS" />
                QRIS
            </div>
            <div id="wallet-option" onclick="selectMethod('wallet')">
                <img src="https://img.icons8.com/color/48/000000/wallet.png" alt="E-Wallet" />
                E-Wallet
            </div>
            <div id="va-option" onclick="selectMethod('va')">
                <img src="https://img.icons8.com/color/48/000000/bank.png" alt="Virtual Account" />
                Virtual Account
            </div>
        </div>

        <div class="credential-selection" id="credential-selection">
            <label for="project-select">Pilih Proyek:</label>
            <select id="project-select" style="width: 100%;">
                <option value="">Loading...</option>
            </select>
        </div>

        <div class="input-amount" id="amount-input">
            <label for="amount">Nominal (Amount):</label>
            <input type="number" id="amount" placeholder="Masukkan nominal">
        </div>

        <div class="va-channel-selection" id="va-channel-selection">
            <label for="va-channel">Pilih Bank untuk Virtual Account:</label>
            <select id="va-channel" style="width: 100%;">
                <option value="">Pilih Bank</option>
                <option value="008">Mandiri</option>
                <option value="014">BCA</option>
                <option value="002">BRI</option>
                <option value="009">BNI</option>
                <option value="013">Permata</option>
                <option value="011">Danamon</option>
                <option value="022">CIMB</option>
                <option value="153">Sahabat Sampoerna</option>
            </select>
        </div>

        <button class="generate-btn" id="generate-btn" onclick="generatePayment()">Generate</button>

        <div id="loading">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>

        <div id="response"></div>
    </div>

    <div class="popup" id="popup">
        <div id="popup-content"></div>
        <button onclick="closePopup()">Close</button>
    </div>

    <div class="overlay" id="overlay"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let selectedMethod = null;

        function loadProjects() {
            fetch('get_projects.php')
                .then(response => response.json())
                .then(data => {
                    let projectSelect = document.getElementById('project-select');
                    projectSelect.innerHTML = '<option value="">Pilih Proyek</option>';
                    data.forEach(project => {
                        let option = document.createElement('option');
                        option.value = project;
                        option.textContent = project;
                        projectSelect.appendChild(option);
                    });
                    $('#project-select').select2({ placeholder: "Pilih Proyek", allowClear: true });
                })
                .catch(error => {
                    console.error('Error fetching projects:', error);
                    document.getElementById('project-select').innerHTML = '<option value="">Error loading projects</option>';
                });
        }

        window.onload = loadProjects;

        function selectMethod(method) {
            document.getElementById('qris-option').classList.remove('selected');
            document.getElementById('wallet-option').classList.remove('selected');
            document.getElementById('va-option').classList.remove('selected');
            
            document.getElementById('amount-input').style.display = 'none';
            document.getElementById('va-channel-selection').style.display = 'none';

            if (method === 'qris') {
                document.getElementById('qris-option').classList.add('selected');
                selectedMethod = 'qris';
                document.getElementById('amount-input').style.display = 'block';
            } else if (method === 'wallet') {
                document.getElementById('wallet-option').classList.add('selected');
                selectedMethod = 'wallet';
                document.getElementById('amount-input').style.display = 'none';
            } else if (method === 'va') {
                document.getElementById('va-option').classList.add('selected');
                selectedMethod = 'va';
                document.getElementById('amount-input').style.display = 'block';
                document.getElementById('va-channel-selection').style.display = 'block';
            }

            document.getElementById('credential-selection').style.display = 'block';
            document.getElementById('generate-btn').style.display = 'inline-block';
            document.getElementById('response').innerHTML = '';
            document.getElementById('popup-content').innerHTML = '';
        }

        function generatePayment() {
            if (!selectedMethod) { alert('Pilih metode pembayaran terlebih dahulu!'); return; }
            let projectName = $('#project-select').val();
            if (!projectName) { alert('Pilih proyek terlebih dahulu!'); return; }

            let amount = document.getElementById('amount').value;
            if (!amount || amount <= 0) { alert('Masukkan nominal yang valid!'); return; }

            if (selectedMethod === 'qris') {
                processPayment('process_qris.php', amount, projectName);
            } else if (selectedMethod === 'va') {
                let vaChannel = document.getElementById('va-channel').value;
                if (!vaChannel) { alert('Pilih bank untuk Virtual Account!'); return; }
                processPayment('process_va.php', amount, projectName, vaChannel);
            } else if (selectedMethod === 'wallet') {
                alert("Wallet belum diimplementasikan.");
            }
        }

        function processPayment(url, amount, projectName, channel = '') {
            document.getElementById('loading').style.display = 'block';
            
            // Menyiapkan parameter POST
            let params = `amount=${encodeURIComponent(amount)}&projectName=${encodeURIComponent(projectName)}`;
            if (selectedMethod === 'va') {
                params += `&channel=${encodeURIComponent(channel)}`;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    document.getElementById('loading').style.display = 'none';
                    if (xhr.status === 200) {
                        document.getElementById('popup-content').innerHTML = xhr.responseText;
                        document.getElementById('popup').style.display = 'block';
                        document.getElementById('overlay').style.display = 'block';
                    } else {
                        document.getElementById('response').innerHTML = '<p style="color: red;">Terjadi kesalahan. Silakan coba lagi.</p>';
                    }
                }
            };
            xhr.send(params);
        }

        function closePopup() {
            document.getElementById('popup-content').innerHTML = '';
            document.getElementById('popup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }
    </script>

</body>
</html>
