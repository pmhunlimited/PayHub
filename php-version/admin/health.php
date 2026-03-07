<?php
// php-version/admin/health.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'System Health - Admin Hub';

$db = Database::connect();

// Mock health stats
$stats = [
    'api_latency' => '45ms',
    'uptime' => '1,248h 12m',
    'active_connections' => rand(10, 50),
    'db_status' => 'Connected',
    'last_backup' => date('Y-m-d H:i')
];

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">System Health</h1>
                <p class="text-slate-500">Monitor API uptime, database performance, and server latency in real-time</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <h4 class="text-slate-500 text-sm font-bold mb-4 uppercase tracking-widest">API Latency</h4>
                    <p class="text-4xl font-bold text-emerald-600"><?php echo $stats['api_latency']; ?></p>
                    <div class="mt-4 flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: 85%"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-400">Optimal</span>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <h4 class="text-slate-500 text-sm font-bold mb-4 uppercase tracking-widest">System Uptime</h4>
                    <p class="text-4xl font-bold text-slate-900"><?php echo $stats['uptime']; ?></p>
                    <p class="text-xs text-slate-400 mt-4">Continuous service since last patch</p>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <h4 class="text-slate-500 text-sm font-bold mb-4 uppercase tracking-widest">Database</h4>
                    <p class="text-4xl font-bold text-indigo-600"><?php echo $stats['db_status']; ?></p>
                    <p class="text-xs text-slate-400 mt-4">Last automatic backup: <?php echo $stats['last_backup']; ?></p>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
