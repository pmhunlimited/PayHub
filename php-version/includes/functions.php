<?php
// php-version/includes/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Security Headers
$isCheckoutEmbed = (strpos($_SERVER['SCRIPT_NAME'] ?? '', 'checkout.php') !== false) && (isset($_GET['embed']) && $_GET['embed'] === '1');
if (!$isCheckoutEmbed) {
    header("X-Frame-Options: SAMEORIGIN");
}
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Define base path
define('BASE_PATH', dirname(__DIR__) . '/');
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '/';
$base_dir = str_replace(basename($script_name), '', $script_name);
// Ensure we get the root of the php-version directory
if (strpos($base_dir, '/admin/') !== false) {
    $base_dir = explode('/admin/', $base_dir)[0] . '/';
} elseif (strpos($base_dir, '/merchant/') !== false) {
    $base_dir = explode('/merchant/', $base_dir)[0] . '/';
} elseif (strpos($base_dir, '/api/') !== false) {
    $base_dir = explode('/api/', $base_dir)[0] . '/';
}
// Remove double slashes
$base_dir = preg_replace('#/+#', '/', $base_dir);
define('BASE_URL', $protocol . "://" . $host . $base_dir);

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/db.php';

// Include Composer Autoloader for PHPMailer
if (file_exists(BASE_PATH . 'vendor/autoload.php')) {
    require_once BASE_PATH . 'vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Lightweight auto-migration for critical table stability
function ensure_critical_tables() {
    if (!isInstalled()) return;

    // Quick version check to avoid redundant DB calls on every request
    $version = '1.0.6';
    if (getConfig('sys_db_version') === $version) return;

    try {
        $db = Database::connect();
        $essential_tables = [
            'config' => "CREATE TABLE IF NOT EXISTS config (`key` VARCHAR(100) PRIMARY KEY, `value` TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'api_logs' => "CREATE TABLE IF NOT EXISTS api_logs (id INT AUTO_INCREMENT PRIMARY KEY, endpoint VARCHAR(255), method VARCHAR(10), payload TEXT, response TEXT, status_code INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'users' => "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL, business_name VARCHAR(255), role ENUM('admin', 'merchant') DEFAULT 'merchant', wallet_balance DECIMAL(15, 2) DEFAULT 0.00, is_kyc_verified TINYINT DEFAULT 0, is_suspended TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'virtual_accounts' => "CREATE TABLE IF NOT EXISTS virtual_accounts (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, bank_name VARCHAR(255), account_number VARCHAR(50), account_name VARCHAR(255), customer_email VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'transactions' => "CREATE TABLE IF NOT EXISTS transactions (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, reference VARCHAR(100) UNIQUE, amount DECIMAL(15,2), status VARCHAR(20) DEFAULT 'pending', customer_email VARCHAR(255), payment_method VARCHAR(50) DEFAULT 'card', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'ledger' => "CREATE TABLE IF NOT EXISTS ledger (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, amount DECIMAL(15, 2), type ENUM('credit', 'debit'), category VARCHAR(50), description TEXT, balance_after DECIMAL(15, 2), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'disputes' => "CREATE TABLE IF NOT EXISTS disputes (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, transaction_id INT, reason TEXT, status ENUM('open', 'won', 'lost') DEFAULT 'open', evidence_path VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'customers' => "CREATE TABLE IF NOT EXISTS customers (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, full_name VARCHAR(255), email VARCHAR(255), phone VARCHAR(50), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY `merchant_customer` (`user_id`, `email`)) ENGINE=InnoDB",
            'transaction_timeline' => "CREATE TABLE IF NOT EXISTS transaction_timeline (id INT AUTO_INCREMENT PRIMARY KEY, transaction_id INT, event_type VARCHAR(50), description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'payouts' => "CREATE TABLE IF NOT EXISTS payouts (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, amount DECIMAL(15,2), status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending', reference VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'subscriptions' => "CREATE TABLE IF NOT EXISTS subscriptions (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, customer_email VARCHAR(255), plan_name VARCHAR(100), amount DECIMAL(15,2), status VARCHAR(20), next_billing_date DATE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'support_tickets' => "CREATE TABLE IF NOT EXISTS support_tickets (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, subject VARCHAR(255), message TEXT, status ENUM('open', 'closed') DEFAULT 'open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'blog_posts' => "CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), content TEXT, author VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'webhook_logs' => "CREATE TABLE IF NOT EXISTS webhook_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, event_type VARCHAR(100), payload TEXT, response_code INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
            'staff_roles' => "CREATE TABLE IF NOT EXISTS staff_roles (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, role VARCHAR(50), permissions TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB"
        ];
        foreach ($essential_tables as $sql) { $db->exec($sql); }

        // Ensure critical columns exist
        $cols = [
            'users' => [
                'is_deleted' => "TINYINT DEFAULT 0",
                'settlement_bank' => "VARCHAR(100)",
                'settlement_account' => "VARCHAR(50)",
                'parent_id' => "INT DEFAULT NULL",
                'fee_percentage' => "DECIMAL(5, 2) DEFAULT NULL",
                'fee_flat' => "DECIMAL(15, 2) DEFAULT NULL",
                'is_suspended' => "TINYINT DEFAULT 0",
                'registration_number' => "VARCHAR(100)",
                'bn_number' => "VARCHAR(100)",
                'tin' => "VARCHAR(100)",
                'business_type' => "VARCHAR(50)",
                'id_card_path' => "VARCHAR(255)",
                'utility_bill_path' => "VARCHAR(255)",
                'liveliness_path' => "VARCHAR(255)",
                'cac_cert_path' => "VARCHAR(255)",
                'cac_form_path' => "VARCHAR(255)",
                'memart_path' => "VARCHAR(255)",
                'bn_cert_path' => "VARCHAR(255)",
                'bn_form_path' => "VARCHAR(255)",
                'ngo_form_path' => "VARCHAR(255)",
                'ngo_constitution_path' => "VARCHAR(255)",
                'gov_auth_letter_path' => "VARCHAR(255)",
                'gov_gazette_path' => "VARCHAR(255)",
                'business_address_proof_path' => "VARCHAR(255)",
                'is_test_mode' => "TINYINT DEFAULT 1",
                'webhook_url' => "VARCHAR(255)"
            ],
            'transactions' => [
                'customer_email' => "VARCHAR(255)",
                'customer_name' => "VARCHAR(255)",
                'payment_method' => "VARCHAR(50) DEFAULT 'card'",
                'is_test' => "TINYINT DEFAULT 0",
                'fee_amount' => "DECIMAL(15, 2) DEFAULT 0.00",
                'settled_amount' => "DECIMAL(15, 2) DEFAULT 0.00",
                'currency' => "VARCHAR(10) DEFAULT 'NGN'",
                'gateway_reference' => "VARCHAR(100)",
                'invoice_id' => "INT DEFAULT NULL",
                'metadata' => "TEXT"
            ],
            'invoices' => [
                'reference' => "VARCHAR(100)",
                'customer_name' => "VARCHAR(255)",
                'description' => "TEXT",
                'status' => "ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending'"
            ],
            'virtual_accounts' => [
                'customer_email' => "VARCHAR(255)",
                'account_name' => "VARCHAR(255)"
            ]
        ];
        foreach ($cols as $table => $columns) {
            foreach ($columns as $col => $def) {
                try {
                    $cCheck = $db->query("SHOW COLUMNS FROM `$table` LIKE '$col'")->fetch();
                    if (!$cCheck) {
                        $db->exec("ALTER TABLE `$table` ADD `$col` $def");
                    }
                } catch (\Throwable $e) {}
            }
        }

        // Set version flag to skip future checks until next code update
        $stmt = $db->prepare("INSERT INTO config (`key`, `value`) VALUES ('sys_db_version', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $stmt->execute([$version]);

    } catch (\Throwable $e) {
        error_log("Critical Table Migration Error: " . $e->getMessage());
    }
}
ensure_critical_tables();

function isInstalled() {
    return file_exists(__DIR__ . '/config.php');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

function generateApiKey($prefix) {
    return $prefix . bin2hex(random_bytes(16));
}

function getAuthUser() {
    if (!isLoggedIn()) return null;
    try {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (\Throwable $e) {
        return null;
    }
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Robustly get input for API requests, merging GET, POST, and JSON body.
 */
function get_api_input() {
    $input = [];
    // 1. Get query params
    $input = array_merge($input, $_GET);
    // 2. Get POST params
    $input = array_merge($input, $_POST);
    // 3. Get JSON body
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) {
        $input = array_merge($input, $json);
    }
    return $input;
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getConfig($key, $default = '') {
    try {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT `value` FROM config WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (\Throwable $e) {
        return $default;
    }
}

function get_stats($userId) {
    $db = Database::connect();
    $stmt = $db->prepare("
        SELECT 
            SUM(amount) as total_volume,
            COUNT(*) as transaction_count,
            (SELECT wallet_balance FROM users WHERE id = ?) as balance
        FROM transactions 
        WHERE user_id = ? AND status = 'success'
    ");
    $stmt->execute([$userId, $userId]);
    $stats = $stmt->fetch();

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = $stmt->fetch();

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND status = 'success'");
    $stmt->execute([$userId]);
    $success = $stmt->fetch();

    $successRate = $total['count'] > 0 ? number_format(($success['count'] / $total['count']) * 100, 1) : '100';

    return [
        'total_volume' => $stats['total_volume'] ?? 0,
        'transaction_count' => $stats['transaction_count'] ?? 0,
        'balance' => $stats['balance'] ?? 0,
        'success_rate' => $successRate . '%'
    ];
}

/**
 * Paystack Integration Helpers
 */
function paystack_call($endpoint, $method = 'GET', $data = [], $is_test = null) {
    if ($is_test === null) {
        $user = getAuthUser();
        $is_test = $user ? ($user['is_test_mode'] == 1) : false;
    }

    $secret_key = $is_test ? getConfig('paystack_test_secret_key') : getConfig('paystack_secret_key');
    if (!$secret_key) $secret_key = getConfig('paystack_secret_key'); // Fallback to live key if test not set

    if (!$secret_key) return ['status' => false, 'message' => 'Paystack not configured'];

    $url = "https://api.paystack.co/" . $endpoint;
    $headers = [
        "Authorization: Bearer " . $secret_key,
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT' || $method === 'PATCH' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) return ['status' => false, 'message' => 'CURL error'];

    // Log the API call
    try {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO api_logs (endpoint, method, payload, response, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$endpoint, $method, json_encode($data), $response, (int)$http_code]);
    } catch (\Throwable $t) {}

    $result = json_decode($response, true);
    return $result;
}

/**
 * Ensures a customer has a virtual account and is registered locally.
 */
function ensure_virtual_account($userId, $email, $customerData = [], $is_test = null) {
    try {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $merchant = $stmt->fetch();
        if (!$merchant) return ['status' => false, 'message' => 'Merchant not found'];

        if ($is_test === null) {
            $is_test = ($merchant['is_test_mode'] == 1);
        }

        $stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE user_id = ? AND customer_email = ?");
        $stmt->execute([$userId, $email]);
        $existing = $stmt->fetch();

        if ($existing && !empty($existing['account_number']) && $existing['account_number'] !== '0000000000') {
            return ['status' => true, 'message' => 'Account exists', 'data' => $existing];
        }

        $fullName = $customerData['full_name'] ?? 'Customer';
        $phone = $customerData['phone'] ?? '';

        // Paystack Customer logic
        $paystackCustomer = paystack_call('customer', 'POST', [
            'email' => $email,
            'first_name' => explode(' ', $fullName)[0],
            'last_name' => explode(' ', $fullName)[1] ?? 'Merchant',
            'phone' => $phone,
            'metadata' => ['merchant_id' => $userId]
        ], $is_test);

        if (!$paystackCustomer || !$paystackCustomer['status']) {
            return ['status' => false, 'message' => 'Paystack Customer Error: ' . ($paystackCustomer['message'] ?? 'Unknown error')];
        }
        $customerCode = $paystackCustomer['data']['customer_code'];

        $dvaRes = paystack_call('dedicated_account', 'POST', ['customer' => $customerCode], $is_test);

        if ($dvaRes && $dvaRes['status']) {
            $acc = $dvaRes['data'];
            $bank = $acc['bank']['name'] ?? 'Virtual Bank';
            $number = $acc['account_number'] ?? '';
            $accName = $acc['account_name'] ?? $merchant['business_name'];

            if (!empty($number)) {
                $stmt = $db->prepare("INSERT INTO virtual_accounts (user_id, bank_name, account_number, account_name, customer_email) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE account_number = VALUES(account_number)");
                $stmt->execute([$userId, $bank, $number, $accName, $email]);
                return ['status' => true, 'message' => 'VA generated', 'data' => ['account_number' => $number, 'bank_name' => $bank]];
            }
        }
        return ['status' => false, 'message' => 'Failed to generate VA'];
    } catch (\Throwable $e) {
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

function log_transaction_event($transactionId, $type, $desc) {
    try {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO transaction_timeline (transaction_id, event_type, description) VALUES (?, ?, ?)");
        return $stmt->execute([$transactionId, $type, $desc]);
    } catch (\Throwable $e) {
        return false;
    }
}

function calculate_fees($amount, $is_international = false, $userId = null) {
    $percent = null;
    $flat = null;

    if ($userId) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT fee_percentage, fee_flat FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if ($user) {
            $percent = $user['fee_percentage'];
            $flat = $user['fee_flat'];
        }
    }

    if ($is_international) {
        if ($percent === null) $percent = (float)getConfig('international_fee_percent', '3.9');
        if ($flat === null) $flat = (float)getConfig('international_fee_flat', '100');
        $fee = ($amount * ($percent / 100)) + $flat;
        return $fee;
    } else {
        if ($percent === null) $percent = (float)getConfig('transaction_fee_percent', '1.5');
        if ($flat === null) {
            $flat = ($amount < 2500) ? 0 : (float)getConfig('transaction_fee_flat', '100');
        }
        $cap = (float)getConfig('transaction_fee_cap', '2000');

        $fee = ($amount * ($percent / 100)) + $flat;
        if ($fee > $cap) $fee = $cap;
        return $fee;
    }
}

function log_ledger_entry($userId, $amount, $type, $category, $desc, $is_test = false) {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $current = (float)$stmt->fetch()['wallet_balance'];

    if ($is_test) {
        $stmt = $db->prepare("INSERT INTO ledger (user_id, amount, type, category, description, balance_after) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $amount, $type, $category, "[TEST] " . $desc, $current]);
    }

    $newBalance = ($type === 'credit') ? ($current + $amount) : ($current - $amount);
    $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")->execute([$newBalance, $userId]);
    $stmt = $db->prepare("INSERT INTO ledger (user_id, amount, type, category, description, balance_after) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $amount, $type, $category, $desc, $newBalance]);
}

function sendEmail($to, $subject, $body) {
    $smtp_host = getConfig('smtp_host');
    $smtp_port = getConfig('smtp_port');
    $smtp_user = getConfig('smtp_user');
    $smtp_pass = getConfig('smtp_pass');
    $smtp_from = getConfig('smtp_from');
    $site_name = getConfig('site_name', 'Payhub');
    $logo = getConfig('site_logo');

    if (!$smtp_host || !$smtp_user) {
        $logo_html = '';
        if ($logo) {
            $logo_url = BASE_URL . 'uploads/' . $logo;
            $logo_html = "<div style='text-align: center; margin-bottom: 20px;'><img src='$logo_url' alt='$site_name' style='height: 60px; width: auto; max-width: 200px;'></div>";
        }
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: $site_name <$smtp_from>\r\n";
        $full_body = "<div style='padding: 40px;'>$logo_html $body</div>";
        return mail($to, $subject, $full_body, $headers);
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;
        $mail->setFrom($smtp_from, $site_name);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "<div style='background-color: #f9fafb; padding: 40px 0; font-family: sans-serif;'><div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; padding: 40px;'>$body</div></div>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
