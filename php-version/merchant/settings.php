<?php
// php-version/settings.php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
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
        $bank_info = explode('|', $_POST['bank_data']);
        $bank_name = sanitize($bank_info[0]);
        $bank_code = sanitize($bank_info[1] ?? '');
        $account_number = sanitize($_POST['account_number']);
        $payout_method = sanitize($_POST['payout_method']);
        $currency = sanitize($_POST['settlement_currency']);
        
        try {
            $stmt = $db->prepare("UPDATE users SET settlement_bank = ?, settlement_bank_code = ?, settlement_account_number = ?, payout_method = ?, settlement_currency = ? WHERE id = ?");
            $stmt->execute([$bank_name, $bank_code, $account_number, $payout_method, $currency, $user['id']]);
            $success_msg = "Settlement details updated successfully!";
            $user = getAuthUser();
        } catch (Exception $e) {
            $error_msg = "Failed to update settlement details: " . $e->getMessage();
        }
    }
}

// Fetch bank list from Paystack
$banks_response = paystack_call('bank?currency=NGN');
$banks = $banks_response['data'] ?? [];

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
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
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
                        Settlement Settings
                    </h3>
                    <form method="POST" class="space-y-8">
                        <input type="hidden" name="action" value="update_settlement">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Payout Method</label>
                                <select name="payout_method" class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 outline-none font-medium">
                                    <option value="manual" <?php echo $user['payout_method'] === 'manual' ? 'selected' : ''; ?>>Manual Payout</option>
                                    <option value="automated" <?php echo $user['payout_method'] === 'automated' ? 'selected' : ''; ?>>Automated Next-Day</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Settlement Currency</label>
                                <select name="settlement_currency" class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 outline-none font-medium">
                                    <option value="NGN" <?php echo $user['settlement_currency'] === 'NGN' ? 'selected' : ''; ?>>Nigerian Naira (NGN)</option>
                                    <option value="USD" <?php echo $user['settlement_currency'] === 'USD' ? 'selected' : ''; ?>>US Dollars (USD)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Settlement Bank</label>
                                <select name="bank_data" class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 outline-none font-medium">
                                    <option value="">Select Bank</option>
                                    <?php foreach($banks as $b): ?>
                                        <option value="<?php echo $b['name'].'|'.$b['code']; ?>" <?php echo $user['settlement_bank'] === $b['name'] ? 'selected' : ''; ?>><?php echo $b['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Account Number</label>
                                <input 
                                    type="text" 
                                    name="account_number"
                                    value="<?php echo htmlspecialchars($user['settlement_account_number']); ?>"
                                    class="w-full px-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 outline-none font-medium"
                                    placeholder="0123456789"
                                >
                            </div>
                        </div>
                        <button type="submit" class="bg-emerald-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200">
                            Update Settlement Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php include "../includes/merchant-quick-actions.php"; ?>
</main>
</body>
</html>
