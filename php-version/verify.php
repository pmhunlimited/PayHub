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

if ($tx) {
    // Call Paystack to verify
    $res = paystack_call("transaction/verify/" . $ref, 'GET', [], (bool)$tx['is_test']);
    if ($res['status'] && $res['data']['status'] === 'success') {
        $status = 'success';
        $amount = $res['data']['amount'] / 100;

        if ($tx['status'] === 'pending') {
            $db->beginTransaction();
            try {
                // Update transaction
                $stmt = $db->prepare("UPDATE transactions SET status = 'success', gateway_reference = ? WHERE id = ?");
                $stmt->execute([$res['data']['id'], $tx['id']]);

                // Calculate fees
                $is_intl = ($res['data']['currency'] !== 'NGN');
                $fee = calculate_fees($amount, $is_intl, $tx['user_id']);
                $settled = $amount - $fee;

                $stmt = $db->prepare("UPDATE transactions SET fee_amount = ?, settled_amount = ? WHERE id = ?");
                $stmt->execute([$fee, $settled, $tx['id']]);

                // Update or Create Customer record
                $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ? AND email = ?");
                $stmt->execute([$tx['user_id'], $tx['customer_email']]);
                if (!$stmt->fetch()) {
                    $stmt = $db->prepare("INSERT INTO customers (user_id, full_name, email) VALUES (?, ?, ?)");
                    $stmt->execute([$tx['user_id'], $tx['customer_name'] ?: 'Guest Customer', $tx['customer_email']]);
                }

                // Log ledger and update user balance
                log_ledger_entry($tx['user_id'], $settled, 'credit', 'payment', "Payment verified for Ref: $ref");

                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
            }
        }
    } else {
        $status = 'failed';
    }
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
