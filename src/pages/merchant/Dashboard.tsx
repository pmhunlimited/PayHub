import React from 'react';
import { 
  Routes, 
  Route, 
  Link, 
  useLocation,
  useNavigate
} from 'react-router-dom';
import { 
  LayoutDashboard, 
  ArrowUpRight, 
  ArrowDownLeft, 
  CreditCard, 
  Users, 
  Settings, 
  HelpCircle,
  Bell,
  Search,
  Plus,
  MoreVertical,
  Filter,
  Download,
  Wallet,
  FileText,
  RefreshCcw,
  ShieldCheck,
  Code,
  BarChart3,
  LogOut,
  ShieldAlert,
  Ticket,
  Ban,
  Activity
} from 'lucide-react';
import { 
  AreaChart, 
  Area, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer
} from 'recharts';
import { formatCurrency } from '../../lib/utils';

// Sub-components for Merchant Dashboard
const Overview = ({ stats, transactions, user, revenueData }: any) => {
  const formatCurrency = (val: number) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);
  
  const onboardingSteps = [
    { id: 1, label: 'Verify Email', status: 'completed', desc: 'Confirm your email address' },
    { id: 2, label: 'Submit KYC', status: user.is_kyc_verified ? 'completed' : 'pending', desc: 'Upload business documents' },
    { id: 3, label: 'Settlement Bank', status: user.settlement_bank ? 'completed' : 'pending', desc: 'Set where you receive funds' },
    { id: 4, label: 'First Payment', status: stats.transaction_count > 0 ? 'completed' : 'pending', desc: 'Receive your first transaction' },
  ];

  const isFullyOnboarded = onboardingSteps.every(s => s.status === 'completed');

  return (
    <>
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 mb-2">Welcome back, {user.business_name}</h1>
          <p className="text-slate-500">Here's what's happening with your business today.</p>
        </div>
        <div className="flex items-center gap-3">
          <Link to="/dashboard/payouts" className="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <ArrowDownLeft size={18} className="text-amber-500" />
            New Payout
          </Link>
          <Link to="/dashboard/invoices" className="flex items-center gap-2 bg-indigo-600 px-4 py-2.5 rounded-xl text-sm font-bold text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
            <Plus size={18} />
            Create Invoice
          </Link>
        </div>
      </div>

      {!isFullyOnboarded && (
        <div className="mb-8 bg-indigo-900 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl">
          <div className="relative z-10">
            <div className="flex items-center gap-3 mb-6">
              <div className="p-2 bg-white/10 rounded-lg">
                <ShieldCheck size={24} className="text-indigo-300" />
              </div>
              <h3 className="text-xl font-bold">Onboarding Checklist</h3>
            </div>
            <div className="grid md:grid-cols-4 gap-6">
              {onboardingSteps.map((step) => (
                <div key={step.id} className="relative">
                  <div className="flex items-center gap-3 mb-2">
                    <div className={`w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold border ${
                      step.status === 'completed' ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-white/10 border-white/20 text-white/60'
                    }`}>
                      {step.status === 'completed' ? '✓' : step.id}
                    </div>
                    <span className={`text-sm font-bold ${step.status === 'completed' ? 'text-white' : 'text-white/60'}`}>{step.label}</span>
                  </div>
                  <p className="text-xs text-white/40">{step.desc}</p>
                </div>
              ))}
            </div>
          </div>
          <div className="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
          <div className="absolute bottom-0 left-0 w-48 h-48 bg-indigo-500/10 rounded-full -ml-24 -mb-24 blur-3xl"></div>
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatCard title="Available Balance" value={formatCurrency(stats.balance || 0)} icon={<Wallet className="text-indigo-600" />} />
        <StatCard title="Total Revenue" value={formatCurrency(stats.total_volume || 0)} icon={<BarChart3 className="text-emerald-600" />} />
        <StatCard title="Total Transactions" value={stats.transaction_count || 0} icon={<ArrowUpRight className="text-blue-600" />} />
        <StatCard title="Success Rate" value={stats.success_rate || '100%'} icon={<ShieldCheck className="text-purple-600" />} />
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        <div className="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
          <h3 className="font-bold text-slate-900 mb-6">Revenue Overview</h3>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={revenueData}>
                <defs>
                  <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#4f46e5" stopOpacity={0.1}/>
                    <stop offset="95%" stopColor="#4f46e5" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fill: '#94a3b8', fontSize: 12}} dy={10} />
                <YAxis axisLine={false} tickLine={false} tick={{fill: '#94a3b8', fontSize: 12}} />
                <Tooltip contentStyle={{borderRadius: '12px', border: 'none', boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)'}} />
                <Area type="monotone" dataKey="revenue" stroke="#4f46e5" strokeWidth={3} fillOpacity={1} fill="url(#colorRevenue)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>
        <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
          <h3 className="font-bold text-slate-900 mb-6">Payment Methods</h3>
          <div className="space-y-6">
            <PaymentMethodProgress label="Card" percentage={65} color="bg-indigo-600" />
            <PaymentMethodProgress label="Bank Transfer" percentage={25} color="bg-emerald-500" />
            <PaymentMethodProgress label="USSD" percentage={10} color="bg-amber-500" />
          </div>
        </div>
      </div>

      <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 className="font-bold text-slate-900">Recent Transactions</h3>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead>
              <tr className="bg-slate-50/50">
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Reference</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {transactions.map((tx: any, i: number) => (
                <tr key={i} className="hover:bg-slate-50/50 transition-colors">
                  <td className="px-6 py-4 font-mono text-sm">{tx.reference}</td>
                  <td className="px-6 py-4 text-sm">{tx.customer_email}</td>
                  <td className="px-6 py-4 text-sm font-bold">{formatCurrency(tx.amount)}</td>
                  <td className="px-6 py-4">
                    <span className={`px-2 py-1 rounded-full text-xs font-bold ${tx.status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`}>
                      {tx.status}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
};

const VirtualAccounts = ({ userId, user }: any) => {
  const [accounts, setAccounts] = React.useState<any[]>([]);
  const fetchAccounts = () => fetch(`/api/merchant/virtual-accounts/${userId}`).then(r => r.json()).then(setAccounts);
  React.useEffect(() => { 
    if (user.business_type !== 'Starter') {
      fetchAccounts(); 
    }
  }, [userId, user.business_type]);

  if (user.business_type === 'Starter') {
    return (
      <div className="bg-white p-12 rounded-3xl border border-slate-200 text-center max-w-2xl mx-auto shadow-sm">
        <div className="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6">
          <Wallet className="text-indigo-600 w-10 h-10" />
        </div>
        <h2 className="text-2xl font-bold text-slate-900 mb-4">Virtual Accounts Restricted</h2>
        <p className="text-slate-500 mb-8 leading-relaxed">
          Starter businesses do not have access to Virtual Bank Accounts. 
          To enable this feature, please upgrade your account by completing the 
          <strong> Registered Business</strong> or <strong>Business Name</strong> compliance process.
        </p>
        <Link to="/dashboard/kyc" className="inline-flex items-center gap-2 bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
          Complete Compliance <ArrowUpRight size={18} />
        </Link>
      </div>
    );
  }

  const createAccount = async () => {
    await fetch('/api/merchant/virtual-accounts', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId })
    });
    fetchAccounts();
  };

  return (
    <div className="bg-white p-8 rounded-3xl border border-slate-200">
      <div className="flex justify-between items-center mb-8">
        <h2 className="text-2xl font-bold">Virtual Accounts</h2>
        <button onClick={createAccount} className="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold flex items-center gap-2">
          <Plus size={18} /> Generate Account
        </button>
      </div>
      <div className="grid md:grid-cols-2 gap-6">
        {accounts.map((acc, i) => (
          <div key={i} className="p-6 bg-slate-50 rounded-2xl border border-slate-100">
            <p className="text-xs font-bold text-slate-400 uppercase mb-2">{acc.bank_name}</p>
            <p className="text-2xl font-mono font-bold text-slate-900 mb-1">{acc.account_number}</p>
            <p className="text-sm text-slate-600">{acc.account_name}</p>
          </div>
        ))}
        {accounts.length === 0 && <p className="text-slate-500 col-span-2 text-center py-12">No virtual accounts yet.</p>}
      </div>
    </div>
  );
};

const Compliance = ({ user }: any) => {
  const [businessType, setBusinessType] = React.useState('Starter');
  const [kycData, setKycData] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);
  const [submitting, setSubmitting] = React.useState(false);
  const [formData, setFormData] = React.useState<any>({});
  const [uploading, setUploading] = React.useState<string | null>(null);

  React.useEffect(() => {
    fetch(`/api/merchant/kyc/${user.id}`)
      .then(r => r.json())
      .then(data => {
        setKycData(data);
        if (data) setBusinessType(data.business_type);
        setLoading(false);
      });
  }, [user.id]);

  const handleUpload = async (type: string) => {
    setUploading(type);
    try {
      const res = await fetch('/api/merchant/kyc/upload', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: user.id, type })
      });
      const data = await res.json();
      setFormData({ ...formData, [`${type}_path`]: data.path });
    } finally {
      setUploading(null);
    }
  };

  const handleSubmit = async () => {
    setSubmitting(true);
    try {
      const res = await fetch('/api/merchant/kyc', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: user.id, business_type: businessType, ...formData })
      });
      if (res.ok) {
        alert('KYC submitted successfully!');
        window.location.reload();
      }
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) return <div className="p-8 text-center text-slate-500">Loading compliance data...</div>;

  const status = kycData?.status || 'unsubmitted';
  const isVerified = user.is_kyc_verified === 1;
  const isPending = user.is_kyc_verified === 2 || status === 'pending';

  return (
    <div className="max-w-4xl">
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-slate-900 mb-2">Compliance & Onboarding</h1>
        <p className="text-slate-500">Complete your KYC to increase your collection limits and go live.</p>
      </div>

      <div className="grid lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 space-y-6">
          {/* Status Banner */}
          <div className={`p-6 rounded-3xl border flex items-start gap-4 ${
            isVerified ? 'bg-emerald-50 border-emerald-100 text-emerald-800' :
            isPending ? 'bg-amber-50 border-amber-100 text-amber-800' :
            'bg-slate-50 border-slate-200 text-slate-800'
          }`}>
            <div className={`p-3 rounded-2xl ${
              isVerified ? 'bg-emerald-100' :
              isPending ? 'bg-amber-100' :
              'bg-slate-200'
            }`}>
              {isVerified ? <ShieldCheck size={24} /> : <ShieldAlert size={24} />}
            </div>
            <div>
              <h3 className="font-bold text-lg">
                {isVerified ? 'Account Verified' : isPending ? 'Verification in Progress' : 'Action Required'}
              </h3>
              <p className="text-sm opacity-90 mt-1">
                {isVerified ? 'Your business is fully verified. You have unlimited access to all features.' :
                 isPending ? 'Our compliance team is currently reviewing your documents. This usually takes 24-48 hours.' :
                 'Please select your business type and provide the required documents to start accepting payments.'}
              </p>
              {user.kyc_notes && (
                <div className="mt-4 p-3 bg-white/50 rounded-xl border border-current/10 text-xs italic">
                  <strong>Admin Note:</strong> {user.kyc_notes}
                </div>
              )}
            </div>
          </div>

          {!isVerified && !isPending && (
            <div className="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
              <div className="mb-8">
                <label className="block text-sm font-bold text-slate-700 mb-4 uppercase tracking-wider">Select Business Type</label>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  {[
                    { id: 'Starter', label: 'Starter', desc: 'Unregistered' },
                    { id: 'Registered', label: 'Registered', desc: 'LLC / CAC' },
                    { id: 'Business Name', label: 'Business Name', desc: 'Sole Prop' },
                    { id: 'Special', label: 'Special', desc: 'NGO / Gov' }
                  ].map(type => (
                    <button
                      key={type.id}
                      onClick={() => setBusinessType(type.id)}
                      className={`p-4 rounded-2xl border-2 text-left transition-all ${
                        businessType === type.id ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-100 hover:border-slate-200'
                      }`}
                    >
                      <p className={`font-bold text-sm ${businessType === type.id ? 'text-indigo-600' : 'text-slate-900'}`}>{type.label}</p>
                      <p className="text-[10px] text-slate-500 mt-1">{type.desc}</p>
                    </button>
                  ))}
                </div>
              </div>
 
              <div className="space-y-8">
                {/* Dynamic Fields based on businessType */}
                {businessType === 'Starter' && (
                  <div className="space-y-6">
                    <div className="grid md:grid-cols-2 gap-6">
                      <InputField label="BVN (Bank Verification Number)" placeholder="222********" onChange={(v: string) => setFormData({...formData, bvn: v})} />
                      <SelectField label="Identity Type" options={['NIN Slip', 'Drivers License', 'Voters Card', 'International Passport']} onChange={(v: string) => setFormData({...formData, id_type: v})} />
                    </div>
                    <InputField label="Residential Address" placeholder="123 Main St, Lagos" onChange={(v: string) => setFormData({...formData, residential_address: v})} />
                    <UploadField label="Proof of Identity" onUpload={() => handleUpload('id')} uploading={uploading === 'id'} path={formData.id_path} />
                  </div>
                )}
 
                {businessType === 'Registered' && (
                  <div className="space-y-6">
                    <div className="grid md:grid-cols-2 gap-6">
                      <InputField label="RC Number" placeholder="RC123456" onChange={(v: string) => setFormData({...formData, rc_number: v})} />
                      <InputField label="Tax Identification Number (TIN)" placeholder="TIN-123456" onChange={(v: string) => setFormData({...formData, tin: v})} />
                    </div>
                    <div className="grid md:grid-cols-2 gap-6">
                      <UploadField label="Certificate of Incorporation" onUpload={() => handleUpload('cac_cert')} uploading={uploading === 'cac_cert'} path={formData.cac_cert_path} />
                      <UploadField label="Form CAC 1.1" onUpload={() => handleUpload('cac_form')} uploading={uploading === 'cac_form'} path={formData.cac_form_path} />
                    </div>
                    <div className="grid md:grid-cols-2 gap-6">
                      <UploadField label="MEMART" onUpload={() => handleUpload('memart')} uploading={uploading === 'memart'} path={formData.memart_path} />
                      <UploadField label="Proof of Business Address" onUpload={() => handleUpload('business_address_proof')} uploading={uploading === 'business_address_proof'} path={formData.business_address_proof_path} />
                    </div>
                  </div>
                )}
 
                {businessType === 'Business Name' && (
                  <div className="space-y-6">
                    <div className="grid md:grid-cols-2 gap-6">
                      <InputField label="BN Number" placeholder="BN123456" onChange={(v: string) => setFormData({...formData, bn_number: v})} />
                      <InputField label="Tax Identification Number (TIN)" placeholder="TIN-123456" onChange={(v: string) => setFormData({...formData, tin: v})} />
                    </div>
                    <div className="grid md:grid-cols-2 gap-6">
                      <UploadField label="Certificate of Registration" onUpload={() => handleUpload('bn_cert')} uploading={uploading === 'bn_cert'} path={formData.bn_cert_path} />
                      <UploadField label="Form BN/1" onUpload={() => handleUpload('bn_form')} uploading={uploading === 'bn_form'} path={formData.bn_form_path} />
                    </div>
                  </div>
                )}
 
                {businessType === 'Special' && (
                  <div className="space-y-6">
                    <div className="grid md:grid-cols-2 gap-6">
                      <UploadField label="Form CAC IT/1" onUpload={() => handleUpload('ngo_form')} uploading={uploading === 'ngo_form'} path={formData.ngo_form_path} />
                      <UploadField label="Organization Constitution" onUpload={() => handleUpload('ngo_constitution')} uploading={uploading === 'ngo_constitution'} path={formData.ngo_constitution_path} />
                    </div>
                    <div className="grid md:grid-cols-2 gap-6">
                      <UploadField label="Authorization Letter" onUpload={() => handleUpload('gov_auth_letter')} uploading={uploading === 'gov_auth_letter'} path={formData.gov_auth_letter_path} />
                      <UploadField label="Official Gazette" onUpload={() => handleUpload('gov_gazette')} uploading={uploading === 'gov_gazette'} path={formData.gov_gazette_path} />
                    </div>
                  </div>
                )}

                <button 
                  onClick={handleSubmit}
                  disabled={submitting}
                  className="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all disabled:opacity-50"
                >
                  {submitting ? 'Submitting...' : 'Submit Compliance Documents'}
                </button>
              </div>
            </div>
          )}
        </div>

        <div className="lg:col-span-1 space-y-6">
          <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <h3 className="font-bold text-slate-900 mb-4">Why KYC?</h3>
            <ul className="space-y-4">
              {[
                { title: 'Higher Limits', desc: 'Collect more than ₦2M lifetime.' },
                { title: 'Payouts', desc: 'Enable settlements to your bank account.' },
                { title: 'Trust', desc: 'Build credibility with your customers.' },
                { title: 'Compliance', desc: 'Meet CBN and regulatory standards.' }
              ].map((item, i) => (
                <li key={i} className="flex gap-3">
                  <div className="mt-1 p-1 bg-indigo-50 rounded-full text-indigo-600">
                    <ShieldCheck size={14} />
                  </div>
                  <div>
                    <p className="text-sm font-bold text-slate-900">{item.title}</p>
                    <p className="text-xs text-slate-500">{item.desc}</p>
                  </div>
                </li>
              ))}
            </ul>
          </div>
          
          <div className="bg-indigo-600 p-6 rounded-3xl text-white shadow-lg shadow-indigo-200">
            <HelpCircle className="mb-4 opacity-80" size={32} />
            <h3 className="font-bold mb-2">Need Help?</h3>
            <p className="text-sm text-indigo-100 mb-4">Our compliance team is available to guide you through the process.</p>
            <Link to="/dashboard/tickets" className="text-xs font-bold bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl transition-all inline-block">Contact Support</Link>
          </div>
        </div>
      </div>
    </div>
  );
};

const InputField = ({ label, placeholder, onChange }: any) => (
  <div>
    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">{label}</label>
    <input 
      type="text" 
      placeholder={placeholder} 
      onChange={e => onChange(e.target.value)}
      className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all" 
    />
  </div>
);

const SelectField = ({ label, options, onChange }: any) => (
  <div>
    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">{label}</label>
    <select 
      onChange={e => onChange(e.target.value)}
      className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all"
    >
      <option value="">Select Option</option>
      {options.map((o: string) => <option key={o} value={o}>{o}</option>)}
    </select>
  </div>
);

const UploadField = ({ label, onUpload, uploading, path }: any) => (
  <div>
    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">{label}</label>
    <div 
      onClick={!path ? onUpload : undefined}
      className={`border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer ${
        path ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 hover:border-indigo-400 bg-slate-50/50'
      }`}
    >
      {uploading ? (
        <RefreshCcw className="mx-auto mb-2 text-indigo-600 animate-spin" size={20} />
      ) : path ? (
        <ShieldCheck className="mx-auto mb-2 text-emerald-600" size={20} />
      ) : (
        <Download className="mx-auto mb-2 text-slate-400" size={20} />
      )}
      <p className={`text-xs font-bold ${path ? 'text-emerald-700' : 'text-slate-500'}`}>
        {uploading ? 'Uploading...' : path ? 'Document Uploaded' : 'Click to upload PDF/Image'}
      </p>
      {path && <p className="text-[10px] text-emerald-600 mt-1 truncate">{path.split('/').pop()}</p>}
    </div>
  </div>
);

const Transactions = ({ transactions, onRefresh }: any) => {
  const [selectedTx, setSelectedTx] = React.useState<any>(null);
  const [timeline, setTimeline] = React.useState<any[]>([]);
  const [loadingTimeline, setLoadingTimeline] = React.useState(false);

  const fetchTimeline = async (txId: number) => {
    setLoadingTimeline(true);
    try {
      const res = await fetch(`/api/merchant/transactions/${txId}/timeline`);
      const data = await res.json();
      setTimeline(data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoadingTimeline(false);
    }
  };

  const handleRefund = async (txId: number) => {
    if (!confirm('Are you sure you want to refund this transaction? The amount will be deducted from your balance.')) return;
    try {
      const res = await fetch(`/api/merchant/transactions/${txId}/refund`, { method: 'POST' });
      if (res.ok) {
        alert('Refund initiated successfully');
        onRefresh();
      } else {
        const data = await res.json();
        alert(data.error || 'Refund failed');
      }
    } catch (e) {
      alert('An error occurred');
    }
  };

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 className="font-bold text-slate-900">All Transactions</h3>
          <div className="flex gap-2">
            <button className="p-2 text-slate-500 hover:bg-slate-50 rounded-lg border border-slate-200"><Filter size={18} /></button>
            <button className="p-2 text-slate-500 hover:bg-slate-50 rounded-lg border border-slate-200"><Download size={18} /></button>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead>
              <tr className="bg-slate-50/50">
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Reference</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Fee</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Settled</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {transactions.map((tx: any, i: number) => (
                <tr key={i} className="hover:bg-slate-50/50 transition-colors">
                  <td className="px-6 py-4 font-mono text-sm">{tx.reference}</td>
                  <td className="px-6 py-4 text-sm">{tx.customer_email}</td>
                  <td className="px-6 py-4 text-sm font-bold">{formatCurrency(tx.amount)}</td>
                  <td className="px-6 py-4 text-sm text-red-500">-{formatCurrency(tx.fee_amount)}</td>
                  <td className="px-6 py-4 text-sm text-emerald-600 font-bold">{formatCurrency(tx.settled_amount)}</td>
                  <td className="px-6 py-4">
                    <span className={`px-2 py-1 rounded-full text-xs font-bold ${
                      tx.status === 'success' ? 'bg-emerald-100 text-emerald-700' : 
                      tx.status === 'refunded' ? 'bg-blue-100 text-blue-700' :
                      'bg-amber-100 text-amber-700'
                    }`}>
                      {tx.status}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-2">
                      <button 
                        onClick={() => { setSelectedTx(tx); fetchTimeline(tx.id); }}
                        className="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
                        title="View Timeline"
                      >
                        <BarChart3 size={16} />
                      </button>
                      {tx.status === 'success' && (
                        <button 
                          onClick={() => handleRefund(tx.id)}
                          className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                          title="Initiate Refund"
                        >
                          <RefreshCcw size={16} />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {selectedTx && (
        <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-3xl border border-slate-200 w-full max-w-lg overflow-hidden shadow-2xl">
            <div className="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
              <div>
                <h3 className="font-bold text-slate-900">Transaction Timeline</h3>
                <p className="text-xs text-slate-500 font-mono mt-1">{selectedTx.reference}</p>
              </div>
              <button onClick={() => setSelectedTx(null)} className="p-2 hover:bg-slate-200 rounded-full transition-colors">
                <ShieldAlert size={20} className="rotate-45" />
              </button>
            </div>
            <div className="p-8 max-h-[60vh] overflow-y-auto">
              {loadingTimeline ? (
                <div className="flex justify-center py-12">
                  <RefreshCcw className="animate-spin text-indigo-600" size={32} />
                </div>
              ) : timeline.length > 0 ? (
                <div className="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-indigo-500 before:via-slate-200 before:to-slate-200">
                  {timeline.map((event, i) => (
                    <div key={i} className="relative flex items-start gap-6 group">
                      <div className="absolute left-0 w-10 h-10 rounded-full bg-white border-2 border-indigo-500 flex items-center justify-center z-10 shadow-sm">
                        <div className="w-2 h-2 rounded-full bg-indigo-500"></div>
                      </div>
                      <div className="pl-14">
                        <p className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                          {new Date(event.created_at).toLocaleString()}
                        </p>
                        <h4 className="font-bold text-slate-900 capitalize">{event.event_type.replace('_', ' ')}</h4>
                        <p className="text-sm text-slate-500 mt-1">{event.description}</p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-12 space-y-4">
                  <div className="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto">
                    <FileText className="text-slate-300" size={32} />
                  </div>
                  <div className="space-y-2">
                    <p className="text-slate-900 font-bold">No events found</p>
                    <p className="text-sm text-slate-500">We're still processing the timeline for this transaction.</p>
                  </div>
                </div>
              )}
            </div>
            <div className="p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
              <button onClick={() => setSelectedTx(null)} className="px-6 py-2 bg-white border border-slate-200 rounded-xl font-bold text-slate-700 hover:bg-slate-50 transition-colors">Close</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const Payouts = ({ userId }: any) => {
  const [payouts, setPayouts] = React.useState<any[]>([]);
  const [amount, setAmount] = React.useState('');
  const [balance, setBalance] = React.useState(0);
  const [profile, setProfile] = React.useState<any>(null);
  const [payoutFee, setPayoutFee] = React.useState(0);
  const [loading, setLoading] = React.useState(true);

  const fetchPayouts = () => fetch(`/api/admin/payouts`).then(r => r.json()).then(data => setPayouts(data.filter((p: any) => p.user_id === userId)));
  const fetchBalance = () => fetch(`/api/merchant/stats/${userId}`).then(r => r.json()).then(data => setBalance(data.balance || 0));
  const fetchProfile = () => fetch(`/api/merchant/profile/${userId}`).then(r => r.json()).then(setProfile);
  const fetchConfig = () => fetch('/api/admin/config').then(r => r.json()).then(data => {
    const fee = data.find((c: any) => c.key === 'payout_fee')?.value || '0';
    setPayoutFee(parseFloat(fee));
  });

  React.useEffect(() => { 
    Promise.all([fetchPayouts(), fetchBalance(), fetchProfile(), fetchConfig()]).finally(() => setLoading(false));
  }, [userId]);

  const withdrawable = Math.max(0, parseFloat(amount || '0') - payoutFee);

  const requestPayout = async () => {
    if (!profile?.settlement_bank || !profile?.settlement_account_number) {
      alert('Please set up your settlement bank details in settings first.');
      return;
    }
    if (parseFloat(amount) > balance) {
      alert('Insufficient balance');
      return;
    }
    const res = await fetch('/api/merchant/payouts', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        userId, 
        amount: parseFloat(amount)
      })
    });
    if (res.ok) {
      setAmount('');
      fetchPayouts();
      fetchBalance();
      alert('Payout request submitted!');
    } else {
      const data = await res.json();
      alert(data.error || 'Payout failed');
    }
  };

  if (loading) return <div className="p-8 text-center text-slate-500">Loading payout data...</div>;

  return (
    <div className="grid lg:grid-cols-3 gap-8">
      <div className="lg:col-span-1 space-y-6">
        <div className="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
          <h3 className="font-bold text-slate-900 mb-4">Withdraw Funds</h3>
          <div className="mb-6 p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
            <p className="text-xs text-indigo-600 uppercase font-bold mb-1">Available Balance</p>
            <p className="text-2xl font-bold text-indigo-700">{formatCurrency(balance)}</p>
          </div>
          
          <div className="mb-6 p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <p className="text-[10px] text-slate-400 uppercase font-bold mb-2 tracking-widest">Settlement Destination</p>
            {profile?.settlement_bank ? (
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-white rounded-xl border border-slate-200 flex items-center justify-center text-indigo-600">
                  <Wallet size={20} />
                </div>
                <div>
                  <p className="text-sm font-bold text-slate-900">{profile.settlement_bank}</p>
                  <p className="text-xs text-slate-500 font-mono">{profile.settlement_account_number}</p>
                </div>
              </div>
            ) : (
              <div className="text-center py-2">
                <p className="text-xs text-amber-600 font-bold mb-2">No bank account set</p>
                <Link to="/dashboard/settings" className="text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-lg font-bold hover:bg-amber-200 transition-colors">Set Up Now</Link>
              </div>
            )}
          </div>

          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Amount to Withdraw</label>
              <div className="relative">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₦</span>
                <input 
                  type="number" 
                  value={amount} 
                  onChange={e => setAmount(e.target.value)} 
                  className="w-full pl-8 pr-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" 
                  placeholder="0.00" 
                />
              </div>
            </div>
            {parseFloat(amount) > 0 && (
              <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500">Payout Fee:</span>
                  <span className="font-bold text-slate-900">{formatCurrency(payoutFee)}</span>
                </div>
                <div className="flex justify-between text-sm pt-2 border-t border-slate-200">
                  <span className="text-slate-500">You will receive:</span>
                  <span className="font-bold text-emerald-600">{formatCurrency(withdrawable)}</span>
                </div>
              </div>
            )}
            <button 
              onClick={requestPayout} 
              disabled={!profile?.settlement_bank || !amount || parseFloat(amount) <= 0}
              className="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all disabled:opacity-50 disabled:shadow-none"
            >
              Confirm Withdrawal
            </button>
          </div>
        </div>
      </div>
      <div className="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-6 border-b border-slate-100 font-bold">Payout History</div>
        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Amount</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Details</th>
                <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Date</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {payouts.map((p, i) => (
                <tr key={i}>
                  <td className="px-6 py-4 text-sm font-bold">{formatCurrency(p.amount)}</td>
                  <td className="px-6 py-4">
                    <span className={`px-2 py-1 rounded-full text-xs font-bold ${
                      p.status === 'processed' ? 'bg-emerald-100 text-emerald-700' : 
                      p.status === 'declined' ? 'bg-red-100 text-red-700' :
                      'bg-slate-100 text-slate-600'
                    }`}>
                      {p.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-xs text-slate-500 max-w-[200px] truncate" title={p.status_details}>
                    {p.status_details || 'No details available'}
                  </td>
                  <td className="px-6 py-4 text-sm text-slate-500">{new Date(p.request_date).toLocaleDateString()}</td>
                </tr>
              ))}
              {payouts.length === 0 && <tr><td colSpan={4} className="px-6 py-12 text-center text-slate-500">No payout history.</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

const Customers = ({ userId }: any) => {
  const [customers, setCustomers] = React.useState<any[]>([]);
  React.useEffect(() => { fetch(`/api/merchant/customers/${userId}`).then(r => r.json()).then(setCustomers); }, []);

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 font-bold">Customer Directory</div>
      <table className="w-full text-left">
        <thead className="bg-slate-50">
          <tr>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Name</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Email</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Phone</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {customers.map((c, i) => (
            <tr key={i}>
              <td className="px-6 py-4 text-sm font-bold">{c.full_name}</td>
              <td className="px-6 py-4 text-sm">{c.email}</td>
              <td className="px-6 py-4 text-sm">{c.phone}</td>
            </tr>
          ))}
          {customers.length === 0 && <tr><td colSpan={3} className="px-6 py-12 text-center text-slate-500">No customers yet.</td></tr>}
        </tbody>
      </table>
    </div>
  );
};

const Invoices = ({ userId }: any) => {
  const [invoices, setInvoices] = React.useState<any[]>([]);
  React.useEffect(() => { fetch(`/api/merchant/invoices/${userId}`).then(r => r.json()).then(setInvoices); }, []);

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 flex justify-between items-center">
        <h3 className="font-bold">Invoices</h3>
        <button className="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold">+ Create Invoice</button>
      </div>
      <table className="w-full text-left">
        <thead className="bg-slate-50">
          <tr>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Customer</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Amount</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Due Date</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {invoices.map((inv, i) => (
            <tr key={i}>
              <td className="px-6 py-4 text-sm font-bold">{inv.customer_name}</td>
              <td className="px-6 py-4 text-sm">{formatCurrency(inv.amount)}</td>
              <td className="px-6 py-4 text-sm">{inv.due_date}</td>
              <td className="px-6 py-4"><span className="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">{inv.status}</span></td>
            </tr>
          ))}
          {invoices.length === 0 && <tr><td colSpan={4} className="px-6 py-12 text-center text-slate-500">No invoices yet.</td></tr>}
        </tbody>
      </table>
    </div>
  );
};

const Subscriptions = ({ userId }: any) => {
  const [subs, setSubs] = React.useState<any[]>([]);
  React.useEffect(() => { fetch(`/api/merchant/subscriptions/${userId}`).then(r => r.json()).then(setSubs); }, []);

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 flex justify-between items-center">
        <h3 className="font-bold">Subscription Plans</h3>
        <button className="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold">+ New Plan</button>
      </div>
      <div className="grid md:grid-cols-3 gap-6 p-6">
        {subs.map((s, i) => (
          <div key={i} className="p-6 border border-slate-100 rounded-2xl bg-slate-50">
            <h4 className="font-bold text-lg mb-1">{s.plan_name}</h4>
            <p className="text-2xl font-bold text-indigo-600 mb-4">{formatCurrency(s.amount)}<span className="text-sm text-slate-400 font-normal">/{s.interval}</span></p>
            <div className="flex justify-between text-sm text-slate-500">
              <span>Status:</span>
              <span className="text-emerald-600 font-bold">{s.status}</span>
            </div>
          </div>
        ))}
        {subs.length === 0 && <p className="col-span-3 text-center py-12 text-slate-500">No subscription plans created.</p>}
      </div>
    </div>
  );
};

const Disputes = ({ userId }: any) => {
  const [disputes, setDisputes] = React.useState<any[]>([]);
  React.useEffect(() => { fetch(`/api/merchant/disputes/${userId}`).then(r => r.json()).then(setDisputes); }, []);

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 font-bold">Transaction Disputes</div>
      <table className="w-full text-left">
        <thead className="bg-slate-50">
          <tr>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Transaction Ref</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Reason</th>
            <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {disputes.map((d, i) => (
            <tr key={i}>
              <td className="px-6 py-4 text-sm font-mono">{d.transaction_ref}</td>
              <td className="px-6 py-4 text-sm">{d.reason}</td>
              <td className="px-6 py-4"><span className="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold">{d.status}</span></td>
            </tr>
          ))}
          {disputes.length === 0 && <tr><td colSpan={3} className="px-6 py-12 text-center text-slate-500">No active disputes.</td></tr>}
        </tbody>
      </table>
    </div>
  );
};

const Tickets = ({ userId }: any) => {
  const [tickets, setTickets] = React.useState<any[]>([]);
  const [subject, setSubject] = React.useState('');
  const [message, setMessage] = React.useState('');

  const fetchTickets = () => fetch(`/api/merchant/tickets/${userId}`).then(r => r.json()).then(setTickets);
  React.useEffect(() => { fetchTickets(); }, []);

  const submitTicket = async () => {
    await fetch('/api/merchant/tickets', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId, subject, message, priority: 'medium' })
    });
    setSubject('');
    setMessage('');
    fetchTickets();
    alert('Ticket submitted!');
  };

  return (
    <div className="grid lg:grid-cols-3 gap-8">
      <div className="lg:col-span-1 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <h3 className="font-bold mb-6">Open New Ticket</h3>
        <div className="space-y-4">
          <input value={subject} onChange={e => setSubject(e.target.value)} placeholder="Subject" className="w-full px-4 py-2 border border-slate-200 rounded-xl" />
          <textarea value={message} onChange={e => setMessage(e.target.value)} placeholder="Describe your issue..." className="w-full px-4 py-2 border border-slate-200 rounded-xl h-32" />
          <button onClick={submitTicket} className="w-full bg-indigo-600 text-white py-2 rounded-xl font-bold">Submit Ticket</button>
        </div>
      </div>
      <div className="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-6 border-b border-slate-100 font-bold">My Tickets</div>
        <table className="w-full text-left">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Subject</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Status</th>
              <th className="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Date</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {tickets.map((t, i) => (
              <tr key={i}>
                <td className="px-6 py-4 text-sm font-bold">{t.subject}</td>
                <td className="px-6 py-4"><span className="px-2 py-1 bg-slate-100 rounded-full text-xs">{t.status}</span></td>
                <td className="px-6 py-4 text-sm text-slate-500">{new Date(t.created_at).toLocaleDateString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

const ApiKeys = ({ userId }: any) => {
  const [keys, setKeys] = React.useState<any>(null);
  const [loading, setLoading] = React.useState(true);
  const [regenerating, setRegenerating] = React.useState(false);

  const fetchKeys = () => {
    setLoading(true);
    fetch(`/api/merchant/api-keys/${userId}`)
      .then(r => r.json())
      .then(data => {
        setKeys(data);
        setLoading(false);
      });
  };

  React.useEffect(() => { fetchKeys(); }, [userId]);

  const handleRegenerate = async () => {
    if (!confirm('Are you sure you want to regenerate your API keys? This will immediately break any existing integrations using the current keys.')) {
      return;
    }

    setRegenerating(true);
    try {
      const res = await fetch('/api/merchant/api-keys/regenerate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId })
      });
      const data = await res.json();
      setKeys(data);
      alert('API keys regenerated successfully!');
    } catch (e) {
      alert('Failed to regenerate keys');
    } finally {
      setRegenerating(false);
    }
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    alert('Copied to clipboard!');
  };

  if (loading) return <div className="p-8 text-center text-slate-500">Loading API keys...</div>;

  return (
    <div className="bg-white p-8 rounded-3xl border border-slate-200 max-w-3xl">
      <div className="flex items-center gap-3 mb-8">
        <div className="p-2 bg-indigo-50 rounded-xl text-indigo-600">
          <Code size={24} />
        </div>
        <h2 className="text-2xl font-bold">API Keys</h2>
      </div>
      
      <div className="space-y-8">
        <div className="p-6 bg-slate-50 rounded-2xl border border-slate-100">
          <div className="flex justify-between items-center mb-4">
            <p className="text-xs font-bold text-slate-400 uppercase tracking-widest">Public Key</p>
            <span className="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded font-bold uppercase">Live</span>
          </div>
          <div className="flex gap-2">
            <input readOnly value={keys?.public_key || ''} className="flex-1 px-4 py-3 bg-white border border-slate-200 rounded-xl font-mono text-sm focus:outline-none" />
            <button 
              onClick={() => copyToClipboard(keys?.public_key)}
              className="px-6 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all active:scale-95"
            >
              Copy
            </button>
          </div>
          <p className="mt-2 text-[10px] text-slate-400">Use this key to identify your account in client-side integrations.</p>
        </div>

        <div className="p-6 bg-slate-50 rounded-2xl border border-slate-100">
          <div className="flex justify-between items-center mb-4">
            <p className="text-xs font-bold text-slate-400 uppercase tracking-widest">Secret Key</p>
            <span className="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded font-bold uppercase">Private</span>
          </div>
          <div className="flex gap-2">
            <input readOnly type="password" value={keys?.secret_key || ''} className="flex-1 px-4 py-3 bg-white border border-slate-200 rounded-xl font-mono text-sm focus:outline-none" />
            <button 
              onClick={() => copyToClipboard(keys?.secret_key)}
              className="px-6 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all active:scale-95"
            >
              Copy
            </button>
          </div>
          <p className="mt-2 text-[10px] text-slate-400">Keep this key secure. Never expose it in client-side code.</p>
        </div>

        <div className="pt-6 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h4 className="font-bold text-slate-900 text-sm">Danger Zone</h4>
            <p className="text-xs text-slate-400 mt-1">Regenerating keys will break existing integrations.</p>
          </div>
          <button 
            onClick={handleRegenerate}
            disabled={regenerating}
            className="bg-red-50 text-red-600 px-6 py-3 rounded-xl font-bold text-sm hover:bg-red-100 transition-all disabled:opacity-50"
          >
            {regenerating ? 'Regenerating...' : 'Regenerate Keys'}
          </button>
        </div>
      </div>
    </div>
  );
};

const Ledger = ({ userId }: any) => {
  const [ledger, setLedger] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetch(`/api/merchant/ledger/${userId}`)
      .then(r => r.json())
      .then(data => {
        setLedger(data);
        setLoading(false);
      });
  }, [userId]);

  if (loading) return <div className="p-8 text-center text-slate-500">Loading ledger...</div>;

  return (
    <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
        <div>
          <h2 className="text-xl font-bold text-slate-900">Balance Ledger</h2>
          <p className="text-xs text-slate-500 mt-1">Detailed history of all wallet balance movements</p>
        </div>
        <div className="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider">
          <Activity size={14} />
          Real-time
        </div>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-left">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date & Time</th>
              <th className="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Description</th>
              <th className="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Category</th>
              <th className="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Amount</th>
              <th className="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Balance After</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {ledger.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-6 py-12 text-center text-slate-400 italic">No ledger entries found</td>
              </tr>
            ) : (
              ledger.map((entry, i) => (
                <tr key={i} className="hover:bg-slate-50/50 transition-colors">
                  <td className="px-6 py-4">
                    <div className="text-sm font-medium text-slate-900">{new Date(entry.created_at).toLocaleDateString()}</div>
                    <div className="text-[10px] text-slate-400">{new Date(entry.created_at).toLocaleTimeString()}</div>
                  </td>
                  <td className="px-6 py-4">
                    <p className="text-sm text-slate-700">{entry.description}</p>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                      entry.category === 'payout' ? 'bg-amber-50 text-amber-600' :
                      entry.category === 'refund' ? 'bg-red-50 text-red-600' :
                      entry.category === 'fee' ? 'bg-slate-100 text-slate-600' :
                      'bg-emerald-50 text-emerald-600'
                    }`}>
                      {entry.category}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span className={`text-sm font-bold ${entry.type === 'credit' ? 'text-emerald-600' : 'text-red-600'}`}>
                      {entry.type === 'credit' ? '+' : '-'} ₦{entry.amount.toLocaleString()}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <span className="text-sm font-mono font-bold text-slate-900">₦{entry.balance_after.toLocaleString()}</span>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

const SettingsPage = ({ userId }: any) => {
  const [profile, setProfile] = React.useState<any>({ email: '', phone_number: '', webhook_url: '', business_name: '', settlement_bank: '', settlement_account_number: '', settlement_account_name: '' });
  const [banks, setBanks] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    Promise.all([
      fetch(`/api/merchant/profile/${userId}`).then(r => r.json()),
      fetch('/api/banks').then(r => r.json())
    ]).then(([profileData, banksData]) => {
      setProfile(profileData);
      setBanks(banksData);
      setLoading(false);
    });
  }, [userId]);

  const saveChanges = async () => {
    const res = await fetch('/api/merchant/profile', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId, ...profile })
    });
    
    // Also save settlement bank separately if needed or together
    const resBank = await fetch('/api/merchant/settlement-bank', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        userId, 
        bank_name: profile.settlement_bank, 
        account_number: profile.settlement_account_number, 
        account_name: profile.settlement_account_name 
      })
    });

    if (res.ok && resBank.ok) {
      alert('Settings updated successfully!');
    } else {
      alert('Failed to update settings');
    }
  };

  if (loading) return <div className="p-8 text-center text-slate-500">Loading settings...</div>;

  return (
    <div className="bg-white p-8 rounded-3xl border border-slate-200 max-w-3xl">
      <h2 className="text-2xl font-bold mb-8">Business Settings</h2>
      <div className="space-y-6">
        <div className="grid md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Business Name</label>
            <input 
              value={profile.business_name} 
              onChange={e => setProfile({...profile, business_name: e.target.value})}
              className="w-full px-4 py-2 border border-slate-200 rounded-xl" 
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Contact Email</label>
            <input 
              value={profile.email} 
              onChange={e => setProfile({...profile, email: e.target.value})}
              className="w-full px-4 py-2 border border-slate-200 rounded-xl" 
            />
          </div>
        </div>
        <div className="grid md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
            <input 
              value={profile.phone_number || ''} 
              onChange={e => setProfile({...profile, phone_number: e.target.value})}
              placeholder="+234..."
              className="w-full px-4 py-2 border border-slate-200 rounded-xl" 
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Webhook URL</label>
            <input 
              value={profile.webhook_url || ''} 
              onChange={e => setProfile({...profile, webhook_url: e.target.value})}
              placeholder="https://your-api.com/webhook"
              className="w-full px-4 py-2 border border-slate-200 rounded-xl" 
            />
          </div>
        </div>
        <div className="pt-4 border-t border-slate-100">
          <h3 className="font-bold mb-4">Settlement Bank Account</h3>
          <p className="text-xs text-slate-500 mb-6 italic">This is where your funds will be sent when you request a payout.</p>
          <div className="space-y-6">
            <div className="grid md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Settlement Bank</label>
                <select 
                  value={profile.settlement_bank || ''} 
                  onChange={e => setProfile({...profile, settlement_bank: e.target.value})}
                  className="w-full px-4 py-2 border border-slate-200 rounded-xl"
                >
                  <option value="">Select Bank</option>
                  {banks.map(b => <option key={b.code} value={b.name}>{b.name}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Account Number</label>
                <input 
                  value={profile.settlement_account_number || ''} 
                  onChange={e => setProfile({...profile, settlement_account_number: e.target.value})}
                  placeholder="0123456789" 
                  className="w-full px-4 py-2 border border-slate-200 rounded-xl" 
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Account Name</label>
              <input 
                value={profile.settlement_account_name || ''} 
                onChange={e => setProfile({...profile, settlement_account_name: e.target.value})}
                placeholder="Business or Personal Name" 
                className="w-full px-4 py-2 border border-slate-200 rounded-xl" 
              />
            </div>
          </div>
        </div>
        <button onClick={saveChanges} className="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">Save All Changes</button>
      </div>
    </div>
  );
};

export default function MerchantDashboard() {
  const [user, setUser] = React.useState<any>(null);
  const [stats, setStats] = React.useState<any>({ total_volume: 0, transaction_count: 0, balance: 0 });
  const [transactions, setTransactions] = React.useState<any[]>([]);
  const [revenueData, setRevenueData] = React.useState<any[]>([]);
  const location = useLocation();
  const navigate = useNavigate();

  React.useEffect(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      const parsedUser = JSON.parse(storedUser);
      // Fetch latest profile to get business_type and kyc status
      fetch(`/api/merchant/profile/${parsedUser.id}`)
        .then(r => r.json())
        .then(profileData => {
          const updatedUser = { ...parsedUser, ...profileData };
          setUser(updatedUser);
          localStorage.setItem('user', JSON.stringify(updatedUser));
          fetchStats(updatedUser.id);
          fetchTransactions(updatedUser.id);
          fetchRevenueData(updatedUser.id);
        });
    } else {
      navigate('/login');
    }
  }, []);

  const fetchStats = (userId: number) => fetch(`/api/merchant/stats/${userId}`).then(r => r.json()).then(setStats);
  const fetchTransactions = (userId: number) => fetch(`/api/merchant/transactions/${userId}`).then(r => r.json()).then(setTransactions);
  const fetchRevenueData = (userId: number) => fetch(`/api/merchant/stats/revenue/${userId}`).then(r => r.json()).then(setRevenueData);

  if (!user) return null;

  if (user.is_suspended) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4">
        <div className="bg-white p-12 rounded-3xl border border-slate-200 text-center max-w-md shadow-xl">
          <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <Ban className="text-red-600 w-10 h-10" />
          </div>
          <h2 className="text-2xl font-bold text-slate-900 mb-4">Account Suspended</h2>
          <p className="text-slate-500 mb-8">Your merchant account has been suspended by the platform administrator. You cannot process transactions or access your dashboard at this time.</p>
          <button onClick={() => { localStorage.removeItem('user'); navigate('/login'); }} className="w-full bg-slate-900 text-white py-3 rounded-xl font-bold">Logout</button>
        </div>
      </div>
    );
  }

  const isActive = (path: string) => location.pathname === path || (path === '/dashboard' && location.pathname === '/dashboard/');

  return (
    <div className="min-h-screen bg-slate-50 flex">
      <aside className="w-64 bg-white border-r border-slate-200 hidden lg:flex flex-col fixed h-full">
        <div className="p-6 border-b border-slate-100 flex items-center gap-3">
          <div className="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
            <CreditCard className="text-white w-5 h-5" />
          </div>
          <span className="text-xl font-bold text-slate-900">Payhub</span>
        </div>
        <nav className="flex-1 p-4 space-y-6 overflow-y-auto">
          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Main</div>
            <div className="space-y-1">
              <SidebarLink to="/dashboard" icon={<LayoutDashboard size={20} />} label="Dashboard" active={isActive('/dashboard')} />
              <SidebarLink to="/dashboard/kyc" icon={<ShieldAlert size={20} />} label="Compliance" active={isActive('/dashboard/kyc')} />
            </div>
          </div>

          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Collections</div>
            <div className="space-y-1">
              <SidebarLink to="/dashboard/transactions" icon={<ArrowUpRight size={20} />} label="Transactions" active={isActive('/dashboard/transactions')} />
              {user.business_type !== 'Starter' && (
                <SidebarLink to="/dashboard/virtual-accounts" icon={<Wallet size={20} />} label="Virtual Accounts" active={isActive('/dashboard/virtual-accounts')} />
              )}
              <SidebarLink to="/dashboard/invoices" icon={<FileText size={20} />} label="Invoices" active={isActive('/dashboard/invoices')} />
              <SidebarLink to="/dashboard/subscriptions" icon={<RefreshCcw size={20} />} label="Subscriptions" active={isActive('/dashboard/subscriptions')} />
            </div>
          </div>

          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Finance</div>
            <div className="space-y-1">
              <SidebarLink to="/dashboard/payouts" icon={<ArrowDownLeft size={20} />} label="Payouts" active={isActive('/dashboard/payouts')} />
              <SidebarLink to="/dashboard/ledger" icon={<Activity size={20} />} label="Balance Ledger" active={isActive('/dashboard/ledger')} />
              <SidebarLink to="/dashboard/disputes" icon={<ShieldCheck size={20} />} label="Disputes" active={isActive('/dashboard/disputes')} />
            </div>
          </div>

          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Management</div>
            <div className="space-y-1">
              <SidebarLink to="/dashboard/customers" icon={<Users size={20} />} label="Customers" active={isActive('/dashboard/customers')} />
              <SidebarLink to="/dashboard/tickets" icon={<Ticket size={20} />} label="Support" active={isActive('/dashboard/tickets')} />
            </div>
          </div>

          <div>
            <div className="px-3 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Developer</div>
            <div className="space-y-1">
              <SidebarLink to="/dashboard/api-keys" icon={<Code size={20} />} label="API Keys" active={isActive('/dashboard/api-keys')} />
              <SidebarLink to="/dashboard/settings" icon={<Settings size={20} />} label="Settings" active={isActive('/dashboard/settings')} />
            </div>
          </div>
        </nav>
        <div className="p-4 border-t border-slate-100">
          <button onClick={() => { localStorage.removeItem('user'); navigate('/login'); }} className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition-all">
            <LogOut size={20} /> Logout
          </button>
        </div>
      </aside>

      <main className="flex-1 lg:ml-64">
        <header className="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 sticky top-0 z-10">
          <div className="flex items-center gap-4 flex-1 max-w-xl">
            <div className="relative w-full">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
              <input type="text" placeholder="Search..." className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
            </div>
          </div>
          <div className="flex items-center gap-4">
            <button className="p-2 text-slate-500 hover:bg-slate-50 rounded-lg relative"><Bell size={20} /></button>
            <div className="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold">{user.business_name?.[0]}</div>
          </div>
        </header>

        <div className="p-8 relative">
          <Routes>
            <Route index element={<Overview stats={stats} transactions={transactions} user={user} revenueData={revenueData} />} />
            <Route path="kyc" element={<Compliance user={user} />} />
            {user.business_type !== 'Starter' && (
              <Route path="virtual-accounts" element={<VirtualAccounts userId={user.id} user={user} />} />
            )}
            <Route path="transactions" element={<Transactions transactions={transactions} onRefresh={() => fetchTransactions(user.id)} />} />
            <Route path="payouts" element={<Payouts userId={user.id} />} />
            <Route path="ledger" element={<Ledger userId={user.id} />} />
            <Route path="customers" element={<Customers userId={user.id} />} />
            <Route path="invoices" element={<Invoices userId={user.id} />} />
            <Route path="subscriptions" element={<Subscriptions userId={user.id} />} />
            <Route path="disputes" element={<Disputes userId={user.id} />} />
            <Route path="tickets" element={<Tickets userId={user.id} />} />
            <Route path="api-keys" element={<ApiKeys userId={user.id} />} />
            <Route path="settings" element={<SettingsPage userId={user.id} />} />
            <Route path="*" element={<div className="bg-white p-12 rounded-3xl border border-slate-200 text-center text-slate-500">Feature coming soon...</div>} />
          </Routes>

          {/* Quick Actions Floating Button */}
          <div className="fixed bottom-8 right-8 group z-40">
            <div className="absolute bottom-full right-0 mb-4 flex flex-col items-end gap-3 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-all translate-y-4 group-hover:translate-y-0">
              <Link to="/dashboard/payouts" className="flex items-center gap-3 bg-white px-4 py-2.5 rounded-xl shadow-xl border border-slate-100 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all whitespace-nowrap">
                <ArrowDownLeft size={18} className="text-amber-500" />
                Request Payout
              </Link>
              <Link to="/dashboard/invoices" className="flex items-center gap-3 bg-white px-4 py-2.5 rounded-xl shadow-xl border border-slate-100 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all whitespace-nowrap">
                <FileText size={18} className="text-indigo-500" />
                Create Invoice
              </Link>
              <Link to="/dashboard/tickets" className="flex items-center gap-3 bg-white px-4 py-2.5 rounded-xl shadow-xl border border-slate-100 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all whitespace-nowrap">
                <Ticket size={18} className="text-emerald-500" />
                Open Ticket
              </Link>
            </div>
            <button className="w-14 h-14 bg-indigo-600 rounded-full flex items-center justify-center text-white shadow-2xl shadow-indigo-200 hover:bg-indigo-700 transition-all hover:scale-110 active:scale-95">
              <Plus size={28} className="group-hover:rotate-45 transition-transform duration-300" />
            </button>
          </div>
        </div>
      </main>
    </div>
  );
}

function SidebarLink({ to, icon, label, active }: any) {
  return (
    <Link to={to} className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
      active ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
    }`}>
      {icon}
      {label}
    </Link>
  );
}

function StatCard({ title, value, icon, trend }: any) {
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

function PaymentMethodProgress({ label, percentage, color }: any) {
  return (
    <div>
      <div className="flex justify-between text-sm mb-2">
        <span className="font-medium text-slate-600">{label}</span>
        <span className="font-bold text-slate-900">{percentage}%</span>
      </div>
      <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
        <div className={`h-full ${color} rounded-full`} style={{ width: `${percentage}%` }}></div>
      </div>
    </div>
  );
}
