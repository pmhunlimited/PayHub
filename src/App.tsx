import React from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { CheckCircle2, Database, UserCircle, Settings, ArrowRight, ArrowLeft, Download, Code } from 'lucide-react';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

import { Stage1Welcome } from './components/Installer/Stage1Welcome';
import { Stage2Database } from './components/Installer/Stage2Database';
import { Stage3Admin } from './components/Installer/Stage3Admin';
import { Stage4Success } from './components/Installer/Stage4Success';
import { PHPCodeViewer } from './components/Installer/PHPCodeViewer';

type Stage = 1 | 2 | 3 | 4;

export default function App() {
  const [stage, setStage] = React.useState<Stage>(1);
  const [showPHPCode, setShowPHPCode] = React.useState(false);
  const [config, setConfig] = React.useState({
    db_host: 'localhost',
    db_name: '',
    db_user: '',
    db_pass: '',
    admin_email: '',
    admin_pass: '',
  });

  const nextStage = () => setStage((prev) => (prev < 4 ? (prev + 1) as Stage : prev));
  const prevStage = () => setStage((prev) => (prev > 1 ? (prev - 1) as Stage : prev));

  const steps = [
    { id: 1, name: 'Welcome', icon: Settings },
    { id: 2, name: 'Database', icon: Database },
    { id: 3, name: 'Admin', icon: UserCircle },
    { id: 4, name: 'Finish', icon: CheckCircle2 },
  ];

  return (
    <div className="min-h-screen bg-[#f5f5f5] font-sans text-slate-900 flex flex-col">
      {/* Header */}
      <header className="bg-white border-b border-slate-200 py-6 px-8 flex justify-between items-center">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">
            P
          </div>
          <div>
            <h1 className="text-xl font-semibold tracking-tight">Payhub Installer</h1>
            <p className="text-xs text-slate-500 font-medium uppercase tracking-wider">System Setup Wizard</p>
          </div>
        </div>
        
        <button 
          onClick={() => setShowPHPCode(!showPHPCode)}
          className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 hover:text-emerald-600 transition-colors bg-slate-100 rounded-lg"
        >
          <Code size={18} />
          {showPHPCode ? 'Hide PHP Code' : 'View PHP Source'}
        </button>
      </header>

      <main className="flex-1 flex items-center justify-center p-6">
        <div className="w-full max-w-4xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* Sidebar Steps */}
          <div className="lg:col-span-3 space-y-4">
            {steps.map((step) => {
              const Icon = step.icon;
              const isActive = stage === step.id;
              const isCompleted = stage > step.id;

              return (
                <div 
                  key={step.id}
                  className={cn(
                    "flex items-center gap-4 p-4 rounded-2xl transition-all duration-300",
                    isActive ? "bg-white shadow-sm border border-slate-200" : "opacity-50"
                  )}
                >
                  <div className={cn(
                    "w-10 h-10 rounded-xl flex items-center justify-center transition-colors",
                    isActive ? "bg-emerald-600 text-white" : isCompleted ? "bg-emerald-100 text-emerald-600" : "bg-slate-200 text-slate-500"
                  )}>
                    {isCompleted ? <CheckCircle2 size={20} /> : <Icon size={20} />}
                  </div>
                  <div>
                    <p className="text-xs font-bold text-slate-400 uppercase tracking-widest">Step 0{step.id}</p>
                    <p className="font-semibold text-sm">{step.name}</p>
                  </div>
                </div>
              );
            })}
          </div>

          {/* Main Content Area */}
          <div className="lg:col-span-9">
            <AnimatePresence mode="wait">
              {showPHPCode ? (
                <motion.div
                  key="php-code"
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -10 }}
                  className="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden"
                >
                  <PHPCodeViewer config={config} />
                </motion.div>
              ) : (
                <motion.div
                  key={stage}
                  initial={{ opacity: 0, x: 20 }}
                  animate={{ opacity: 1, x: 0 }}
                  exit={{ opacity: 0, x: -20 }}
                  className="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 min-h-[500px] flex flex-col"
                >
                  <div className="flex-1">
                    {stage === 1 && <Stage1Welcome onNext={nextStage} />}
                    {stage === 2 && (
                      <Stage2Database 
                        config={config} 
                        setConfig={setConfig} 
                        onNext={nextStage} 
                        onBack={prevStage} 
                      />
                    )}
                    {stage === 3 && (
                      <Stage3Admin 
                        config={config} 
                        setConfig={setConfig} 
                        onNext={nextStage} 
                        onBack={prevStage} 
                      />
                    )}
                    {stage === 4 && <Stage4Success />}
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="py-6 px-8 text-center text-slate-400 text-sm">
        &copy; {new Date().getFullYear()} Payhub Platform. All rights reserved.
      </footer>
    </div>
  );
}
