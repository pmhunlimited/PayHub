<?php
// php-version/docs.php
require_once 'includes/functions.php';
$pageTitle = 'Documentation - Payhub';
include 'includes/header.php';
?>
<div class="pt-20 flex min-h-screen">
    <aside class="w-64 border-r border-slate-100 p-8 hidden md:block sticky top-0 h-screen overflow-y-auto">
        <h3 class="font-bold text-slate-900 mb-6">Documentation</h3>
        <nav class="space-y-4">
            <a href="#" class="block text-sm text-indigo-600 font-bold">Getting Started</a>
            <a href="#" class="block text-sm text-slate-600 hover:text-slate-900">Integration Guide</a>
            <a href="#" class="block text-sm text-slate-600 hover:text-slate-900">Payment Methods</a>
            <a href="#" class="block text-sm text-slate-600 hover:text-slate-900">Webhooks</a>
            <a href="api-reference.php" class="block text-sm text-slate-600 hover:text-slate-900">API Reference</a>
        </nav>
    </aside>
    <main class="flex-1 p-8 lg:p-16 max-w-4xl">
        <h1 class="text-4xl font-bold text-slate-900 mb-8">Getting Started</h1>
        <p class="text-lg text-slate-600 mb-8 leading-relaxed">
            Welcome to the Payhub developer documentation. Our APIs are designed to be simple, 
            powerful, and easy to integrate into your application.
        </p>
        
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-slate-900 mb-4">Quick Start</h2>
            <div class="bg-slate-900 rounded-xl p-6 text-slate-300 font-mono text-sm">
                <p class="mb-2 text-slate-500"># Install the SDK</p>
                <p class="text-indigo-400">npm install @payhub/sdk</p>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-slate-900 mb-4">Authentication</h2>
            <p class="text-slate-600 mb-4">
                All API requests must be authenticated using your Secret Key. 
                You can find your keys in the Developer section of your dashboard.
            </p>
            <div class="bg-slate-900 rounded-xl p-6 text-slate-300 font-mono text-sm">
                <p class="text-emerald-400">Authorization: Bearer YOUR_SECRET_KEY</p>
            </div>
        </section>
    </main>
    </div>
    <?php include 'footer.php'; ?>
