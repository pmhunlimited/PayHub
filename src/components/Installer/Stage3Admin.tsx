import React from 'react';
import { UserCircle, ArrowRight, ArrowLeft, Mail, Lock, ShieldCheck } from 'lucide-react';

interface Props {
  config: any;
  setConfig: (config: any) => void;
  onNext: () => void;
  onBack: () => void;
}

export function Stage3Admin({ config, setConfig, onNext, onBack }: Props) {
  const [isSaving, setIsSaving] = React.useState(false);

  const handleSave = async () => {
    setIsSaving(true);
    // Simulate saving admin details
    await new Promise(resolve => setTimeout(resolve, 1000));
    setIsSaving(false);
    onNext();
  };

  return (
    <div className="space-y-8">
      <div className="space-y-2">
        <h2 className="text-3xl font-bold tracking-tight text-slate-900">Administrator Setup</h2>
        <p className="text-slate-500">Create the primary administrator account for your platform.</p>
      </div>

      <div className="space-y-6">
        <div className="space-y-2">
          <label className="text-sm font-bold text-slate-700 uppercase tracking-wider">Admin Email</label>
          <div className="relative">
            <Mail className="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
            <input
              type="email"
              value={config.admin_email}
              onChange={(e) => setConfig({ ...config, admin_email: e.target.value })}
              className="w-full pl-14 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all outline-none font-medium"
              placeholder="admin@payhub.com"
            />
          </div>
        </div>

        <div className="space-y-2">
          <label className="text-sm font-bold text-slate-700 uppercase tracking-wider">Admin Password</label>
          <div className="relative">
            <Lock className="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
            <input
              type="password"
              value={config.admin_pass}
              onChange={(e) => setConfig({ ...config, admin_pass: e.target.value })}
              className="w-full pl-14 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all outline-none font-medium"
              placeholder="••••••••"
            />
          </div>
        </div>

        <div className="p-6 bg-emerald-50 rounded-2xl border border-emerald-100 flex items-start gap-4">
          <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
            <ShieldCheck size={24} />
          </div>
          <div className="space-y-1">
            <p className="text-sm font-bold text-emerald-900">Security Recommendation</p>
            <p className="text-xs text-emerald-700 leading-relaxed">
              Use a strong password with at least 8 characters, including numbers and special symbols. 
              This account will have full access to all system settings and financial data.
            </p>
          </div>
        </div>
      </div>

      <div className="pt-8 border-t border-slate-100 flex justify-between items-center">
        <button
          onClick={onBack}
          className="flex items-center gap-2 px-6 py-4 text-slate-500 font-bold hover:text-slate-800 transition-colors"
        >
          <ArrowLeft size={20} />
          Back
        </button>
        <button
          onClick={handleSave}
          disabled={isSaving || !config.admin_email || !config.admin_pass}
          className="flex items-center gap-2 px-8 py-4 bg-emerald-600 text-white font-bold rounded-2xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/20 disabled:opacity-50 group min-w-[180px] justify-center"
        >
          {isSaving ? 'Saving...' : (
            <>
              Complete Setup
              <ArrowRight size={20} className="group-hover:translate-x-1 transition-transform" />
            </>
          )}
        </button>
      </div>
    </div>
  );
}
