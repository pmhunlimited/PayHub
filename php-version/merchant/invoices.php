<?php
// php-version/merchant/invoices.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Invoices - Payhub';

$db = Database::connect();
$stmt = $db->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$invoices = $stmt->fetchAll();

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-2">Invoices</h1>
                    <p class="text-slate-500">Create professional one-off invoices for your customers</p>
                </div>
                <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all">
                    <i class="lucide-plus w-4 h-4"></i> Create Invoice
                </button>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 font-bold">All Invoices</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Customer</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Amount</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Due Date</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($invoices as $inv): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900"><?php echo $inv['customer_name']; ?></div>
                                        <div class="text-xs text-slate-500"><?php echo $inv['customer_email']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold"><?php echo formatCurrency($inv['amount']); ?></td>
                                    <td class="px-6 py-4 text-sm"><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $inv['status'] === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                            <?php echo $inv['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="text-indigo-600 hover:text-indigo-700 font-bold text-xs">Manage</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">No invoices yet.</td>
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
