<?php
// php-version/topbar.php
require_once 'functions.php';
$user = getAuthUser();
?>
<header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shrink-0">
    <div class="flex items-center gap-4">
        <button class="md:hidden text-slate-500">
            <i class="lucide-menu w-6 h-6"></i>
        </button>
        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-full">
            <div class="w-2 h-2 rounded-full <?php echo $user['is_test_mode'] ? 'bg-amber-500' : 'bg-emerald-500'; ?>"></div>
            <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider"><?php echo $user['is_test_mode'] ? 'Test Mode' : 'Live Mode'; ?></span>
        </div>
    </div>

    <div class="flex items-center gap-6">
        <button class="text-slate-400 hover:text-slate-600 transition-colors relative">
            <i class="lucide-bell w-6 h-6"></i>
            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
        </button>
        <div class="flex items-center gap-3 pl-6 border-l border-slate-100">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-bold text-slate-900"><?php echo $user['business_name']; ?></p>
                <p class="text-[10px] text-slate-500 font-medium"><?php echo $user['email']; ?></p>
            </div>
            <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 font-bold">
                <?php echo strtoupper(substr($user['business_name'], 0, 1)); ?>
            </div>
        </div>
    </div>
</header>
