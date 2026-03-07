import React from 'react';
import { CheckCircle2, XCircle, ArrowRight, Server, Cpu, Globe } from 'lucide-react';

interface Requirement {
  name: string;
  required: string;
  current: string;
  status: boolean;
  icon: React.ElementType;
}

export function Stage1Welcome({ onNext }: { onNext: () => void }) {
  const requirements: Requirement[] = [
    { name: 'PHP Version', required: '>= 8.1.0', current: '8.2.12', status: true, icon: Server },
    { name: 'MySQL Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Globe },
    { name: 'BCMath Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Cpu },
    { name: 'Ctype Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Cpu },
    { name: 'JSON Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Globe },
    { name: 'Mbstring Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Globe },
    { name: 'OpenSSL Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Globe },
    { name: 'PDO Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Globe },
    { name: 'Tokenizer Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Globe },
    { name: 'XML Extension', required: 'Enabled', current: 'Enabled', status: true, icon: Globe },
  ];

  const allMet = requirements.every((r) => r.status);

  return (
    <div className="space-y-8">
      <div className="space-y-2">
        <h2 className="text-3xl font-bold tracking-tight text-slate-900">Welcome to Payhub</h2>
        <p className="text-slate-500">Before we begin, please ensure your server meets the following requirements.</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {requirements.map((req) => {
          const Icon = req.icon;
          return (
            <div key={req.name} className="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 bg-white rounded-lg flex items-center justify-center text-slate-400 shadow-sm">
                  <Icon size={16} />
                </div>
                <div>
                  <p className="text-sm font-semibold text-slate-700">{req.name}</p>
                  <p className="text-xs text-slate-400">Required: {req.required}</p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-xs font-medium text-slate-500">{req.current}</span>
                {req.status ? (
                  <CheckCircle2 className="text-emerald-500" size={18} />
                ) : (
                  <XCircle className="text-rose-500" size={18} />
                )}
              </div>
            </div>
          );
        })}
      </div>

      <div className="pt-8 border-t border-slate-100 flex justify-end">
        <button
          onClick={onNext}
          disabled={!allMet}
          className="flex items-center gap-2 px-8 py-4 bg-emerald-600 text-white font-bold rounded-2xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/20 disabled:opacity-50 disabled:cursor-not-allowed group"
        >
          Begin Installation
          <ArrowRight size={20} className="group-hover:translate-x-1 transition-transform" />
        </button>
      </div>
    </div>
  );
}
