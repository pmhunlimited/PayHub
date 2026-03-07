<?php
// php-version/compliance.php
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getAuthUser();
$db = Database::connect();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_compliance') {
    $business_type = sanitize($_POST['business_type']);
    $registration_number = sanitize($_POST['registration_number']);
    
    try {
        $stmt = $db->prepare("UPDATE users SET business_type = ?, registration_number = ? WHERE id = ?");
        $stmt->execute([$business_type, $registration_number, $user['id']]);
        $success_msg = "Compliance information updated successfully!";
        $user = getAuthUser(); // Refresh user data
    } catch (Exception $e) {
        $error_msg = "Failed to update compliance: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance - Payhub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include 'topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="p-3 bg-indigo-600 rounded-2xl text-white shadow-lg shadow-indigo-200">
                    <i class="lucide-shield-check w-6 h-6"></i>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Compliance</h1>
            </div>

            <?php if ($success_msg): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 font-medium">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="md:col-span-2 space-y-8">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900 mb-6">Business Information</h3>
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="update_compliance">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Business Type</label>
                                    <select name="business_type" class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-slate-700">
                                        <option value="sole_proprietorship" <?php echo $user['business_type'] === 'sole_proprietorship' ? 'selected' : ''; ?>>Sole Proprietorship</option>
                                        <option value="limited_liability" <?php echo $user['business_type'] === 'limited_liability' ? 'selected' : ''; ?>>Limited Liability Company</option>
                                        <option value="non_profit" <?php echo $user['business_type'] === 'non_profit' ? 'selected' : ''; ?>>Non-Profit Organization</option>
                                        <option value="partnership" <?php echo $user['business_type'] === 'partnership' ? 'selected' : ''; ?>>Partnership</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Registration Number (RC/BN)</label>
                                    <input 
                                        type="text" 
                                        name="registration_number"
                                        value="<?php echo $user['registration_number']; ?>"
                                        class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-slate-700"
                                        placeholder="RC-1234567"
                                    >
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100">
                                <button type="submit" class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-900 mb-6">Document Verification</h3>
                        <div class="space-y-4">
                            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between group hover:bg-white hover:shadow-md transition-all cursor-pointer">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-xl border border-slate-200 flex items-center justify-center text-slate-400 group-hover:text-indigo-600 transition-colors">
                                        <i class="lucide-file-text w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">Certificate of Incorporation</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Required</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-amber-600 font-bold text-xs bg-amber-50 px-3 py-1 rounded-full">
                                    <i class="lucide-clock w-3 h-3"></i>
                                    Pending
                                </div>
                            </div>

                            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between group hover:bg-white hover:shadow-md transition-all cursor-pointer">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-xl border border-slate-200 flex items-center justify-center text-slate-400 group-hover:text-indigo-600 transition-colors">
                                        <i class="lucide-user-check w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">Director's ID Card</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Required</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-emerald-600 font-bold text-xs bg-emerald-50 px-3 py-1 rounded-full">
                                    <i class="lucide-check-circle-2 w-3 h-3"></i>
                                    Verified
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-1">
                    <div class="bg-indigo-600 p-8 rounded-[2.5rem] text-white shadow-xl shadow-indigo-200 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                        <h4 class="text-lg font-bold mb-4 relative z-10">Verification Status</h4>
                        <div class="space-y-6 relative z-10">
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-2 opacity-80 uppercase tracking-widest">
                                    <span>Overall Progress</span>
                                    <span>65%</span>
                                </div>
                                <div class="w-full h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full bg-white rounded-full" style="width: 65%"></div>
                                </div>
                            </div>
                            <p class="text-sm opacity-80 leading-relaxed">Complete all verification steps to increase your transaction limits and enable all payment methods.</p>
                            <button class="w-full bg-white text-indigo-600 py-3 rounded-xl font-bold text-sm hover:bg-indigo-50 transition-all">
                                View Limits
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
