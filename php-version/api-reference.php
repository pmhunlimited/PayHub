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
                    <a href="#authentication" class="block text-sm text-slate-600 hover:text-indigo-600 font-medium">Authentication</a>
                    <a href="#inline-checkout" class="block text-sm text-slate-600 hover:text-indigo-600 font-medium">Inline Checkout</a>
                    <a href="#webhooks" class="block text-sm text-slate-600 hover:text-indigo-600 font-medium">Webhooks & Callback</a>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-3">Payments</p>
                <div class="space-y-2">
                    <a href="#initialize" class="block text-sm text-slate-600 hover:text-indigo-600 font-medium">Initialize Transaction</a>
                    <a href="#verify" class="block text-sm text-slate-600 hover:text-indigo-600 font-medium">Verify Transaction</a>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-3">Plugins</p>
                <div class="space-y-2">
                    <a href="#woocommerce" class="block text-sm text-emerald-600 font-bold">WooCommerce Plugin</a>
                    <a href="#whmcs" class="block text-sm text-blue-600 font-bold">WHMCS Module</a>
                </div>
            </div>
        </nav>
    </aside>
    <main class="flex-1 p-8 lg:p-16 max-w-5xl">
        <div class="mb-12 border-b border-slate-100 pb-12">
            <h1 class="text-4xl font-extrabold text-slate-900 mb-4">API Documentation</h1>
            <p class="text-xl text-slate-500 leading-relaxed">Everything you need to build powerful payment experiences with Payhub.</p>
        </div>

        <section id="authentication" class="mb-20 scroll-mt-24">
            <h2 class="text-2xl font-bold text-slate-900 mb-4">Authentication</h2>
            <p class="text-slate-600 mb-6 leading-relaxed">The Payhub API uses Secret Keys to authenticate requests. You can view and manage your API keys in the <a href="merchant/api-keys.php" class="text-indigo-600 font-bold">Dashboard</a>. Your secret keys carry many privileges, so be sure to keep them secure!</p>
            <div class="bg-slate-900 rounded-2xl p-6 text-slate-300 font-mono text-sm">
                Authorization: Bearer sk_live_xxxxxxxxxxxx
            </div>
        </section>

        <section id="inline-checkout" class="mb-20 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-lg font-bold text-sm">JAVASCRIPT</span>
                <h2 class="text-2xl font-bold text-slate-900">Inline Checkout</h2>
            </div>
            <p class="text-slate-600 mb-8 leading-relaxed">Collect payments without redirecting your customers. Our inline checkout provides a seamless experience for your users.</p>
            <div class="bg-slate-900 rounded-2xl p-8 text-slate-300 font-mono text-sm overflow-x-auto">
                <p class="text-slate-500 mb-4">// Add the Payhub Inline Script</p>
                <pre>&lt;script src="https://js.payhub.com/v1/inline.js"&gt;&lt;/script&gt;

&lt;script&gt;
  const paymentForm = document.getElementById('paymentForm');
  paymentForm.addEventListener("submit", payWithPayhub, false);

  function payWithPayhub(e) {
    e.preventDefault();

    let handler = PayhubPop.setup({
      key: 'YOUR_PUBLIC_KEY', // Replace with your public key
      email: document.getElementById("email-address").value,
      amount: document.getElementById("amount").value * 100,
      ref: ''+Math.floor((Math.random() * 1000000000) + 1), // generates a pseudo-unique reference.
      onClose: function(){
        alert('Window closed.');
      },
      callback: function(response){
        let message = 'Payment complete! Reference: ' + response.reference;
        alert(message);
        window.location.href = "/success.php?ref=" + response.reference;
      }
    });

    handler.openIframe();
  }
&lt;/script&gt;</pre>
            </div>
        </section>

        <section id="initialize" class="mb-20 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg font-bold text-sm">POST</span>
                <h2 class="text-2xl font-bold text-slate-900">Initialize Transaction</h2>
            </div>
            <p class="text-slate-600 mb-8 leading-relaxed">Start a transaction from your server to get a checkout URL.</p>
            <div class="bg-slate-900 rounded-2xl p-8 text-slate-300 font-mono text-sm overflow-x-auto">
                <pre>curl https://api.payhub.com/transaction/initialize \
-H "Authorization: Bearer YOUR_SECRET_KEY" \
-d email="customer@email.com" \
-d amount=500000</pre>
            </div>
        </section>

        <section id="verify" class="mb-20 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold text-sm">GET</span>
                <h2 class="text-2xl font-bold text-slate-900">Verify Transaction</h2>
            </div>
            <p class="text-slate-600 mb-8 leading-relaxed">Confirm the status of a transaction using its reference.</p>
            <div class="bg-slate-900 rounded-2xl p-8 text-slate-300 font-mono text-sm overflow-x-auto">
                <pre>curl https://api.payhub.com/transaction/verify/:reference \
-H "Authorization: Bearer YOUR_SECRET_KEY"</pre>
            </div>
        </section>

        <section id="webhooks" class="mb-20 scroll-mt-24">
            <div class="flex items-center gap-4 mb-6">
                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg font-bold text-sm">WEBHOOK</span>
                <h2 class="text-2xl font-bold text-slate-900">Webhooks & Callback</h2>
            </div>
            <p class="text-slate-600 mb-8 leading-relaxed">Configure your server to listen for events from Payhub.</p>
            <div class="bg-slate-900 rounded-2xl p-8 text-slate-300 font-mono text-sm overflow-x-auto">
                <pre>{
  "event": "charge.success",
  "data": {
    "reference": "ref_123",
    "amount": 10000,
    "status": "success"
  }
}</pre>
            </div>
        </section>

        <section id="woocommerce" class="mb-20 scroll-mt-24">
            <div class="p-8 bg-emerald-50 rounded-[2.5rem] border border-emerald-100 flex flex-col md:flex-row items-center gap-8">
                <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center shadow-sm">
                    <i class="lucide-shopping-cart text-emerald-600 w-10 h-10"></i>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h3 class="text-2xl font-bold text-emerald-900 mb-2">WooCommerce Plugin</h3>
                    <p class="text-emerald-700 mb-6">Accept payments on your WordPress store in minutes. No coding required.</p>
                    <a href="downloads/payhub-woocommerce.zip" class="inline-flex items-center gap-2 bg-emerald-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200">
                        <i class="lucide-download w-4 h-4"></i> Download Plugin
                    </a>
                </div>
            </div>
        </section>

        <section id="whmcs" class="mb-20 scroll-mt-24">
            <div class="p-8 bg-blue-50 rounded-[2.5rem] border border-blue-100 flex flex-col md:flex-row items-center gap-8">
                <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center shadow-sm">
                    <i class="lucide-server text-blue-600 w-10 h-10"></i>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h3 class="text-2xl font-bold text-blue-900 mb-2">WHMCS Payment Module</h3>
                    <p class="text-blue-700 mb-6">Automate your hosting business with the Payhub WHMCS gateway module.</p>
                    <a href="downloads/payhub-whmcs.zip" class="inline-flex items-center gap-2 bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">
                        <i class="lucide-download w-4 h-4"></i> Download Module
                    </a>
                </div>
            </div>
        </section>
    </main>
</div>
<?php include 'includes/footer.php'; ?>
