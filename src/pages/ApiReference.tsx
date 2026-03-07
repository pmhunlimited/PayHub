import React from 'react';

export default function ApiReference() {
  return (
    <div className="min-h-screen bg-white flex">
      <aside className="w-64 border-r border-slate-100 p-8 hidden md:block sticky top-0 h-screen overflow-y-auto">
        <h3 className="font-bold text-slate-900 mb-6 uppercase text-xs tracking-widest">API Reference</h3>
        <nav className="space-y-6">
          <div>
            <p className="text-xs font-bold text-slate-400 uppercase mb-3">Core</p>
            <div className="space-y-2">
              <NavLink label="Authentication" active />
              <NavLink label="Errors" />
              <NavLink label="Pagination" />
            </div>
          </div>
          <div>
            <p className="text-xs font-bold text-slate-400 uppercase mb-3">Payments</p>
            <div className="space-y-2">
              <NavLink label="Transactions" />
              <NavLink label="Customers" />
              <NavLink label="Invoices" />
            </div>
          </div>
        </nav>
      </aside>
      <main className="flex-1 p-8 lg:p-16 max-w-5xl">
        <h1 className="text-4xl font-bold text-slate-900 mb-4">API Reference</h1>
        <p className="text-slate-600 mb-12">Learn how to interact with the Payhub API programmatically.</p>

        <section className="mb-16">
          <div className="flex items-center gap-4 mb-6">
            <span className="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg font-bold text-sm">POST</span>
            <h2 className="text-2xl font-bold text-slate-900">Initialize Transaction</h2>
          </div>
          <div className="grid lg:grid-cols-2 gap-8">
            <div>
              <p className="text-slate-600 mb-6">Initialize a transaction from your backend to get a payment URL.</p>
              <h4 className="font-bold text-sm text-slate-900 mb-4 uppercase tracking-wider">Parameters</h4>
              <div className="space-y-4">
                <Param name="amount" type="integer" description="Amount in kobo" required />
                <Param name="email" type="string" description="Customer email" required />
                <Param name="reference" type="string" description="Unique transaction reference" />
              </div>
            </div>
            <div className="bg-slate-900 rounded-2xl p-6 text-slate-300 font-mono text-sm overflow-x-auto">
              <p className="text-slate-500 mb-4">// Request Example</p>
              <pre>{`curl https://api.payhub.com/transaction/initialize \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -d amount=10000 \\
  -d email="customer@email.com"`}</pre>
            </div>
          </div>
        </section>

        <section className="mb-16">
          <div className="flex items-center gap-4 mb-6">
            <span className="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg font-bold text-sm">WEBHOOK</span>
            <h2 className="text-2xl font-bold text-slate-900">Webhook Notifications</h2>
          </div>
          <div className="grid lg:grid-cols-2 gap-8">
            <div>
              <p className="text-slate-600 mb-6">Receive real-time notifications for transaction events.</p>
              <h4 className="font-bold text-sm text-slate-900 mb-4 uppercase tracking-wider">Retry Logic</h4>
              <p className="text-sm text-slate-600 mb-4">If your server returns a non-200 status code, we will retry the notification up to 5 times with exponential backoff.</p>
              <p className="text-sm text-slate-600">You can also manually trigger a retry from the Admin Dashboard if needed.</p>
            </div>
            <div className="bg-slate-900 rounded-2xl p-6 text-slate-300 font-mono text-sm overflow-x-auto">
              <p className="text-slate-500 mb-4">// Webhook Payload</p>
              <pre>{`{
  "event": "charge.success",
  "data": {
    "reference": "ref_123456",
    "amount": 10000,
    "status": "success",
    "customer": {
      "email": "customer@email.com"
    }
  }
}`}</pre>
            </div>
          </div>
        </section>
      </main>
    </div>
  );
}

function NavLink({ label, active = false }: any) {
  return (
    <a href="#" className={`block text-sm ${active ? 'text-indigo-600 font-bold' : 'text-slate-600 hover:text-slate-900'}`}>
      {label}
    </a>
  );
}

function Param({ name, type, description, required = false }: any) {
  return (
    <div className="flex items-start gap-4 py-3 border-b border-slate-100 last:border-0">
      <div className="min-w-[100px]">
        <p className="font-mono text-sm text-slate-900">{name}</p>
        <p className="text-xs text-slate-400">{type}</p>
      </div>
      <div className="flex-1">
        <p className="text-sm text-slate-600">{description}</p>
        {required && <span className="text-[10px] font-bold text-red-500 uppercase">Required</span>}
      </div>
    </div>
  );
}
