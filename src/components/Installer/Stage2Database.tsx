import React from 'react';
import { Database, ArrowRight, ArrowLeft, Loader2, AlertCircle } from 'lucide-react';

interface Props {
  config: any;
  setConfig: (config: any) => void;
  onNext: () => void;
  onBack: () => void;
}

export function Stage2Database({ config, setConfig, onNext, onBack }: Props) {
  const [isTesting, setIsTesting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  const handleTest = async () => {
    setIsTesting(true);
    setError(null);
    // Simulate connection test
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    if (!config.db_name || !config.db_user) {
      setError('Please fill in all database fields.');
      setIsTesting(false);
      return;
    }

    setIsTesting(false);
    onNext();
  };

  return (
    <div className="space-y-8">
      <div className="space-y-2">
        <h2 className="text-3xl font-bold tracking-tight text-slate-900">Database Configuration</h2>
        <p className="text-slate-500">Provide your MySQL database credentials to establish a connection.</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="space-y-2">
          <label className="text-sm font-bold text-slate-700 uppercase tracking-wider">Database Host</label>
          <input
            type="text"
            value={config.db_host}
            onChange={(e) => setConfig({ ...config, db_host: e.target.value })}
            className="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all outline-none font-medium"
            placeholder="localhost"
          />
        </div>
        <div className="space-y-2">
          <label className="text-sm font-bold text-slate-700 uppercase tracking-wider">Database Name</label>
          <input
            type="text"
            value={config.db_name}
            onChange={(e) => setConfig({ ...config, db_name: e.target.value })}
            className="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all outline-none font-medium"
            placeholder="payhub_db"
          />
        </div>
        <div className="space-y-2">
          <label className="text-sm font-bold text-slate-700 uppercase tracking-wider">Username</label>
          <input
            type="text"
            value={config.db_user}
            onChange={(e) => setConfig({ ...config, db_user: e.target.value })}
            className="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all outline-none font-medium"
            placeholder="root"
          />
        </div>
        <div className="space-y-2">
          <label className="text-sm font-bold text-slate-700 uppercase tracking-wider">Password</label>
          <input
            type="password"
            value={config.db_pass}
            onChange={(e) => setConfig({ ...config, db_pass: e.target.value })}
            className="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all outline-none font-medium"
            placeholder="••••••••"
          />
        </div>
      </div>

      {error && (
        <div className="flex items-center gap-3 p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-600 text-sm font-medium">
          <AlertCircle size={20} />
          {error}
        </div>
      )}

      <div className="pt-8 border-t border-slate-100 flex justify-between items-center">
        <button
          onClick={onBack}
          className="flex items-center gap-2 px-6 py-4 text-slate-500 font-bold hover:text-slate-800 transition-colors"
        >
          <ArrowLeft size={20} />
          Back
        </button>
        <button
          onClick={handleTest}
          disabled={isTesting}
          className="flex items-center gap-2 px-8 py-4 bg-emerald-600 text-white font-bold rounded-2xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/20 disabled:opacity-70 group min-w-[180px] justify-center"
        >
          {isTesting ? (
            <>
              <Loader2 className="animate-spin" size={20} />
              Installing...
            </>
          ) : (
            <>
              Next Step
              <ArrowRight size={20} className="group-hover:translate-x-1 transition-transform" />
            </>
          )}
        </button>
      </div>
    </div>
  );
}
