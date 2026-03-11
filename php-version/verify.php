<?php
// php-version/verify.php
require_once 'includes/functions.php';

$ref = sanitize($_GET['reference'] ?? '');

if (!$ref) redirect('index.php');

$db = Database::connect();
$stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ?");
$stmt->execute([$ref]);
$tx = $stmt->fetch();

$status = 'pending';
$amount = 0;

// Determine if we should use test mode
$is_test = false;
if ($tx) {
    $is_test = (bool)$tx['is_test'];
} else {
    // If no transaction record, attempt to determine mode from reference prefix
    // Invoices use INV_INV-xxxx_random
    if (strpos($ref, 'INV_') === 0) {
        $parts = explode('_', $ref);
        if (count($parts) >= 2) {
            $inv_ref = $parts[1];
            $stmt = $db->prepare("SELECT u.is_test_mode FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.reference = ?");
            $stmt->execute([$inv_ref]);
            $row = $stmt->fetch();
            if ($row) {
                $is_test = ($row['is_test_mode'] == 1);
            }
        }
    }
}

// Call Paystack to verify - Always use platform keys for verification on public pages
$res = paystack_call("transaction/verify/" . $ref, 'GET', [], $is_test);

if ($res['status'] && $res['data']['status'] === 'success') {
    $status = 'success';
    $data = $res['data'];
    $amount = $data['amount'] / 100;
    $currency = $data['currency'];

    // 1. If no transaction record exists, create one (e.g. for dynamic invoice payments)
    if (!$tx) {
        $userId = null;
        $invoiceId = null;
        $customerEmail = $data['customer']['email'];
        $customerName = trim(($data['customer']['first_name'] ?? '') . ' ' . ($data['customer']['last_name'] ?? ''));

        // Identify merchant and invoice from metadata or reference
        if (isset($data['metadata']['invoice_id'])) {
            $invoiceId = (int)$data['metadata']['invoice_id'];
            $stmt = $db->prepare("SELECT user_id FROM invoices WHERE id = ?");
            $stmt->execute([$invoiceId]);
            $userId = $stmt->fetchColumn();
        } elseif (strpos($ref, 'INV_') === 0) {
            $parts = explode('_', $ref);
            $inv_ref = $parts[1];
            $stmt = $db->prepare("SELECT id, user_id FROM invoices WHERE reference = ?");
            $stmt->execute([$inv_ref]);
            $inv = $stmt->fetch();
            if ($inv) {
                $invoiceId = $inv['id'];
                $userId = $inv['user_id'];
            }
        }

        if ($userId) {
            // Create the missing transaction record
            $stmt = $db->prepare("INSERT IGNORE INTO transactions (user_id, reference, amount, status, customer_email, customer_name, currency, is_test) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)");
            $stmt->execute([$userId, $ref, $amount, $customerEmail, $customerName, $currency, $is_test ? 1 : 0]);

            $stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ?");
            $stmt->execute([$ref]);
            $tx = $stmt->fetch();
        }
    }

    // 2. Process fulfillment if transaction is still pending
    if ($tx && ($tx['status'] === 'pending' || $tx['status'] === 'failed')) {
        $db->beginTransaction();
        try {
            // Update transaction basic info
            $stmt = $db->prepare("UPDATE transactions SET status = 'success', gateway_reference = ?, currency = ? WHERE id = ?");
            $stmt->execute([$data['id'], $currency, $tx['id']]);

            // Calculate fees
            $is_intl = ($currency !== 'NGN');
            $fee = calculate_fees($amount, $is_intl, $tx['user_id']);
            $settled = $amount - $fee;

            $stmt = $db->prepare("UPDATE transactions SET fee_amount = ?, settled_amount = ? WHERE id = ?");
            $stmt->execute([$fee, $settled, $tx['id']]);

            // Ensure Customer record exists locally
            $stmt = $db->prepare("INSERT INTO customers (user_id, full_name, email) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)");
            $stmt->execute([$tx['user_id'], $tx['customer_name'] ?: 'Guest Customer', $tx['customer_email']]);

            // Handle Invoice linkage
            $inv_id = null;
            if (isset($data['metadata']['invoice_id'])) {
                $inv_id = (int)$data['metadata']['invoice_id'];
            } elseif (strpos($ref, 'INV_') === 0) {
                $parts = explode('_', $ref);
                $inv_ref = $parts[1];
                $stmt = $db->prepare("SELECT id FROM invoices WHERE reference = ?");
                $stmt->execute([$inv_ref]);
                $inv_id = $stmt->fetchColumn();
            }

            if ($inv_id) {
                $db->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?")->execute([$inv_id]);
                $db->prepare("UPDATE transactions SET invoice_id = ? WHERE id = ?")->execute([$inv_id, $tx['id']]);
            }

            // Log ledger and credit merchant wallet
            log_ledger_entry($tx['user_id'], $settled, 'credit', 'payment', "Payment verified for Ref: $ref");
            log_transaction_event($tx['id'], 'verified', "Payment verified and credited via direct lookup.");

            $db->commit();

            // 3. Notify Merchant Webhook immediately
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
                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Short timeout for user experience
                curl_exec($ch);
                curl_close($ch);
            }

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Verification Error: " . $e->getMessage());
        }
    }
} elseif ($res['status'] && $res['data']['status'] !== 'pending') {
    $status = 'failed';
} else {
    $status = 'pending';
}

include 'includes/header.php';
?>
<div class="pt-32 pb-20 bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden text-center p-12">
        <?php if ($status === 'success'): ?>
            <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="lucide-check-circle-2 w-10 h-10"></i>
            </div>
            <h2 class="text-3xl font-bold text-slate-900 mb-2">Payment Successful!</h2>
            <p class="text-slate-500 mb-8">Thank you for your payment. Your transaction reference is <strong><?php echo $ref; ?></strong>.</p>
        <?php elseif ($status === 'failed'): ?>
            <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="lucide-x-circle w-10 h-10"></i>
            </div>
            <h2 class="text-3xl font-bold text-slate-900 mb-2">Payment Failed</h2>
            <p class="text-slate-500 mb-8">We could not process your payment. Please try again or contact support.</p>
        <?php else: ?>
            <div class="w-20 h-20 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                <i class="lucide-refresh-ccw w-10 h-10"></i>
            </div>
            <h2 class="text-3xl font-bold text-slate-900 mb-2">Verifying Payment...</h2>
            <p class="text-slate-500 mb-8">Please wait while we confirm your transaction with the bank.</p>
            <script>setTimeout(() => window.location.reload(), 3000);</script>
        <?php endif; ?>

        <a href="index.php" class="inline-block bg-slate-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-slate-800 transition-all">Return Home</a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
