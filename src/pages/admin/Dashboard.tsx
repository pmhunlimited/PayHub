import React from 'react';
import { 
  Routes, 
  Route, 
  Link, 
  useLocation,
  useNavigate
} from 'react-router-dom';
import { 
  BarChart3, 
  Users, 
  ShieldAlert, 
  CreditCard, 
  Settings, 
  Search, 
  Bell, 
  CheckCircle2, 
  XCircle,
  MoreVertical,
  Filter,
  Download,
  AlertCircle,
  TrendingUp,
  Activity,
  LogOut,
  Ticket,
  Key,
  FileText,
  UserPlus,
  ShieldCheck,
  Ban,
  Percent,
  Gavel,
  Trash2,
  LogIn,
  Upload,
  Webhook,
  Wallet,
  ArrowRight,
  Zap
} from 'lucide-react';
import { 
  AreaChart, 
  Area, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer,
  BarChart,
  Bar,
  Cell
} from 'recharts';
import { formatCurrency } from '../../lib/utils';

// Sub-components for Admin Dashboard
const Overview = ({ merchants, config, onUpdateConfig, adminStats }: any) => {
  const feePercent = config.find((c: any) => c.key === 'transaction_fee_percent')?.value || '0';
  const feeFlat = config.find((c: any) => c.key === 'transaction_fee_flat')?.value || '0';
  const [editing, setEditing] = React.useState(false);
  const [tempPercent, setTempPercent] = React.useState(feePercent);
  const [tempFlat, setTempFlat] = React.useState(feeFlat);
  const [activityData, setActivityData] = React.useState<any[]>([]);
  const [realtimeFlow, setRealtimeFlow] = React.useState<any[]>([]);

  React.useEffect(() => {
    fetch('/api/admin/stats/activity').then(r => r.json()).then(setActivityData);
    fetch('/api/admin/stats/realtime').then(r => r.json()).then(setRealtimeFlow);
    
    // Poll for realtime updates
    const interval = setInterval(() => {
      fetch('/api/admin/stats/realtime').then(r => r.json()).then(setRealtimeFlow);
    }, 5000);
    return () => clearInterval(interval);
  }, []);

  const handleSave = async () => {
    await onUpdateConfig('transaction_fee_percent', tempPercent);
    await onUpdateConfig('transaction_fee_flat', tempFlat);
    setEditing(false);
  };

  return (
    <>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <AdminStatCard title="Total GTV" value={formatCurrency(adminStats.total_gtv || 0)} icon={<TrendingUp className="text-indigo-600" />} />
        <AdminStatCard title="Active Merchants" value={adminStats.active_merchants || 0} icon={<Users className="text-blue-600" />} />
        <AdminStatCard title="Success Rate" value={adminStats.success_rate || '100%'} icon={<CheckCircle2 className="text-emerald-600" />} />
        <AdminStatCard title="Pending KYC" value={adminStats.pending_kyc || 0} icon={<AlertCircle className="text-amber-600" />} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <div className="lg:col-span-2 space-y-8">
          <div className="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between mb-8">
              <div>
                <h3 className="font-bold text-slate-900">System Activity</h3>
                <p className="text-sm text-slate-500">Transaction volume across the platform</p>
              </div>
              <div className="flex gap-4">
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full bg-indigo-500"></div>
                  <span className="text-xs font-bold text-slate-500">Volume</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full bg-emerald-500"></div>
                  <span className="text-xs font-bold text-slate-500">Count</span>
                </div>
              </div>
            </div>
            <div className="h-[350px]">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={activityData}>
                  <defs>
                    <linearGradient id="colorVolume" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#4f46e5" stopOpacity={0.1}/>
                      <stop offset="95%" stopColor="#4f46e5" stopOpacity={0}/>
                    </linearGradient>
                    <linearGradient id="colorCount" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#10b981" stopOpacity={0.1}/>
                      <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                  <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fill: '#94a3b8', fontSize: 12}} dy={10} />
                  <YAxis axisLine={false} tickLine={false} tick={{fill: '#94a3b8', fontSize: 12}} />
                  <Tooltip contentStyle={{borderRadius: '16px', border: 'none', boxShadow: '0 20px 25px -5px rgb(0 0 0 / 0.1)'}} />
                  <Area type="monotone" dataKey="volume" stroke="#4f46e5" strokeWidth={3} fillOpacity={1} fill="url(#colorVolume)" />
                  <Area type="monotone" dataKey="count" stroke="#10b981" strokeWidth={3} fillOpacity={1} fill="url(#colorCount)" />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </div>

          <div className="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between mb-8">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-amber-50 rounded-xl text-amber-600">
                  <Zap size={20} className="fill-amber-600" />
                </div>
                <h3 className="font-bold text-slate-900">Real-time Transaction Flow</h3>
              </div>
              <span className="flex items-center gap-2 text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">
                <span className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                Live Feed
              </span>
            </div>
            <div className="space-y-4">
              {realtimeFlow.map((flow) => (
                <div key={flow.id} className="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 group hover:bg-white hover:shadow-md transition-all duration-300">
                  <div className="flex items-center gap-4">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs ${
                      flow.status === 'success' ? 'bg-emerald-100 text-emerald-700' : 
                      flow.status === 'failed' ? 'bg-red-100 text-red-700' : 
                      'bg-indigo-100 text-indigo-700'
                    }`}>
                      {flow.from[0]}
                    </div>
                    <div>
                      <p className="text-sm font-bold text-slate-900">{flow.from}</p>
                      <p className="text-xs text-slate-500">Initiated payment</p>
                    </div>
                  </div>
                  <div className="flex flex-col items-center gap-1">
                    <ArrowRight className="text-slate-300 group-hover:text-indigo-500 transition-colors" size={16} />
                    <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{flow.status}</span>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-bold text-slate-900">{formatCurrency(flow.amount)}</p>
                    <p className="text-xs text-slate-500">to {flow.to}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col h-fit sticky top-28">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-2">
              <div className="p-2 bg-indigo-50 rounded-xl text-indigo-600">
                <Percent size={20} />
              </div>
              <h4 className="font-bold text-slate-900">Global Fees</h4>
            </div>
            {editing ? (
              <div className="flex gap-2">
                <button onClick={handleSave} className="text-xs font-bold text-indigo-600 hover:underline">Save</button>
                <button onClick={() => { setEditing(false); setTempPercent(feePercent); setTempFlat(feeFlat); }} className="text-xs font-bold text-slate-400 hover:underline">Cancel</button>
              </div>
            ) : (
              <button onClick={() => { setEditing(true); setTempPercent(feePercent); setTempFlat(feeFlat); }} className="text-xs font-bold text-indigo-600 hover:underline">Edit Defaults</button>
            )}
          </div>

          <div className="space-y-4 flex-1">
            <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100">
              <p className="text-xs font-bold text-slate-400 uppercase mb-1">Default Percentage</p>
              {editing ? (
                <div className="relative">
                  <input 
                    type="number" 
                    step="0.01"
                    value={tempPercent} 
                    onChange={e => setTempPercent(e.target.value)} 
                    className="w-full bg-white border border-slate-200 rounded-lg px-3 py-1 text-sm font-bold outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                  <span className="absolute right-3 top-1.5 text-xs text-slate-400">%</span>
                </div>
              ) : (
                <p className="text-2xl font-bold text-slate-900">{feePercent}%</p>
              )}
            </div>

            <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100">
              <p className="text-xs font-bold text-slate-400 uppercase mb-1">Default Flat Fee</p>
              {editing ? (
                <div className="relative">
                  <input 
                    type="number" 
                    value={tempFlat} 
                    onChange={e => setTempFlat(e.target.value)} 
                    className="w-full bg-white border border-slate-200 rounded-lg px-3 py-1 text-sm font-bold outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                  <span className="absolute right-3 top-1.5 text-xs text-slate-400">NGN</span>
                </div>
              ) : (
                <p className="text-2xl font-bold text-slate-900">{formatCurrency(parseFloat(feeFlat))}</p>
              )}
            </div>
          </div>

          <div className="mt-6 p-3 bg-amber-50 rounded-xl border border-amber-100 flex items-start gap-2">
            <AlertCircle size={14} className="text-amber-500 mt-0.5 shrink-0" />
            <p className="text-[10px] text-amber-700 leading-tight">
              These fees apply to all merchants who do not have custom fee overrides set in the Merchant Directory.
            </p>
          </div>
        </div>
      </div>
    </>
  );
};

const Merchants = ({ merchants, onVerify, onSuspend, onUpdateFees, onDelete, onImpersonate, onUpdatePayoutReview }: any) => {
  const [editingFees, setEditingFees] = React.useState<number | null>(null);
  const [feePercent, setFeePercent] = React.useState('');
  const [feeFlat, setFeeFlat] = React.useState('');

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 flex items-center justify-between">
        <h3 className="font-bold text-slate-900">Merchant Directory</h3>
        <div className="text-xs text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
          Empty fee fields will revert to Global Platform Fees
        </div>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-left">
          <thead>
            <tr className="bg-slate-50/50">
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Business Name</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">KYC Status</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Account Status</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Payout Review</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Custom Fees</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {merchants.map((m: any, i: number) => (
              <tr key={i} className="hover:bg-slate-50/50 transition-colors">
                <td className="px-6 py-4">
                  <div className="font-bold text-slate-900">{m.business_name}</div>
                  <div className="text-xs text-slate-500">{m.email}</div>
                </td>
                <td className="px-6 py-4">
                  <span className={`px-2 py-1 rounded-full text-xs font-bold ${m.is_kyc_verified ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`}>
                    {m.is_kyc_verified ? 'Verified' : 'Pending'}
                  </span>
                </td>
                <td className="px-6 py-4">
                  <span className={`px-2 py-1 rounded-full text-xs font-bold ${m.is_suspended ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}`}>
                    {m.is_suspended ? 'Suspended' : 'Active'}
                  </span>
                </td>
                <td className="px-6 py-4">
                  <button 
                    onClick={() => onUpdatePayoutReview(m.id, !m.require_payout_review)}
                    className={`px-3 py-1 rounded-xl text-[10px] font-bold transition-all ${
                      m.require_payout_review ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400'
                    }`}
                  >
                    {m.require_payout_review ? 'Review ON' : 'Review OFF'}
                  </button>
                </td>
                <td className="px-6 py-4 text-sm">
                  {editingFees === m.id ? (
                    <div className="flex items-center gap-2">
                      <div className="relative">
                        <input 
                          type="number"
                          step="0.01"
                          value={feePercent} 
                          onChange={e => setFeePercent(e.target.value)} 
                          placeholder="%" 
                          className="w-16 px-2 py-1 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 outline-none" 
                        />
                        <span className="absolute right-2 top-1.5 text-[10px] text-slate-400">%</span>
                      </div>
                      <div className="relative">
                        <input 
                          type="number"
                          value={feeFlat} 
                          onChange={e => setFeeFlat(e.target.value)} 
                          placeholder="Flat" 
                          className="w-20 px-2 py-1 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 outline-none" 
                        />
                        <span className="absolute right-2 top-1.5 text-[10px] text-slate-400">NGN</span>
                      </div>
                      <button 
                        onClick={() => { onUpdateFees(m.id, feePercent, feeFlat); setEditingFees(null); }} 
                        className="p-1 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors"
                        title="Save Fees"
                      >
                        <ShieldCheck size={14} />
                      </button>
                      <button 
                        onClick={() => setEditingFees(null)} 
                        className="p-1 bg-slate-200 text-slate-600 rounded-md hover:bg-slate-300 transition-colors"
                        title="Cancel"
                      >
                        <XCircle size={14} />
                      </button>
                    </div>
                  ) : (
                    <div className="flex items-center gap-2 group">
                      <span className={`font-medium ${m.fee_percentage !== null ? 'text-indigo-600' : 'text-slate-400 italic'}`}>
                        {m.fee_percentage !== null ? `${m.fee_percentage}% + ${m.fee_flat}` : 'Global Default'}
                      </span>
                      <button 
                        onClick={() => { 
                          setEditingFees(m.id); 
                          setFeePercent(m.fee_percentage !== null ? m.fee_percentage.toString() : ''); 
                          setFeeFlat(m.fee_flat !== null ? m.fee_flat.toString() : ''); 
                        }} 
                        className="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-indigo-600 transition-all"
                      >
                        <Percent size={14} />
                      </button>
                    </div>
                  )}
                </td>
                <td className="px-6 py-4">
                  <div className="flex items-center gap-3">
                    <button onClick={() => onImpersonate(m.id)} className="text-indigo-600 hover:text-indigo-800" title="Login as Merchant">
                      <LogIn size={18} />
                    </button>
                    <button onClick={() => onVerify(m.id, !m.is_kyc_verified)} className="text-indigo-600 font-bold text-xs hover:underline">
                      {m.is_kyc_verified ? 'Unverify' : 'Verify'}
                    </button>
                    <button onClick={() => onSuspend(m.id, !m.is_suspended)} className={`${m.is_suspended ? 'text-emerald-600' : 'text-red-600'} font-bold text-xs hover:underline flex items-center gap-1`}>
                      {m.is_suspended ? <ShieldCheck size={14} /> : <Ban size={14} />}
                      {m.is_suspended ? 'Activate' : 'Suspend'}
                    </button>
                    <button onClick={() => { if(confirm('Soft delete this merchant?')) onDelete(m.id) }} className="text-red-400 hover:text-red-600">
                      <Trash2 size={18} />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

const Settlements = () => {
  const [payouts, setPayouts] = React.useState<any[]>([]);
  const [processingId, setProcessingId] = React.useState<number | null>(null);
  const [details, setDetails] = React.useState('');

  const fetchPayouts = () => fetch('/api/admin/payouts').then(r => r.json()).then(setPayouts);
  React.useEffect(() => { fetchPayouts(); }, []);

  const processPayout = async (payoutId: number, status: string) => {
    await fetch('/api/admin/payouts/process', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ payoutId, status, details })
    });
    setProcessingId(null);
    setDetails('');
    fetchPayouts();
  };

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 flex justify-between items-center">
        <h3 className="font-bold text-slate-900">Settlement Requests</h3>
        <span className="text-xs text-slate-500">{payouts.filter(p => p.status === 'pending').length} pending requests</span>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-left">
          <thead>
            <tr className="bg-slate-50/50">
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Merchant</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Bank Details</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {payouts.map((p, i) => (
              <tr key={i} className="hover:bg-slate-50/30 transition-colors">
                <td className="px-6 py-4">
                  <div className="font-bold text-slate-900">{p.business_name}</div>
                  <div className="text-xs text-slate-500">{new Date(p.request_date).toLocaleDateString()}</div>
                </td>
                <td className="px-6 py-4">
                  <div className="text-sm font-bold text-slate-900">{p.bank_name || 'N/A'}</div>
                  <div className="text-xs text-slate-500 font-mono">{p.account_number || 'N/A'}</div>
                </td>
                <td className="px-6 py-4">
                  <div className="text-sm font-bold text-slate-900">{formatCurrency(p.net_amount)}</div>
                  <div className="text-[10px] text-slate-400">Fee: {formatCurrency(p.fee_amount)}</div>
                </td>
                <td className="px-6 py-4">
                  <span className={`px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                    p.status === 'processed' ? 'bg-emerald-100 text-emerald-700' : 
                    p.status === 'declined' ? 'bg-red-100 text-red-700' : 
                    'bg-amber-100 text-amber-700'
                  }`}>
                    {p.status}
                  </span>
                </td>
                <td className="px-6 py-4">
                  {p.status === 'pending' && (
                    <>
                      {processingId === p.id ? (
                        <div className="flex flex-col gap-2 min-w-[200px]">
                          <input 
                            value={details} 
                            onChange={e => setDetails(e.target.value)} 
                            placeholder="Add processing details..." 
                            className="px-2 py-1 border rounded-lg text-[10px] outline-none focus:ring-1 focus:ring-indigo-500" 
                          />
                          <div className="flex gap-1">
                            <button onClick={() => processPayout(p.id, 'processed')} className="bg-emerald-600 text-white px-2 py-1 rounded text-[10px] font-bold">Approve</button>
                            <button onClick={() => processPayout(p.id, 'declined')} className="bg-red-600 text-white px-2 py-1 rounded text-[10px] font-bold">Decline</button>
                            <button onClick={() => setProcessingId(null)} className="bg-slate-200 text-slate-600 px-2 py-1 rounded text-[10px] font-bold">Cancel</button>
                          </div>
                        </div>
                      ) : (
                        <button 
                          onClick={() => setProcessingId(p.id)} 
                          className="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-100"
                        >
                          Process
                        </button>
                      )}
                    </>
                  )}
                </td>
              </tr>
            ))}
            {payouts.length === 0 && <tr><td colSpan={5} className="px-6 py-12 text-center text-slate-500">No payout requests.</td></tr>}
          </tbody>
        </table>
      </div>
    </div>
  );
};

const EmailSettings = ({ config, onUpdateConfig }: any) => {
  const smtpHost = config.find((c: any) => c.key === 'smtp_host')?.value || '';
  const smtpPort = config.find((c: any) => c.key === 'smtp_port')?.value || '';
  const smtpUser = config.find((c: any) => c.key === 'smtp_user')?.value || '';
  const smtpPass = config.find((c: any) => c.key === 'smtp_pass')?.value || '';
  const smtpFrom = config.find((c: any) => c.key === 'smtp_from')?.value || '';

  const [formData, setFormData] = React.useState({
    smtp_host: smtpHost,
    smtp_port: smtpPort,
    smtp_user: smtpUser,
    smtp_pass: smtpPass,
    smtp_from: smtpFrom
  });

  React.useEffect(() => {
    setFormData({
      smtp_host: smtpHost,
      smtp_port: smtpPort,
      smtp_user: smtpUser,
      smtp_pass: smtpPass,
      smtp_from: smtpFrom
    });
  }, [smtpHost, smtpPort, smtpUser, smtpPass, smtpFrom]);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    for (const [key, value] of Object.entries(formData)) {
      await onUpdateConfig(key, value);
    }
    alert('SMTP settings updated!');
  };

  return (
    <div className="bg-white p-8 rounded-3xl border border-slate-200 max-w-2xl shadow-sm">
      <div className="flex items-center gap-3 mb-8">
        <div className="p-3 bg-indigo-50 rounded-2xl text-indigo-600">
          <Bell size={24} />
        </div>
        <div>
          <h2 className="text-2xl font-bold text-slate-900">Email Notification System</h2>
          <p className="text-sm text-slate-500">Configure SMTP settings for system emails.</p>
        </div>
      </div>

      <form onSubmit={handleSave} className="space-y-6">
        <div className="grid grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-bold text-slate-700 mb-2">SMTP Host</label>
            <input 
              value={formData.smtp_host} 
              onChange={e => setFormData({...formData, smtp_host: e.target.value})}
              className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none" 
              placeholder="smtp.mailtrap.io"
            />
          </div>
          <div>
            <label className="block text-sm font-bold text-slate-700 mb-2">SMTP Port</label>
            <input 
              value={formData.smtp_port} 
              onChange={e => setFormData({...formData, smtp_port: e.target.value})}
              className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none" 
              placeholder="587"
            />
          </div>
        </div>

        <div className="grid grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-bold text-slate-700 mb-2">SMTP Username</label>
            <input 
              value={formData.smtp_user} 
              onChange={e => setFormData({...formData, smtp_user: e.target.value})}
              className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none" 
            />
          </div>
          <div>
            <label className="block text-sm font-bold text-slate-700 mb-2">SMTP Password</label>
            <input 
              type="password"
              value={formData.smtp_pass} 
              onChange={e => setFormData({...formData, smtp_pass: e.target.value})}
              className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none" 
            />
          </div>
        </div>

        <div>
          <label className="block text-sm font-bold text-slate-700 mb-2">Sender Email (From)</label>
          <input 
            value={formData.smtp_from} 
            onChange={e => setFormData({...formData, smtp_from: e.target.value})}
            className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 outline-none" 
            placeholder="noreply@payhub.com"
          />
        </div>

        <div className="pt-4">
          <button type="submit" className="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
            Save SMTP Configuration
          </button>
        </div>
      </form>
    </div>
  );
};

const PlatformConfig = ({ config, onUpdateConfig }: any) => {
  const [newKey, setNewKey] = React.useState('');
  const [newValue, setNewValue] = React.useState('');

  const saveConfig = async () => {
    await onUpdateConfig(newKey, newValue);
    setNewKey('');
    setNewValue('');
  };

  const globalPayoutReview = config.find((c: any) => c.key === 'global_payout_review')?.value === '1';

  const configReference = [
    { key: 'paystack_secret_key', label: 'Paystack Secret Key', example: 'sk_test_...', desc: 'API key for payment processing' },
    { key: 'transaction_fee_percent', label: 'Transaction Fee (%)', example: '1.5', desc: 'Percentage fee on collections' },
    { key: 'transaction_fee_flat', label: 'Transaction Fee (Flat)', example: '100', desc: 'Flat fee in NGN on collections' },
    { key: 'payout_fee', label: 'Payout Fee', example: '50', desc: 'Flat fee in NGN per withdrawal' },
    { key: 'global_payout_review', label: 'Global Payout Review', example: '1', desc: '1 = Review all, 0 = Auto-process' },
    { key: 'smtp_host', label: 'SMTP Host', example: 'smtp.mailtrap.io', desc: 'Email server host' },
    { key: 'smtp_port', label: 'SMTP Port', example: '587', desc: 'Email server port' },
    { key: 'smtp_user', label: 'SMTP User', example: 'user@example.com', desc: 'Email server username' },
    { key: 'smtp_pass', label: 'SMTP Pass', example: 'password123', desc: 'Email server password' },
    { key: 'smtp_from', label: 'SMTP From', example: 'noreply@payhub.com', desc: 'Sender email address' },
  ];

  return (
    <div className="grid lg:grid-cols-3 gap-8 max-w-7xl">
      <div className="lg:col-span-2 space-y-8">
        <div className="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between mb-8">
            <h2 className="text-2xl font-bold">Platform Configuration</h2>
            <div className="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
              <div className="flex flex-col">
                <span className="text-xs font-bold text-slate-900">Global Payout Review</span>
                <span className="text-[10px] text-slate-500">Force all payouts to be reviewed</span>
              </div>
              <button 
                onClick={() => onUpdateConfig('global_payout_review', globalPayoutReview ? '0' : '1')}
                className={`w-12 h-6 rounded-full transition-all relative ${globalPayoutReview ? 'bg-indigo-600' : 'bg-slate-300'}`}
              >
                <div className={`absolute top-1 w-4 h-4 bg-white rounded-full transition-all ${globalPayoutReview ? 'left-7' : 'left-1'}`}></div>
              </button>
            </div>
          </div>
          
          <div className="grid md:grid-cols-2 gap-6 mb-12">
            {config.map((c: any, i: number) => (
              <div key={i} className="p-4 bg-slate-50 rounded-2xl border border-slate-100 group relative">
                <p className="text-[10px] font-bold text-slate-400 uppercase mb-1 tracking-wider">{c.key}</p>
                <p className="font-mono text-sm text-slate-900 break-all">{c.value}</p>
                <button 
                  onClick={() => { setNewKey(c.key); setNewValue(c.value); }}
                  className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 text-indigo-600 hover:bg-indigo-50 rounded transition-all"
                  title="Edit this key"
                >
                  <Settings size={14} />
                </button>
              </div>
            ))}
          </div>

          <div className="bg-slate-900 p-8 rounded-2xl text-white">
            <h3 className="font-bold mb-6 flex items-center gap-2">
              <Settings className="text-indigo-400" size={20} />
              Update Configuration
            </h3>
            <div className="grid md:grid-cols-2 gap-6 mb-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Configuration Key</label>
                <input 
                  value={newKey} 
                  onChange={e => setNewKey(e.target.value)} 
                  placeholder="e.g. payout_fee" 
                  className="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono" 
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">New Value</label>
                <input 
                  value={newValue} 
                  onChange={e => setNewValue(e.target.value)} 
                  placeholder="Enter value..." 
                  className="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-sm font-mono" 
                />
              </div>
            </div>
            <button 
              onClick={saveConfig} 
              disabled={!newKey || !newValue}
              className="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-indigo-900/20 hover:bg-indigo-700 transition-all disabled:opacity-50"
            >
              Update Platform Setting
            </button>
          </div>
        </div>
      </div>

      <div className="lg:col-span-1">
        <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm sticky top-8">
          <h3 className="font-bold text-slate-900 mb-6 flex items-center gap-2">
            <ShieldAlert className="text-amber-500" size={20} />
            Configuration Guide
          </h3>
          <div className="space-y-4">
            {configReference.map((item, i) => (
              <div key={i} className="p-4 bg-slate-50 rounded-2xl border border-slate-100 hover:border-indigo-200 transition-colors group">
                <div className="flex justify-between items-start mb-2">
                  <span className="text-xs font-bold text-slate-900">{item.label}</span>
                  <button 
                    onClick={() => { setNewKey(item.key); setNewValue(item.example); }}
                    className="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded hover:bg-indigo-100 transition-colors"
                  >
                    Use Key
                  </button>
                </div>
                <code className="block text-[10px] font-mono text-indigo-600 mb-2 bg-white p-1 rounded border border-slate-100">{item.key}</code>
                <p className="text-[10px] text-slate-500 leading-relaxed">{item.desc}</p>
                <div className="mt-2 pt-2 border-t border-slate-200/50">
                  <span className="text-[9px] font-bold text-slate-400 uppercase">Example: </span>
                  <span className="text-[10px] font-mono text-slate-600">{item.example}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

const BlogManager = ({ user }: any) => {
  const [title, setTitle] = React.useState('');
  const [content, setContent] = React.useState('');
  const [slug, setSlug] = React.useState('');

  const savePost = async () => {
    await fetch('/api/blog', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        title, 
        slug, 
        content, 
        author_id: user.id,
        excerpt: content.substring(0, 150) + '...',
        meta_title: title,
        meta_description: content.substring(0, 150)
      })
    });
    setTitle('');
    setContent('');
    setSlug('');
    alert('Post published!');
  };

  return (
    <div className="bg-white p-8 rounded-3xl border border-slate-200 max-w-4xl">
      <h2 className="text-2xl font-bold mb-8">Create Blog Post</h2>
      <div className="space-y-6">
        <input value={title} onChange={e => { setTitle(e.target.value); setSlug(e.target.value.toLowerCase().replace(/ /g, '-')); }} placeholder="Post Title" className="w-full px-4 py-3 rounded-xl border border-slate-200" />
        <input value={slug} onChange={e => setSlug(e.target.value)} placeholder="URL Slug" className="w-full px-4 py-3 rounded-xl border border-slate-200 font-mono text-sm" />
        <textarea value={content} onChange={e => setContent(e.target.value)} placeholder="Write your post content here (Markdown supported)..." className="w-full px-4 py-3 rounded-xl border border-slate-200 h-64" />
        <button onClick={savePost} className="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold">Publish Post</button>
      </div>
    </div>
  );
};

const Compliance = ({ merchants, onVerify }: any) => {
  const pending = merchants.filter((m: any) => m.is_kyc_verified === 2 || !m.is_kyc_verified);
  const [rejectionNotes, setRejectionNotes] = React.useState<Record<number, string>>({});
  const [selectedMerchantKyc, setSelectedMerchantKyc] = React.useState<any>(null);

  const handleNoteChange = (userId: number, value: string) => {
    setRejectionNotes(prev => ({ ...prev, [userId]: value }));
  };

  const viewKycDetails = async (userId: number) => {
    const res = await fetch(`/api/merchant/kyc/${userId}`);
    const data = await res.json();
    setSelectedMerchantKyc(data);
  };

  return (
    <div className="space-y-8">
      <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-6 border-b border-slate-100 font-bold flex justify-between items-center">
          <span>KYC Review Queue</span>
          <span className="text-xs font-normal text-slate-500">{pending.length} applications pending</span>
        </div>
        <table className="w-full text-left">
          <thead>
            <tr className="bg-slate-50">
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Merchant</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Type</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Rejection Reason</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {pending.map((m: any, i: number) => (
              <tr key={i} className="hover:bg-slate-50/30 transition-colors">
                <td className="px-6 py-4">
                  <div className="font-bold text-slate-900">{m.business_name}</div>
                  <div className="text-xs text-slate-500">{m.email}</div>
                </td>
                <td className="px-6 py-4">
                  <span className={`px-2 py-1 rounded-lg text-[10px] font-bold uppercase ${
                    m.is_kyc_verified === 2 ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600'
                  }`}>
                    {m.is_kyc_verified === 2 ? 'Submitted' : 'New'}
                  </span>
                </td>
                <td className="px-6 py-4">
                  <textarea 
                    value={rejectionNotes[m.id] || ''} 
                    onChange={e => handleNoteChange(m.id, e.target.value)} 
                    placeholder="Reason for rejection..." 
                    className="px-3 py-2 border border-slate-200 rounded-xl text-xs w-full focus:ring-2 focus:ring-red-500 outline-none min-h-[60px]" 
                  />
                </td>
                <td className="px-6 py-4">
                  <div className="flex gap-2">
                    <button 
                      onClick={() => viewKycDetails(m.id)} 
                      className="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors"
                    >
                      Review Docs
                    </button>
                    <button 
                      onClick={() => onVerify(m.id, true)} 
                      className="bg-emerald-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-emerald-700 transition-colors"
                    >
                      Approve
                    </button>
                    <button 
                      onClick={() => {
                        if (!rejectionNotes[m.id]) {
                          alert('Please provide a rejection reason.');
                          return;
                        }
                        onVerify(m.id, false, rejectionNotes[m.id]);
                      }} 
                      className="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-xl text-xs font-bold hover:bg-red-50 transition-colors"
                    >
                      Reject
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {pending.length === 0 && <tr><td colSpan={4} className="px-6 py-12 text-center text-slate-500">No pending reviews.</td></tr>}
          </tbody>
        </table>
      </div>

      {selectedMerchantKyc && (
        <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-3xl border border-slate-200 w-full max-w-2xl overflow-hidden shadow-2xl">
            <div className="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
              <h3 className="font-bold text-slate-900">KYC Document Review</h3>
              <button onClick={() => setSelectedMerchantKyc(null)} className="p-2 hover:bg-slate-200 rounded-full transition-colors">
                <ShieldAlert size={20} className="rotate-45" />
              </button>
            </div>
            <div className="p-8 max-h-[70vh] overflow-y-auto">
              <div className="grid md:grid-cols-2 gap-8">
                <div>
                  <h4 className="text-xs font-bold text-slate-400 uppercase mb-4 tracking-widest">Business Information</h4>
                  <div className="space-y-4">
                    <InfoItem label="Business Type" value={selectedMerchantKyc.business_type} />
                    {selectedMerchantKyc.bvn && <InfoItem label="BVN" value={selectedMerchantKyc.bvn} />}
                    {selectedMerchantKyc.rc_number && <InfoItem label="RC Number" value={selectedMerchantKyc.rc_number} />}
                    {selectedMerchantKyc.bn_number && <InfoItem label="BN Number" value={selectedMerchantKyc.bn_number} />}
                    {selectedMerchantKyc.tin && <InfoItem label="TIN" value={selectedMerchantKyc.tin} />}
                    {selectedMerchantKyc.residential_address && <InfoItem label="Residential Address" value={selectedMerchantKyc.residential_address} />}
                  </div>
                </div>
                <div>
                  <h4 className="text-xs font-bold text-slate-400 uppercase mb-4 tracking-widest">Uploaded Documents</h4>
                  <div className="space-y-3">
                    {Object.entries(selectedMerchantKyc).map(([key, value]) => {
                      if (key.endsWith('_path') && value) {
                        return (
                          <a 
                            key={key}
                            href={value as string} 
                            target="_blank" 
                            rel="noreferrer"
                            className="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-indigo-200 hover:bg-indigo-50 transition-all group"
                          >
                            <div className="p-2 bg-white rounded-lg text-indigo-600 group-hover:text-indigo-700">
                              <FileText size={16} />
                            </div>
                            <span className="text-xs font-bold text-slate-700 capitalize">{key.replace('_path', '').replace(/_/g, ' ')}</span>
                          </a>
                        );
                      }
                      return null;
                    })}
                  </div>
                </div>
              </div>
            </div>
            <div className="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
              <button onClick={() => setSelectedMerchantKyc(null)} className="px-6 py-2 bg-white border border-slate-200 rounded-xl font-bold text-slate-700 hover:bg-slate-50 transition-colors">Close</button>
              <button 
                onClick={() => { onVerify(selectedMerchantKyc.user_id, true); setSelectedMerchantKyc(null); }} 
                className="px-6 py-2 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition-colors"
              >
                Approve Now
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const InfoItem = ({ label, value }: any) => (
  <div>
    <p className="text-[10px] font-bold text-slate-400 uppercase">{label}</p>
    <p className="text-sm font-bold text-slate-900 capitalize">{value}</p>
  </div>
);

const DisputeManagement = () => {
  const [disputes, setDisputes] = React.useState<any[]>([]);
  const [uploadingFor, setUploadingFor] = React.useState<number | null>(null);
  const [evidenceUrl, setEvidenceUrl] = React.useState('');

  const fetchDisputes = () => fetch('/api/admin/disputes').then(r => r.json()).then(setDisputes);
  React.useEffect(() => { fetchDisputes(); }, []);

  const adjudicate = async (disputeId: number, outcome: string) => {
    await fetch('/api/admin/disputes/adjudicate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ disputeId, outcome })
    });
    fetchDisputes();
  };

  const handleEvidenceSubmit = async (disputeId: number) => {
    if (!evidenceUrl) return;
    await fetch('/api/admin/disputes/evidence', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ disputeId, evidence: evidenceUrl })
    });
    setUploadingFor(null);
    setEvidenceUrl('');
    fetchDisputes();
  };

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 font-bold flex justify-between items-center">
        <span>Dispute Management</span>
        <div className="flex items-center gap-2 text-xs font-normal text-slate-500">
          <AlertCircle size={14} className="text-amber-500" />
          Requires immediate attention
        </div>
      </div>
      <table className="w-full text-left">
        <thead>
          <tr className="bg-slate-50">
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Merchant</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Transaction</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Reason</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Evidence</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {disputes.map((d, i) => (
            <tr key={i} className="hover:bg-slate-50/30 transition-colors">
              <td className="px-6 py-4 text-sm font-bold text-slate-900">{d.business_name}</td>
              <td className="px-6 py-4">
                <div className="text-xs font-mono text-slate-600">{d.transaction_ref}</div>
                <div className="text-xs font-bold text-slate-900">{formatCurrency(d.amount)}</div>
              </td>
              <td className="px-6 py-4 text-sm text-slate-600">{d.reason}</td>
              <td className="px-6 py-4">
                {uploadingFor === d.id ? (
                  <div className="flex flex-col gap-2 min-w-[180px]">
                    <input 
                      autoFocus
                      value={evidenceUrl} 
                      onChange={e => setEvidenceUrl(e.target.value)} 
                      placeholder="Evidence URL or text..." 
                      className="px-2 py-1 border rounded-lg text-[10px] outline-none focus:ring-1 focus:ring-indigo-500" 
                    />
                    <div className="flex gap-1">
                      <button onClick={() => handleEvidenceSubmit(d.id)} className="bg-indigo-600 text-white px-2 py-0.5 rounded text-[10px] font-bold">Save</button>
                      <button onClick={() => setUploadingFor(null)} className="bg-slate-200 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold">Cancel</button>
                    </div>
                  </div>
                ) : (
                  <div className="flex flex-col gap-1">
                    {d.evidence_path ? (
                      <a href={d.evidence_path} target="_blank" rel="noreferrer" className="text-indigo-600 hover:underline text-xs flex items-center gap-1">
                        <FileText size={12} /> View Evidence
                      </a>
                    ) : (
                      <span className="text-xs text-slate-400 italic">No evidence provided</span>
                    )}
                    <button 
                      onClick={() => { setUploadingFor(d.id); setEvidenceUrl(d.evidence_path || ''); }} 
                      className="text-[10px] text-indigo-600 font-bold hover:underline flex items-center gap-1"
                    >
                      <Upload size={10} /> {d.evidence_path ? 'Update Evidence' : 'Add Evidence'}
                    </button>
                  </div>
                )}
              </td>
              <td className="px-6 py-4">
                <span className={`px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                  d.status === 'won' ? 'bg-emerald-100 text-emerald-700' : 
                  d.status === 'lost' ? 'bg-red-100 text-red-700' : 
                  'bg-amber-100 text-amber-700'
                }`}>
                  {d.status}
                </span>
              </td>
              <td className="px-6 py-4">
                <div className="flex gap-2">
                  {d.status === 'open' && (
                    <>
                      <button 
                        onClick={() => adjudicate(d.id, 'won')} 
                        className="bg-emerald-50 text-emerald-600 border border-emerald-100 px-3 py-1 rounded-lg text-[10px] font-bold hover:bg-emerald-100 transition-colors"
                      >
                        Mark Won
                      </button>
                      <button 
                        onClick={() => adjudicate(d.id, 'lost')} 
                        className="bg-red-50 text-red-600 border border-red-100 px-3 py-1 rounded-lg text-[10px] font-bold hover:bg-red-100 transition-colors"
                      >
                        Mark Lost
                      </button>
                    </>
                  )}
                </div>
              </td>
            </tr>
          ))}
          {disputes.length === 0 && <tr><td colSpan={6} className="px-6 py-12 text-center text-slate-500">No active disputes found.</td></tr>}
        </tbody>
      </table>
    </div>
  );
};

const VirtualAccounts = () => {
  const [accounts, setAccounts] = React.useState<any[]>([]);
  React.useEffect(() => { fetch('/api/admin/virtual-accounts').then(r => r.json()).then(setAccounts); }, []);

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 font-bold">Platform Virtual Accounts</div>
      <table className="w-full text-left">
        <thead>
          <tr className="bg-slate-50">
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Merchant</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Bank</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Account Number</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Account Name</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {accounts.map((a, i) => (
            <tr key={i}>
              <td className="px-6 py-4 text-sm font-bold">{a.business_name}</td>
              <td className="px-6 py-4 text-sm">{a.bank_name}</td>
              <td className="px-6 py-4 text-sm font-mono">{a.account_number}</td>
              <td className="px-6 py-4 text-sm">{a.account_name}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

const WebhookLogs = () => {
  const [logs, setLogs] = React.useState<any[]>([]);
  const fetchLogs = () => fetch('/api/admin/webhook-logs').then(r => r.json()).then(setLogs);
  React.useEffect(() => { fetchLogs(); }, []);

  const retry = async (logId: number) => {
    await fetch('/api/admin/webhooks/retry', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ logId })
    });
    fetchLogs();
    alert('Retry triggered!');
  };

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 font-bold">Webhook Delivery Logs</div>
      <table className="w-full text-left">
        <thead>
          <tr className="bg-slate-50">
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Transaction</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Attempts</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Last Attempt</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {logs.map((l, i) => (
            <tr key={i}>
              <td className="px-6 py-4 text-xs font-mono">{l.transaction_ref}</td>
              <td className="px-6 py-4">
                <span className={`px-2 py-1 rounded-full text-xs font-bold ${l.status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`}>
                  {l.status}
                </span>
              </td>
              <td className="px-6 py-4 text-sm">{l.attempt_count}</td>
              <td className="px-6 py-4 text-sm text-slate-500">{new Date(l.last_attempt_at).toLocaleString()}</td>
              <td className="px-6 py-4">
                <button onClick={() => retry(l.id)} className="text-indigo-600 hover:text-indigo-800" title="Retry Webhook">
                  <Webhook size={18} />
                </button>
              </td>
            </tr>
          ))}
          {logs.length === 0 && <tr><td colSpan={5} className="px-6 py-12 text-center text-slate-500">No webhook logs found.</td></tr>}
        </tbody>
      </table>
    </div>
  );
};

const StaffManagement = () => {
  const [roles, setRoles] = React.useState<any[]>([]);
  const [staff, setStaff] = React.useState<any[]>([]);
  const [showAddStaff, setShowAddStaff] = React.useState(false);
  const [newStaff, setNewStaff] = React.useState({ email: '', full_name: '', password: 'password123', roleId: '' });

  const fetchData = async () => {
    const [rRes, sRes] = await Promise.all([
      fetch('/api/admin/staff/roles'),
      fetch('/api/admin/staff/users')
    ]);
    setRoles(await rRes.json());
    setStaff(await sRes.json());
  };

  React.useEffect(() => { fetchData(); }, []);

  const addStaff = async () => {
    await fetch('/api/admin/staff/users', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newStaff)
    });
    setShowAddStaff(false);
    fetchData();
  };

  return (
    <div className="space-y-8">
      <div className="bg-white p-6 rounded-3xl border border-slate-200">
        <div className="flex justify-between items-center mb-6">
          <h3 className="font-bold">Staff Members</h3>
          <button onClick={() => setShowAddStaff(true)} className="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2">
            <UserPlus size={18} /> Add Staff
          </button>
        </div>
        <table className="w-full text-left">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Name</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Email</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Role</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {staff.map((s, i) => (
              <tr key={i}>
                <td className="px-6 py-4 text-sm font-bold">{s.full_name}</td>
                <td className="px-6 py-4 text-sm">{s.email}</td>
                <td className="px-6 py-4"><span className="px-2 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold">{s.role_name}</span></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {showAddStaff && (
        <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50">
          <div className="bg-white p-8 rounded-3xl border border-slate-200 w-full max-w-md">
            <h3 className="text-xl font-bold mb-6">Add New Staff Member</h3>
            <div className="space-y-4">
              <input placeholder="Full Name" value={newStaff.full_name} onChange={e => setNewStaff({...newStaff, full_name: e.target.value})} className="w-full px-4 py-2 border rounded-xl" />
              <input placeholder="Email" value={newStaff.email} onChange={e => setNewStaff({...newStaff, email: e.target.value})} className="w-full px-4 py-2 border rounded-xl" />
              <select value={newStaff.roleId} onChange={e => setNewStaff({...newStaff, roleId: e.target.value})} className="w-full px-4 py-2 border rounded-xl">
                <option value="">Select Role</option>
                {roles.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
              </select>
              <div className="flex gap-4 mt-6">
                <button onClick={() => setShowAddStaff(false)} className="flex-1 px-4 py-2 border rounded-xl font-bold">Cancel</button>
                <button onClick={addStaff} className="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold">Create Member</button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const SystemHealth = () => {
  const [stats, setStats] = React.useState<any>(null);
  React.useEffect(() => { fetch('/api/admin/health/stats').then(r => r.json()).then(setStats); }, []);

  return (
    <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div className="bg-white p-6 rounded-3xl border border-slate-200">
        <h4 className="text-slate-500 text-sm mb-2">API Latency</h4>
        <p className="text-3xl font-bold text-emerald-600">{stats?.api_latency || '...'}</p>
      </div>
      <div className="bg-white p-6 rounded-3xl border border-slate-200">
        <h4 className="text-slate-500 text-sm mb-2">Uptime</h4>
        <p className="text-3xl font-bold text-slate-900">{Math.floor(stats?.uptime / 3600) || 0}h {Math.floor((stats?.uptime % 3600) / 60) || 0}m</p>
      </div>
      <div className="bg-white p-6 rounded-3xl border border-slate-200">
        <h4 className="text-slate-500 text-sm mb-2">Active Connections</h4>
        <p className="text-3xl font-bold text-indigo-600">{stats?.active_connections || '...'}</p>
      </div>
    </div>
  );
};

const Tickets = () => {
  const [tickets, setTickets] = React.useState<any[]>([]);
  React.useEffect(() => { fetch('/api/admin/tickets').then(r => r.json()).then(setTickets); }, []);

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 font-bold">Support Tickets</div>
      <table className="w-full text-left">
        <thead>
          <tr className="bg-slate-50">
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">User</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Subject</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Priority</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {tickets.map((t, i) => (
            <tr key={i}>
              <td className="px-6 py-4 text-sm">{t.user_email}</td>
              <td className="px-6 py-4 text-sm font-bold">{t.subject}</td>
              <td className="px-6 py-4"><span className="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">{t.priority}</span></td>
              <td className="px-6 py-4 text-sm">{t.status}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

const ApiManager = () => {
  const [config, setConfig] = React.useState<any[]>([]);
  React.useEffect(() => { fetch('/api/admin/config').then(r => r.json()).then(setConfig); }, []);
  const paystackKey = config.find(c => c.key === 'paystack_secret_key')?.value || '';

  return (
    <div className="bg-white p-8 rounded-3xl border border-slate-200 max-w-2xl">
      <h2 className="text-2xl font-bold mb-8">API Manager</h2>
      <div className="space-y-6">
        <div>
          <label className="block text-sm font-bold mb-2">Paystack Secret Key</label>
          <input readOnly type="password" value={paystackKey} className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono" />
        </div>
        <div className="p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
          <p className="text-sm text-indigo-700">This key is used to process all live transactions on the platform.</p>
        </div>
        <button className="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold">Update Integration</button>
      </div>
    </div>
  );
};

export default function AdminDashboard() {
  const [user, setUser] = React.useState<any>(null);
  const [merchants, setMerchants] = React.useState<any[]>([]);
  const [config, setConfig] = React.useState<any[]>([]);
  const [adminStats, setAdminStats] = React.useState<any>({});
  const [loading, setLoading] = React.useState(true);
  const location = useLocation();
  const navigate = useNavigate();

  React.useEffect(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      const parsedUser = JSON.parse(storedUser);
      if (parsedUser.role !== 'admin') {
        navigate('/dashboard');
      } else {
        setUser(parsedUser);
        fetchMerchants();
        fetchConfig();
        fetchAdminStats();
      }
    } else {
      navigate('/login');
    }
  }, []);

  const fetchAdminStats = async () => {
    try {
      const res = await fetch('/api/admin/stats');
      const data = await res.json();
      setAdminStats(data);
    } catch (e) {
      console.error(e);
    }
  };

  const fetchMerchants = async () => {
    try {
      const res = await fetch('/api/admin/merchants');
      const data = await res.json();
      setMerchants(data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const fetchConfig = async () => {
    try {
      const res = await fetch('/api/admin/config');
      const data = await res.json();
      setConfig(data);
    } catch (e) {
      console.error(e);
    }
  };

  const handleUpdateConfig = async (key: string, value: string) => {
    await fetch('/api/admin/config', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ key, value })
    });
    fetchConfig();
  };

  const handleVerify = async (userId: number, status: boolean, notes?: string) => {
    await fetch('/api/admin/merchants/verify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId, status, notes })
    });
    fetchMerchants();
  };

  const handleSuspend = async (userId: number, status: boolean) => {
    await fetch('/api/admin/merchants/suspend', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId, status })
    });
    fetchMerchants();
  };

  const handleUpdateFees = async (userId: number, fee_percentage: string, fee_flat: string) => {
    await fetch('/api/admin/merchants/fees', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        userId, 
        fee_percentage: fee_percentage === '' ? null : parseFloat(fee_percentage), 
        fee_flat: fee_flat === '' ? null : parseFloat(fee_flat) 
      })
    });
    fetchMerchants();
  };

  const handleUpdatePayoutReview = async (userId: number, status: boolean) => {
    await fetch('/api/admin/merchants/payout-review', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId, status })
    });
    fetchMerchants();
  };

  const handleDelete = async (userId: number) => {
    await fetch('/api/admin/merchants/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId })
    });
    fetchMerchants();
  };

  const handleImpersonate = async (userId: number) => {
    const res = await fetch('/api/admin/merchants/impersonate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId })
    });
    const merchantUser = await res.json();
    localStorage.setItem('user', JSON.stringify(merchantUser));
    navigate('/dashboard');
  };

  const isActive = (path: string) => location.pathname === path || (path === '/admin' && location.pathname === '/admin/');

  return (
    <div className="min-h-screen bg-slate-50 flex">
      <aside className="w-64 bg-slate-900 text-slate-400 hidden lg:flex flex-col fixed h-full">
        <div className="p-6 border-b border-slate-800 flex items-center gap-3">
          <div className="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
            <ShieldAlert className="text-white w-5 h-5" />
          </div>
          <span className="text-xl font-bold text-white">Admin Hub</span>
        </div>
        <nav className="flex-1 p-4 space-y-6 overflow-y-auto">
          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Operations</div>
            <div className="space-y-1">
              <AdminSidebarLink to="/admin" icon={<BarChart3 size={20} />} label="Overview" active={isActive('/admin')} />
              <AdminSidebarLink to="/admin/merchants" icon={<Users size={20} />} label="Merchants" active={isActive('/admin/merchants')} />
              <AdminSidebarLink to="/admin/compliance" icon={<ShieldCheck size={20} />} label="Compliance" active={isActive('/admin/compliance')} />
              <AdminSidebarLink to="/admin/settlements" icon={<CreditCard size={20} />} label="Settlements" active={isActive('/admin/settlements')} />
              <AdminSidebarLink to="/admin/disputes" icon={<Gavel size={20} />} label="Disputes" active={isActive('/admin/disputes')} />
              <AdminSidebarLink to="/admin/virtual-accounts" icon={<Wallet size={20} />} label="Virtual Accounts" active={isActive('/admin/virtual-accounts')} />
            </div>
          </div>

          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">System</div>
            <div className="space-y-1">
              <AdminSidebarLink to="/admin/health" icon={<Activity size={20} />} label="System Health" active={isActive('/admin/health')} />
              <AdminSidebarLink to="/admin/webhooks" icon={<Webhook size={20} />} label="Webhook Logs" active={isActive('/admin/webhooks')} />
              <AdminSidebarLink to="/admin/api-manager" icon={<Key size={20} />} label="API Manager" active={isActive('/admin/api-manager')} />
            </div>
          </div>

          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Content & Support</div>
            <div className="space-y-1">
              <AdminSidebarLink to="/admin/tickets" icon={<Ticket size={20} />} label="Tickets" active={isActive('/admin/tickets')} />
              <AdminSidebarLink to="/admin/blog" icon={<FileText size={20} />} label="Blog Manager" active={isActive('/admin/blog')} />
            </div>
          </div>

          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Configuration</div>
            <div className="space-y-1">
              <AdminSidebarLink to="/admin/staff" icon={<Users size={20} />} label="Staff Management" active={isActive('/admin/staff')} />
              <AdminSidebarLink to="/admin/email-settings" icon={<Bell size={20} />} label="Email Settings" active={isActive('/admin/email-settings')} />
              <AdminSidebarLink to="/admin/config" icon={<Settings size={20} />} label="Platform Config" active={isActive('/admin/config')} />
            </div>
          </div>
        </nav>
        <div className="p-4 border-t border-slate-800">
          <button onClick={() => { localStorage.removeItem('user'); navigate('/login'); }} className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-400 hover:bg-red-900/20 transition-all">
            <LogOut size={20} /> Logout
          </button>
        </div>
      </aside>

      <main className="flex-1 lg:ml-64">
        <header className="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 sticky top-0 z-10">
          <h2 className="text-xl font-bold text-slate-900">Platform Overview</h2>
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold">
              <span className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
              API Status: Healthy
            </div>
            <button className="p-2 text-slate-500 hover:bg-slate-50 rounded-lg relative"><Bell size={20} /></button>
          </div>
        </header>

        <div className="p-8">
          <Routes>
            <Route index element={<Overview merchants={merchants} config={config} onUpdateConfig={handleUpdateConfig} adminStats={adminStats} />} />
            <Route path="merchants" element={<Merchants merchants={merchants} onVerify={handleVerify} onSuspend={handleSuspend} onUpdateFees={handleUpdateFees} onDelete={handleDelete} onImpersonate={handleImpersonate} onUpdatePayoutReview={handleUpdatePayoutReview} />} />
            <Route path="compliance" element={<Compliance merchants={merchants} onVerify={handleVerify} />} />
            <Route path="settlements" element={<Settlements />} />
            <Route path="disputes" element={<DisputeManagement />} />
            <Route path="virtual-accounts" element={<VirtualAccounts />} />
            <Route path="webhooks" element={<WebhookLogs />} />
            <Route path="staff" element={<StaffManagement />} />
            <Route path="health" element={<SystemHealth />} />
            <Route path="tickets" element={<Tickets />} />
            <Route path="blog" element={<BlogManager user={user} />} />
            <Route path="email-settings" element={<EmailSettings config={config} onUpdateConfig={handleUpdateConfig} />} />
            <Route path="config" element={<PlatformConfig config={config} onUpdateConfig={handleUpdateConfig} />} />
            <Route path="api-manager" element={<ApiManager />} />
            <Route path="*" element={<div className="bg-white p-12 rounded-3xl border border-slate-200 text-center text-slate-500">Feature coming soon...</div>} />
          </Routes>
        </div>
      </main>
    </div>
  );
}

function AdminSidebarLink({ to, icon, label, active }: any) {
  return (
    <Link to={to} className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
      active ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
    }`}>
      {icon}
      {label}
    </Link>
  );
}

function AdminStatCard({ title, value, icon, trend }: any) {
  return (
    <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
      <div className="flex items-center justify-between mb-4">
        <div className="p-2 bg-slate-50 rounded-xl">{icon}</div>
        {trend && <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">{trend}</span>}
      </div>
      <p className="text-sm font-medium text-slate-500 mb-1">{title}</p>
      <h4 className="text-2xl font-bold text-slate-900 tracking-tight">{value}</h4>
    </div>
  );
}
