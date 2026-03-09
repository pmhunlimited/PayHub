<?php
// php-version/checkout.php
require_once 'includes/functions.php';

$pk = getConfig('paystack_public_key');
// Check if user is logged in and in test mode, or if ref implies test
$isTest = false;
$user = getAuthUser();
if ($user && $user['is_test_mode'] == 1) {
    $isTest = true;
    $pk = getConfig('paystack_test_public_key');
    if (!$pk) $pk = getConfig('paystack_public_key'); // Fallback
}

$amount = (float)($_GET['amount'] ?? 1000);
$email = $_GET['email'] ?? '';
$ref = $_GET['ref'] ?? 'PH_'.time();
$isEmbedded = isset($_GET['embed']) && $_GET['embed'] == '1';

if (!$isEmbedded) {
    include 'includes/header.php';
} else {
    // Basic styles for embedded version
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    </head>
    <body class="bg-white">
    <?php
}
?>
<div class="<?php echo $isEmbedded ? '' : 'pt-32 pb-20 bg-slate-50 min-h-screen flex items-center justify-center p-4'; ?>">
    <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
        <div class="p-8 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white">
                    <i class="lucide-credit-card w-4 h-4"></i>
                </div>
                <span class="font-bold text-slate-900 uppercase text-xs tracking-widest">Secure Checkout</span>
            </div>
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase">Amount</p>
                <p class="text-lg font-bold text-indigo-600"><?php echo formatCurrency($amount); ?></p>
            </div>
        </div>
        <div class="p-8">
            <div class="mb-8">
                <p class="text-sm text-slate-500 mb-1">Paying to</p>
                <p class="font-bold text-slate-900 text-lg"><?php echo getConfig('site_name', 'Payhub'); ?></p>
            </div>

            <div class="space-y-4 mb-8">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-slate-400">
                        <i class="lucide-user w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Customer</p>
                        <p class="text-sm font-bold text-slate-700"><?php echo $email ?: 'Guest Customer'; ?></p>
                    </div>
                </div>
            </div>

            <script src="https://js.paystack.co/v1/inline.js"></script>
            <button onclick="payWithPaystack()" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all flex items-center justify-center gap-2">
                <i class="lucide-shield-check w-5 h-5"></i> Pay Now
            </button>

            <p class="mt-6 text-center text-[10px] text-slate-400 uppercase tracking-widest flex items-center justify-center gap-1">
                <i class="lucide-lock w-3 h-3"></i> Secured by Payhub
            </p>
        </div>
    </div>
</div>

<script>
function payWithPaystack() {
    let handler = PaystackPop.setup({
        key: '<?php echo $pk; ?>',
        email: '<?php echo $email; ?>',
        amount: <?php echo $amount * 100; ?>,
        ref: '<?php echo $ref; ?>',
        onClose: function(){
            alert('Transaction cancelled.');
        },
        callback: function(response){
            <?php if ($isEmbedded): ?>
                window.parent.postMessage({
                    type: 'payhub_success',
                    data: response
                }, '*');
            <?php else: ?>
                window.location.href = "verify.php?reference=" + response.reference;
            <?php endif; ?>
        }
    });
    handler.openIframe();
}
</script>

<?php
if (!$isEmbedded) {
    include 'includes/footer.php';
} else {
    ?></body></html><?php
}
?>
