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

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Balance Ledger</h1>
                <p class="text-slate-500">Detailed history of all wallet balance movements</p>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="font-bold text-slate-900">Wallet Activities</h2>
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <i class="lucide-activity w-4 h-4"></i> Real-time
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date & Time</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Description</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Category</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Amount</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($ledger as $entry): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-slate-900"><?php echo date('M d, Y', strtotime($entry['created_at'])); ?></div>
                                        <div class="text-[10px] text-slate-400"><?php echo date('h:i A', strtotime($entry['created_at'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700"><?php echo $entry['description']; ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                                            <?php echo $entry['category'] === 'payout' ? 'bg-amber-50 text-amber-600' : ($entry['category'] === 'refund' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'); ?>">
                                            <?php echo $entry['category']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-bold <?php echo $entry['type'] === 'credit' ? 'text-emerald-600' : 'text-red-600'; ?>">
                                            <?php echo $entry['type'] === 'credit' ? '+' : '-'; ?> <?php echo formatCurrency($entry['amount']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-sm"><?php echo formatCurrency($entry['balance_after']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ledger)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">No ledger entries found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
