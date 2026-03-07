import React from 'react';

export default function Docs() {
  return (
    <div className="min-h-screen bg-white flex">
      <aside className="w-64 border-r border-slate-100 p-8 hidden md:block">
        <h3 className="font-bold text-slate-900 mb-6">Documentation</h3>
        <nav className="space-y-4">
          <DocLink label="Getting Started" active />
          <DocLink label="Integration Guide" />
          <DocLink label="Payment Methods" />
          <DocLink label="Webhooks" />
          <DocLink label="API Reference" />
        </nav>
      </aside>
      <main className="flex-1 p-8 lg:p-16 max-w-4xl">
        <h1 className="text-4xl font-bold text-slate-900 mb-8">Getting Started</h1>
        <p className="text-lg text-slate-600 mb-8 leading-relaxed">
          Welcome to the Payhub developer documentation. Our APIs are designed to be simple, 
          powerful, and easy to integrate into your application.
        </p>
        
        <section className="mb-12">
          <h2 className="text-2xl font-bold text-slate-900 mb-4">Quick Start</h2>
          <div className="bg-slate-900 rounded-xl p-6 text-slate-300 font-mono text-sm">
            <p className="mb-2"># Install the SDK</p>
            <p className="text-indigo-400">npm install @payhub/sdk</p>
          </div>
        </section>

        <section>
          <h2 className="text-2xl font-bold text-slate-900 mb-4">Authentication</h2>
          <p className="text-slate-600 mb-4">
            All API requests must be authenticated using your Secret Key. 
            You can find your keys in the Developer section of your dashboard.
          </p>
          <div className="bg-slate-900 rounded-xl p-6 text-slate-300 font-mono text-sm">
            <p className="text-emerald-400">Authorization: Bearer YOUR_SECRET_KEY</p>
          </div>
        </section>
      </main>
    </div>
  );
}

function DocLink({ label, active = false }: any) {
  return (
    <a href="#" className={`block text-sm ${active ? 'text-indigo-600 font-bold' : 'text-slate-600 hover:text-slate-900'}`}>
      {label}
    </a>
  );
}
