<?php
// php-version/merchant/subscriptions.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Subscriptions - Payhub';

$db = Database::connect();
$stmt = $db->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$subscriptions = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Subscriptions</h1>
                        <p class="text-slate-500">Set up and manage recurring billing plans for your services</p>
                    </div>
                    <button class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                        <i class="lucide-plus w-5 h-5"></i> New Subscription Plan
                    </button>
                </div>

                <?php if (empty($subscriptions)): ?>
                    <div class="bg-white rounded-[2rem] border border-slate-200 border-dashed p-20 text-center">
                        <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="lucide-refresh-ccw text-indigo-600 w-10 h-10"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">No subscription plans yet</h3>
                        <p class="text-slate-500 mb-8 max-w-sm mx-auto">Create your first plan to start collecting recurring payments from your customers automatically.</p>
                        <button class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all">Get Started</button>
                    </div>
                <?php else: ?>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($subscriptions as $s): ?>
                            <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm hover:border-indigo-600 transition-all group relative">
                                <div class="flex justify-between items-start mb-6">
                                    <div class="p-3 bg-slate-50 rounded-2xl group-hover:bg-indigo-50 transition-colors">
                                        <i class="lucide-zap text-slate-400 group-hover:text-indigo-600 w-6 h-6"></i>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider <?php echo $s['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>"><?php echo $s['status']; ?></span>
                                </div>
                                <h4 class="font-bold text-xl text-slate-900 mb-1"><?php echo $s['plan_name']; ?></h4>
                                <div class="flex items-baseline gap-1 mb-8">
                                    <span class="text-3xl font-bold text-indigo-600"><?php echo formatCurrency($s['amount']); ?></span>
                                    <span class="text-sm text-slate-400 font-medium">/ <?php echo $s['interval']; ?></span>
                                </div>
                                <div class="pt-6 border-t border-slate-100 flex justify-between items-center">
                                    <span class="text-xs font-medium text-slate-400">Created <?php echo date('M d, Y', strtotime($s['created_at'])); ?></span>
                                    <button class="text-indigo-600 font-bold text-sm hover:underline">Edit Plan</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php include "../includes/merchant-quick-actions.php"; ?>
</main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
