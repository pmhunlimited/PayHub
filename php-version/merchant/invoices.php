<?php
// php-version/merchant/invoices.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Invoices - Payhub';

$db = Database::connect();
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    if ($_POST['action'] === 'create_invoice') {
        $customer_name = sanitize($_POST['customer_name']);
    $customer_email = sanitize($_POST['customer_email']);
    $amount = (float)$_POST['amount'];
    $due_date = sanitize($_POST['due_date']);
    $description = sanitize($_POST['description']);
    $ref = 'INV-' . strtoupper(bin2hex(random_bytes(4)));

    $stmt = $db->prepare("INSERT INTO invoices (user_id, reference, customer_name, customer_email, amount, due_date, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$user['id'], $ref, $customer_name, $customer_email, $amount, $due_date, $description])) {
            $link = BASE_URL . "invoice.php?ref=" . $ref;
            $body = "<h2>New Invoice from {$user['business_name']}</h2>
                     <p>You have a new invoice for <strong>".formatCurrency($amount)."</strong>.</p>
                     <p>Due Date: " . date('M d, Y', strtotime($due_date)) . "</p>
                     <p><a href='$link' style='display:inline-block; padding:12px 24px; background:#4f46e5; color:white; border-radius:8px; text-decoration:none; font-weight:bold;'>View & Pay Invoice</a></p>";
            sendEmail($customer_email, "Invoice from {$user['business_name']}", $body);
            $success_msg = "Invoice created and sent successfully!";
        } else {
            $error_msg = "Failed to create invoice.";
        }
    } elseif ($_POST['action'] === 'edit_invoice') {
        $id = (int)$_POST['invoice_id'];
        $customer_name = sanitize($_POST['customer_name']);
        $customer_email = sanitize($_POST['customer_email']);
        $amount = (float)$_POST['amount'];
        $due_date = sanitize($_POST['due_date']);
        $description = sanitize($_POST['description']);

        $stmt = $db->prepare("UPDATE invoices SET customer_name = ?, customer_email = ?, amount = ?, due_date = ?, description = ? WHERE id = ? AND user_id = ? AND status = 'pending'");
        if ($stmt->execute([$customer_name, $customer_email, $amount, $due_date, $description, $id, $user['id']])) {
            $success_msg = "Invoice updated successfully!";
        } else {
            $error_msg = "Failed to update invoice.";
        }
    } elseif ($_POST['action'] === 'delete_invoice') {
        $id = (int)$_POST['invoice_id'];
        $stmt = $db->prepare("DELETE FROM invoices WHERE id = ? AND user_id = ? AND status = 'pending'");
        if ($stmt->execute([$id, $user['id']])) {
            $success_msg = "Invoice deleted.";
        } else {
            $error_msg = "Failed to delete invoice.";
        }
    }
}

$stmt = $db->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$invoices = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false, showCreate: false, showEdit: false, editingInv: {id:null, customer_name:'', customer_email:'', amount:0, due_date:'', description:''} }">
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

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900 mb-2">Invoices</h1>
                        <p class="text-slate-500">Create and manage professional invoices for your customers</p>
                    </div>
                    <button @click="showCreate = true" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                        <i data-lucide="plus" class="w-5 h-5"></i> Create Invoice
                    </button>
                </div>

                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h3 class="font-bold text-slate-900">All Invoices</h3>
                        <div class="flex gap-2">
                            <button class="p-2 text-slate-400 hover:text-indigo-600 transition-colors"><i data-lucide="filter" class="w-5 h-5"></i></button>
                            <button class="p-2 text-slate-400 hover:text-indigo-600 transition-colors"><i data-lucide="download" class="w-5 h-5"></i></button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($invoices as $inv): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900"><?php echo $inv['customer_name']; ?></div>
                                            <div class="text-xs text-slate-500 font-medium"><?php echo $inv['customer_email']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-bold text-slate-900"><?php echo formatCurrency($inv['amount']); ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-slate-600"><?php echo date('M d, Y', strtotime($inv['due_date'])); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider <?php echo $inv['status'] === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                                <?php echo $inv['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button onclick="const link = '<?php echo BASE_URL . "invoice.php?ref=" . $inv['reference']; ?>'; navigator.clipboard.writeText(link); alert('Invoice link copied!');" class="p-2 text-slate-400 hover:text-indigo-600 transition-all hover:bg-indigo-50 rounded-lg" title="Copy Invoice Link">
                                                    <i data-lucide="copy" class="w-4 h-4"></i>
                                                </button>
                                            <?php if ($inv['status'] === 'pending'): ?>
                                                    <button @click="editingInv = <?php echo htmlspecialchars(json_encode($inv), ENT_QUOTES, 'UTF-8'); ?>; showEdit = true;" class="p-2 text-slate-400 hover:text-indigo-600 transition-all hover:bg-indigo-50 rounded-lg" title="Edit Invoice">
                                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                    </button>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this invoice?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="delete_invoice">
                                                        <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 transition-all hover:bg-rose-50 rounded-lg" title="Delete Invoice">
                                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-[10px] text-slate-400 font-bold uppercase italic">Finalized</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-20 text-center">
                                            <div class="flex flex-col items-center gap-2 opacity-30">
                                                <i data-lucide="file-text" class="w-12 h-12"></i>
                                                <p class="font-bold">No invoices generated yet</p>
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

        <!-- Create Invoice Modal -->
        <div x-show="showCreate" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-lg overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Create New Invoice</h3>
                    <button @click="showCreate = false" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="create_invoice">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Customer Name</label>
                                <input type="text" name="customer_name" required placeholder="Jane Doe" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Customer Email</label>
                                <input type="email" name="customer_email" required placeholder="jane@example.com" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Amount (NGN)</label>
                                <input type="number" step="0.01" name="amount" required placeholder="0.00" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Due Date</label>
                                <input type="date" name="due_date" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Description</label>
                            <textarea name="description" rows="3" placeholder="Service details..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 mt-4">Generate & Send Invoice</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Invoice Modal -->
        <div x-show="showEdit" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-lg overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Edit Invoice</h3>
                    <button @click="showEdit = false" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="edit_invoice">
                        <input type="hidden" name="invoice_id" :value="editingInv.id">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Customer Name</label>
                                <input type="text" name="customer_name" x-model="editingInv.customer_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Customer Email</label>
                                <input type="email" name="customer_email" x-model="editingInv.customer_email" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Amount (NGN)</label>
                                <input type="number" step="0.01" name="amount" x-model="editingInv.amount" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Due Date</label>
                                <input type="date" name="due_date" x-model="editingInv.due_date" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Description</label>
                            <textarea name="description" x-model="editingInv.description" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 mt-4">Save Changes</button>
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