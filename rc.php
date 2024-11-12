<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Check</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link rel="icon" href="./icon.png">

    <style>
        /* Sticky Footer CSS */
        html, body {
            height: 100%;
            margin: 0; 
        }
        .wrapper {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
        }
        footer {
            background-color: #007bf5; /* Dark background */
            color: white; /* White text */
            text-align: center;
            padding: 15px 0; /* Vertical padding */
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="container mt-5">
                <h1 class="text-center">Resend Callback Cronosengine</h1>
                <form id="apiCheckForm" action="process.php" method="post">
                    <div class="form-group">
                        <label for="projectSelect">Select Project:</label>
                        <select class="form-control" id="projectSelect" name="projectSelect">
                            <option value="" disabled selected>Select a project</option>
                            <?php
                            // Include the project credentials array
                            $projects = include('credentials.php'); // This file should contain the $projects array

                            // Loop through the projects array to create options
                            foreach ($projects as $name => $credentials) {
                                echo '<option value="' . $credentials["api_key"] . '|' . $credentials["api_token"] . '">' . $name . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="key">API Key:</label>
                        <input type="text" class="form-control" id="key" name="key" required>
                    </div>
                    <div class="form-group">
                        <label for="token">API Token:</label>
                        <input type="text" class="form-control" id="token" name="token" required>
                    </div>
                    <div class="form-group">
                        <label for="ids">IDs (comma-separated):</label>
                        <textarea class="form-control" id="ids" name="ids" rows="5" required placeholder='"id1", "id2", id3'></textarea>
                        <small class="form-text text-muted">Please enter IDs in the format: "id1", "id2", id3</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Check API</button>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <footer>
            <p>&copy; <?php echo date("Y"); ?> Antzein. All Rights Reserved.</p>
        </footer>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#projectSelect').select2();

            // Update credentials when a project is selected
            $('#projectSelect').on('change', function() {
                const selectedValue = $(this).val();
                if (selectedValue) {
                    const [key, token] = selectedValue.split("|");
                    $('#key').val(key);
                    $('#token').val(token);
                } else {
                    $('#key').val('');
                    $('#token').val('');
                }
            });

            // Function to format IDs
            function formatIDs(ids) {
                // Split the input by commas
                let idArray = ids.split(',').map(id => id.trim()); // Trim whitespace
                // Remove quotes from each ID
                idArray = idArray.map(id => id.replace(/^["']|["']$/g, '')); // Remove surrounding quotes
                // Join back into a formatted string
                return idArray.join(', ');
            }

            // Form submission validation
            $('#apiCheckForm').on('submit', function(event) {
                const ids = $('#ids').val();
                // Update IDs formatting
                const formattedIDs = formatIDs(ids);
                $('#ids').val(formattedIDs); // Set the formatted IDs back to the textarea

                // Validation: Check if IDs are empty after formatting
                if (!formattedIDs) {
                    alert('Please enter valid IDs in the format: "id1", "id2", id3');
                    event.preventDefault(); // Prevent form submission
                }
            });
        });
    </script>
</body>
</html>
