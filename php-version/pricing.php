<?php
// php-version/pricing.php
require_once 'includes/functions.php';
$pageTitle = 'Pricing - Payhub';
include 'includes/header.php';
?>
<div class="pt-32 pb-24 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h1 class="text-4xl font-bold text-slate-900 mb-4 tracking-tight">Simple, transparent pricing</h1>
            <p class="text-lg text-slate-600">No hidden fees. No setup costs. Only pay when you get paid.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Starter -->
            <div class="p-8 rounded-[2rem] border bg-white border-slate-200 text-slate-900 shadow-sm hover:shadow-xl transition-all">
                <h3 class="text-xl font-bold mb-2">Starter</h3>
                <div class="flex items-baseline gap-1 mb-4">
                    <span class="text-4xl font-bold tracking-tight">1.5%</span>
                    <span class="text-slate-500 text-sm">/transaction</span>
                </div>
                <p class="mb-8 text-sm text-slate-600">Perfect for small businesses and side projects.</p>
                
                <ul class="space-y-4 mb-8">
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-600"></i>
                        Local Payments
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-600"></i>
                        Email Support
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-600"></i>
                        Basic Analytics
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-600"></i>
                        Payment Pages
                    </li>
                </ul>

                <a href="register.php" class="block w-full text-center py-4 rounded-2xl font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    Get Started
                </a>
            </div>

            <!-- Growth -->
            <div class="p-8 rounded-[2rem] border bg-indigo-600 text-white border-indigo-600 shadow-2xl shadow-indigo-200 scale-105 relative z-10">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-white text-indigo-600 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-widest shadow-lg">Most Popular</div>
                <h3 class="text-xl font-bold mb-2">Growth</h3>
                <div class="flex items-baseline gap-1 mb-4">
                    <span class="text-4xl font-bold tracking-tight">1.5% + ₦100</span>
                    <span class="text-indigo-200 text-sm">/transaction</span>
                </div>
                <p class="mb-8 text-sm text-indigo-100">For growing businesses that need more power.</p>
                
                <ul class="space-y-4 mb-8">
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-300"></i>
                        Global Payments
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-300"></i>
                        Priority Support
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-300"></i>
                        Advanced Analytics
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-300"></i>
                        Virtual Accounts
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-300"></i>
                        Invoicing
                    </li>
                </ul>

                <a href="register.php" class="block w-full text-center py-4 rounded-2xl font-bold bg-white text-indigo-600 hover:bg-slate-50 transition-all shadow-xl">
                    Get Started
                </a>
            </div>

            <!-- Enterprise -->
            <div class="p-8 rounded-[2rem] border bg-white border-slate-200 text-slate-900 shadow-sm hover:shadow-xl transition-all">
                <h3 class="text-xl font-bold mb-2">Enterprise</h3>
                <div class="flex items-baseline gap-1 mb-4">
                    <span class="text-4xl font-bold tracking-tight">Custom</span>
                </div>
                <p class="mb-8 text-sm text-slate-600">Tailored solutions for large-scale operations.</p>
                
                <ul class="space-y-4 mb-8">
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-600"></i>
                        Dedicated Account Manager
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-600"></i>
                        Custom Fee Structure
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-600"></i>
                        White-labeling
                    </li>
                    <li class="flex items-center gap-3 text-sm">
                        <i class="lucide-check w-5 h-5 text-indigo-600"></i>
                        API Integration Support
                    </li>
                </ul>

                <a href="register.php" class="block w-full text-center py-4 rounded-2xl font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    Contact Sales
                </a>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
