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
header("X-Frame-Options: SAMEORIGIN");
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

// Simple Migration Helper
function check_migrations() {
    if (!isInstalled()) return;
    try {
        $db = Database::connect();

        // Ensure we are working on the right table structure
        $tables = [
            'users' => [
                'phone_number' => "VARCHAR(50)",
                'country' => "VARCHAR(100) DEFAULT 'Nigeria'",
                'is_deleted' => "TINYINT DEFAULT 0",
                'parent_id' => "INT DEFAULT NULL",
                'kyc_notes' => "TEXT",
                'require_payout_review' => "TINYINT DEFAULT 0",
                'is_test_mode' => "TINYINT DEFAULT 1",
                'payout_method' => "ENUM('manual', 'automated') DEFAULT 'manual'",
                'settlement_currency' => "ENUM('NGN', 'USD') DEFAULT 'NGN'",
                'id_type' => "VARCHAR(100)",
                'id_path' => "VARCHAR(255)",
                'id_card_path' => "VARCHAR(255)",
                'bvn' => "VARCHAR(20)",
                'residential_address' => "TEXT",
                'rc_number' => "VARCHAR(100)",
                'tin' => "VARCHAR(100)",
                'cac_cert_path' => "VARCHAR(255)",
                'cac_form_path' => "VARCHAR(255)",
                'memart_path' => "VARCHAR(255)",
                'business_address_proof_path' => "VARCHAR(255)",
                'bn_number' => "VARCHAR(100)",
                'bn_cert_path' => "VARCHAR(255)",
                'bn_form_path' => "VARCHAR(255)",
                'ngo_form_path' => "VARCHAR(255)",
                'ngo_constitution_path' => "VARCHAR(255)",
                'gov_auth_letter_path' => "VARCHAR(255)",
                'gov_gazette_path' => "VARCHAR(255)",
                'id_expiry_date' => "DATE",
                'utility_bill_path' => "VARCHAR(255)",
                'liveliness_path' => "VARCHAR(255)",
                'settlement_bank_code' => "VARCHAR(10)",
                'test_public_key' => "VARCHAR(255)",
                'test_secret_key' => "VARCHAR(255)"
            ],
            'transactions' => [
                'currency' => "VARCHAR(10) DEFAULT 'NGN'",
                'fee_amount' => "DECIMAL(15, 2) DEFAULT 0.00",
                'settled_amount' => "DECIMAL(15, 2) DEFAULT 0.00",
                'gateway_reference' => "VARCHAR(100)",
                'payment_method' => "VARCHAR(50) DEFAULT 'card'",
                'is_test' => "TINYINT DEFAULT 0",
                'invoice_id' => "INT DEFAULT NULL"
            ],
            'payouts' => [
                'fee_amount' => "DECIMAL(15, 2) DEFAULT 0.00",
                'net_amount' => "DECIMAL(15, 2) NOT NULL DEFAULT 0.00",
                'status_details' => "TEXT"
            ],
            'tickets' => [
                'guest_email' => "VARCHAR(255)",
                'is_registered' => "TINYINT DEFAULT 1"
            ],
            'blog_posts' => [
                'featured_image' => "VARCHAR(255)",
                'meta_title' => "VARCHAR(255)",
                'meta_description' => "TEXT",
                'meta_keywords' => "VARCHAR(255)"
            ],
            'api_logs' => [
                'endpoint' => "VARCHAR(255)",
                'method' => "VARCHAR(10)",
                'payload' => "TEXT",
                'response' => "TEXT",
                'status_code' => "INT",
                'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
            ],
            'virtual_accounts' => [
                'bank_name' => "VARCHAR(255)",
                'account_number' => "VARCHAR(50)",
                'account_name' => "VARCHAR(255)",
                'customer_email' => "VARCHAR(255)",
                'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
            ],
            'invoices' => [
                'reference' => "VARCHAR(100)",
                'description' => "TEXT"
            ],
            'subscriptions' => [
                'description' => "TEXT",
                'plan_code' => "VARCHAR(100)"
            ],
            'config' => [
                'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
            ]
        ];

        // Create missing tables
        $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS marketing_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS marketing_contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            full_name VARCHAR(255),
            group_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES marketing_groups(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS transaction_timeline (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        foreach ($tables as $table => $columns) {
            foreach ($columns as $col => $def) {
                $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                if (!$stmt->fetch()) {
                    $db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
                }
            }
        }

    } catch (Exception $e) {
        // Log error if needed: error_log($e->getMessage());
    }
}
check_migrations();

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
    } catch (Exception $e) {
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
    } catch (Exception $e) {
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

    // Log the API call
    try {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO api_logs (endpoint, method, payload, response, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$endpoint, $method, json_encode($data), $response, $http_code]);
    } catch (Exception $e) {}

    $result = json_decode($response, true);
    return $result;
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
