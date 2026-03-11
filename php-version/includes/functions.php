<?php
// php-version/functions.php

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
    $base_dir = str_replace('/admin/', '/', $base_dir);
} elseif (strpos($base_dir, '/merchant/') !== false) {
    $base_dir = str_replace('/merchant/', '/', $base_dir);
}
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

// Migrations are now handled via php-version/install/migrate.php
// Lightweight auto-migration for critical table stability
function ensure_critical_tables() {
    if (!isInstalled()) return;
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
            'customers' => "CREATE TABLE IF NOT EXISTS customers (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, full_name VARCHAR(255), email VARCHAR(255), phone VARCHAR(50), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY `merchant_customer` (`user_id`, `email`)) ENGINE=InnoDB"
        ];
        foreach ($essential_tables as $sql) { $db->exec($sql); }

        // Ensure critical columns exist in users (for dashboard and admin list)
        $cols = [
            'users' => [
                'is_deleted' => "TINYINT DEFAULT 0",
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
                'business_address_proof_path' => "VARCHAR(255)"
            ],
            'transactions' => [
                'customer_email' => "VARCHAR(255)",
                'customer_name' => "VARCHAR(255)",
                'payment_method' => "VARCHAR(50) DEFAULT 'card'",
                'is_test' => "TINYINT DEFAULT 0",
                'fee_amount' => "DECIMAL(15, 2) DEFAULT 0.00",
                'settled_amount' => "DECIMAL(15, 2) DEFAULT 0.00",
                'currency' => "VARCHAR(10) DEFAULT 'NGN'",
                'gateway_reference' => "VARCHAR(100)"
            ]
        ];
        foreach ($cols as $table => $columns) {
            foreach ($columns as $col => $def) {
                try {
                    $cCheck = $db->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                    if (!$cCheck->fetch()) {
                        $db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
                    }
                } catch (\Throwable $e) {}
            }
        }

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
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) return ['status' => false, 'message' => 'CURL error'];

    // Log the API call - Exceptionally robust to prevent site-wide crashes
    try {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO api_logs (endpoint, method, payload, response, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$endpoint, $method, json_encode($data), $response, (int)$http_code]);
    } catch (\Exception $e) {
        // Fallback check if table doesn't exist
        if (strpos($e->getMessage(), '1146') !== false || strpos($e->getMessage(), 'not found') !== false) {
            // Silently ignore if table missing
        } else {
            error_log("API Log Error: " . $e->getMessage());
        }
    } catch (\Throwable $t) {
        // Ultimate fallback
    }

    $result = json_decode($response, true);
    return $result;
}

/**
 * Ensures a customer has a virtual account and is registered locally.
 * Automates the process of creating/fetching a Paystack customer and
 * generating a dedicated virtual account.
 */
function ensure_virtual_account($userId, $email, $customerData = []) {
    try {
        $db = Database::connect();

        // 1. Fetch Merchant User details
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $merchant = $stmt->fetch();

        if (!$merchant) return ['status' => false, 'message' => 'Merchant not found'];

        // 2. Business Tier & KYC Check (Restrict to Registered/Special and Verified)
        if ($merchant['business_type'] === 'Starter' || $merchant['is_kyc_verified'] != 1) {
            return ['status' => false, 'message' => 'Merchant business tier or KYC status does not support virtual accounts'];
        }

        $is_test = ($merchant['is_test_mode'] == 1);

        // 3. Check if Virtual Account already exists locally
        $stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE user_id = ? AND customer_email = ?");
        $stmt->execute([$userId, $email]);
        $existing = $stmt->fetch();

        if ($existing && !empty($existing['account_number']) && $existing['account_number'] !== '0000000000') {
            return [
                'status' => true,
                'message' => 'Virtual account already exists',
                'data' => [
                    'bank_name' => $existing['bank_name'],
                    'account_number' => $existing['account_number'],
                    'account_name' => $existing['account_name']
                ]
            ];
        }

        // 4. Create/Fetch/Update Customer on Paystack
        $fullName = $customerData['full_name'] ?? '';
        $phone = $customerData['phone'] ?? '';

        if (empty($fullName) || empty($phone)) {
            $stmt = $db->prepare("SELECT full_name, phone FROM customers WHERE user_id = ? AND email = ?");
            $stmt->execute([$userId, $email]);
            $localCust = $stmt->fetch();
            if ($localCust) {
                if (empty($fullName)) $fullName = $localCust['full_name'];
                if (empty($phone)) $phone = $localCust['phone'];
            }
        }

        $names = explode(' ', trim($fullName));
        $firstName = array_shift($names) ?: 'Customer';
        $lastName = implode(' ', $names);

        // Check if customer exists on Paystack
        $checkCustomer = paystack_call('customer/' . $email, 'GET', [], $is_test);

        if ($checkCustomer && $checkCustomer['status']) {
            $customerCode = $checkCustomer['data']['customer_code'];
            // If phone or name missing on Paystack but available locally, update it
            if (empty($checkCustomer['data']['phone']) && !empty($phone)) {
                paystack_call('customer/' . $customerCode, 'PUT', [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone
                ], $is_test);
            }
        } else {
            // Create new customer
            $paystackCustomer = paystack_call('customer', 'POST', [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'metadata' => ['merchant_id' => $userId]
            ], $is_test);

            if (!$paystackCustomer || !$paystackCustomer['status']) {
                return ['status' => false, 'message' => 'Paystack Customer Error: ' . ($paystackCustomer['message'] ?? 'Unknown error')];
            }
            $customerCode = $paystackCustomer['data']['customer_code'];
        }

        // 5. Create Dedicated Virtual Account on Paystack
        $dvaRes = paystack_call('dedicated_account', 'POST', [
            'customer' => $customerCode
        ], $is_test);

        if ($dvaRes && $dvaRes['status']) {
            $acc = $dvaRes['data'];
            $bank = $acc['bank']['name'] ?? 'Virtual Bank';
            $number = $acc['account_number'] ?? '';
            $accName = $acc['account_name'] ?? $merchant['business_name'];

            if (!empty($number)) {
                if ($existing) {
                    $stmt = $db->prepare("UPDATE virtual_accounts SET bank_name = ?, account_number = ?, account_name = ? WHERE id = ?");
                    $stmt->execute([$bank, $number, $accName, $existing['id']]);
                } else {
                    $stmt = $db->prepare("INSERT INTO virtual_accounts (user_id, bank_name, account_number, account_name, customer_email) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $bank, $number, $accName, $email]);
                }

                // 6. Ensure customer exists locally
                $stmt = $db->prepare("INSERT INTO customers (user_id, full_name, email, phone) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), phone = VALUES(phone)");
                $stmt->execute([$userId, trim($firstName . ' ' . $lastName), $email, $customerData['phone'] ?? '']);

                return [
                    'status' => true,
                    'message' => 'Virtual account generated successfully',
                    'data' => [
                        'bank_name' => $bank,
                        'account_number' => $number,
                        'account_name' => $accName
                    ]
                ];
            } else {
                return ['status' => false, 'message' => 'Account created but number not yet assigned by Paystack'];
            }
        } else {
            return ['status' => false, 'message' => 'Paystack DVA Error: ' . ($dvaRes['message'] ?? 'Unknown error')];
        }
    } catch (\Throwable $e) {
        return ['status' => false, 'message' => 'System Error: ' . $e->getMessage()];
    }
}

function log_transaction_event($transactionId, $type, $desc) {
    $db = Database::connect();
    $stmt = $db->prepare("INSERT INTO transaction_timeline (transaction_id, event_type, description) VALUES (?, ?, ?)");
    return $stmt->execute([$transactionId, $type, $desc]);
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

function log_ledger_entry($userId, $amount, $type, $category, $desc) {
    $db = Database::connect();

    // Get current balance
    $stmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $current = $stmt->fetch()['wallet_balance'];

    $newBalance = ($type === 'credit') ? ($current + $amount) : ($current - $amount);

    // Update user balance
    $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $userId]);

    // Log entry
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
        // Fallback to native mail if SMTP not fully configured
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
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;

        // Recipients
        $mail->setFrom($smtp_from, $site_name);
        $mail->addAddress($to);

        // Content
        $logo_html = '';
        if ($logo) {
            $logo_url = BASE_URL . 'uploads/' . $logo;
            $logo_html = "<div style='text-align: center; margin-bottom: 20px;'><img src='$logo_url' alt='$site_name' style='height: 60px; width: auto; max-width: 200px;'></div>";
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
        <div style='background-color: #f9fafb; padding: 40px 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);'>
                <div style='padding: 32px; background-color: #ffffff; border-bottom: 1px solid #f3f4f6; text-align: center;'>
                    $logo_html
                </div>
                <div style='padding: 40px; line-height: 1.6; color: #374151;'>
                    $body
                </div>
                <div style='padding: 32px; background-color: #f9fafb; text-align: center; font-size: 12px; color: #9ca3af;'>
                    <p style='margin-bottom: 8px;'>&copy; " . date('Y') . " $site_name. All rights reserved.</p>
                    <p>You are receiving this email because you have an account with $site_name.</p>
                </div>
            </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
