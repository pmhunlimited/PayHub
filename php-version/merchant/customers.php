<?php
// php-version/merchant/customers.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Customer Directory - Payhub';

$db = Database::connect();
$stmt = $db->prepare("SELECT * FROM customers WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$customers = $stmt->fetchAll();

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Customer Directory</h1>
                <p class="text-slate-500">A centralized database of everyone who has paid you</p>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 font-bold">All Customers</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Email</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Phone</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Date Joined</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($customers as $c): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-bold"><?php echo $c['full_name']; ?></td>
                                    <td class="px-6 py-4 text-sm"><?php echo $c['email']; ?></td>
                                    <td class="px-6 py-4 text-sm font-mono"><?php echo $c['phone']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <button class="text-indigo-600 hover:text-indigo-700 font-bold text-xs">View Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">No customers yet.</td>
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
