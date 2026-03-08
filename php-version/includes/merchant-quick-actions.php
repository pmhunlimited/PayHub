<!-- php-version/includes/merchant-quick-actions.php -->
<div x-data="{ quickActions: false }">
    <div class="fixed bottom-8 right-8 z-40 flex flex-col items-end gap-4">
        <div x-show="quickActions"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="flex flex-col gap-3 mb-2" x-cloak>

            <a href="payouts.php" class="flex items-center gap-3 bg-white px-5 py-3 rounded-2xl shadow-xl border border-slate-100 group hover:border-indigo-600 transition-all">
                <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-600">Request Payout</span>
                <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-all">
                    <i data-lucide="arrow-down-left" class="w-5 h-5"></i>
                </div>
            </a>

            <a href="invoices.php" class="flex items-center gap-3 bg-white px-5 py-3 rounded-2xl shadow-xl border border-slate-100 group hover:border-indigo-600 transition-all">
                <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-600">Create Invoice</span>
                <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                </div>
            </a>

            <a href="tickets.php" class="flex items-center gap-3 bg-white px-5 py-3 rounded-2xl shadow-xl border border-slate-100 group hover:border-indigo-600 transition-all">
                <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-600">Open Ticket</span>
                <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                    <i data-lucide="message-square" class="w-5 h-5"></i>
                </div>
            </a>
        </div>

        <button @click="quickActions = !quickActions"
                :class="quickActions ? 'bg-slate-900 rotate-45' : 'bg-indigo-600 shadow-indigo-200'"
                class="w-16 h-16 rounded-[2rem] text-white flex items-center justify-center shadow-2xl transition-all duration-300 transform">
            <i data-lucide="plus" class="w-8 h-8"></i>
        </button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
