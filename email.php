<?php
// Ensure this script is accessed via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo 'failed_method_not_allowed';
    exit;
}

// Default subject and recipient
$subject = 'You Got Message'; 
$to = 'info@designesia.com';  // Recipient's E-mail

// Get data from POST request
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$userMessage = $_POST['message'] ?? ''; // Renamed to avoid conflict with $message variable for email body

// Basic validation for required fields
if (empty($name) || empty($email) || empty($userMessage)) {
    http_response_code(400); // Bad Request
    echo 'failed_missing_fields';
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo 'failed_invalid_email';
    exit;
}

// Sanitize inputs to prevent email header injection
// Remove newline characters from name and email for security in headers
$sanitized_name = str_replace(["\r", "\n"], '', $name);
$sanitized_email = str_replace(["\r", "\n"], '', $email);

// Construct the email body
// Using htmlspecialchars for data displayed in HTML email body is a good practice if message was HTML
// For plain text, ensure newlines are correctly formatted.
$messageBody = "Name: " . htmlspecialchars($name) . "\n"; // Use original name for body
$messageBody .= "Email: " . htmlspecialchars($email) . "\n";
$messageBody .= "Phone: " . htmlspecialchars($phone) . "\n";
$messageBody .= "Message: \n" . htmlspecialchars($userMessage);

// Construct email headers
// Using an array for headers can be cleaner
$headers = [
    'MIME-Version' => '1.0',
    'Content-type' => 'text/plain; charset=UTF-8', // Changed to plain text for simplicity, UTF-8 for charset
    // Use sanitized email in From header
    'From' => $sanitized_name . ' <' . $sanitized_email . '>',
    // Reply-To is often more useful than Return-Path for user-submitted forms
    'Reply-To' => $sanitized_name . ' <' . $sanitized_email . '>',
    'X-Mailer' => 'PHP/' . phpversion()
];

$headerString = '';
foreach ($headers as $key => $value) {
    $headerString .= $key . ': ' . $value . "\r\n";
}

// The mail() function's 4th parameter for additional headers is a string.
// The 5th parameter for additional_parameters (like -f for Return-Path) is platform dependent and best avoided if possible.
// Setting 'From' correctly is usually enough for bounces.

if (mail($to, $subject, $messageBody, $headerString)) {
    echo 'sent';
} else {
    // More detailed error logging for server admin
    error_log("Mail failed to send from email.php: To: $to, Subject: $subject, From: {$headers['From']}");
    echo 'failed_server_error';
}
?>