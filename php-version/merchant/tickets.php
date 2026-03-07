<?php
// php-version/merchant/tickets.php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Support Center - Payhub';

$db = Database::connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_ticket') {
    $subject = sanitize($_POST['subject']);
    $priority = sanitize($_POST['priority']);
    $message = sanitize($_POST['message']);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO tickets (user_id, subject, priority, status) VALUES (?, ?, ?, 'open')");
        $stmt->execute([$user['id'], $subject, $priority]);
        $ticketId = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$ticketId, $user['id'], $message]);

        $db->commit();
        $success_msg = "Ticket opened successfully!";
    } catch (Exception $e) {
        $db->rollBack();
        $error_msg = "Failed to open ticket: " . $e->getMessage();
    }
}

$stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Support Center</h1>
                <p class="text-slate-500">A built-in helpdesk to communicate directly with the Payhub admin team</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm h-fit">
                    <h3 class="font-bold mb-6 flex items-center gap-2"><i class="lucide-plus-circle text-indigo-600"></i> Open New Ticket</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="new_ticket">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Subject</label>
                            <input type="text" name="subject" required placeholder="Brief description of issue" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Priority</label>
                            <select name="priority" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Describe Your Issue</label>
                            <textarea name="message" required rows="5" placeholder="Provide more details here..." class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Submit Ticket</button>
                    </form>
                </div>
                <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 font-bold">My Tickets</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Subject</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Priority</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Date</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($tickets as $t): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-bold text-slate-900"><?php echo $t['subject']; ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider <?php echo $t['priority'] === 'critical' || $t['priority'] === 'high' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'; ?>"><?php echo $t['priority']; ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider <?php echo $t['status'] === 'open' ? 'bg-indigo-50 text-indigo-700' : ($t['status'] === 'resolved' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>"><?php echo $t['status']; ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500 font-medium"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                                        <td class="px-6 py-4">
                                            <button class="text-indigo-600 hover:text-indigo-700 font-bold text-xs">View Thread</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($tickets)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-medium">No support tickets found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
