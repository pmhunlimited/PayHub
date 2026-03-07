<?php
// php-version/merchant/compliance.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$db = Database::connect();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_compliance') {
    $business_type = sanitize($_POST['business_type']);
    $registration_number = sanitize($_POST['registration_number'] ?? '');
    $id_type = sanitize($_POST['id_type']);
    $id_expiry = sanitize($_POST['id_expiry_date'] ?? '');
    $bvn = sanitize($_POST['bvn'] ?? '');
    $address = sanitize($_POST['residential_address'] ?? '');

    // Basic logic for expiry date requirement
    $needs_expiry = in_with_any($id_type, ["Drivers License", "International Passport"]);
    
    // File Uploads
    $uploads = [];
    $files_to_handle = ['utility_bill', 'liveliness', 'cac_cert', 'cac_form'];
    foreach ($files_to_handle as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
            $filename = $field . '_' . $user['id'] . '_' . time() . '.' . $ext;
            if (!is_dir('../uploads')) mkdir('../uploads');
            move_uploaded_file($_FILES[$field]['tmp_name'], '../uploads/' . $filename);
            $uploads[$field . '_path'] = $filename;
        }
    }

    try {
        $sql = "UPDATE users SET
            business_type = ?,
            registration_number = ?,
            id_type = ?,
            id_expiry_date = ?,
            bvn = ?,
            residential_address = ?,
            is_kyc_verified = 2";

        $params = [$business_type, $registration_number, $id_type, $needs_expiry ? $id_expiry : null, $bvn, $address];

        foreach ($uploads as $col => $val) {
            $sql .= ", $col = ?";
            $params[] = $val;
        }

        $sql .= " WHERE id = ?";
        $params[] = $user['id'];

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $success_msg = "Compliance documents submitted for review!";
        $user = getAuthUser();
    } catch (Exception $e) {
        $error_msg = "Submission failed: " . $e->getMessage();
    }
}

function in_with_any($needle, $haystack) {
    return in_array($needle, $haystack);
}

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden" x-data="{
        businessType: '<?php echo $user['business_type'] ?: 'Starter'; ?>',
        idType: '<?php echo $user['id_type']; ?>',
        get needsExpiry() { return ['Drivers License', 'International Passport'].includes(this.idType) }
    }">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-4xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Compliance & KYC</h1>
                    <p class="text-slate-500">Provide required documents to verify your business and increase limits</p>
                </div>

                <?php if ($success_msg): ?>
                    <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-medium"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm">
                            <form method="POST" class="space-y-8">
                                <input type="hidden" name="action" value="update_compliance">

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-4 tracking-wider">Select Business Type</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <?php
                                        $types = [
                                            ['id' => 'Starter', 'label' => 'Starter', 'desc' => 'Individual / Freelancer'],
                                            ['id' => 'Registered', 'label' => 'Registered', 'desc' => 'LLC / CAC Registered'],
                                            ['id' => 'Business Name', 'label' => 'Business Name', 'desc' => 'Sole Proprietorship'],
                                            ['id' => 'Special', 'label' => 'Special', 'desc' => 'NGO / Government']
                                        ];
                                        foreach($types as $t):
                                        ?>
                                            <label class="relative flex flex-col p-4 border-2 rounded-2xl cursor-pointer transition-all" :class="businessType === '<?php echo $t['id']; ?>' ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-100 hover:border-slate-200'">
                                                <input type="radio" name="business_type" value="<?php echo $t['id']; ?>" x-model="businessType" class="absolute opacity-0">
                                                <span class="font-bold text-sm" :class="businessType === '<?php echo $t['id']; ?>' ? 'text-indigo-600' : 'text-slate-900'"><?php echo $t['label']; ?></span>
                                                <span class="text-[10px] text-slate-500 mt-1"><?php echo $t['desc']; ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <h4 class="font-bold text-slate-900 border-b border-slate-100 pb-2">Identity Information</h4>
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Government ID Type</label>
                                            <select name="id_type" x-model="idType" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                                                <option value="">Select ID Type</option>
                                                <option value="NIN Slip">NIN Slip</option>
                                                <option value="Drivers License">Drivers License</option>
                                                <option value="Voters Card">Voters Card</option>
                                                <option value="International Passport">International Passport</option>
                                            </select>
                                        </div>
                                        <div x-show="needsExpiry">
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">ID Expiry Date</label>
                                            <input type="date" name="id_expiry_date" value="<?php echo $user['id_expiry_date']; ?>" :required="needsExpiry" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                                        </div>
                                    </div>
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">BVN / NIN Number</label>
                                            <input type="text" name="bvn" value="<?php echo $user['bvn']; ?>" placeholder="222********" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                                        </div>
                                        <div x-show="businessType !== 'Starter'">
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Registration Number (RC/BN)</label>
                                            <input type="text" name="registration_number" value="<?php echo $user['registration_number']; ?>" placeholder="RC123456" :required="businessType !== 'Starter'" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Residential Address</label>
                                        <input type="text" name="residential_address" value="<?php echo $user['residential_address']; ?>" placeholder="123 Main St, Lagos" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                                    </div>
                                </div>

                                <div class="space-y-6" x-show="businessType !== 'Starter'">
                                    <h4 class="font-bold text-slate-900 border-b border-slate-100 pb-2">Business Documents</h4>
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div class="p-4 border-2 border-dashed border-slate-200 rounded-2xl text-center relative hover:border-indigo-400 transition-colors">
                                            <input type="file" name="cac_cert" class="absolute inset-0 opacity-0 cursor-pointer">
                                            <i class="lucide-file-text text-slate-400 mb-2"></i>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">CAC Certificate</p>
                                        </div>
                                        <div class="p-4 border-2 border-dashed border-slate-200 rounded-2xl text-center relative hover:border-indigo-400 transition-colors">
                                            <input type="file" name="cac_form" class="absolute inset-0 opacity-0 cursor-pointer">
                                            <i class="lucide-file-text text-slate-400 mb-2"></i>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">Form CAC 1.1</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <h4 class="font-bold text-slate-900 border-b border-slate-100 pb-2">Document Uploads</h4>
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div class="p-4 border-2 border-dashed border-slate-200 rounded-2xl text-center relative hover:border-indigo-400 transition-colors">
                                            <input type="file" name="utility_bill" class="absolute inset-0 opacity-0 cursor-pointer">
                                            <i class="lucide-upload text-slate-400 mb-2"></i>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">Utility Bill</p>
                                            <p class="text-[9px] text-slate-400 mt-1">Not older than 3 months</p>
                                        </div>
                                        <div class="p-4 border-2 border-dashed border-slate-200 rounded-2xl text-center relative hover:border-indigo-400 transition-colors">
                                            <input type="file" name="liveliness" class="absolute inset-0 opacity-0 cursor-pointer">
                                            <i class="lucide-camera text-slate-400 mb-2"></i>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">Liveliness Snapshot</p>
                                            <p class="text-[9px] text-slate-400 mt-1">Real-time selfie photo</p>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">Submit for Verification</button>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-indigo-900 p-6 rounded-[2rem] text-white shadow-xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16 blur-2xl"></div>
                            <h4 class="font-bold mb-4 relative z-10">KYC Level</h4>
                            <div class="space-y-4 relative z-10">
                                <div class="p-4 bg-white/10 rounded-2xl border border-white/10">
                                    <p class="text-[10px] font-bold text-indigo-300 uppercase tracking-widest mb-1">Status</p>
                                    <p class="text-sm font-bold capitalize"><?php echo $user['is_kyc_verified'] == 1 ? 'Verified' : ($user['is_kyc_verified'] == 2 ? 'Under Review' : 'Action Required'); ?></p>
                                </div>
                                <p class="text-xs text-indigo-200 leading-relaxed">Verification typically takes 24-48 business hours. You'll be notified via email once processed.</p>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
                            <h4 class="font-bold text-slate-900 mb-4">Why verify?</h4>
                            <ul class="space-y-4">
                                <li class="flex gap-3">
                                    <i class="lucide-check-circle-2 text-emerald-500 w-4 h-4 shrink-0"></i>
                                    <span class="text-xs text-slate-600">Increase collection limits</span>
                                </li>
                                <li class="flex gap-3">
                                    <i class="lucide-check-circle-2 text-emerald-500 w-4 h-4 shrink-0"></i>
                                    <span class="text-xs text-slate-600">Enable bank transfer payments</span>
                                </li>
                                <li class="flex gap-3">
                                    <i class="lucide-check-circle-2 text-emerald-500 w-4 h-4 shrink-0"></i>
                                    <span class="text-xs text-slate-600">Withdraw funds to bank</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
