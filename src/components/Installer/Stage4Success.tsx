import React from 'react';
import { CheckCircle2, ArrowRight, ExternalLink, ShieldAlert, FileText, Trash2 } from 'lucide-react';

export function Stage4Success() {
  return (
    <div className="space-y-8 text-center">
      <div className="flex flex-col items-center space-y-4">
        <div className="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 shadow-xl shadow-emerald-600/10">
          <CheckCircle2 size={48} />
        </div>
        <div className="space-y-2">
          <h2 className="text-3xl font-bold tracking-tight text-slate-900">Congratulations!</h2>
          <p className="text-slate-500">Payhub has been successfully installed on your server.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
        <div className="p-6 bg-slate-50 rounded-2xl border border-slate-100 space-y-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-600 shadow-sm">
              <ShieldAlert size={20} className="text-rose-500" />
            </div>
            <h3 className="font-bold text-slate-800">Security Steps</h3>
          </div>
          <ul className="space-y-3">
            <li className="flex items-start gap-2 text-sm text-slate-600">
              <Trash2 size={16} className="mt-0.5 shrink-0 text-slate-400" />
              Delete the <code className="bg-slate-200 px-1.5 py-0.5 rounded font-mono text-xs">/install</code> directory immediately.
            </li>
            <li className="flex items-start gap-2 text-sm text-slate-600">
              <FileText size={16} className="mt-0.5 shrink-0 text-slate-400" />
              Check file permissions for <code className="bg-slate-200 px-1.5 py-0.5 rounded font-mono text-xs">config.php</code>.
            </li>
          </ul>
        </div>

        <div className="p-6 bg-slate-50 rounded-2xl border border-slate-100 space-y-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-600 shadow-sm">
              <ExternalLink size={20} className="text-emerald-500" />
            </div>
            <h3 className="font-bold text-slate-800">Quick Links</h3>
          </div>
          <div className="space-y-3">
            <button className="w-full flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200 hover:border-emerald-500 transition-colors group">
              <span className="text-sm font-semibold text-slate-700">Admin Dashboard</span>
              <ArrowRight size={16} className="text-slate-400 group-hover:text-emerald-500 group-hover:translate-x-1 transition-all" />
            </button>
            <button className="w-full flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200 hover:border-emerald-500 transition-colors group">
              <span className="text-sm font-semibold text-slate-700">View Website</span>
              <ArrowRight size={16} className="text-slate-400 group-hover:text-emerald-500 group-hover:translate-x-1 transition-all" />
            </button>
          </div>
        </div>
      </div>

      <div className="pt-8 border-t border-slate-100">
        <button
          onClick={() => window.location.href = '/'}
          className="w-full py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/10"
        >
          Go to Dashboard
        </button>
      </div>
    </div>
  );
}
