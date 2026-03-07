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

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-2">Subscriptions</h1>
                    <p class="text-slate-500">Set up recurring billing for your customers</p>
                </div>
                <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all">
                    <i class="lucide-plus w-4 h-4"></i> New Plan
                </button>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 font-bold">Subscription Plans</div>
                <div class="grid md:grid-cols-3 gap-6 p-6">
                    <?php foreach ($subscriptions as $s): ?>
                        <div class="p-6 border border-slate-100 rounded-3xl bg-slate-50/50 hover:border-indigo-200 transition-all group">
                            <h4 class="font-bold text-lg mb-1"><?php echo $s['plan_name']; ?></h4>
                            <p class="text-2xl font-bold text-indigo-600 mb-4"><?php echo formatCurrency($s['amount']); ?><span class="text-xs text-slate-400 font-normal"> /<?php echo $s['interval']; ?></span></p>
                            <div class="flex justify-between items-center text-sm text-slate-500">
                                <span class="px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase <?php echo $s['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>"><?php echo $s['status']; ?></span>
                                <button class="opacity-0 group-hover:opacity-100 text-indigo-600 font-bold transition-all">Edit Plan</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($subscriptions)): ?>
                        <div class="col-span-3 py-12 text-center text-slate-500">No subscription plans created.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
