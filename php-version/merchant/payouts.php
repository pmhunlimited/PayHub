<?php
// php-version/payouts.php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = getAuthUser();
$db = Database::connect();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_payout') {
    $amount = (float)$_POST['amount'];
    if ($amount > 0 && $amount <= $user['wallet_balance']) {
        if ($user['settlement_bank'] && $user['settlement_account_number']) {
            $db->beginTransaction();
            try {
                // Deduct balance
                $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                $stmt->execute([$amount, $user['id']]);
                
                // Create payout record
                $stmt = $db->prepare("INSERT INTO payouts (user_id, amount, bank_name, account_number, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$user['id'], $amount, $user['settlement_bank'], $user['settlement_account_number']]);
                
                $db->commit();
                $success_msg = "Payout request submitted successfully.";
                $user = getAuthUser(); // Refresh user data
            } catch (Exception $e) {
                $db->rollBack();
                $error_msg = "Payout failed: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please set up your settlement bank details in settings first.";
        }
    } else {
        $error_msg = "Invalid amount or insufficient balance.";
    }
}

$stmt = $db->prepare("SELECT * FROM payouts WHERE user_id = ? ORDER BY request_date DESC");
$stmt->execute([$user['id']]);
$payouts = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-slate-900 mb-8">Payouts</h1>

            <?php if ($success_msg): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-medium">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900 mb-6">Withdraw Funds</h3>
                        <div class="mb-8 p-6 bg-indigo-50 rounded-3xl border border-indigo-100">
                            <p class="text-xs text-indigo-600 uppercase font-bold mb-1 tracking-widest">Available Balance</p>
                            <p class="text-3xl font-bold text-indigo-700 tracking-tight"><?php echo formatCurrency($user['wallet_balance']); ?></p>
                        </div>
                        
                        <div class="mb-8 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                            <p class="text-[10px] text-slate-400 uppercase font-bold mb-3 tracking-widest">Settlement Destination</p>
                            <?php if ($user['settlement_bank']): ?>
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-white rounded-2xl border border-slate-200 flex items-center justify-center text-indigo-600 shadow-sm">
                                        <i data-lucide="wallet" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900"><?php echo $user['settlement_bank']; ?></p>
                                        <p class="text-xs text-slate-500 font-mono"><?php echo $user['settlement_account_number']; ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-sm text-amber-600 font-bold mb-3">No bank account set</p>
                                    <a href="settings.php" class="text-xs bg-amber-100 text-amber-700 px-4 py-2 rounded-xl font-bold hover:bg-amber-200 transition-colors">Set Up Now</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="request_payout">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Amount to Withdraw</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₦</span>
                                    <input 
                                        type="number" 
                                        name="amount"
                                        required
                                        step="0.01"
                                        class="w-full pl-8 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-bold text-slate-900" 
                                        placeholder="0.00" 
                                    >
                                </div>
                            </div>
                            <button 
                                type="submit" 
                                <?php echo !$user['settlement_bank'] ? 'disabled' : ''; ?>
                                class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all disabled:opacity-50 disabled:shadow-none"
                            >
                                Confirm Withdrawal
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h3 class="text-xl font-bold text-slate-900">Payout History</h3>
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                            Real-time
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-8 py-5 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Amount</th>
                                    <th class="px-8 py-5 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                    <th class="px-8 py-5 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date</th>
                                    <th class="px-8 py-5 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Destination</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($payouts as $p): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-8 py-5 font-bold text-slate-900"><?php echo formatCurrency($p['amount']); ?></td>
                                        <td class="px-8 py-5">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $p['status'] === 'processed' ? 'bg-emerald-100 text-emerald-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'); ?>">
                                                <?php echo $p['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-5 text-sm text-slate-500 font-medium"><?php echo date('M d, Y H:i', strtotime($p['request_date'])); ?></td>
                                        <td class="px-8 py-5">
                                            <p class="text-xs font-bold text-slate-700"><?php echo $p['bank_name']; ?></p>
                                            <p class="text-[10px] text-slate-400 font-mono"><?php echo $p['account_number']; ?></p>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($payouts)): ?>
                                    <tr>
                                        <td colspan="4" class="px-8 py-16 text-center text-slate-400 italic">No payout history found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php include "../includes/merchant-quick-actions.php"; ?>
</main>
<script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>