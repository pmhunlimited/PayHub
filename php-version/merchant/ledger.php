<?php
// php-version/merchant/ledger.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Balance Ledger - Payhub';

$db = Database::connect();
$stmt = $db->prepare("SELECT * FROM ledger WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$ledger = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false, search: '' }">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                <div class="mb-8 flex justify-between items-end">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Balance Ledger</h1>
                        <p class="text-slate-500">Detailed history of all wallet balance movements</p>
                    </div>
                    <div class="bg-white p-4 px-6 rounded-2xl border border-slate-200 shadow-sm">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Available Balance</p>
                        <p class="text-xl font-bold text-indigo-600"><?php echo formatCurrency($user['wallet_balance']); ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
                        <h2 class="font-bold text-slate-900">Wallet Activities</h2>
                        <div class="relative w-full md:w-64">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
                            <input type="text" x-model="search" placeholder="Quick search..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date & Time</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Description</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Category</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Amount</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Balance After</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="entry in <?php echo htmlspecialchars(json_encode($ledger), ENT_QUOTES, 'UTF-8'); ?>.filter(i => !search || i.description.toLowerCase().includes(search.toLowerCase()) || i.category.toLowerCase().includes(search.toLowerCase()))" :key="entry.id">
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold text-slate-900" x-text="new Date(entry.created_at).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'})"></div>
                                            <div class="text-[10px] text-slate-400 font-medium" x-text="new Date(entry.created_at).toLocaleTimeString(undefined, {hour:'2-digit', minute:'2-digit'})"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-sm text-slate-600 font-medium" x-text="entry.description"></p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                                                :class="entry.category === 'payout' ? 'bg-amber-50 text-amber-600' : (entry.category === 'refund' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600')"
                                                x-text="entry.category">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-bold" :class="entry.type === 'credit' ? 'text-emerald-600' : 'text-red-600'" x-text="(entry.type === 'credit' ? '+' : '-') + ' ₦' + parseFloat(entry.amount).toLocaleString()">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <span class="text-sm font-mono font-bold text-slate-900" x-text="'₦' + parseFloat(entry.balance_after).toLocaleString()"></span>
                                        </td>
                                    </tr>
                                </template>
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