<?php
// php-version/webhook-paystack.php
require_once 'includes/functions.php';

$input = file_get_contents("php://input");
$event = json_decode($input, true);

if (!$event) exit;

// Verify Paystack Signature
$paystack_secret = getConfig('paystack_secret_key');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (!$signature || $signature !== hash_hmac('sha512', $input, $paystack_secret)) {
    exit;
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
    if (!$tx && isset($data['dedicated_combined_account'])) {
        $acc_number = $data['dedicated_combined_account']['account_number'];
        $stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE account_number = ?");
        $stmt->execute([$acc_number]);
        $va = $stmt->fetch();

        if ($va) {
            // Create a pending transaction for this VA payment
            $stmt = $db->prepare("INSERT INTO transactions (user_id, reference, amount, status, customer_email, payment_method) VALUES (?, ?, ?, 'pending', ?, 'bank_transfer')");
            $stmt->execute([$va['user_id'], $ref, $amount, $va['customer_email']]);

            $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ?");
            $stmt->execute([$ref]);
            $tx = $stmt->fetch();
        }
    }

    if ($tx && $tx['status'] === 'pending') {
        $db->beginTransaction();
        try {
            // Update transaction
            $stmt = $db->prepare("UPDATE transactions SET status = 'success', currency = ?, gateway_reference = ? WHERE id = ?");
            $stmt->execute([$currency, $data['id'], $tx['id']]);

            // Calculate fees
            $is_intl = ($currency !== 'NGN');
            $fee = calculate_fees($amount, $is_intl);
            $settled = $amount - $fee;

            $stmt = $db->prepare("UPDATE transactions SET fee_amount = ?, settled_amount = ? WHERE id = ?");
            $stmt->execute([$fee, $settled, $tx['id']]);

            // Log ledger and update user balance
            log_ledger_entry($tx['user_id'], $settled, 'credit', 'payment', "Payment received for Ref: $ref");

            log_transaction_event($tx['id'], 'success', 'Payment confirmed via Webhook');

            $db->commit();

            // Notify Merchant
            $stmt = $db->prepare("SELECT email, business_name FROM users WHERE id = ?");
            $stmt->execute([$tx['user_id']]);
            $m = $stmt->fetch();

            sendEmail($m['email'], "New Payment Received", "<h2>Payment Confirmed</h2><p>You have received a payment of <strong>".formatCurrency($amount)."</strong>.</p><p>Reference: $ref</p>");

        } catch (Exception $e) {
            $db->rollBack();
        }
    }
}

http_response_code(200);
