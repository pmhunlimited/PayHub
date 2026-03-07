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
    } elseif ($_POST['action'] === 'impersonate') {
        $merchantId = (int)$_POST['merchant_id'];
        $_SESSION['user_id'] = $merchantId;
        $_SESSION['role'] = 'merchant';
        redirect('../merchant/dashboard.php');
    }
}

$stmt = $db->query("SELECT * FROM users WHERE role = 'merchant' ORDER BY created_at DESC");
$merchants = $stmt->fetchAll();

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden" x-data="{ showFees: false, merchantId: null, feePercentage: null, feeFlat: null, merchantName: '' }">
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
                    <p class="text-slate-500">Manage KYC, account status, and custom fees for all merchants</p>
                </div>
                <div class="text-xs text-slate-500 bg-slate-100 px-3 py-1 rounded-full font-medium">
                    Empty fee fields will revert to Global Platform Fees
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Business Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">KYC Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Account Status</th>
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
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="impersonate">
                                                <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-800" title="Login as Merchant">
                                                    <i class="lucide-log-in w-4 h-4"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="verify_merchant">
                                                <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $m['is_kyc_verified'] == 1 ? 0 : 1; ?>">
                                                <button type="submit" class="text-indigo-600 font-bold text-xs hover:underline">
                                                    <?php echo $m['is_kyc_verified'] == 1 ? 'Unverify' : 'Verify'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="suspend_merchant">
                                                <input type="hidden" name="merchant_id" value="<?php echo $m['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $m['is_suspended'] ? 0 : 1; ?>">
                                                <button type="submit" class="font-bold text-xs hover:underline <?php echo $m['is_suspended'] ? 'text-emerald-600' : 'text-red-600'; ?>">
                                                    <?php echo $m['is_suspended'] ? 'Activate' : 'Suspend'; ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fee Modal -->
        <div x-show="showFees" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Custom Fees</h3>
                    <button @click="showFees = false" class="text-slate-400 hover:text-slate-600 transition-colors">
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
