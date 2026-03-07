<?php
// php-version/api-reference.php
require_once 'includes/functions.php';
$pageTitle = 'API Reference - Payhub';
include 'includes/header.php';
?>
<div class="pt-20 flex min-h-screen">
    <aside class="w-64 border-r border-slate-100 p-8 hidden md:block sticky top-0 h-screen overflow-y-auto">
        <h3 class="font-bold text-slate-900 mb-6 uppercase text-xs tracking-widest">API Reference</h3>
        <nav class="space-y-6">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-3">Core</p>
                <div class="space-y-2">
                    <a href="#" class="block text-sm text-indigo-600 font-bold">Authentication</a>
                    <a href="#" class="block text-sm text-slate-600 hover:text-slate-900">Errors</a>
                    <a href="#" class="block text-sm text-slate-600 hover:text-slate-900">Pagination</a>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-3">Payments</p>
                <div class="space-y-2">
                    <a href="#" class="block text-sm text-slate-600 hover:text-slate-900">Transactions</a>
                    <a href="#" class="block text-sm text-slate-600 hover:text-slate-900">Customers</a>
                    <a href="#" class="block text-sm text-slate-600 hover:text-slate-900">Invoices</a>
                </div>
            </div>
        </nav>
    </aside>
    <main class="flex-1 p-8 lg:p-16 max-w-5xl">
        <h1 class="text-4xl font-bold text-slate-900 mb-4">API Reference</h1>
        <p class="text-slate-600 mb-12">Learn how to interact with the Payhub API programmatically.</p>

        <section class="mb-16">
            <div class="flex items-center gap-4 mb-6">
                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg font-bold text-sm">POST</span>
                <h2 class="text-2xl font-bold text-slate-900">Initialize Transaction</h2>
            </div>
            <div class="grid lg:grid-cols-2 gap-8">
                <div>
                    <p class="text-slate-600 mb-6">Initialize a transaction from your backend to get a payment URL.</p>
                    <h4 class="font-bold text-sm text-slate-900 mb-4 uppercase tracking-wider">Parameters</h4>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 py-3 border-b border-slate-100 last:border-0">
                            <div class="min-w-[100px]">
                                <p class="font-mono text-sm text-slate-900">amount</p>
                                <p class="text-xs text-slate-400">integer</p>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-slate-600">Amount in kobo</p>
                                <span class="text-[10px] font-bold text-red-500 uppercase">Required</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 py-3 border-b border-slate-100 last:border-0">
                            <div class="min-w-[100px]">
                                <p class="font-mono text-sm text-slate-900">email</p>
                                <p class="text-xs text-slate-400">string</p>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-slate-600">Customer email</p>
                                <span class="text-[10px] font-bold text-red-500 uppercase">Required</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 py-3 border-b border-slate-100 last:border-0">
                            <div class="min-w-[100px]">
                                <p class="font-mono text-sm text-slate-900">reference</p>
                                <p class="text-xs text-slate-400">string</p>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-slate-600">Unique transaction reference</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-900 rounded-2xl p-6 text-slate-300 font-mono text-sm overflow-x-auto">
                    <p class="text-slate-500 mb-4">// Request Example</p>
                    <pre>curl https://api.payhub.com/transaction/initialize \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -d amount=10000 \
  -d email="customer@email.com"</pre>
                </div>
            </div>
        </section>

        <section class="mb-16">
            <div class="flex items-center gap-4 mb-6">
                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg font-bold text-sm">WEBHOOK</span>
                <h2 class="text-2xl font-bold text-slate-900">Webhook Notifications</h2>
            </div>
            <div class="grid lg:grid-cols-2 gap-8">
                <div>
                    <p class="text-slate-600 mb-6">Receive real-time notifications for transaction events.</p>
                    <h4 class="font-bold text-sm text-slate-900 mb-4 uppercase tracking-wider">Retry Logic</h4>
                    <p class="text-sm text-slate-600 mb-4">If your server returns a non-200 status code, we will retry the notification up to 5 times with exponential backoff.</p>
                    <p class="text-sm text-slate-600">You can also manually trigger a retry from the Admin Dashboard if needed.</p>
                </div>
                <div class="bg-slate-900 rounded-2xl p-6 text-slate-300 font-mono text-sm overflow-x-auto">
                    <p class="text-slate-500 mb-4">// Webhook Payload</p>
                    <pre>{
  "event": "charge.success",
  "data": {
    "reference": "ref_123456",
    "amount": 10000,
    "status": "success",
    "customer": {
      "email": "customer@email.com"
    }
  }
}</pre>
                </div>
            </div>
        </section>
    </main>
    </div>
    <?php include 'footer.php'; ?>
