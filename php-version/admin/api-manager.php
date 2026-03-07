<?php
// php-version/admin/api-manager.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'API Manager - Admin Hub';

$db = Database::connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_keys') {
    $pk = sanitize($_POST['paystack_public_key']);
    $sk = sanitize($_POST['paystack_secret_key']);

    $stmt = $db->prepare("INSERT INTO config (`key`, `value`) VALUES ('paystack_public_key', ?) ON DUPLICATE KEY UPDATE `value` = ?");
    $stmt->execute([$pk, $pk]);
    $stmt = $db->prepare("INSERT INTO config (`key`, `value`) VALUES ('paystack_secret_key', ?) ON DUPLICATE KEY UPDATE `value` = ?");
    $stmt->execute([$sk, $sk]);
    $success_msg = "Paystack integration keys updated.";
}

$pk = getConfig('paystack_public_key');
$sk = getConfig('paystack_secret_key');

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
                <h1 class="text-2xl font-bold text-slate-900 mb-2">API Manager</h1>
                <p class="text-slate-500">The Paystack API is integrated as the source for the PayHub Payment Gateway</p>
            </div>

            <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm max-w-2xl">
                <div class="flex items-center gap-3 mb-8">
                    <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600">
                        <i class="lucide-key w-6 h-6"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">Gateway Configuration</h2>
                        <p class="text-sm text-slate-500">Configure your Paystack credentials for platform-wide processing</p>
                    </div>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_keys">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Paystack Public Key</label>
                        <input type="text" name="paystack_public_key" value="<?php echo $pk; ?>" placeholder="pk_live_..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none font-mono text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Paystack Secret Key</label>
                        <input type="password" name="paystack_secret_key" value="<?php echo $sk; ?>" placeholder="sk_live_..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none font-mono text-sm">
                    </div>
                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-3">
                        <i class="lucide-shield-alert text-amber-500 w-5 h-5 shrink-0"></i>
                        <p class="text-xs text-amber-700 leading-relaxed">
                            <strong>Security Warning:</strong> These keys are extremely sensitive. They allow full access to your Paystack account funds and data. Never share them or expose them in client-side code.
                        </p>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Update Integration</button>
                </form>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
