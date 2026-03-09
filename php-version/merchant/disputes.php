<?php
// php-version/merchant/disputes.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Transaction Disputes - Payhub';

$db = Database::connect();
$stmt = $db->prepare("SELECT d.*, t.reference as transaction_ref, t.amount FROM disputes d JOIN transactions t ON d.transaction_id = t.id WHERE d.user_id = ? ORDER BY d.created_at DESC");
$stmt->execute([$user['id']]);
$disputes = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Disputes</h1>
                    <p class="text-slate-500">Manage chargebacks and provide evidence to defend your transactions</p>
                </div>

                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 font-bold text-slate-900 bg-slate-50/50">Active Disputes</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Transaction Ref</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Reason</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($disputes as $d): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-mono font-bold text-slate-900"><?php echo $d['transaction_ref']; ?></td>
                                        <td class="px-6 py-4 text-sm font-bold text-slate-900"><?php echo formatCurrency($d['amount']); ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-slate-600"><?php echo $d['reason']; ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider <?php echo $d['status'] === 'open' ? 'bg-amber-100 text-amber-700' : ($d['status'] === 'won' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'); ?>">
                                                <?php echo $d['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button class="text-indigo-600 hover:text-indigo-800 font-bold text-xs flex items-center gap-1">
                                                <i data-lucide="upload" class="w-3 h-3"></i> Add Evidence
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($disputes)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-20 text-center">
                                            <div class="flex flex-col items-center gap-2 opacity-30">
                                                <i data-lucide="shield-alert" class="w-12 h-12"></i>
                                                <p class="font-bold">No active disputes</p>
                                            </div>
                                        </td>
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