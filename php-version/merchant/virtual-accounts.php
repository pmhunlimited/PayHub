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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate_account') {
        $email = sanitize($_POST['email'] ?: $_POST['new_email']);
        $full_name = trim(sanitize($_POST['first_name'] . ' ' . $_POST['last_name']));
        $phone = sanitize($_POST['phone'] ?? '');

        $va_res = ensure_virtual_account($user['id'], $email, [
            'full_name' => $full_name,
            'phone' => $phone
        ]);

        if ($va_res['status']) {
            $success_msg = "Virtual account generated: " . $va_res['data']['bank_name'] . " - " . $va_res['data']['account_number'];
        } else {
            $error_msg = $va_res['message'];
        }
    } elseif ($_POST['action'] === 'sync_accounts') {
        $res = paystack_call('dedicated_account', 'GET');
        if ($res && $res['status']) {
            $synced = 0;
            foreach ($res['data'] as $acc) {
                $email = $acc['customer']['email'];
                $merchant_id = $acc['customer']['metadata']['merchant_id'] ?? null;

                // Match by metadata or existing local customer
                $is_mine = false;
                if ($merchant_id == $user['id']) {
                    $is_mine = true;
                } else {
                    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ? AND email = ?");
                    $stmt->execute([$user['id'], $email]);
                    if ($stmt->fetch()) $is_mine = true;
                }

                if ($is_mine) {
                    // Standardize keys with maximum resilience
                    $bank = $acc['bank']['name'] ?? ($acc['Bank']['name'] ?? ($acc['Bank_name'] ?? ($acc['bank_name'] ?? 'Virtual Bank')));
                    $number = $acc['account_number'] ?? ($acc['Account_number'] ?? ($acc['Account'] ?? ($acc['account'] ?? '')));
                    $name = $acc['account_name'] ?? ($acc['Account_name'] ?? ($acc['Name'] ?? ($acc['account_name'] ?? $user['business_name'])));

                    if (empty($number)) $number = '0000000000'; // Placeholder for pending

                    // Check if already in DB
                    $stmt = $db->prepare("SELECT id, account_number FROM virtual_accounts WHERE (account_number = ? AND account_number != '0000000000') OR (customer_email = ? AND user_id = ?)");
                    $stmt->execute([$number, $email, $user['id']]);
                    $existing = $stmt->fetch();

                    if (!$existing) {
                        $stmt = $db->prepare("INSERT INTO virtual_accounts (user_id, bank_name, account_number, account_name, customer_email) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$user['id'], $bank, $number, $name, $email]);
                        $synced++;
                    } else {
                        // Update if number was placeholder but now we have it
                        if ($existing['account_number'] === '0000000000' && $number !== '0000000000') {
                             $stmt = $db->prepare("UPDATE virtual_accounts SET account_number = ?, bank_name = ? WHERE id = ?");
                             $stmt->execute([$number, $bank, $existing['id']]);
                             $synced++;
                        }
                    }

                    // Also ensure customer exists locally
                    $stmt = $db->prepare("INSERT IGNORE INTO customers (user_id, full_name, email) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], trim($acc['customer']['first_name'] . ' ' . $acc['customer']['last_name']), $email]);
                }
            }
            $success_msg = "Synced $synced accounts from Paystack.";
        } else {
            $error_msg = "Failed to sync: " . ($res['message'] ?? 'Unknown error');
        }
    } elseif ($_POST['action'] === 'delete_account') {
        $accId = (int)$_POST['account_id'];
        $stmt = $db->prepare("DELETE FROM virtual_accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$accId, $user['id']]);
        $success_msg = "Virtual account record removed.";
    }
}

// Fetch virtual accounts (only valid ones)
$stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE user_id = ? AND account_number IS NOT NULL AND account_number != '' ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers for selection
$stmt = $db->prepare("SELECT id, full_name, email FROM customers WHERE user_id = ? ORDER BY full_name ASC");
$stmt->execute([$user['id']]);
$customers = $stmt->fetchAll();

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

                <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Virtual Accounts</h1>
                        <p class="text-slate-500">Dedicated bank accounts for your customers to pay via bank transfer</p>
                    </div>
                    <div class="flex gap-3">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="sync_accounts">
                            <button type="submit" class="bg-white border border-slate-200 text-slate-700 px-6 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-slate-50 transition-all shadow-sm">
                                <i data-lucide="refresh-cw" class="w-5 h-5"></i> Sync
                            </button>
                        </form>
                        <button @click="showGenerate = true" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                            <i data-lucide="plus" class="w-5 h-5"></i> Generate Account
                        </button>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <?php foreach ($accounts as $acc):
                        $bankName = $acc['bank_name'] ?? ($acc['Bank_name'] ?? ($acc['Bank'] ?? 'Virtual Bank'));
                        $accNum = $acc['account_number'] ?? ($acc['Account_number'] ?? ($acc['Account'] ?? '0000000000'));
                        $accName = $acc['account_name'] ?? ($acc['Account_name'] ?? ($acc['Name'] ?? $user['business_name']));
                    ?>
                        <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm hover:border-indigo-600 transition-all group">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all">
                                    <i data-lucide="wallet" class="w-6 h-6"></i>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-bold uppercase tracking-wider">Active</span>
                                    <form method="POST" class="inline" onsubmit="return confirm('Remove this virtual account record?');">
                                        <input type="hidden" name="action" value="delete_account">
                                        <input type="hidden" name="account_id" value="<?php echo $acc['id']; ?>">
                                        <button type="submit" class="p-1 text-slate-300 hover:text-rose-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
                                </div>
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-widest"><?php echo htmlspecialchars($bankName); ?></p>
                            <h3 class="text-2xl font-mono font-bold text-slate-900 mb-1">
                                <?php if($accNum === '0000000000'): ?>
                                    <span class="text-amber-500 italic text-lg">Awaiting Number...</span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($accNum); ?>
                                <?php endif; ?>
                            </h3>
                            <p class="text-sm text-slate-500 font-medium"><?php echo htmlspecialchars($accName); ?></p>
                            <?php if (!empty($acc['customer_email']) || !empty($acc['Customer_email'])): ?>
                                <p class="text-[10px] text-slate-400 mt-2">Customer: <span class="font-bold"><?php echo htmlspecialchars($acc['customer_email'] ?? $acc['Customer_email']); ?></span></p>
                            <?php endif; ?>

                            <div class="mt-8 pt-6 border-t border-slate-50 flex items-center justify-between">
                                <p class="text-[10px] text-slate-400 font-bold uppercase">
                                    <?php
                                    $time = !empty($acc['created_at']) ? strtotime($acc['created_at']) : false;
                                    $created = ($time && $time > 0) ? date('M d, Y', $time) : 'Recently';
                                    echo "Created " . $created;
                                    ?>
                                </p>
                                <button onclick="const text = 'Bank: <?php echo addslashes($bankName); ?>\\nAccount: <?php echo addslashes($accNum); ?>\\nName: <?php echo addslashes($accName); ?>'; navigator.clipboard.writeText(text); alert('Account details copied to clipboard!');" class="text-indigo-600 text-xs font-bold hover:underline flex items-center gap-1">
                                    <i data-lucide="copy" class="w-3 h-3"></i> Copy Details
                                </button>
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
        <div x-show="showGenerate" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-data="{ mode: 'existing' }">
            <div class="bg-white rounded-[2rem] w-full max-w-md overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Generate Virtual Account</h3>
                    <button @click="showGenerate = false" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <div class="flex p-1 bg-slate-100 rounded-xl mb-6">
                        <button @click="mode = 'existing'" :class="mode === 'existing' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all">Existing Customer</button>
                        <button @click="mode = 'new'" :class="mode === 'new' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all">New Customer</button>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="generate_account">

                        <div x-show="mode === 'existing'">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Select Customer</label>
                            <select name="email" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">-- Choose Customer --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c['email']; ?>"><?php echo htmlspecialchars($c['full_name'] . ' (' . $c['email'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div x-show="mode === 'new'" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Customer Email</label>
                                <input type="email" name="new_email" placeholder="customer@example.com" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">First Name</label>
                                    <input type="text" name="first_name" :required="mode === 'new'" placeholder="John" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Last Name</label>
                                    <input type="text" name="last_name" :required="mode === 'new'" placeholder="Doe" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Phone Number</label>
                                <input type="tel" name="phone" :required="mode === 'new'" placeholder="08012345678" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
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
