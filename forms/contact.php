<?php
/**
 * Advanced Contact Form Handler with Telegram Bot Integration (cURL version)
 * More reliable version using cURL for HTTP requests
 */
date('Y-m-d H:i:s');
// Telegram Bot Configuration
$telegram_bot_token = '7512898128:AAFgFRIxcHB38waNzZYp6b4Oa_S9aja76JI'; // Замените на токен вашего бота
$telegram_chat_id = '-1002924544668';     // Замените на ID чата/канала

/**
 * Send message to Telegram using cURL
 */
function sendToTelegramCurl($bot_token, $chat_id, $message) {
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

    $data = array(
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($result === false || $http_code !== 200) {
        return false;
    }

    return json_decode($result, true);
}

/**
 * Validate and sanitize input
 */
function validateInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Log errors for debugging
 */
function logError($message) {
    $log_file = 'contact_form_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
}

// Set content type for JSON response
header('Content-Type: application/json');

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method!');
    }

    // Validate and sanitize input data
    $name = validateInput($_POST['name'] ?? '');
    $email = validateInput($_POST['email'] ?? '');
    $subject = validateInput($_POST['subject'] ?? '');
    $message = validateInput($_POST['message'] ?? '');

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        throw new Exception('All fields are required!');
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format!');
    }

    // Check message length
    if (strlen($message) > 1000) {
        throw new Exception('Message is too long! Maximum 1000 characters allowed.');
    }

    // Format message for Telegram with better formatting
    $telegram_message = "🔔 <b>New Portfolio Contact</b>\n";
    $telegram_message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $telegram_message .= "👤 <b>Name:</b> {$name}\n";
    $telegram_message .= "📧 <b>Email:</b> {$email}\n";
    $telegram_message .= "📋 <b>Subject:</b> {$subject}\n\n";
    $telegram_message .= "💬 <b>Message:</b>\n<i>{$message}</i>\n\n";
    $telegram_message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $telegram_message .= "🕐 <b>Received:</b> " . date('Y-m-d H:i:s') . " (UTC)";

    // Send to Telegram
    $result = sendToTelegramCurl($telegram_bot_token, $telegram_chat_id, $telegram_message);

    // Check if message was sent successfully
    if ($result && isset($result['ok']) && $result['ok'] === true) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your message has been sent successfully.'
        ]);
    } else {
        logError("Telegram API Error: " . json_encode($result));
        throw new Exception('Failed to send message. Please try again later.');
    }

} catch (Exception $e) {
    logError("Form Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>