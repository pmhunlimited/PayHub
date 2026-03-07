import React from 'react';
import { Check } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function Pricing() {
  return (
    <div className="min-h-screen bg-slate-50 py-24 px-4">
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-16">
          <h1 className="text-4xl font-bold text-slate-900 mb-4">Simple, transparent pricing</h1>
          <p className="text-lg text-slate-600">No hidden fees. No setup costs. Only pay when you get paid.</p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          <PricingCard 
            name="Starter"
            price="1.5%"
            description="Perfect for small businesses and side projects."
            features={['Local Payments', 'Email Support', 'Basic Analytics', 'Payment Pages']}
          />
          <PricingCard 
            name="Growth"
            price="1.5% + ₦100"
            description="For growing businesses that need more power."
            features={['Global Payments', 'Priority Support', 'Advanced Analytics', 'Virtual Accounts', 'Invoicing']}
            featured
          />
          <PricingCard 
            name="Enterprise"
            price="Custom"
            description="Tailored solutions for large-scale operations."
            features={['Dedicated Account Manager', 'Custom Fee Structure', 'White-labeling', 'API Integration Support']}
          />
        </div>
      </div>
    </div>
  );
}

function PricingCard({ name, price, description, features, featured = false }: any) {
  return (
    <div className={`p-8 rounded-3xl border ${featured ? 'bg-indigo-600 text-white border-indigo-600 shadow-xl shadow-indigo-200' : 'bg-white border-slate-200 text-slate-900'}`}>
      <h3 className="text-xl font-bold mb-2">{name}</h3>
      <div className="flex items-baseline gap-1 mb-4">
        <span className="text-4xl font-bold">{price}</span>
        {!price.includes('Custom') && <span className={featured ? 'text-indigo-200' : 'text-slate-500'}>/transaction</span>}
      </div>
      <p className={`mb-8 text-sm ${featured ? 'text-indigo-100' : 'text-slate-600'}`}>{description}</p>
      
      <ul className="space-y-4 mb-8">
        {features.map((f: string, i: number) => (
          <li key={i} className="flex items-center gap-3 text-sm">
            <Check className={`w-5 h-5 ${featured ? 'text-indigo-300' : 'text-indigo-600'}`} />
            {f}
          </li>
        ))}
      </ul>

      <Link to="/register" className={`block w-full text-center py-3 rounded-xl font-bold transition-all ${featured ? 'bg-white text-indigo-600 hover:bg-slate-50' : 'bg-indigo-600 text-white hover:bg-indigo-700'}`}>
        Get Started
      </Link>
    </div>
  );
}
