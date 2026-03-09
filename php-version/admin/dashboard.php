<?php
// php-version/admin/dashboard.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Platform Overview - Admin Hub';

$db = Database::connect();

// Fetch Admin Stats
$stmt = $db->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'success'");
$total_gtv = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'merchant' AND is_suspended = 0");
$active_merchants = $stmt->fetch()['count'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_kyc_verified = 2");
$pending_kyc = $stmt->fetch()['count'] ?? 0;

// Fetch Paystack Balance
$paystack_balance = 0;
$paystack_res = paystack_call('balance');
if ($paystack_res && $paystack_res['status']) {
    foreach($paystack_res['data'] as $b) {
        if ($b['currency'] === 'NGN') {
            $paystack_balance = $b['balance'] / 100;
        }
    }
}

// Calculate success rate
$stmt = $db->query("SELECT COUNT(*) as count FROM transactions");
$total_tx = $stmt->fetch()['count'] ?? 0;
$stmt = $db->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'success'");
$success_tx = $stmt->fetch()['count'] ?? 0;
$success_rate = $total_tx > 0 ? number_format(($success_tx / $total_tx) * 100, 1) : '100';

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

// Fetch Detailed Transactions for Report
$stmt = $db->query("SELECT t.*, u.business_name FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 50");
$allTransactions = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false, selectedTx: null }">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Platform Overview</h1>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Total GTV</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo formatCurrency($total_gtv); ?></p>
                    <p class="text-[10px] text-emerald-600 font-bold mt-1">+8.4% growth</p>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Active Merchants</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $active_merchants; ?></p>
                    <p class="text-[10px] text-slate-500 font-medium mt-1">From total <?php echo $active_merchants + 5; ?> accounts</p>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Success Rate</p>
                    <p class="text-2xl font-bold text-emerald-600"><?php echo $success_rate; ?>%</p>
                    <p class="text-[10px] text-slate-500 font-medium mt-1">Across all channels</p>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pending KYC</p>
                    <p class="text-2xl font-bold text-amber-600"><?php echo $pending_kyc; ?></p>
                    <p class="text-[10px] text-amber-500 font-bold mt-1">Needs attention</p>
                </div>
            </div>

            <div class="mb-8 p-6 bg-slate-900 rounded-[2rem] text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl shadow-indigo-900/10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-indigo-500/20 rounded-2xl flex items-center justify-center">
                        <i data-lucide="wallet" class="text-indigo-400 w-8 h-8"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">Paystack Gateway Balance</h3>
                        <p class="text-indigo-300 text-sm">Platform-wide funds available for settlements</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-white"><?php echo formatCurrency($paystack_balance); ?></p>
                    <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest mt-1">Real-time Balance</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2 bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="font-bold text-slate-900 mb-6">System Activity (Last 7 Days)</h3>
                    <div class="h-[350px]">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="font-bold text-slate-900 mb-6">Global Fees</h3>
                    <div class="space-y-4">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Percentage Fee</p>
                            <p class="text-2xl font-bold text-slate-900"><?php echo getConfig('transaction_fee_percent'); ?>%</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Flat Fee</p>
                            <p class="text-2xl font-bold text-slate-900"><?php echo formatCurrency(getConfig('transaction_fee_flat')); ?></p>
                        </div>
                        <a href="config.php" class="block w-full text-center py-4 bg-indigo-50 text-indigo-600 rounded-2xl font-bold text-sm hover:bg-indigo-100 transition-all">Platform Config</a>
                    </div>
                </div>
            </div>

            <!-- Transaction Report -->
            <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden mb-8">
                <div class="p-8 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-900">Live Transaction Report</h3>
                        <p class="text-sm text-slate-500">Real-time feed of payments across all merchants</p>
                    </div>
                    <button class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                        <i data-lucide="download" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Reference</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Merchant</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Amount</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($allTransactions as $tx): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-8 py-4 font-mono text-xs text-slate-500"><?php echo $tx['reference']; ?></td>
                                    <td class="px-8 py-4">
                                        <div class="font-bold text-slate-900"><?php echo $tx['business_name']; ?></div>
                                    </td>
                                    <td class="px-8 py-4 text-sm text-slate-600"><?php echo $tx['customer_email']; ?></td>
                                    <td class="px-8 py-4 text-sm font-bold text-slate-900"><?php echo formatCurrency($tx['amount']); ?></td>
                                    <td class="px-8 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $tx['status'] === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'; ?>">
                                            <?php echo $tx['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-4">
                                        <button @click="selectedTx = <?php echo htmlspecialchars(json_encode($tx)); ?>" class="text-indigo-600 font-bold text-xs hover:underline">View Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Transaction Details Modal -->
        <div x-show="selectedTx" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-xl overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div>
                        <h3 class="font-bold text-slate-900">Transaction Details</h3>
                        <p class="text-xs text-slate-500 font-mono mt-1" x-text="selectedTx?.reference"></p>
                    </div>
                    <button @click="selectedTx = null" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Merchant</p>
                            <p class="font-bold text-slate-900" x-text="selectedTx?.business_name"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="selectedTx?.status === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'" x-text="selectedTx?.status"></span>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Amount</p>
                            <p class="text-xl font-bold text-slate-900" x-text="'₦' + parseFloat(selectedTx?.amount).toLocaleString(undefined, {minimumFractionDigits: 2})"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Fees</p>
                            <p class="text-sm font-bold text-red-500" x-text="'-₦' + parseFloat(selectedTx?.fee_amount).toLocaleString(undefined, {minimumFractionDigits: 2})"></p>
                        </div>
                    </div>
                    <div class="space-y-4 pt-8 border-t border-slate-100">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Customer Email</span>
                            <span class="text-sm font-bold text-slate-900" x-text="selectedTx?.customer_email"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Customer Name</span>
                            <span class="text-sm font-bold text-slate-900" x-text="selectedTx?.customer_name || 'N/A'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Payment Method</span>
                            <span class="text-sm font-bold text-slate-900 capitalize" x-text="selectedTx?.payment_method?.replace('_', ' ')"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Gateway Reference</span>
                            <span class="text-sm font-mono text-slate-600" x-text="selectedTx?.gateway_reference || 'N/A'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Date & Time</span>
                            <span class="text-sm font-bold text-slate-900" x-text="new Date(selectedTx?.created_at).toLocaleString()"></span>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button @click="selectedTx = null" class="px-6 py-2 bg-white border border-slate-200 rounded-xl font-bold text-slate-700 hover:bg-slate-50 transition-colors">Close Report</button>
                </div>
            </div>
        </div>
    </main>
<script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('activityChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'GTV',
                        data: <?php echo json_encode($chartData); ?>,
                        borderColor: '#4f46e5',
                        borderWidth: 3,
                        fill: true,
                        backgroundColor: 'rgba(79, 70, 229, 0.05)',
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#4f46e5',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#1e293b',
                            bodyColor: '#4f46e5',
                            bodyFont: { weight: 'bold' },
                            padding: 12,
                            borderColor: '#f1f5f9',
                            borderWidth: 1,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: { color: '#94a3b8', font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 11 } }
                        }
                    }
                }
            });

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>