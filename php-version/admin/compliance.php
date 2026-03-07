<?php
// php-version/admin/compliance.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Compliance Queue - Admin Hub';

$db = Database::connect();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'process_kyc') {
        $merchantId = (int)$_POST['merchant_id'];
        $status = (int)$_POST['status'];
        $notes = sanitize($_POST['notes']);
        // If approved (status = 1), also turn off test mode
        $test_mode_sql = ($status == 1) ? ", is_test_mode = 0" : "";
        $stmt = $db->prepare("UPDATE users SET is_kyc_verified = ?, kyc_notes = ? $test_mode_sql WHERE id = ?");
        $stmt->execute([$status, $notes, $merchantId]);
        $success_msg = "KYC application processed.";

        // Notify Merchant
        $stmt = $db->prepare("SELECT email, business_name FROM users WHERE id = ?");
        $stmt->execute([$merchantId]);
        $m = $stmt->fetch();
        $status_text = $status == 1 ? 'Approved' : 'Rejected';
        sendEmail($m['email'], "KYC Verification $status_text", "<h2>Hello {$m['business_name']},</h2><p>Your KYC verification request has been <strong>$status_text</strong>.</p><p>Admin Notes: $notes</p>");
    }
}

// Fetch Pending KYC (is_kyc_verified = 2 is submitted, but let's show all unverified for now)
$stmt = $db->query("SELECT * FROM users WHERE role = 'merchant' AND is_kyc_verified != 1 ORDER BY is_kyc_verified DESC, created_at DESC");
$pending = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden" x-data="{ showReview: false, merchant: {} }">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <?php if (isset($success_msg)): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Compliance Queue</h1>
                <p class="text-slate-500">Review uploaded KYC documents and approve or reject applications</p>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 font-bold flex justify-between items-center">
                    <span>KYC Review Queue</span>
                    <span class="text-xs font-normal text-slate-500"><?php echo count(array_filter($pending, fn($m) => $m['is_kyc_verified'] == 2)); ?> applications pending review</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Merchant</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Business Type</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($pending as $m): ?>
                                <tr class="hover:bg-slate-50/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900"><?php echo $m['business_name']; ?></div>
                                        <div class="text-xs text-slate-500"><?php echo $m['email']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-700"><?php echo $m['business_type'] ?: 'Not selected'; ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $m['is_kyc_verified'] == 2 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'; ?>">
                                            <?php echo $m['is_kyc_verified'] == 2 ? 'Submitted' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button @click="showReview = true; merchant = <?php echo htmlspecialchars(json_encode($m)); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors">Review Docs</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Review Modal -->
        <div x-show="showReview" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-2xl overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">KYC Document Review</h3>
                    <button @click="showReview = false" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full transition-colors">
                        <i class="lucide-x w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-8 max-h-[70vh] overflow-y-auto">
                    <div class="grid md:grid-cols-2 gap-8">
                        <div>
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase mb-4 tracking-widest">Business Information</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Business Name</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="merchant.business_name"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Business Type</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="merchant.business_type"></p>
                                </div>
                                <div x-show="merchant.bvn">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">BVN</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="merchant.bvn"></p>
                                </div>
                                <div x-show="merchant.rc_number">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">RC Number</p>
                                    <p class="text-sm font-bold text-slate-900" x-text="merchant.rc_number"></p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase mb-4 tracking-widest">Actions & Notes</h4>
                            <form method="POST" id="kycForm">
                                <input type="hidden" name="action" value="process_kyc">
                                <input type="hidden" name="merchant_id" :value="merchant.id">
                                <input type="hidden" name="status" id="kycStatus">
                                <textarea name="notes" placeholder="Enter rejection reason or approval notes..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none h-32 mb-4"></textarea>
                                <div class="flex gap-2">
                                    <button type="button" @click="document.getElementById('kycStatus').value = 1; document.getElementById('kycForm').submit();" class="flex-1 bg-emerald-600 text-white py-3 rounded-xl font-bold">Approve</button>
                                    <button type="button" @click="document.getElementById('kycStatus').value = 0; document.getElementById('kycForm').submit();" class="flex-1 bg-red-50 text-red-600 border border-red-200 py-3 rounded-xl font-bold">Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
