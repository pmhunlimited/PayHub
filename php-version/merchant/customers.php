<?php
// php-version/merchant/customers.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Customer Directory - Payhub';

$db = Database::connect();
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_customer') {
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);

        // Split name for Paystack
        $name_parts = explode(' ', $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';

        $customer_res = paystack_call('customer', 'POST', [
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'metadata' => [
                'merchant_id' => $user['id']
            ]
        ]);

        if ($customer_res && $customer_res['status']) {
            $stmt = $db->prepare("INSERT IGNORE INTO customers (user_id, full_name, email, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], $full_name, $email, $phone]);
            $success_msg = "Customer added successfully!";
        } else {
            $error_msg = "Paystack Error: " . ($customer_res['message'] ?? 'Failed to create customer');
        }
    } elseif ($_POST['action'] === 'generate_va') {
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);

        // 1. Fetch customer code from Paystack
        $customer_res = paystack_call("customer/$email", 'GET');

        if ($customer_res && $customer_res['status']) {
            $customer_code = $customer_res['data']['customer_code'];

            // 2. Create Dedicated Virtual Account
            $dva_res = paystack_call('dedicated_account', 'POST', [
                'customer' => $customer_code
            ]);

            if ($dva_res && $dva_res['status']) {
                $acc = $dva_res['data'];
                $bank = $acc['bank']['name'] ?? 'Virtual Bank';
                $number = $acc['account_number'] ?? '';
                $name = $acc['account_name'] ?? $user['business_name'];

                $stmt = $db->prepare("INSERT INTO virtual_accounts (user_id, bank_name, account_number, account_name, customer_email) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user['id'], $bank, $number, $name, $email]);

                $success_msg = "Virtual account generated for $full_name!";
            } else {
                $error_msg = "Failed to generate VA: " . ($dva_res['message'] ?? 'Unknown error');
            }
        } else {
            $error_msg = "Failed to fetch customer: " . ($customer_res['message'] ?? 'Unknown error');
        }
    }
}

$stmt = $db->prepare("SELECT * FROM customers WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$customers = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false, showAddCustomer: false }">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                <?php if ($success_msg): ?>
                    <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-medium"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Customers</h1>
                        <p class="text-slate-500">A centralized database of everyone who has paid you</p>
                    </div>
                    <button @click="showAddCustomer = true" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                        <i data-lucide="plus" class="w-5 h-5"></i> Add Customer
                    </button>
                </div>

                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h3 class="font-bold text-slate-900">All Customers</h3>
                        <div class="relative w-64">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
                            <input type="text" placeholder="Search customers..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer Details</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Phone Number</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date Joined</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($customers as $c): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 font-bold text-xs uppercase">
                                                    <?php echo substr($c['full_name'], 0, 2); ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900"><?php echo $c['full_name']; ?></div>
                                                    <div class="text-[10px] text-slate-400 font-medium"><?php echo $c['email']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-mono text-slate-600"><?php echo $c['phone']; ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-slate-500"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <form method="POST" class="inline" onsubmit="return confirm('Generate a dedicated virtual account for this customer?');">
                                                    <input type="hidden" name="action" value="generate_va">
                                                    <input type="hidden" name="email" value="<?php echo $c['email']; ?>">
                                                    <input type="hidden" name="full_name" value="<?php echo $c['full_name']; ?>">
                                                    <button type="submit" class="p-2 text-slate-400 hover:text-indigo-600 transition-all hover:bg-indigo-50 rounded-lg" title="Generate Virtual Account">
                                                        <i data-lucide="wallet" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                                <button class="p-2 text-slate-400 hover:text-slate-600 transition-colors"><i data-lucide="more-horizontal" class="w-4 h-4"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-20 text-center">
                                            <div class="flex flex-col items-center gap-2 opacity-30">
                                                <i data-lucide="users" class="w-12 h-12"></i>
                                                <p class="font-bold">No customers found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Customer Modal -->
        <div x-show="showAddCustomer" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-md overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Add New Customer</h3>
                    <button @click="showAddCustomer = false" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_customer">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Full Name</label>
                            <input type="text" name="full_name" required placeholder="John Doe" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email Address</label>
                            <input type="email" name="email" required placeholder="customer@example.com" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Phone Number</label>
                            <input type="tel" name="phone" required placeholder="08012345678" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 mt-4">Create Customer</button>
                    </form>
                </div>
            </div>
        </div>

    <?php include "../includes/merchant-quick-actions.php"; ?>
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