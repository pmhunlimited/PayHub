<?php
// php-version/sidebar.php
require_once __DIR__ . '/functions.php';
$user = getAuthUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-white border-r border-slate-200 flex flex-col hidden md:flex shrink-0">
    <div class="p-6 border-b border-slate-100">
        <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                <i class="lucide-credit-card text-white w-5 h-5"></i>
            </div>
            <span class="text-xl font-bold tracking-tight text-slate-900">Payhub</span>
        </a>
    </div>
    
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <a href="<?php echo BASE_URL; ?>merchant/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'dashboard.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
            <i class="lucide-layout-dashboard w-5 h-5"></i>
            Overview
        </a>
        <a href="<?php echo BASE_URL; ?>merchant/transactions.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'transactions.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
            <i class="lucide-arrow-up-right w-5 h-5"></i>
            Transactions
        </a>
        <a href="<?php echo BASE_URL; ?>merchant/virtual-accounts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'virtual-accounts.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
            <i class="lucide-wallet w-5 h-5"></i>
            Virtual Accounts
        </a>
        <a href="<?php echo BASE_URL; ?>merchant/payouts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'payouts.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
            <i class="lucide-arrow-down-left w-5 h-5"></i>
            Payouts
        </a>
        <a href="<?php echo BASE_URL; ?>merchant/compliance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'compliance.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
            <i class="lucide-shield-check w-5 h-5"></i>
            Compliance
        </a>
        <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Resources</div>
        <a href="<?php echo BASE_URL; ?>merchant/settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'settings.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
            <i class="lucide-settings w-5 h-5"></i>
            Settings
        </a>
        <a href="<?php echo BASE_URL; ?>merchant/api-keys.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'api-keys.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
            <i class="lucide-code w-5 h-5"></i>
            API Keys
        </a>
        <a href="<?php echo BASE_URL; ?>support.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all">
            <i class="lucide-help-circle w-5 h-5"></i>
            Support
        </a>
    </nav>

    <div class="p-4 border-t border-slate-100">
        <a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50 transition-all">
            <i class="lucide-log-out w-5 h-5"></i>
            Logout
        </a>
    </div>
</aside>
