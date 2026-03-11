<?php
// php-version/api/virtual-accounts.php
require_once '../includes/functions.php';

header('Content-Type: application/json');

$headers = getallheaders();
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$secret_key = str_replace('Bearer ', '', $auth);

if (!$secret_key) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized: Missing API Key']);
    exit;
}

$db = Database::connect();
// Check both live and test keys
$stmt = $db->prepare("SELECT * FROM users WHERE secret_key = ? OR test_secret_key = ?");
$stmt->execute([$secret_key, $secret_key]);
$merchant = $stmt->fetch();

if (!$merchant) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized: Invalid API Key']);
    exit;
}

/**
 * GET /api/virtual-accounts.php
 * Fetch virtual accounts for the authenticated merchant.
 * Headers: Authorization: Bearer <Secret Key>
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT bank_name, account_number, account_name, customer_email, created_at FROM virtual_accounts WHERE user_id = ? AND account_number IS NOT NULL AND account_number != '' ORDER BY created_at DESC");
    $stmt->execute([$merchant['id']]);
    $accounts = $stmt->fetchAll();

    // Transform created_at to avoid invalid dates
    foreach ($accounts as &$acc) {
        $time = strtotime($acc['created_at']);
        $acc['created_at_formatted'] = ($time && $time > 0) ? date('Y-m-d H:i:s', $time) : date('Y-m-d H:i:s');
    }

    echo json_encode(['status' => true, 'data' => $accounts]);
} else {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method not allowed']);
}
