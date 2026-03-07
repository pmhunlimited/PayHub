<?php
// php-version/support.php
require_once 'functions.php';
$pageTitle = 'Support Center - Payhub';
include 'header.php';
?>
<div class="pt-20">
    <div class="bg-indigo-600 py-24 px-4 text-center">
        <h1 class="text-4xl font-bold text-white mb-8 tracking-tight">How can we help you?</h1>
        <div class="max-w-2xl mx-auto relative">
            <i class="lucide-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5"></i>
            <input 
                type="text" 
                placeholder="Search for articles, guides..." 
                class="w-full pl-12 pr-4 py-4 rounded-2xl border-none focus:ring-4 focus:ring-indigo-300 shadow-2xl"
            >
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 -mt-12 mb-24">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-xl transition-all group">
                <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="lucide-book-open text-indigo-600 w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Knowledge Base</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Browse our comprehensive guides and tutorials.</p>
            </div>
            <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-xl transition-all group">
                <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="lucide-message-circle text-emerald-600 w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Community</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Join the discussion with other Payhub developers.</p>
            </div>
            <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-xl transition-all group">
                <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="lucide-help-circle text-amber-600 w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Direct Support</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Can't find what you need? Talk to our team.</p>
            </div>
        </div>

        <div class="mt-24">
            <h2 class="text-2xl font-bold text-slate-900 mb-8">Popular Articles</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <a href="#" class="p-6 bg-white rounded-2xl border border-slate-200 hover:border-indigo-300 hover:text-indigo-600 transition-all font-bold text-slate-700 flex items-center justify-between group">
                    How to integrate Payhub on your website
                    <i class="lucide-arrow-right w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </a>
                <a href="#" class="p-6 bg-white rounded-2xl border border-slate-200 hover:border-indigo-300 hover:text-indigo-600 transition-all font-bold text-slate-700 flex items-center justify-between group">
                    Understanding transaction fees
                    <i class="lucide-arrow-right w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </a>
                <a href="#" class="p-6 bg-white rounded-2xl border border-slate-200 hover:border-indigo-300 hover:text-indigo-600 transition-all font-bold text-slate-700 flex items-center justify-between group">
                    Setting up virtual accounts
                    <i class="lucide-arrow-right w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </a>
                <a href="#" class="p-6 bg-white rounded-2xl border border-slate-200 hover:border-indigo-300 hover:text-indigo-600 transition-all font-bold text-slate-700 flex items-center justify-between group">
                    Managing payouts and settlements
                    <i class="lucide-arrow-right w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </a>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
