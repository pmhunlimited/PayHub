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

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Platform Overview</h1>
                <p class="text-slate-500">A "God-view" of the entire Payhub ecosystem</p>
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

            <div class="grid lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2 bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="font-bold text-slate-900 mb-6">System Activity</h3>
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
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('activityChart').getContext('2d');
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
</body>
</html>
