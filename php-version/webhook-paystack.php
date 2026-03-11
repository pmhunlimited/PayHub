<?php
// php-version/webhook-paystack.php
require_once 'includes/functions.php';

$input = file_get_contents("php://input");
$event = json_decode($input, true);

// Start Logging for Debugging
$log_payload = [
    'time' => date('Y-m-d H:i:s'),
    'event' => $event['event'] ?? 'unknown',
    'full_input' => $event
];
file_put_contents('webhook_debug.log', json_encode($log_payload) . PHP_EOL, FILE_APPEND);

if (!$event) {
    http_response_code(400);
    die('No input');
}

// Verify Paystack Signature
$paystack_secret = getConfig('paystack_secret_key');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

$is_verified = false;
if ($signature && $signature === hash_hmac('sha512', $input, $paystack_secret)) {
    $is_verified = true;
} else {
    // Try with test secret key
    $paystack_test_secret = getConfig('paystack_test_secret_key');
    if ($signature && $signature === hash_hmac('sha512', $input, $paystack_test_secret)) {
        $is_verified = true;
    }
}

if (!$is_verified) {
    file_put_contents('webhook_debug.log', "Signature Verification Failed" . PHP_EOL, FILE_APPEND);
    http_response_code(401);
    die('Invalid signature');
}

$db = Database::connect();

if ($event['event'] === 'charge.success') {
    $data = $event['data'];
    $ref = $data['reference'];
    $amount = $data['amount'] / 100;
    $currency = $data['currency'];

    $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ?");
    $stmt->execute([$ref]);
    $tx = $stmt->fetch();

    // If no transaction found, check if it's a payment to a dedicated virtual account
    if (!$tx) {
        $acc_number = null;
        // Check standard structures for dedicated account payments
        if (isset($data['dedicated_combined_account']['account_number'])) {
            $acc_number = $data['dedicated_combined_account']['account_number'];
        } elseif (isset($data['customer']['dedicated_account'])) {
            $acc_number = $data['customer']['dedicated_account'];
        } elseif (isset($data['authorization']['receiver_bank_account_number'])) {
            $acc_number = $data['authorization']['receiver_bank_account_number'];
        } elseif (isset($data['receiver_bank_account_number'])) {
            $acc_number = $data['receiver_bank_account_number'];
        } elseif (isset($data['authorization']['account_number'])) {
            $acc_number = $data['authorization']['account_number'];
        }

        if ($acc_number) {
            $stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE account_number = ?");
            $stmt->execute([$acc_number]);
            $va = $stmt->fetch();

            if ($va) {
                // Create a pending transaction for this VA payment
                // Using INSERT IGNORE in case webhook is retried quickly
                $stmt = $db->prepare("INSERT IGNORE INTO transactions (user_id, reference, amount, status, customer_email, payment_method) VALUES (?, ?, ?, 'pending', ?, 'bank_transfer')");
                $stmt->execute([$va['user_id'], $ref, $amount, $va['customer_email']]);

                $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ?");
                $stmt->execute([$ref]);
                $tx = $stmt->fetch();

                file_put_contents('webhook_debug.log', "Matched Virtual Account: $acc_number for user " . $va['user_id'] . PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents('webhook_debug.log', "Dedicated Account payment but NO MATCH in DB: $acc_number" . PHP_EOL, FILE_APPEND);
            }
        }
    }

    if ($tx && $tx['status'] === 'pending') {
        $db->beginTransaction();
        try {
            // Update transaction
            $stmt = $db->prepare("UPDATE transactions SET status = 'success', currency = ?, gateway_reference = ? WHERE id = ?");
            $stmt->execute([$currency, $data['id'], $tx['id']]);

            // Handle Invoice Payment automation
            if (isset($data['metadata']['invoice_id'])) {
                $inv_id = (int)$data['metadata']['invoice_id'];
                $db->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?")->execute([$inv_id]);
                $db->prepare("UPDATE transactions SET invoice_id = ? WHERE id = ?")->execute([$inv_id, $tx['id']]);
            } elseif (strpos($ref, 'INV-') === 0) {
                // Try matching by reference if metadata is missing (e.g. legacy or manual ref)
                $db->prepare("UPDATE invoices SET status = 'paid' WHERE reference = ?")->execute([$ref]);
            }

            // Calculate fees
            $is_intl = ($currency !== 'NGN');
            $fee = calculate_fees($amount, $is_intl, $tx['user_id']);
            $settled = $amount - $fee;

            $stmt = $db->prepare("UPDATE transactions SET fee_amount = ?, settled_amount = ? WHERE id = ?");
            $stmt->execute([$fee, $settled, $tx['id']]);

            // Log ledger and update user balance
            log_ledger_entry($tx['user_id'], $settled, 'credit', 'payment', "Payment received for Ref: $ref");

            log_transaction_event($tx['id'], 'payment_completed', 'Payment successfully processed and confirmed via Webhook');

            $db->commit();

            // Notify Merchant Webhook
            $stmt = $db->prepare("SELECT webhook_url FROM users WHERE id = ?");
            $stmt->execute([$tx['user_id']]);
            $merchant_hook = $stmt->fetch()['webhook_url'] ?? '';

            if ($merchant_hook) {
                $webhook_data = [
                    'event' => 'payment.success',
                    'data' => [
                        'reference' => $ref,
                        'amount' => $amount,
                        'currency' => $currency,
                        'customer_email' => $tx['customer_email'],
                        'status' => 'success',
                        'metadata' => $data['metadata'] ?? []
                    ]
                ];

                $ch = curl_init($merchant_hook);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhook_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_exec($ch);
                curl_close($ch);
            }

            // Notify Merchant
            $stmt = $db->prepare("SELECT email, business_name FROM users WHERE id = ?");
            $stmt->execute([$tx['user_id']]);
            $m = $stmt->fetch();

            sendEmail($m['email'], "New Payment Received", "<h2>Payment Confirmed</h2><p>You have received a payment of <strong>".formatCurrency($amount)."</strong>.</p><p>Reference: $ref</p>");

        } catch (Exception $e) {
            $db->rollBack();
            file_put_contents('webhook_debug.log', "Transaction Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
} elseif ($event['event'] === 'refund.processed') {
    $data = $event['data'];
    $ref = $data['transaction_reference'];

    $stmt = $db->prepare("SELECT id FROM transactions WHERE gateway_reference = ? OR reference = ?");
    $stmt->execute([$data['transaction_id'], $ref]);
    $tx = $stmt->fetch();

    if ($tx) {
        log_transaction_event($tx['id'], 'refund_completed', "Refund of " . formatCurrency($data['amount']/100) . " has been completed by Paystack.");
    }
} elseif ($event['event'] === 'dispute.create') {
    $data = $event['data'];
    $stmt = $db->prepare("SELECT id, user_id FROM transactions WHERE gateway_reference = ?");
    $stmt->execute([$data['transaction_id']]);
    $tx = $stmt->fetch();

    if ($tx) {
        $stmt = $db->prepare("INSERT INTO disputes (user_id, transaction_id, reason, status) VALUES (?, ?, ?, 'open')");
        $stmt->execute([$tx['user_id'], $tx['id'], $data['reason'] ?? 'Chargeback initiated']);
        log_transaction_event($tx['id'], 'dispute_opened', "A chargeback dispute has been opened for this transaction.");
    }
}

http_response_code(200);
echo "Webhook processed";
