<?php
// php-version/admin/staff.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Staff Management - Admin Hub';

$db = Database::connect();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_staff') {
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role_id = (int)$_POST['role_id'];

        $stmt = $db->prepare("INSERT INTO staff_users (email, full_name, password_hash, role_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $full_name, $password, $role_id]);
        $success_msg = "Staff member added successfully.";
    }
}

$stmt = $db->query("SELECT s.*, r.name as role_name FROM staff_users s LEFT JOIN staff_roles r ON s.role_id = r.id ORDER BY s.created_at DESC");
$staff = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM staff_roles ORDER BY name ASC");
$roles = $stmt->fetchAll();

include '../includes/dashboard-head.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden" x-data="{ showAdd: false }">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <?php if (isset($success_msg)): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-2">Staff Management</h1>
                    <p class="text-slate-500">Create roles and manage staff access with granular permissions</p>
                </div>
                <button @click="showAdd = true" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    <i class="lucide-user-plus w-4 h-4"></i> Add Staff
                </button>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 font-bold">Staff Members</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Email</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Role</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($staff as $s): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-bold text-slate-900"><?php echo $s['full_name']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-700"><?php echo $s['email']; ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700">
                                            <?php echo $s['role_name'] ?: 'No Role'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="text-slate-400 hover:text-red-600"><i class="lucide-trash-2 w-4 h-4"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($staff)): ?>
                                <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500 font-medium">No staff members found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <div x-show="showAdd" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-md overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Add New Staff Member</h3>
                    <button @click="showAdd = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="lucide-x w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="add_staff">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Full Name</label>
                            <input type="text" name="full_name" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email Address</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Temporary Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Assign Role</label>
                            <select name="role_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">Select a role...</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
