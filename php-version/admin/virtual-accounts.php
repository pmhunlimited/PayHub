<?php
// php-version/admin/virtual-accounts.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Virtual Accounts - Admin Hub';

$db = Database::connect();

$stmt = $db->query("SELECT v.*, u.business_name FROM virtual_accounts v JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC");
$accounts = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Platform Virtual Accounts</h1>
                <p class="text-slate-500">Overview of all dedicated bank accounts generated for merchants</p>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 font-bold">Active Accounts</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Merchant</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Bank</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Account Number</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Account Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($accounts as $a): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-bold text-slate-900"><?php echo $a['business_name']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-700"><?php echo $a['bank_name']; ?></td>
                                    <td class="px-6 py-4 text-sm font-mono font-bold"><?php echo $a['account_number']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?php echo $a['account_name']; ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo date('M d, Y', strtotime($a['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($accounts)): ?>
                                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">No virtual accounts generated yet.</td></tr>
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
