<?php
// php-version/api/transaction/initialize.php
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$headers = getallheaders();
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$auth || strpos($auth, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$sk = str_replace('Bearer ', '', $auth);
$db = Database::connect();
$stmt = $db->prepare("SELECT id FROM users WHERE secret_key = ? OR test_secret_key = ?");
$stmt->execute([$sk, $sk]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Invalid Secret Key']);
    exit;
}

$email = sanitize($_POST['email'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);

if (!$email || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Missing email or invalid amount']);
    exit;
}

$ref = 'PH_' . bin2hex(random_bytes(8));

// Create transaction in pending state
$stmt = $db->prepare("INSERT INTO transactions (user_id, reference, amount, customer_email, status) VALUES (?, ?, ?, ?, 'pending')");
$stmt->execute([$user['id'], $ref, $amount, $email]);
$txId = $db->lastInsertId();

log_transaction_event($txId, 'initiated', "Transaction initiated via API");

$checkoutUrl = BASE_URL . "checkout.php?ref=$ref&amount=$amount&email=" . urlencode($email);

echo json_encode([
    'status' => true,
    'message' => 'Transaction initialized',
    'data' => [
        'authorization_url' => $checkoutUrl,
        'access_code' => $ref,
        'reference' => $ref
    ]
]);
