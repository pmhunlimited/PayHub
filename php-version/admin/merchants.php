<?php
// php-version/admin/merchants.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Merchant Directory - Admin Hub';

$db = Database::connect();

// Handle Actions
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
    } elseif ($_POST['action'] === 'update_details') {
        $merchantId = (int)$_POST['merchant_id'];
        $biz_name = sanitize($_POST['business_name']);
        $email = sanitize($_POST['email']);
        $stmt = $db->prepare("UPDATE users SET business_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$biz_name, $email, $merchantId]);
        $success_msg = "Merchant details updated.";
    } elseif ($_POST['action'] === 'toggle_payout_review') {
        $merchantId = (int)$_POST['merchant_id'];
        $status = (int)$_POST['status'];
        $stmt = $db->prepare("UPDATE users SET require_payout_review = ? WHERE id = ?");
        $stmt->execute([$status, $merchantId]);
        $success_msg = "Payout review status updated for merchant.";
    } elseif ($_POST['action'] === 'impersonate') {
        $merchantId = (int)$_POST['merchant_id'];
        $_SESSION['user_id'] = $merchantId;
        $_SESSION['role'] = 'merchant';
        redirect('../merchant/dashboard.php');
    } elseif ($_POST['action'] === 'reset_password') {
        $merchantId = (int)$_POST['merchant_id'];
        $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_pass, $merchantId]);
        $success_msg = "Merchant password has been reset.";
    } elseif ($_POST['action'] === 'soft_delete') {
        $merchantId = (int)$_POST['merchant_id'];
        $stmt = $db->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$merchantId]);
        $success_msg = "Merchant account moved to deleted list.";
    } elseif ($_POST['action'] === 'restore_merchant') {
        $merchantId = (int)$_POST['merchant_id'];
        $stmt = $db->prepare("UPDATE users SET is_deleted = 0 WHERE id = ?");
        $stmt->execute([$merchantId]);
        $success_msg = "Merchant account restored.";
    } elseif ($_POST['action'] === 'permanent_delete') {
        $merchantId = (int)$_POST['merchant_id'];
        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM virtual_accounts WHERE user_id = ?")->execute([$merchantId]);
            $db->prepare("DELETE FROM transactions WHERE user_id = ?")->execute([$merchantId]);
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$merchantId]);
            $db->commit();
            $success_msg = "Merchant and all associated data permanently deleted.";
        } catch (Exception $e) {
            $db->rollBack();
            $error_msg = "Delete failed: " . $e->getMessage();
        }
    }
}

$tab = $_GET['tab'] ?? 'active';
if ($tab === 'deleted') {
    $stmt = $db->query("SELECT * FROM users WHERE role = 'merchant' AND is_deleted = 1 ORDER BY created_at DESC");
} else {
    $stmt = $db->query("SELECT * FROM users WHERE role = 'merchant' AND is_deleted = 0 ORDER BY created_at DESC");
}
$merchants = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden" x-data="{ showFees: false, showEdit: false, showReset: false, merchantId: null, feePercentage: null, feeFlat: null, merchantName: '', merchantEmail: '' }">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <?php if (isset($success_msg)): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-2">Merchant Directory</h1>
                    <div class="flex gap-4 mt-2">
                        <a href="?tab=active" class="text-sm font-bold <?php echo $tab === 'active' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400 hover:text-slate-600'; ?> pb-1">Active Merchants</a>
                        <a href="?tab=deleted" class="text-sm font-bold <?php echo $tab === 'deleted' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400 hover:text-slate-600'; ?> pb-1">Deleted Accounts</a>
                    </div>
                </div>
                <?php if ($tab === 'active'): ?>
                    <div class="text-xs text-slate-500 bg-slate-100 px-3 py-1 rounded-full font-medium">
                        Empty fee fields will revert to Global Platform Fees
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Business Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">KYC Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Account Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Payout Review</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Custom Fees</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($merchants as $m): ?>
                                <tr class="hover:bg-slate-50/30 transition-colors">
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
                                    <td class="px-6 py-4">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_payout_review">
                                            <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $m['require_payout_review'] ? 0 : 1; ?>">
                                            <button type="submit" class="px-3 py-1 rounded-xl text-[10px] font-bold transition-all <?php echo $m['require_payout_review'] ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400'; ?>">
                                                <?php echo $m['require_payout_review'] ? 'Review ON' : 'Review OFF'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if ($m['fee_percentage'] !== null): ?>
                                            <span class="text-indigo-600 font-bold"><?php echo $m['fee_percentage']; ?>% + <?php echo $m['fee_flat']; ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic">Global Default</span>
                                        <?php endif; ?>
                                        <button @click="showFees = true; merchantId = <?php echo $m['id']; ?>; feePercentage = '<?php echo $m['fee_percentage']; ?>'; feeFlat = '<?php echo $m['fee_flat']; ?>'; merchantName = '<?php echo addslashes($m['business_name']); ?>'" class="ml-2 text-slate-300 hover:text-indigo-600"><i class="lucide-percent w-3 h-3"></i></button>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <?php if ($tab === 'active'): ?>
                                                <button @click="showEdit = true; merchantId = <?php echo $m['id']; ?>; merchantName = '<?php echo addslashes($m['business_name']); ?>'; merchantEmail = '<?php echo addslashes($m['email']); ?>'" class="text-slate-400 hover:text-indigo-600 transition-colors" title="Edit Details">
                                                    <i class="lucide-edit w-4 h-4"></i>
                                                </button>
                                                <button @click="showReset = true; merchantId = <?php echo $m['id']; ?>; merchantName = '<?php echo addslashes($m['business_name']); ?>'" class="text-slate-400 hover:text-amber-600 transition-colors" title="Reset Password">
                                                    <i class="lucide-key w-4 h-4"></i>
                                                </button>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="impersonate">
                                                    <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-800" title="Login as Merchant">
                                                        <i class="lucide-log-in w-4 h-4"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('Move this merchant to deleted list?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="soft_delete">
                                                    <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                    <button type="submit" class="text-slate-300 hover:text-red-500" title="Delete Account">
                                                        <i class="lucide-trash-2 w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="restore_merchant">
                                                    <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                    <button type="submit" class="text-emerald-600 font-bold text-xs hover:underline">Restore</button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('PERMANENTLY DELETE this merchant and all associated data? This cannot be undone.');">
                                                    <input type="hidden" name="action" value="permanent_delete">
                                                    <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                    <button type="submit" class="text-red-600 font-bold text-xs hover:underline">Delete Permanently</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reset Password Modal -->
        <div x-show="showReset" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Reset Password</h3>
                    <button @click="showReset = false" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full transition-colors">
                        <i class="lucide-x w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <p class="text-sm text-slate-500 mb-6">Enter new password for <span class="text-slate-900 font-bold" x-text="merchantName"></span></p>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="merchant_id" :value="merchantId">
                        <input type="password" name="password" required placeholder="New secure password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none">
                        <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold hover:bg-slate-800 transition-all shadow-lg">Reset Merchant Password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div x-show="showEdit" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Edit Merchant</h3>
                    <button @click="showEdit = false" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full transition-colors">
                        <i class="lucide-x w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_details">
                        <input type="hidden" name="merchant_id" :value="merchantId">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Business Name</label>
                            <input type="text" name="business_name" x-model="merchantName" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email Address</label>
                            <input type="email" name="email" x-model="merchantEmail" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none">
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Update Merchant</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fee Modal -->
        <div x-show="showFees" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Custom Fees</h3>
                    <button @click="showFees = false" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full transition-colors">
                        <i class="lucide-x w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8">
                    <p class="text-sm font-medium text-slate-500 mb-6">Set custom fees for <span class="text-slate-900 font-bold" x-text="merchantName"></span></p>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_fees">
                        <input type="hidden" name="merchant_id" :value="merchantId">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Percentage (%)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="fee_percentage" :value="feePercentage" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Flat Fee (NGN)</label>
                            <div class="relative">
                                <input type="number" name="fee_flat" :value="feeFlat" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">₦</span>
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Save Custom Fees</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
