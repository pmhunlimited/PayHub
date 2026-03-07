import React from 'react';
import { Search, HelpCircle, MessageCircle, BookOpen } from 'lucide-react';

export default function SupportCenter() {
  return (
    <div className="min-h-screen bg-slate-50">
      <div className="bg-indigo-600 py-24 px-4 text-center">
        <h1 className="text-4xl font-bold text-white mb-8">How can we help you?</h1>
        <div className="max-w-2xl mx-auto relative">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            type="text" 
            placeholder="Search for articles, guides..." 
            className="w-full pl-12 pr-4 py-4 rounded-2xl border-none focus:ring-2 focus:ring-indigo-300 shadow-xl"
          />
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 -mt-12 mb-24">
        <div className="grid md:grid-cols-3 gap-8">
          <SupportCard 
            icon={<BookOpen className="text-indigo-600" />}
            title="Knowledge Base"
            description="Browse our comprehensive guides and tutorials."
          />
          <SupportCard 
            icon={<MessageCircle className="text-emerald-600" />}
            title="Community"
            description="Join the discussion with other Payhub developers."
          />
          <SupportCard 
            icon={<HelpCircle className="text-amber-600" />}
            title="Direct Support"
            description="Can't find what you need? Talk to our team."
          />
        </div>

        <div className="mt-24">
          <h2 className="text-2xl font-bold text-slate-900 mb-8">Popular Articles</h2>
          <div className="grid md:grid-cols-2 gap-6">
            <ArticleLink title="How to integrate Payhub on your website" />
            <ArticleLink title="Understanding transaction fees" />
            <ArticleLink title="Setting up virtual accounts" />
            <ArticleLink title="Managing payouts and settlements" />
          </div>
        </div>
      </div>
    </div>
  );
}

function SupportCard({ icon, title, description }: any) {
  return (
    <div className="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
      <div className="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center mb-6">{icon}</div>
      <h3 className="text-xl font-bold text-slate-900 mb-2">{title}</h3>
      <p className="text-slate-600 text-sm leading-relaxed">{description}</p>
    </div>
  );
}

function ArticleLink({ title }: any) {
  return (
    <a href="#" className="p-6 bg-white rounded-2xl border border-slate-200 hover:border-indigo-300 hover:text-indigo-600 transition-all font-medium text-slate-700">
      {title}
    </a>
  );
}
