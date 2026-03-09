<?php
// php-version/merchant/virtual-accounts.php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = getAuthUser();
if ($user['business_type'] === 'Starter' || $user['is_kyc_verified'] != 1) {
    redirect('dashboard.php');
}
$db = Database::connect();
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_account') {
    $email = sanitize($_POST['email']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);

    // 1. Create/Fetch Customer on Paystack
    $customer_res = paystack_call('customer', 'POST', [
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'phone' => $phone
    ]);

    if ($customer_res && $customer_res['status']) {
        $customer_code = $customer_res['data']['customer_code'];

        // 2. Create Dedicated Virtual Account
        // Note: In real scenarios, you might want to specify preferred_bank
        $dva_res = paystack_call('dedicated_account', 'POST', [
            'customer' => $customer_code
        ]);

        if ($dva_res && $dva_res['status']) {
            $acc = $dva_res['data'];
            // Store in DB
            $stmt = $db->prepare("INSERT INTO virtual_accounts (user_id, bank_name, account_number, account_name, customer_email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user['id'], $acc['bank']['name'], $acc['account_number'], $acc['account_name'], $email]);
            $success_msg = "Virtual account generated successfully!";
        } else {
            $error_msg = "Failed to generate virtual account: " . ($dva_res['message'] ?? 'Unknown error');
        }
    } else {
        $error_msg = "Failed to create customer: " . ($customer_res['message'] ?? 'Unknown error');
    }
}

// Fetch virtual accounts
$stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll();

$pageTitle = 'Virtual Accounts - Payhub';
include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false, showGenerate: false }">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-4xl mx-auto">
                <?php if ($success_msg): ?>
                    <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-medium"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Virtual Accounts</h1>
                        <p class="text-slate-500">Dedicated bank accounts for your customers to pay via bank transfer</p>
                    </div>
                    <button @click="showGenerate = true" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                        <i data-lucide="plus" class="w-5 h-5"></i> Generate New Account
                    </button>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <?php foreach ($accounts as $acc): ?>
                        <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm hover:border-indigo-600 transition-all group">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all">
                                    <i data-lucide="wallet" class="w-6 h-6"></i>
                                </div>
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-bold uppercase tracking-wider">Active</span>
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-widest"><?php echo $acc['bank_name']; ?></p>
                            <h3 class="text-2xl font-mono font-bold text-slate-900 mb-1"><?php echo $acc['account_number']; ?></h3>
                            <p class="text-sm text-slate-500 font-medium"><?php echo $acc['account_name']; ?></p>
                            <?php if ($acc['customer_email']): ?>
                                <p class="text-[10px] text-slate-400 mt-2">Customer: <span class="font-bold"><?php echo $acc['customer_email']; ?></span></p>
                            <?php endif; ?>

                            <div class="mt-8 pt-6 border-t border-slate-50 flex items-center justify-between">
                                <p class="text-[10px] text-slate-400 font-bold uppercase">Created <?php echo date('M d, Y', strtotime($acc['created_at'])); ?></p>
                                <button onclick="navigator.clipboard.writeText('<?php echo $acc['account_number']; ?>'); alert('Copied!');" class="text-indigo-600 text-xs font-bold hover:underline">Copy Details</button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($accounts)): ?>
                        <div class="md:col-span-2 bg-white p-16 rounded-[2rem] border border-dashed border-slate-300 text-center">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i data-lucide="wallet" class="text-slate-300 w-10 h-10"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-2">No Virtual Accounts Yet</h3>
                            <p class="text-slate-500 mb-8 max-w-sm mx-auto">Generate a dedicated account to start receiving payments via bank transfers directly into your Payhub wallet.</p>
                            <button @click="showGenerate = true" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Generate Your First Account</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Generate Account Modal -->
        <div x-show="showGenerate" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-md overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Generate Virtual Account</h3>
                    <button @click="showGenerate = false" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <p class="text-sm text-slate-500 mb-6">Enter customer details to generate a dedicated bank account for payments.</p>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="generate_account">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Customer Email</label>
                            <input type="email" name="email" required placeholder="customer@example.com" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">First Name</label>
                                <input type="text" name="first_name" required placeholder="John" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Last Name</label>
                                <input type="text" name="last_name" required placeholder="Doe" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Phone Number</label>
                            <input type="tel" name="phone" required placeholder="08012345678" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 mt-4">Generate Account</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/merchant-quick-actions.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
