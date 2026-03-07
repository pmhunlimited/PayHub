<?php
// php-version/merchant/disputes.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Transaction Disputes - Payhub';

$db = Database::connect();
$stmt = $db->prepare("SELECT d.*, t.reference as transaction_ref FROM disputes d JOIN transactions t ON d.transaction_id = t.id WHERE d.user_id = ? ORDER BY d.created_at DESC");
$stmt->execute([$user['id']]);
$disputes = $stmt->fetchAll();

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Transaction Disputes</h1>
                <p class="text-slate-500">Manage chargebacks and upload evidence to defend your transactions</p>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 font-bold">Active Disputes</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Transaction Ref</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Reason</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Evidence</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($disputes as $d): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-mono"><?php echo $d['transaction_ref']; ?></td>
                                    <td class="px-6 py-4 text-sm"><?php echo $d['reason']; ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $d['status'] === 'open' ? 'bg-amber-100 text-amber-700' : ($d['status'] === 'won' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'); ?>">
                                            <?php echo $d['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($d['evidence_path']): ?>
                                            <a href="<?php echo $d['evidence_path']; ?>" class="text-indigo-600 hover:underline text-xs" target="_blank">View File</a>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic text-xs">Not uploaded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="text-indigo-600 hover:text-indigo-700 font-bold text-xs">Add Evidence</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($disputes)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">No disputes found.</td>
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
