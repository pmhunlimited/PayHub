<?php
// php-version/admin.php
require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$user = getAuthUser();
$tab = $_GET['tab'] ?? 'overview';
$pageTitle = 'Admin Panel - Payhub';

$db = Database::connect();

// Fetch Admin Stats
$adminStats = [
    'total_gtv' => 0,
    'active_merchants' => 0,
    'success_rate' => '100%',
    'pending_kyc' => 0
];

$stmt = $db->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'success'");
$adminStats['total_gtv'] = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'merchant' AND is_suspended = 0");
$adminStats['active_merchants'] = $stmt->fetch()['count'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_kyc_verified = 2");
$adminStats['pending_kyc'] = $stmt->fetch()['count'] ?? 0;

// Fetch Config
$stmt = $db->query("SELECT * FROM config");
$config = $stmt->fetchAll();

// Handle Config Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_config') {
    $key = $_POST['key'];
    $value = $_POST['value'];
    
    $stmt = $db->prepare("INSERT INTO config (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    $stmt->execute([$key, $value]);
    $success_msg = "Configuration updated.";
    
    // Refresh config
    $stmt = $db->query("SELECT * FROM config");
    $config = $stmt->fetchAll();
}

// Handle Merchant Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'verify_merchant') {
        $merchantId = (int)$_POST['merchant_id'];
        $status = (int)$_POST['status'];
        $stmt = $db->prepare("UPDATE users SET is_kyc_verified = ? WHERE id = ?");
        $stmt->execute([$status, $merchantId]);
        $success_msg = "Merchant verification status updated.";
    } elseif ($_POST['action'] === 'suspend_merchant') {
        $merchantId = (int)$_POST['merchant_id'];
        $status = (int)$_POST['status'];
        $stmt = $db->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
        $stmt->execute([$status, $merchantId]);
        $success_msg = "Merchant account status updated.";
    } elseif ($_POST['action'] === 'update_fees') {
        $merchantId = (int)$_POST['merchant_id'];
        $percent = $_POST['fee_percentage'] === '' ? null : (float)$_POST['fee_percentage'];
        $flat = $_POST['fee_flat'] === '' ? null : (float)$_POST['fee_flat'];
        $stmt = $db->prepare("UPDATE users SET fee_percentage = ?, fee_flat = ? WHERE id = ?");
        $stmt->execute([$percent, $flat, $merchantId]);
        $success_msg = "Merchant custom fees updated.";
    }
}

// Fetch Merchants
$stmt = $db->query("SELECT * FROM users WHERE role = 'merchant' ORDER BY created_at DESC");
$merchants = $stmt->fetchAll();

// Fetch Payouts
$stmt = $db->query("SELECT p.*, u.business_name FROM payouts p JOIN users u ON p.user_id = u.id ORDER BY p.request_date DESC");
$payouts = $stmt->fetchAll();

// Fetch Platform Volume for chart (last 7 days)
$stmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        SUM(amount) as revenue
    FROM transactions 
    WHERE status = 'success'
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 7
");
$revenueRaw = array_reverse($stmt->fetchAll());
$chartLabels = [];
$chartData = [];
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
foreach ($revenueRaw as $r) {
    $chartLabels[] = $days[date('w', strtotime($r['date']))];
    $chartData[] = (float)$r['revenue'];
}

// If no data, provide some mocks for visual
if (empty($chartData)) {
    $chartLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $chartData = [12000, 19000, 15000, 25000, 22000, 30000, 45000];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-slate-400 flex flex-col hidden md:flex">
        <div class="p-6 border-b border-slate-800">
            <a href="index.php" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <i class="lucide-shield-check text-white w-5 h-5"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">Admin Hub</span>
            </a>
        </div>
        
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="?tab=overview" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $tab === 'overview' ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                <i class="lucide-layout-dashboard w-5 h-5"></i>
                Platform Overview
            </a>
            <a href="?tab=merchants" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $tab === 'merchants' ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                <i class="lucide-users w-5 h-5"></i>
                Merchant Directory
            </a>
            <a href="?tab=settlements" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $tab === 'settlements' ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                <i class="lucide-wallet w-5 h-5"></i>
                Settlements
            </a>
            <a href="?tab=compliance" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $tab === 'compliance' ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                <i class="lucide-shield-alert w-5 h-5"></i>
                Compliance Queue
            </a>
            <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-600 uppercase tracking-widest">System</div>
            <a href="?tab=config" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $tab === 'config' ? 'bg-slate-800 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                <i class="lucide-settings w-5 h-5"></i>
                Platform Config
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-red-400 hover:bg-red-500/10 transition-all">
                <i class="lucide-log-out w-5 h-5"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Header -->
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shrink-0">
            <h2 class="font-bold text-slate-900">Platform Administration</h2>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3 pl-6 border-l border-slate-100">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-900">System Admin</p>
                        <p class="text-[10px] text-slate-500 font-medium"><?php echo $user['email']; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold">
                        A
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <?php if (isset($success_msg)): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'overview'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Total GTV</p>
                        <p class="text-2xl font-bold text-slate-900"><?php echo formatCurrency($adminStats['total_gtv']); ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Active Merchants</p>
                        <p class="text-2xl font-bold text-slate-900"><?php echo $adminStats['active_merchants']; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Success Rate</p>
                        <p class="text-2xl font-bold text-emerald-600"><?php echo $adminStats['success_rate']; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pending KYC</p>
                        <p class="text-2xl font-bold text-amber-600"><?php echo $adminStats['pending_kyc']; ?></p>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-slate-900 mb-6">Platform Volume</h3>
                        <div class="h-[350px]">
                            <canvas id="platformChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-slate-900 mb-6">Global Fees</h3>
                        <div class="space-y-4">
                            <?php 
                            $feePercent = array_values(array_filter($config, fn($c) => $c['key'] === 'transaction_fee_percent'))[0]['value'] ?? '1.5';
                            $feeFlat = array_values(array_filter($config, fn($c) => $c['key'] === 'transaction_fee_flat'))[0]['value'] ?? '100';
                            ?>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Default Percentage</p>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $feePercent; ?>%</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Default Flat Fee</p>
                                <p class="text-2xl font-bold text-slate-900"><?php echo formatCurrency($feeFlat); ?></p>
                            </div>
                            <a href="?tab=config" class="block w-full text-center py-3 bg-indigo-50 text-indigo-600 rounded-xl font-bold text-sm hover:bg-indigo-100 transition-all">Edit Global Config</a>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctx = document.getElementById('platformChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode($chartLabels); ?>,
                                datasets: [{
                                    label: 'Volume',
                                    data: <?php echo json_encode($chartData); ?>,
                                    borderColor: '#4f46e5',
                                    borderWidth: 3,
                                    fill: true,
                                    backgroundColor: 'rgba(79, 70, 229, 0.05)',
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { grid: { color: '#f1f5f9' } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    });
                </script>

            <?php elseif ($tab === 'merchants'): ?>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Merchant Directory</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Business</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">KYC</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Fees</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($merchants as $m): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900"><?php echo $m['business_name']; ?></div>
                                            <div class="text-xs text-slate-500"><?php echo $m['email']; ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $m['is_kyc_verified'] == 1 ? 'bg-emerald-100 text-emerald-700' : ($m['is_kyc_verified'] == 2 ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700'); ?>">
                                                <?php echo $m['is_kyc_verified'] == 1 ? 'Verified' : ($m['is_kyc_verified'] == 2 ? 'Submitted' : 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $m['is_suspended'] ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'; ?>">
                                                <?php echo $m['is_suspended'] ? 'Suspended' : 'Active'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php if ($m['fee_percentage'] !== null): ?>
                                                <span class="text-indigo-600 font-bold"><?php echo $m['fee_percentage']; ?>% + <?php echo $m['fee_flat']; ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-400 italic">Global Default</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3" x-data="{ showFees: false }">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="verify_merchant">
                                                    <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $m['is_kyc_verified'] == 1 ? 0 : 1; ?>">
                                                    <button type="submit" class="text-indigo-600 font-bold text-xs hover:underline">
                                                        <?php echo $m['is_kyc_verified'] == 1 ? 'Unverify' : 'Verify'; ?>
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="suspend_merchant">
                                                    <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $m['is_suspended'] ? 0 : 1; ?>">
                                                    <button type="submit" class="font-bold text-xs hover:underline <?php echo $m['is_suspended'] ? 'text-emerald-600' : 'text-red-600'; ?>">
                                                        <?php echo $m['is_suspended'] ? 'Activate' : 'Suspend'; ?>
                                                    </button>
                                                </form>
                                                <button @click="showFees = !showFees" class="text-slate-400 hover:text-indigo-600">
                                                    <i class="lucide-percent w-4 h-4"></i>
                                                </button>

                                                <!-- Fee Edit Modal (Simple Inline) -->
                                                <div x-show="showFees" x-cloak class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50">
                                                    <div class="bg-white p-6 rounded-3xl w-80 shadow-2xl" @click.away="showFees = false">
                                                        <h4 class="font-bold mb-4">Custom Fees: <?php echo $m['business_name']; ?></h4>
                                                        <form method="POST" class="space-y-4">
                                                            <input type="hidden" name="action" value="update_fees">
                                                            <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                            <div>
                                                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Percentage (%)</label>
                                                                <input type="number" step="0.01" name="fee_percentage" value="<?php echo $m['fee_percentage']; ?>" class="w-full px-3 py-2 border rounded-xl outline-none focus:ring-2 focus:ring-indigo-500">
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Flat Fee (NGN)</label>
                                                                <input type="number" name="fee_flat" value="<?php echo $m['fee_flat']; ?>" class="w-full px-3 py-2 border rounded-xl outline-none focus:ring-2 focus:ring-indigo-500">
                                                            </div>
                                                            <div class="flex gap-2">
                                                                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-xl font-bold">Save</button>
                                                                <button type="button" @click="showFees = false" class="flex-1 bg-slate-100 text-slate-600 py-2 rounded-xl font-bold">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($tab === 'settlements'): ?>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 font-bold">Settlement Requests</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Merchant</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Bank Details</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Amount</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($payouts as $p): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900"><?php echo $p['business_name']; ?></div>
                                            <div class="text-xs text-slate-500"><?php echo date('M d, Y', strtotime($p['request_date'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold"><?php echo $p['bank_name']; ?></div>
                                            <div class="text-xs font-mono text-slate-500"><?php echo $p['account_number']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 font-bold"><?php echo formatCurrency($p['amount']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $p['status'] === 'processed' ? 'bg-emerald-100 text-emerald-700' : ($p['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'); ?>">
                                                <?php echo $p['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($p['status'] === 'pending'): ?>
                                                <button class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors">Process</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($tab === 'config'): ?>
                <div class="max-w-4xl">
                    <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                        <h3 class="text-xl font-bold mb-8">Platform Configuration</h3>
                        <div class="grid md:grid-cols-2 gap-6 mb-8">
                            <?php foreach ($config as $c): ?>
                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 group relative">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider"><?php echo $c['key']; ?></p>
                                    <p class="font-mono text-sm text-slate-900 break-all"><?php echo $c['value']; ?></p>
                                    <button @click="$dispatch('edit-config', {key: '<?php echo $c['key']; ?>', value: '<?php echo $c['value']; ?>'})" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 text-indigo-600 hover:bg-indigo-50 rounded transition-all">
                                        <i class="lucide-settings w-4 h-4"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="bg-slate-900 p-8 rounded-2xl text-white" x-data="{ key: '', value: '' }" @edit-config.window="key = $event.detail.key; value = $event.detail.value">
                            <h4 class="font-bold mb-6 flex items-center gap-2">
                                <i class="lucide-settings text-indigo-400 w-5 h-5"></i>
                                Update Setting
                            </h4>
                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="action" value="update_config">
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Key</label>
                                        <input x-model="key" name="key" readonly class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl outline-none text-sm font-mono text-slate-400">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Value</label>
                                        <input x-model="value" name="value" class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono">
                                    </div>
                                </div>
                                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-900/20">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
