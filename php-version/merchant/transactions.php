<?php
// php-version/transactions.php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = getAuthUser();
$db = Database::connect();

$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Payhub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-3xl font-bold text-slate-900">Transactions</h1>
                <button class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all">
                    <i class="lucide-download w-4 h-4"></i>
                    Export CSV
                </button>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Reference</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Customer</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Amount</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($transactions as $tx): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-mono text-sm text-slate-600"><?php echo $tx['reference']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-700"><?php echo $tx['customer_email']; ?></td>
                                    <td class="px-6 py-4 text-sm font-bold text-slate-900"><?php echo formatCurrency($tx['amount']); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $tx['status'] === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                            <?php echo $tx['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?php echo date('M d, Y H:i', strtotime($tx['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">No transactions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php include "../includes/merchant-quick-actions.php"; ?>
</main>
</body>
</html>
