<?php
// php-version/virtual-accounts.php
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getAuthUser();
$db = Database::connect();

// Fetch virtual accounts
$stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Accounts - Payhub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include 'topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Virtual Accounts</h1>
                <button class="flex items-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-2xl text-sm font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                    <i class="lucide-plus w-4 h-4"></i>
                    Create New Account
                </button>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php foreach ($accounts as $acc): ?>
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm hover:shadow-xl transition-all group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-full -mr-16 -mt-16 opacity-50 group-hover:scale-110 transition-transform"></div>
                        
                        <div class="flex items-center justify-between mb-8 relative z-10">
                            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                                <i class="lucide-landmark w-6 h-6"></i>
                            </div>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full uppercase tracking-wider">Active</span>
                        </div>

                        <div class="space-y-6 relative z-10">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Account Name</p>
                                <p class="text-lg font-bold text-slate-900"><?php echo $acc['account_name']; ?></p>
                            </div>
                            
                            <div class="flex justify-between gap-4">
                                <div class="flex-1">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Bank Name</p>
                                    <p class="text-sm font-bold text-slate-700"><?php echo $acc['bank_name']; ?></p>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Account Number</p>
                                    <p class="text-sm font-mono font-bold text-indigo-600"><?php echo $acc['account_number']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between relative z-10">
                            <p class="text-[10px] text-slate-400 font-medium">Created <?php echo date('M d, Y', strtotime($acc['created_at'])); ?></p>
                            <button class="text-indigo-600 hover:text-indigo-700 text-xs font-bold flex items-center gap-1">
                                Details <i class="lucide-chevron-right w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($accounts)): ?>
                    <div class="col-span-full bg-white p-12 rounded-[2.5rem] border border-dashed border-slate-300 text-center">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="lucide-wallet text-slate-300 w-8 h-8"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">No virtual accounts yet</h3>
                        <p class="text-slate-500 text-sm mb-6">Create your first virtual account to start receiving payments via bank transfers.</p>
                        <button class="bg-indigo-600 text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all">
                            Create Account
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
