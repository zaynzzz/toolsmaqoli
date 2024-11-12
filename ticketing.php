<?php
$credentials = require 'credentials.php';

// Function to send ticket request
// Function to send ticket request
function send_ticket_request($reference, $merchantRef, $accountNumber, $rrn, $complaint, $attachment, $environment) {
    global $credentials;

    // Get API credentials based on the selected environment
    if (!isset($credentials[$environment])) {
        die("Invalid environment selected.");
    }

    $apiKey = $credentials[$environment]['api_key'];
    $apiSecret = $credentials[$environment]['api_token'];

    // Define the endpoint URL
    $url = ($environment === 'sandbox') ? 'http://188.166.186.24/api/ticket' : 'http://188.166.186.24/api/ticket';

    // Request headers
    $headers = [
        'On-Key: ' . $apiKey,
        'On-Secret: ' . $apiSecret
    ];

    // Request body
    $body = [
        'reference' => $reference,
        'merchantRef' => $merchantRef,
        'accountNumber' => $accountNumber,
        'rrn' => $rrn,
        'complaint' => $complaint,
        'attachment' => $attachment
    ];

    // Generate signature
    $signature = hash_hmac('sha512', json_encode($body), $apiSecret);
    $headers[] = 'On-Signature: ' . $signature;

    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute request
    $response = curl_exec($ch);
    
    // Handle errors
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    
    curl_close($ch);

    // Return the decoded response
    return json_decode($response, true);
}


// Sample usage (You can call this function when form is submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $reference = $_POST['reference'];
    $merchantRef = $_POST['merchantRef'];
    $accountNumber = $_POST['accountNumber'];
    $rrn = $_POST['rrn'];
    $complaint = $_POST['complaint'];
    $attachment = $_POST['attachment'];
    $environment = $_POST['environment'];  // 'sandbox' or 'production'

    // Call function to send ticket request
    $response = send_ticket_request($reference, $merchantRef, $accountNumber, $rrn, $complaint, $attachment, $environment);

    // Output response for debugging (Optional)
    echo '<pre>';
    print_r($response);
    echo '</pre>';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>API Key Selection</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
        }
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        select, input, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
        }
        select:focus, input:focus, button:focus {
            outline: none;
            border-color: #4CAF50;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Submit a Ticket</h2>

        <!-- Ticket Form -->
        <form id="ticketForm">
            <!-- Project Selection -->
            <label for="projectSelect">Select Project</label>
            <select id="projectSelect" name="project" required>
            <option value="">--Select Project--</option>
            <?php foreach ($credentials as $projectName => $projectData): ?>
                <option value="<?php echo htmlspecialchars($projectName); ?>"
                    data-key="<?php echo htmlspecialchars($projectData['api_key']); ?>"
                    data-token="<?php echo htmlspecialchars($projectData['api_token']); ?>">
                    <?php echo htmlspecialchars($projectName); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div>
            <label for="api_key">API Key:</label>
            <input type="text" id="api_key" name="api_key" readonly>
        </div>

        <div>
            <label for="api_token">API Token:</label>
            <input type="text" id="api_token" name="api_token" readonly>
        </div>

            <label for="reference">Reference:</label>
            <input type="text" id="reference" name="reference">

            <label for="merchantRef">Merchant Reference:</label>
            <input type="text" id="merchantRef" name="merchantRef">

            <label for="accountNumber">Account Number:</label>
            <input type="text" id="accountNumber" name="accountNumber">

            <label for="rrn">RRN:</label>
            <input type="text" id="rrn" name="rrn">

            <label for="complaint">Complaint:</label>
            <textarea id="complaint" name="complaint"></textarea>

            <label for="attachment">Attachment URL:</label>
            <input type="url" id="attachment" name="attachment">

            <button type="submit">Submit Ticket</button>
        </form>

        <!-- Response Section -->
        <div id="response" class="response">
            <h3>Response:</h3>
            <pre id="responseOutput"></pre>
        </div>
    </div>

    <script>
        // Ambil elemen select dan input untuk key dan token
        const projectSelect = document.getElementById('projectSelect');
        const apiKeyInput = document.getElementById('api_key');
        const apiTokenInput = document.getElementById('api_token');

        // Menambahkan event listener untuk saat ada perubahan pilihan project
        projectSelect.addEventListener('change', function () {
            // Ambil data-key dan data-token dari option yang dipilih
            const selectedOption = projectSelect.options[projectSelect.selectedIndex];
            const apiKey = selectedOption.getAttribute('data-key');
            const apiToken = selectedOption.getAttribute('data-token');
            
            // Masukkan ke input field
            apiKeyInput.value = apiKey;
            apiTokenInput.value = apiToken;
        });

        // Handle the form submission
document.getElementById('ticketForm').addEventListener('submit', function(event) {
    event.preventDefault();

    // Get the form values
    const reference = document.getElementById('reference').value;
    const merchantRef = document.getElementById('merchantRef').value;
    const accountNumber = document.getElementById('accountNumber').value;
    const rrn = document.getElementById('rrn').value;
    const complaint = document.getElementById('complaint').value;
    const attachment = document.getElementById('attachment').value;
    const apiKey = document.getElementById('api_key').value;
    const apiSecret = document.getElementById('api_token').value;

    // Ensure a project is selected
    if (!apiKey || !apiSecret) {
        alert('Please select a project and ensure the API key and secret are filled.');
        return;
    }

    // Make the API request using Fetch
    fetch('send_ticket_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            reference: reference,
            merchantRef: merchantRef,
            accountNumber: accountNumber,
            rrn: rrn,
            complaint: complaint,
            attachment: attachment,
            apiKey: apiKey,
            apiSecret: apiSecret
        })
    })
    .then(response => response.json())
    .then(data => {
        // Display response data in a formatted way
        document.getElementById('responseOutput').textContent = JSON.stringify(data, null, 4);
        document.getElementById('response').style.display = 'block';

        // Check for a successful API response and display appropriate message
        if (data.success) {
            alert('API Request Successful!');
        } else {
            alert('API Request Failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
    });
});

    </script>

    <script>
        // Define the API keys for each project
        const apiKeys = {
            sandbox: {
                apiKey: 'YOUR_SANDBOX_API_KEY',
                apiSecret: 'YOUR_SANDBOX_SECRET_TOKEN'
            },
            production: {
                apiKey: 'YOUR_PRODUCTION_API_KEY',
                apiSecret: 'YOUR_PRODUCTION_SECRET_TOKEN'
            }
        };

        // Update the API Key and Secret when a project is selected
        document.getElementById('project').addEventListener('change', function() {
            const selectedProject = this.value;
            const apiKeyInput = document.getElementById('apiKey');
            const apiSecretInput = document.getElementById('apiSecret');

            if (selectedProject) {
                // Populate the API Key and Secret for the selected project
                apiKeyInput.value = apiKeys[selectedProject].apiKey;
                apiSecretInput.value = apiKeys[selectedProject].apiSecret;
            } else {
                // Clear the inputs if no project is selected
                apiKeyInput.value = '';
                apiSecretInput.value = '';
            }
        });

        // Handle the form submission
        document.getElementById('ticketForm').addEventListener('submit', function(event) {
            event.preventDefault();

            // Get the form values
            const reference = document.getElementById('reference').value;
            const merchantRef = document.getElementById('merchantRef').value;
            const accountNumber = document.getElementById('accountNumber').value;
            const rrn = document.getElementById('rrn').value;
            const complaint = document.getElementById('complaint').value;
            const attachment = document.getElementById('attachment').value;
            const apiKey = document.getElementById('apiKey').value;
            const apiSecret = document.getElementById('apiSecret').value;

            // Ensure a project is selected
            if (!apiKey || !apiSecret) {
                alert('Please select a project and ensure the API key and secret are filled.');
                return;
            }

            // Make the API request using Fetch
            fetch('send_ticket_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reference: reference,
                    merchantRef: merchantRef,
                    accountNumber: accountNumber,
                    rrn: rrn,
                    complaint: complaint,
                    attachment: attachment,
                    apiKey: apiKey,
                    apiSecret: apiSecret
                })
            })
            .then(response => response.json())
            .then(data => {
                // Display the response data
                document.getElementById('responseOutput').textContent = JSON.stringify(data, null, 4);
                document.getElementById('response').style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
