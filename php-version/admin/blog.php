<?php
// php-version/admin/blog.php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$user = getAuthUser();
$pageTitle = 'Blog Manager - Admin Hub';

$db = Database::connect();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'publish') {
    $title = sanitize($_POST['title']);
    $slug = sanitize($_POST['slug']);
    $content = $_POST['content'];
    $author_id = $user['id'];
    $excerpt = substr(strip_tags($content), 0, 150) . '...';

    $stmt = $db->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, author_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $slug, $content, $excerpt, $author_id]);
    $success_msg = "Blog post published successfully.";
}

$stmt = $db->query("SELECT b.*, u.full_name as author FROM blog_posts b LEFT JOIN users u ON b.author_id = u.id ORDER BY b.created_at DESC");
$posts = $stmt->fetchAll();

include '../includes/header.php';
?>
<body class="bg-slate-50 text-slate-900 flex h-screen overflow-hidden">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 flex flex-col overflow-hidden" x-data="{ showCreate: false }">
        <?php include '../includes/topbar.php'; ?>
        <div class="flex-1 overflow-y-auto p-8">
            <?php if (isset($success_msg)): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-medium">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-2">Blog Manager</h1>
                    <p class="text-slate-500">Publish updates, guides, and news directly to the platform's blog</p>
                </div>
                <button @click="showCreate = true" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    <i class="lucide-plus w-4 h-4"></i> Create Post
                </button>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 font-bold">Recent Posts</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Title</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Author</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Date</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($posts as $p): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900"><?php echo $p['title']; ?></div>
                                        <div class="text-[10px] text-slate-400 font-mono"><?php echo $p['slug']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700"><?php echo $p['author']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <button class="text-indigo-600 hover:text-indigo-800 transition-colors"><i class="lucide-edit w-4 h-4"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div x-show="showCreate" x-cloak class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] w-full max-w-3xl overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-900">Create New Blog Post</h3>
                    <button @click="showCreate = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="lucide-x w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-8">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="publish">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Post Title</label>
                                <input type="text" name="title" required placeholder="e.g. New Feature Release" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">URL Slug</label>
                                <input type="text" name="slug" required placeholder="new-feature-release" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none font-mono text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Content (HTML allowed)</label>
                            <textarea name="content" required rows="10" placeholder="Write your post here..." class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Publish Post</button>
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
