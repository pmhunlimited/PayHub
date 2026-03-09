<?php
// php-version/dashboard.php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = getAuthUser();
if ($user['role'] === 'admin') {
    redirect('../admin/index.php');
}

$tab = $_GET['tab'] ?? 'overview';
$pageTitle = 'Dashboard - Payhub';

// Fetch stats for overview
$stats = get_stats($user['id']);

$db = Database::connect();

// Fetch transactions
$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$recentTransactions = $stmt->fetchAll();

// Fetch revenue data for chart (last 7 days)
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        SUM(amount) as revenue
    FROM transactions 
    WHERE user_id = ? AND status = 'success'
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 7
");
$stmt->execute([$user['id']]);
$revenueRaw = array_reverse($stmt->fetchAll());
$revenueData = [];
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
foreach ($revenueRaw as $r) {
    $revenueData[] = [
        'name' => $days[date('w', strtotime($r['date']))],
        'revenue' => (float)$r['revenue']
    ];
}

// Handle Payout Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_payout') {
    $amount = (float)$_POST['amount'];
    if ($amount > 0 && $amount <= $user['wallet_balance']) {
        if ($user['settlement_bank'] && $user['settlement_account_number']) {
            $db->beginTransaction();
            try {
                // Deduct balance
                $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                $stmt->execute([$amount, $user['id']]);
                
                // Create payout record
                $stmt = $db->prepare("INSERT INTO payouts (user_id, amount, bank_name, account_number, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$user['id'], $amount, $user['settlement_bank'], $user['settlement_account_number']]);
                
                $db->commit();
                $success_msg = "Payout request submitted successfully.";
                // Refresh user data
                $user = getAuthUser();
                $stats = get_stats($user['id']);
            } catch (Exception $e) {
                $db->rollBack();
                $error_msg = "Payout failed: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please set up your settlement bank details in settings first.";
        }
    } else {
        $error_msg = "Invalid amount or insufficient balance.";
    }
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
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <?php if (isset($success_msg)): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error_msg)): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-medium">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'overview'): ?>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 mb-2">Welcome back, <?php echo $user['business_name']; ?></h1>
                        <p class="text-slate-500">Here's what's happening with your business today.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="?tab=payouts" class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
                            <i data-lucide="arrow-down-left text-amber-500 w-4.5 h-4.5"></i>
                            New Payout
                        </a>
                        <a href="#" class="flex items-center gap-2 bg-indigo-600 px-4 py-2.5 rounded-xl text-sm font-bold text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                            <i data-lucide="plus w-4.5 h-4.5"></i>
                            Create Invoice
                        </a>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                <i data-lucide="wallet w-5 h-5"></i>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Balance</span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900"><?php echo formatCurrency($stats['balance']); ?></p>
                        <p class="text-xs text-slate-500 mt-1">Available for payout</p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                                <i data-lucide="bar-chart-3 w-5 h-5"></i>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Volume</span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900"><?php echo formatCurrency($stats['total_volume']); ?></p>
                        <p class="text-xs text-emerald-500 mt-1">+12.5% from last week</p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                                <i data-lucide="arrow-up-right w-5 h-5"></i>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Transactions</span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900"><?php echo $stats['transaction_count']; ?></p>
                        <p class="text-xs text-slate-500 mt-1">Total processed</p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600">
                                <i data-lucide="shield-check w-5 h-5"></i>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Success Rate</span>
                        </div>
                        <p class="text-2xl font-bold text-slate-900"><?php echo $stats['success_rate']; ?></p>
                        <p class="text-xs text-slate-500 mt-1">High reliability</p>
                    </div>
                </div>

                <!-- Chart and Methods -->
                <div class="grid lg:grid-cols-3 gap-8 mb-8">
                    <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-slate-900 mb-6">Revenue Overview</h3>
                        <div class="h-[300px]">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-slate-900 mb-6">Payment Methods</h3>
                        <div class="space-y-6">
                            <div>
                                <div class="flex justify-between text-sm font-bold mb-2">
                                    <span>Card</span>
                                    <span>65%</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-600 rounded-full" style="width: 65%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm font-bold mb-2">
                                    <span>Bank Transfer</span>
                                    <span>25%</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: 25%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm font-bold mb-2">
                                    <span>USSD</span>
                                    <span>10%</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-500 rounded-full" style="width: 10%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900">Recent Transactions</h3>
                        <a href="?tab=transactions" class="text-sm font-bold text-indigo-600 hover:text-indigo-700">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($recentTransactions as $tx): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-sm"><?php echo $tx['reference']; ?></td>
                                        <td class="px-6 py-4 text-sm"><?php echo $tx['customer_email']; ?></td>
                                        <td class="px-6 py-4 text-sm font-bold"><?php echo formatCurrency($tx['amount']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $tx['status'] === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                                <?php echo $tx['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentTransactions)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">No transactions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
<script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>