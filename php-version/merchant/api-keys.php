<?php
// php-version/api-keys.php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = getAuthUser();
$db = Database::connect();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate'])) {
    try {
        $pk = generateApiKey('pk_live_');
        $sk = generateApiKey('sk_live_');
        
        $stmt = $db->prepare("UPDATE users SET public_key = ?, secret_key = ? WHERE id = ?");
        $stmt->execute([$pk, $sk, $user['id']]);
        
        $success_msg = "API keys regenerated successfully!";
        $user = getAuthUser(); // Refresh user data
    } catch (Exception $e) {
        $error_msg = "Failed to regenerate keys: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keys - Payhub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center gap-4 mb-8">
                <div class="p-3 bg-indigo-600 rounded-2xl text-white shadow-lg shadow-indigo-200">
                    <i class="lucide-code w-6 h-6"></i>
                </div>
                <h1 class="text-3xl font-bold text-slate-900">API Keys</h1>
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

            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm space-y-8">
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="flex justify-between items-center mb-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Public Key</p>
                        <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded font-bold uppercase">Live</span>
                    </div>
                    <div class="flex gap-2">
                        <input readonly value="<?php echo $user['public_key']; ?>" class="flex-1 px-4 py-3 bg-white border border-slate-200 rounded-xl font-mono text-sm focus:outline-none">
                        <button 
                            onclick="copyToClipboard('<?php echo $user['public_key']; ?>')"
                            class="px-6 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all active:scale-95"
                        >
                            Copy
                        </button>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400">Use this key to identify your account in client-side integrations.</p>
                </div>

                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="flex justify-between items-center mb-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Secret Key</p>
                        <span class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded font-bold uppercase">Private</span>
                    </div>
                    <div class="flex gap-2">
                        <input readonly type="password" id="secret_key" value="<?php echo $user['secret_key']; ?>" class="flex-1 px-4 py-3 bg-white border border-slate-200 rounded-xl font-mono text-sm focus:outline-none">
                        <button 
                            onclick="copyToClipboard('<?php echo $user['secret_key']; ?>')"
                            class="px-6 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all active:scale-95"
                        >
                            Copy
                        </button>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400">Keep this key secure. Never expose it in client-side code.</p>
                </div>

                <div class="pt-8 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Danger Zone</h4>
                        <p class="text-xs text-slate-400 mt-1">Regenerating keys will break existing integrations.</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to regenerate your API keys? This will immediately break any existing integrations using the current keys.')">
                        <button 
                            type="submit" 
                            name="regenerate"
                            class="bg-red-50 text-red-600 px-6 py-3 rounded-xl font-bold text-sm hover:bg-red-100 transition-all"
                        >
                            Regenerate Keys
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        }
    </script>
</body>
</html>
