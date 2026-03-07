<?php
// php-version/settings.php
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getAuthUser();
$db = Database::connect();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $business_name = sanitize($_POST['business_name']);
        $email = sanitize($_POST['email']);
        
        try {
            $stmt = $db->prepare("UPDATE users SET business_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$business_name, $email, $user['id']]);
            $success_msg = "Profile updated successfully!";
            $user = getAuthUser();
        } catch (Exception $e) {
            $error_msg = "Failed to update profile: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update_settlement') {
        $bank_name = sanitize($_POST['bank_name']);
        $account_number = sanitize($_POST['account_number']);
        
        try {
            $stmt = $db->prepare("UPDATE users SET settlement_bank = ?, settlement_account_number = ? WHERE id = ?");
            $stmt->execute([$bank_name, $account_number, $user['id']]);
            $success_msg = "Settlement details updated successfully!";
            $user = getAuthUser();
        } catch (Exception $e) {
            $error_msg = "Failed to update settlement details: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Payhub</title>
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
            <h1 class="text-3xl font-bold text-slate-900 mb-8 tracking-tight">Settings</h1>

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

            <div class="space-y-8">
                <!-- Profile Settings -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                        <i class="lucide-user text-indigo-600 w-6 h-6"></i>
                        Profile Settings
                    </h3>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Business Name</label>
                                <input 
                                    type="text" 
                                    name="business_name"
                                    value="<?php echo htmlspecialchars($user['business_name']); ?>"
                                    class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-slate-700"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Email Address</label>
                                <input 
                                    type="email" 
                                    name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-slate-700"
                                >
                            </div>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                            Update Profile
                        </button>
                    </form>
                </div>

                <!-- Settlement Settings -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                        <i class="lucide-landmark text-emerald-600 w-6 h-6"></i>
                        Settlement Bank
                    </h3>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_settlement">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Bank Name</label>
                                <input 
                                    type="text" 
                                    name="bank_name"
                                    value="<?php echo htmlspecialchars($user['settlement_bank']); ?>"
                                    class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-slate-700"
                                    placeholder="e.g. Zenith Bank"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Account Number</label>
                                <input 
                                    type="text" 
                                    name="account_number"
                                    value="<?php echo htmlspecialchars($user['settlement_account_number']); ?>"
                                    class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-slate-700"
                                    placeholder="10-digit account number"
                                >
                            </div>
                        </div>
                        <button type="submit" class="bg-emerald-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200">
                            Update Settlement Details
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
