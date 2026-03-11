<?php
// php-version/topbar.php
require_once 'functions.php';
$user = getAuthUser();
?>
<header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shrink-0">
    <div class="flex items-center gap-4">
        <button @click="$store.nav.mobileMenuOpen = true" class="md:hidden flex items-center justify-center text-slate-500 hover:text-slate-900 transition-colors p-2 rounded-lg hover:bg-slate-100 relative z-[70]">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
        <?php if ($user['role'] !== 'admin'): ?>
            <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-full">
                <div class="w-2 h-2 rounded-full <?php echo $user['is_test_mode'] ? 'bg-amber-500' : 'bg-emerald-500'; ?>"></div>
                <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider"><?php echo $user['is_test_mode'] ? 'Test Mode' : 'Live Mode'; ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex items-center gap-6">
        <button class="text-slate-400 hover:text-slate-600 transition-colors relative">
            <i data-lucide="bell" class="w-6 h-6"></i>
            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
        </button>
        <div class="flex items-center gap-3 pl-6 border-l border-slate-100" x-data="{ open: false }">
            <div class="text-right hidden sm:block">
                <div class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 text-sm font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                        <?php echo $user['role'] === 'admin' ? 'System Administrator' : $user['business_name']; ?>
                        <?php if ($user['role'] === 'merchant'): ?>
                            <i data-lucide="chevron-down" class="w-3 h-3"></i>
                        <?php endif; ?>
                    </button>
                    <p class="text-[10px] text-slate-500 font-medium"><?php echo $user['email']; ?></p>

                    <?php if ($user['role'] === 'merchant'): ?>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                            <p class="px-4 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Switch Account</p>
                            <?php
                            $db = Database::connect();
                            $main_id = $user['parent_id'] ?: $user['id'];
                            // Show the current account first, then others
                            $stmt = $db->prepare("SELECT id, business_name, parent_id FROM users WHERE (id = ? OR parent_id = ?) ORDER BY (id = ?) DESC, (parent_id IS NULL) DESC");
                            $stmt->execute([$main_id, $main_id, $user['id']]);
                            $accounts = $stmt->fetchAll();
                            foreach ($accounts as $acc):
                            ?>
                                <form method="POST" action="<?php echo BASE_URL; ?>merchant/sub-accounts.php">
                                    <input type="hidden" name="action" value="switch_account">
                                    <input type="hidden" name="sub_id" value="<?php echo $acc['id']; ?>">
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm <?php echo $acc['id'] == $user['id'] ? 'text-indigo-600 font-bold bg-indigo-50' : 'text-slate-600 hover:bg-slate-50'; ?> transition-colors">
                                        <div class="flex items-center justify-between">
                                            <span><?php echo $acc['business_name']; ?></span>
                                            <?php if (!$acc['parent_id']): ?>
                                                <span class="text-[8px] bg-slate-100 px-1.5 py-0.5 rounded-md text-slate-500 font-bold">MAIN</span>
                                            <?php endif; ?>
                                        </div>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="w-10 h-10 <?php echo $user['role'] === 'admin' ? 'bg-slate-900' : 'bg-indigo-100'; ?> rounded-xl flex items-center justify-center <?php echo $user['role'] === 'admin' ? 'text-white' : 'text-indigo-600'; ?> font-bold">
                <?php echo strtoupper(substr($user['role'] === 'admin' ? 'Admin' : $user['business_name'], 0, 1)); ?>
            </div>
        </div>
    </div>
</header>
