<?php
// php-version/merchant/sub-accounts.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Sub-accounts - Payhub';

$db = Database::connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_subaccount') {
        $email = sanitize($_POST['email']);
        $biz_name = sanitize($_POST['business_name']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $pk = generateApiKey('pk_live_');
        $sk = generateApiKey('sk_live_');

        try {
            $stmt = $db->prepare("INSERT INTO users (email, business_name, password_hash, public_key, secret_key, parent_id, role) VALUES (?, ?, ?, ?, ?, ?, 'merchant')");
            $stmt->execute([$email, $biz_name, $password, $pk, $sk, $user['id']]);
            $success_msg = "Sub-account created successfully! It must complete compliance before going live.";
        } catch (Exception $e) {
            $error_msg = "Creation failed: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'switch_account') {
        $subId = (int)$_POST['sub_id'];
        // Security check: is it actually a sub-account of this user?
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND parent_id = ?");
        $stmt->execute([$subId, $user['id']]);
        if ($stmt->fetch()) {
            $_SESSION['user_id'] = $subId;
            redirect('dashboard.php');
        }
    }
}

$stmt = $db->prepare("SELECT * FROM users WHERE parent_id = ?");
$stmt->execute([$user['id']]);
$subaccounts = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden" x-data="{ showAdd: false }">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Sub-accounts</h1>
                    <p class="text-slate-500">Manage multiple business branches or projects from one place</p>
                </div>
                <button @click="showAdd = true" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    <i class="lucide-plus w-5 h-5"></i> Create Sub-account
                </button>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($subaccounts as $sub): ?>
                    <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm hover:border-indigo-600 transition-all group">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all">
                                <i class="lucide-building w-6 h-6"></i>
                            </div>
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider <?php echo $sub['is_kyc_verified'] == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                <?php echo $sub['is_kyc_verified'] == 1 ? 'Verified' : 'Unverified'; ?>
                            </span>
                        </div>
                        <h3 class="font-bold text-xl text-slate-900 mb-1"><?php echo $sub['business_name']; ?></h3>
                        <p class="text-sm text-slate-400 mb-8"><?php echo $sub['email']; ?></p>
                        <form method="POST">
                            <input type="hidden" name="action" value="switch_account">
                            <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                            <button type="submit" class="w-full py-3 bg-slate-50 text-slate-600 font-bold rounded-xl hover:bg-indigo-600 hover:text-white transition-all text-sm">Switch to Account</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Add Modal -->
        <div x-show="showAdd" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-md overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">New Sub-account</h3>
                    <button @click="showAdd = false" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full transition-colors"><i class="lucide-x w-5 h-5"></i></button>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="create_subaccount">
                        <input type="text" name="business_name" required placeholder="Business Name" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <input type="email" name="email" required placeholder="Contact Email" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <input type="password" name="password" required placeholder="Account Password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
