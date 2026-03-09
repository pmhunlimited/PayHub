<?php
// php-version/index.php
require_once 'includes/functions.php';

if (!isInstalled()) {
    header("Location: install/index.php");
    exit;
}

$pageTitle = 'Payhub - Modern Payments for Ambitious Businesses';
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="max-w-2xl">
                    <h1 class="text-5xl lg:text-7xl font-bold tracking-tight text-slate-900 leading-[1.1] mb-6">
                        Modern payments for <span class="text-indigo-600">ambitious</span> businesses.
                    </h1>
                    <p class="text-xl text-slate-600 mb-10 leading-relaxed">
                        Payhub helps businesses in Africa get paid by anyone, anywhere in the world. 
                        Start accepting payments in minutes with our robust API and no-code tools.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="register.php" class="bg-indigo-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-200 flex items-center justify-center gap-2">
                            Get Started Now <i data-lucide="arrow-right w-5 h-5"></i>
                        </a>
                        <a href="support.php" class="bg-slate-50 text-slate-900 px-8 py-4 rounded-full text-lg font-semibold hover:bg-slate-100 transition-all flex items-center justify-center gap-2">
                            Contact Sales
                        </a>
                    </div>
                    <div class="mt-12 flex items-center gap-6 grayscale opacity-60">
                        <img src="https://picsum.photos/seed/brand1/100/40" alt="Partner" class="h-8" referrerPolicy="no-referrer">
                        <img src="https://picsum.photos/seed/brand2/100/40" alt="Partner" class="h-8" referrerPolicy="no-referrer">
                        <img src="https://picsum.photos/seed/brand3/100/40" alt="Partner" class="h-8" referrerPolicy="no-referrer">
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -inset-4 bg-indigo-100 rounded-[2rem] blur-3xl opacity-30 animate-pulse"></div>
                    <img 
                        src="https://picsum.photos/seed/dashboard/800/600" 
                        alt="Dashboard Preview" 
                        class="relative rounded-2xl shadow-2xl border border-slate-200"
                        referrerPolicy="no-referrer"
                    >
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-sm font-bold text-indigo-600 uppercase tracking-widest mb-4">Why Payhub?</h2>
                <p class="text-4xl font-bold text-slate-900 mb-6">Everything you need to grow your business</p>
                <p class="text-lg text-slate-600">From startups to global corporations, Payhub provides the tools to scale your financial operations.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-10 rounded-3xl border border-slate-100 hover:shadow-xl transition-all group">
                    <div class="mb-6 p-4 bg-indigo-50 rounded-2xl w-fit group-hover:scale-110 transition-transform">
                        <i data-lucide="zap w-8 h-8 text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-4">Fast Integration</h3>
                    <p class="text-slate-600 leading-relaxed">Get up and running in minutes with our well-documented APIs and SDKs.</p>
                </div>
                <div class="bg-white p-10 rounded-3xl border border-slate-100 hover:shadow-xl transition-all group">
                    <div class="mb-6 p-4 bg-indigo-50 rounded-2xl w-fit group-hover:scale-110 transition-transform">
                        <i data-lucide="shield w-8 h-8 text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-4">Secure Payments</h3>
                    <p class="text-slate-600 leading-relaxed">PCI-DSS Level 1 compliant infrastructure with advanced fraud detection.</p>
                </div>
                <div class="bg-white p-10 rounded-3xl border border-slate-100 hover:shadow-xl transition-all group">
                    <div class="mb-6 p-4 bg-indigo-50 rounded-2xl w-fit group-hover:scale-110 transition-transform">
                        <i data-lucide="bar-chart-3 w-8 h-8 text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-4">Deep Insights</h3>
                    <p class="text-slate-600 leading-relaxed">Understand your customers with real-time analytics and custom reports.</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
