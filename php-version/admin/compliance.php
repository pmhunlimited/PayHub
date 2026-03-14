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

        // Notify Merchant asynchronously if possible, or just efficiently
        $stmt = $db->prepare("SELECT email, business_name FROM users WHERE id = ?");
        $stmt->execute([$merchantId]);
        $m = $stmt->fetch();
        $status_text = $status == 1 ? 'Approved' : 'Rejected';
        // Use a simple subject to avoid encoding issues
        sendEmail($m['email'], "KYC Status: $status_text", "<h2>Hello {$m['business_name']},</h2><p>Your KYC verification request has been <strong>$status_text</strong>.</p><p>Admin Notes: " . nl2br($notes) . "</p>");

        // Use a redirect to prevent re-submission and clear POST state
        header("Location: compliance.php?success=1");
        exit;
    }
}

$success_msg = isset($_GET['success']) ? "Compliance processed successfully." : "";

// Fetch Pending KYC (is_kyc_verified = 2 is submitted, but let's show all unverified for now)
$stmt = $db->query("SELECT * FROM users WHERE role = 'merchant' AND is_kyc_verified != 1 ORDER BY is_kyc_verified DESC, created_at DESC");
$pending = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data>
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden"
          x-data="{
              showReview: false,
              processing: false,
              merchant: {},
              maskValue(val) {
                  if (!val) return 'Not provided';
                  const s = String(val);
                  if (s.length <= 4) return s;
                  return '*'.repeat(s.length - 4) + s.slice(-4);
              },
              openReview(m) {
                  this.merchant = m;
                  this.showReview = true;
                  this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
              },
              submitKYC(status) {
                  this.processing = true;
                  document.getElementById('kycStatus').value = status;
                  document.getElementById('kycForm').submit();
              }
          }">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-4 sm:p-8">
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
                                <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 uppercase">Merchant</th>
                                <th class="hidden sm:table-cell px-6 py-4 text-xs font-bold text-slate-500 uppercase">Business Type</th>
                                <th class="hidden md:table-cell px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($pending as $m): ?>
                                <tr class="hover:bg-slate-50/30 transition-colors">
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="font-bold text-slate-900 truncate max-w-[150px] sm:max-w-none"><?php echo $m['business_name']; ?></div>
                                        <div class="text-xs text-slate-500 truncate max-w-[150px] sm:max-w-none"><?php echo $m['email']; ?></div>
                                    </td>
                                    <td class="hidden sm:table-cell px-6 py-4 text-sm font-medium text-slate-700"><?php echo $m['business_type'] ?: 'Not selected'; ?></td>
                                    <td class="hidden md:table-cell px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $m['is_kyc_verified'] == 2 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'; ?>">
                                            <?php echo $m['is_kyc_verified'] == 2 ? 'Submitted' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button @click="openReview(<?php echo htmlspecialchars(json_encode($m)); ?>)" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors">Review Docs</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Review Modal -->
        <div x-show="showReview" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-5xl overflow-hidden shadow-2xl border border-slate-200 flex flex-col h-[90vh]">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div>
                        <h3 class="font-bold text-slate-900 text-lg">KYC Document Review</h3>
                        <p class="text-xs text-slate-500" x-text="merchant.business_name"></p>
                    </div>
                    <button @click="showReview = false" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-8">
                    <div class="grid lg:grid-cols-3 gap-12">
                        <div class="lg:col-span-1 space-y-8">
                            <div>
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase mb-4 tracking-widest">Business Information</h4>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">Business Type</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="merchant.business_type || 'N/A'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">Country</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="merchant.country || 'Nigeria'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">Registration / RC Number</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="merchant.registration_number || merchant.rc_number || 'N/A'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">BN Number</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="merchant.bn_number || 'N/A'"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">TIN (Tax ID)</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="merchant.tin || 'N/A'"></p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase mb-4 tracking-widest">Identification</h4>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">ID Type</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="merchant.id_type || 'N/A'"></p>
                                    </div>
                                    <div x-show="merchant.id_expiry_date">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">ID Expiry</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="merchant.id_expiry_date"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">BVN/NIN</p>
                                        <p class="text-sm font-bold text-slate-900" x-text="maskValue(merchant.bvn)"></p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase mb-4 tracking-widest">Address</h4>
                                <p class="text-sm font-bold text-slate-900" x-text="merchant.residential_address || 'N/A'"></p>
                            </div>
                        </div>

                        <div class="lg:col-span-2 space-y-8">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase mb-4 tracking-widest">Uploaded Documents</h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                                <template x-for="(doc, key) in {
                                    id_card_path: {label: 'Gov\'t ID', icon: 'credit-card'},
                                    utility_bill_path: {label: 'Utility Bill', icon: 'file-text'},
                                    liveliness_path: {label: 'Liveliness', icon: 'user'},
                                    cac_cert_path: {label: 'CAC Cert', icon: 'award'},
                                    cac_form_path: {label: 'CAC Form', icon: 'file-check'},
                                    memart_path: {label: 'MEMART', icon: 'book-open'},
                                    bn_cert_path: {label: 'BN Cert', icon: 'award'},
                                    bn_form_path: {label: 'BN Form', icon: 'file-check'},
                                    ngo_form_path: {label: 'NGO Form', icon: 'file-check'},
                                    ngo_constitution_path: {label: 'Constitution', icon: 'book-open'},
                                    gov_auth_letter_path: {label: 'Auth Letter', icon: 'mail'},
                                    gov_gazette_path: {label: 'Gazette', icon: 'file-text'},
                                    business_address_proof_path: {label: 'Address Proof', icon: 'map-pin'}
                                }" :key="key">
                                    <template x-if="merchant[key]">
                                        <div class="space-y-2">
                                            <a :href="'../uploads/' + merchant[key]" target="_blank" class="block aspect-square bg-slate-100 rounded-2xl border border-slate-200 overflow-hidden hover:ring-2 hover:ring-indigo-500 transition-all group relative shadow-sm">
                                                <template x-if="(merchant[key] || '').match(/\.(jpg|jpeg|png|gif|webp)$/i)">
                                                    <img :src="'../uploads/' + merchant[key]" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!(merchant[key] || '').match(/\.(jpg|jpeg|png|gif|webp)$/i)">
                                                    <div class="w-full h-full flex flex-col items-center justify-center gap-2">
                                                        <i :data-lucide="doc.icon" class="text-slate-400 w-8 h-8"></i>
                                                        <span class="text-[8px] font-bold text-slate-400 uppercase">View File</span>
                                                    </div>
                                                </template>
                                                <div class="absolute inset-0 bg-indigo-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                                    <i data-lucide="eye" class="text-white w-6 h-6"></i>
                                                </div>
                                            </a>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase text-center" x-text="doc.label"></p>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-8 border-t border-slate-100 bg-slate-50/50">
                    <form method="POST" id="kycForm">
                        <input type="hidden" name="action" value="process_kyc">
                        <input type="hidden" name="merchant_id" :value="merchant.id">
                        <input type="hidden" name="status" id="kycStatus">
                        <div class="flex flex-col md:flex-row gap-6 items-end">
                            <div class="flex-1 w-full">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Admin Decision Notes</label>
                                <textarea name="notes" placeholder="Enter rejection reason or approval notes..." class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none h-20 text-sm"></textarea>
                            </div>
                            <div class="flex gap-3 shrink-0">
                                <button type="button"
                                        :disabled="processing"
                                        @click="submitKYC(0)"
                                        class="px-8 py-3 bg-red-50 text-red-600 border border-red-200 rounded-xl font-bold hover:bg-red-100 disabled:opacity-50 transition-all text-sm flex items-center gap-2">
                                    <span x-show="processing && document.getElementById('kycStatus').value == 0" class="w-3 h-3 border-2 border-red-600 border-t-transparent rounded-full animate-spin"></span>
                                    Reject
                                </button>
                                <button type="button"
                                        :disabled="processing"
                                        @click="submitKYC(1)"
                                        class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 disabled:opacity-50 transition-all shadow-lg shadow-indigo-100 text-sm flex items-center gap-2">
                                    <span x-show="processing && document.getElementById('kycStatus').value == 1" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    Approve KYC
                                </button>
                            </div>
                        </div>
                    </form>
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