<?php
// php-version/merchant/api-keys.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'API Keys - Payhub';

$db = Database::connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'regenerate') {
    $pk = generateApiKey('pk_live_');
    $sk = generateApiKey('sk_live_');
    $stmt = $db->prepare("UPDATE users SET public_key = ?, secret_key = ? WHERE id = ?");
    $stmt->execute([$pk, $sk, $user['id']]);
    $success_msg = "API keys regenerated successfully!";
    $user = getAuthUser();
}

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <?php if (isset($success_msg)): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">API Keys</h1>
                <p class="text-slate-500">Access your Public and Secret keys to integrate Payhub into your apps</p>
            </div>

            <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm max-w-3xl">
                <div class="space-y-8">
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <div class="flex justify-between items-center mb-4">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Public Key</p>
                            <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded font-bold uppercase">Live</span>
                        </div>
                        <div class="flex gap-2">
                            <input readonly value="<?php echo $user['public_key']; ?>" class="flex-1 px-4 py-3 bg-white border border-slate-200 rounded-xl font-mono text-sm focus:outline-none">
                            <button onclick="navigator.clipboard.writeText('<?php echo $user['public_key']; ?>'); alert('Copied!');" class="px-6 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all active:scale-95">Copy</button>
                        </div>
                    </div>

                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                        <div class="flex justify-between items-center mb-4">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Secret Key</p>
                            <span class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded font-bold uppercase">Private</span>
                        </div>
                        <div class="flex gap-2">
                            <input type="password" id="sk_input" readonly value="<?php echo $user['secret_key']; ?>" class="flex-1 px-4 py-3 bg-white border border-slate-200 rounded-xl font-mono text-sm focus:outline-none">
                            <button onclick="navigator.clipboard.writeText('<?php echo $user['secret_key']; ?>'); alert('Copied!');" class="px-6 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all active:scale-95">Copy</button>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">Danger Zone</h4>
                            <p class="text-xs text-slate-400 mt-1">Regenerating keys will break existing integrations.</p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to regenerate your keys?');">
                            <input type="hidden" name="action" value="regenerate">
                            <button type="submit" class="bg-red-50 text-red-600 px-6 py-3 rounded-xl font-bold text-sm hover:bg-red-100 transition-all">Regenerate Keys</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
