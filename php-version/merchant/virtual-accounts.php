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

// Fetch virtual accounts
$stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll();

$pageTitle = 'Virtual Accounts - Payhub';
include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-4xl mx-auto">
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Virtual Accounts</h1>
                        <p class="text-slate-500">Dedicated bank accounts for your customers to pay via bank transfer</p>
                    </div>
                    <button class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
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
                            <button class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Generate Your First Account</button>
                        </div>
                    <?php endif; ?>
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
