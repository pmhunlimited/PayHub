<?php
// php-version/admin/transactions.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Transaction Management - Admin Hub';

$db = Database::connect();

$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');

$query = "SELECT t.*, u.business_name FROM transactions t JOIN users u ON t.user_id = u.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (t.reference LIKE ? OR t.customer_email LIKE ? OR u.business_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $query .= " AND t.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY t.created_at DESC LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden"
      x-data="{
          mobileMenuOpen: false,
          selectedTx: null,
          search: '<?php echo addslashes($search); ?>',
          status: '<?php echo addslashes($status); ?>',
          allTransactions: <?php echo htmlspecialchars(json_encode($transactions), ENT_QUOTES, 'UTF-8'); ?>,
          get filteredTransactions() {
              return this.allTransactions.filter(t => {
                  const matchesSearch = !this.search ||
                      t.reference.toLowerCase().includes(this.search.toLowerCase()) ||
                      t.customer_email.toLowerCase().includes(this.search.toLowerCase()) ||
                      t.business_name.toLowerCase().includes(this.search.toLowerCase());
                  const matchesStatus = !this.status || t.status === this.status;
                  return matchesSearch && matchesStatus;
              });
          }
      }">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-2">Platform Transactions</h1>
                    <p class="text-slate-500">Monitor and manage all payments across the platform</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <div class="flex gap-3">
                        <div class="relative w-64">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
                            <input type="text" x-model="search" placeholder="Quick search..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <select x-model="status" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option value="">All Statuses</option>
                            <option value="success">Success</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                        <form method="GET" class="hidden">
                             <input type="text" name="search" :value="search">
                             <input type="text" name="status" :value="status">
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Reference</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Merchant</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Amount</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="tx in filteredTransactions" :key="tx.id">
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs text-slate-500" x-text="tx.reference"></td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900" x-text="tx.business_name"></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600" x-text="tx.customer_email"></td>
                                    <td class="px-6 py-4 text-sm font-bold text-slate-900" x-text="'₦' + parseFloat(tx.amount).toLocaleString(undefined, {minimumFractionDigits:2})"></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                              :class="tx.status === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'"
                                              x-text="tx.status">
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button @click="selectedTx = tx" class="text-indigo-600 font-bold text-xs hover:underline">View Details</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredTransactions.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-medium">No matching transactions found.</td>
                            </tr>
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
                    <button @click="selectedTx = null" class="px-6 py-2 bg-white border border-slate-200 rounded-xl font-bold text-slate-700 hover:bg-slate-50 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </main>
<script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
