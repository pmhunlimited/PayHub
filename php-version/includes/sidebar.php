<?php
// php-version/includes/sidebar.php
require_once __DIR__ . '/functions.php';
$user = getAuthUser();
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'merchant';
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
        <?php if ($role === 'admin'): ?>
            <div class="px-3 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Operations</div>
            <a href="<?php echo BASE_URL; ?>admin/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-layout-dashboard w-5 h-5"></i>
                Overview
            </a>
            <a href="<?php echo BASE_URL; ?>admin/merchants.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'merchants.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-users w-5 h-5"></i>
                Merchants
            </a>
            <a href="<?php echo BASE_URL; ?>admin/compliance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'compliance.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-shield-check w-5 h-5"></i>
                Compliance
            </a>
            <a href="<?php echo BASE_URL; ?>admin/settlements.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'settlements.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-credit-card w-5 h-5"></i>
                Settlements
            </a>
            <a href="<?php echo BASE_URL; ?>admin/disputes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'disputes.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-gavel w-5 h-5"></i>
                Disputes
            </a>

            <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">System</div>
            <a href="<?php echo BASE_URL; ?>admin/health.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'health.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-activity w-5 h-5"></i>
                System Health
            </a>
            <a href="<?php echo BASE_URL; ?>admin/webhooks.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'webhooks.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-webhook w-5 h-5"></i>
                Webhook Logs
            </a>
            <a href="<?php echo BASE_URL; ?>admin/api-manager.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'api-manager.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-key w-5 h-5"></i>
                API Manager
            </a>

            <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Content & Support</div>
            <a href="<?php echo BASE_URL; ?>admin/tickets.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'tickets.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-ticket w-5 h-5"></i>
                Tickets
            </a>
            <a href="<?php echo BASE_URL; ?>admin/blog.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'blog.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-file-text w-5 h-5"></i>
                Blog Manager
            </a>

            <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Configuration</div>
            <a href="<?php echo BASE_URL; ?>admin/staff.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'staff.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-users w-5 h-5"></i>
                Staff Management
            </a>
            <a href="<?php echo BASE_URL; ?>admin/email-settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'email-settings.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-bell w-5 h-5"></i>
                Email Settings
            </a>
            <a href="<?php echo BASE_URL; ?>admin/config.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'config.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-settings w-5 h-5"></i>
                Platform Config
            </a>
        <?php else: ?>
            <div class="px-3 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Main</div>
            <a href="<?php echo BASE_URL; ?>merchant/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'dashboard.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-layout-dashboard w-5 h-5"></i>
                Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>merchant/compliance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'compliance.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-shield-alert w-5 h-5"></i>
                Compliance
            </a>

            <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Collections</div>
            <a href="<?php echo BASE_URL; ?>merchant/transactions.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'transactions.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-arrow-up-right w-5 h-5"></i>
                Transactions
            </a>
            <?php if ($user['business_type'] !== 'Starter'): ?>
            <a href="<?php echo BASE_URL; ?>merchant/virtual-accounts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'virtual-accounts.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-wallet w-5 h-5"></i>
                Virtual Accounts
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>merchant/invoices.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'invoices.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-file-text w-5 h-5"></i>
                Invoices
            </a>
            <a href="<?php echo BASE_URL; ?>merchant/subscriptions.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'subscriptions.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-refresh-ccw w-5 h-5"></i>
                Subscriptions
            </a>

            <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Finance</div>
            <a href="<?php echo BASE_URL; ?>merchant/payouts.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'payouts.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-arrow-down-left w-5 h-5"></i>
                Payouts
            </a>
            <a href="<?php echo BASE_URL; ?>merchant/ledger.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'ledger.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-activity w-5 h-5"></i>
                Balance Ledger
            </a>
            <a href="<?php echo BASE_URL; ?>merchant/disputes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'disputes.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-shield-check w-5 h-5"></i>
                Disputes
            </a>

            <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Management</div>
            <a href="<?php echo BASE_URL; ?>merchant/customers.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'customers.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-users w-5 h-5"></i>
                Customers
            </a>
            <a href="<?php echo BASE_URL; ?>merchant/tickets.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'tickets.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-ticket w-5 h-5"></i>
                Support
            </a>

            <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Developer</div>
            <a href="<?php echo BASE_URL; ?>merchant/api-keys.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'api-keys.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-code w-5 h-5"></i>
                API Keys
            </a>
            <a href="<?php echo BASE_URL; ?>merchant/settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all <?php echo $current_page === 'settings.php' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                <i class="lucide-settings w-5 h-5"></i>
                Settings
            </a>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-slate-100">
        <a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50 transition-all">
            <i class="lucide-log-out w-5 h-5"></i>
            Logout
        </a>
    </div>
</aside>
