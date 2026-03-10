<?php
// php-version/install/migrate.php
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    die("Unauthorized. Admin access required.");
}

function run_migrations() {
    echo "Starting migrations...<br>";
    try {
        $db = Database::connect();

        // 1. Create essential missing tables first
        $essential_tables = [
            'api_logs' => "CREATE TABLE IF NOT EXISTS api_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                endpoint VARCHAR(255),
                method VARCHAR(10),
                payload TEXT,
                response TEXT,
                status_code INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",
            'email_templates' => "CREATE TABLE IF NOT EXISTS email_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",
            'marketing_groups' => "CREATE TABLE IF NOT EXISTS marketing_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",
            'marketing_contacts' => "CREATE TABLE IF NOT EXISTS marketing_contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                full_name VARCHAR(255),
                group_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",
            'transaction_timeline' => "CREATE TABLE IF NOT EXISTS transaction_timeline (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id INT NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        ];

        foreach ($essential_tables as $name => $sql) {
            try {
                $db->exec($sql);
                echo "Checked/Created table: $name<br>";
            } catch (Exception $e) {
                echo "Migration Table Error ($name): " . $e->getMessage() . "<br>";
            }
        }

        // 2. Ensure columns exist
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
                'test_secret_key' => "VARCHAR(255)",
                'fee_percentage' => "DECIMAL(5, 2) DEFAULT NULL",
                'fee_flat' => "DECIMAL(15, 2) DEFAULT NULL"
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
            'marketing_contacts' => [
                'group_id' => "INT"
            ],
            'virtual_accounts' => [
                'bank_name' => "VARCHAR(255)",
                'account_number' => "VARCHAR(50)",
                'account_name' => "VARCHAR(255)",
                'customer_email' => "VARCHAR(255)"
            ],
            'invoices' => [
                'reference' => "VARCHAR(100)",
                'description' => "TEXT"
            ],
            'subscriptions' => [
                'description' => "TEXT",
                'plan_code' => "VARCHAR(100)"
            ]
        ];

        foreach ($tables as $table => $columns) {
            try {
                $stmt = $db->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->fetch()) {
                    foreach ($columns as $col => $def) {
                        try {
                            $stmtCol = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
                            $stmtCol->execute([$col]);
                            if (!$stmtCol->fetch()) {
                                $db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
                                echo "Added column $col to table $table<br>";
                            }
                        } catch (Exception $eCol) { }
                    }
                }
            } catch (Exception $eTab) { }
        }
        echo "Migrations completed successfully.<br>";

    } catch (Exception $e) {
        echo "Migration Fatal Error: " . $e->getMessage() . "<br>";
    }
}

run_migrations();
echo '<a href="../admin/index.php">Back to Dashboard</a>';
