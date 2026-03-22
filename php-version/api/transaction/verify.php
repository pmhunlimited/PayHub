<?php
// php-version/api/transaction/verify.php
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

// Extract reference from URL (assuming rewrite or simple query)
$ref = $_GET['reference'] ?? '';

if (!$ref) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Missing reference']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ? AND user_id = ?");
$stmt->execute([$ref, $user['id']]);
$tx = $stmt->fetch();

if (!$tx) {
    http_response_code(404);
    echo json_encode(['status' => false, 'message' => 'Transaction not found']);
    exit;
}

echo json_encode([
    'status' => true,
    'message' => 'Transaction retrieved',
    'data' => [
        'id' => $tx['id'],
        'status' => $tx['status'],
        'reference' => $tx['reference'],
        'amount' => $tx['amount'] * 100,
        'customer' => [
            'email' => $tx['customer_email']
        ],
        'gateway_response' => $tx['status'] === 'success' ? 'Successful' : 'Pending',
        'created_at' => $tx['created_at']
    ]
]);
