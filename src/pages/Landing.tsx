import React from 'react';
import { 
  CreditCard, 
  Shield, 
  Zap, 
  BarChart3, 
  ArrowRight, 
  CheckCircle2,
  Menu,
  X,
  Globe,
  Smartphone
} from 'lucide-react';
import { Link } from 'react-router-dom';

export default function Landing() {
  const [isMenuOpen, setIsMenuOpen] = React.useState(false);

  return (
    <div className="min-h-screen bg-white font-sans text-slate-900">
      {/* Navigation */}
      <nav className="fixed top-0 w-full bg-white/80 backdrop-blur-md z-50 border-b border-slate-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-20">
            <div className="flex items-center gap-2">
              <div className="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                <CreditCard className="text-white w-6 h-6" />
              </div>
              <span className="text-2xl font-bold tracking-tight text-slate-900">Payhub</span>
            </div>
            
            <div className="hidden md:flex items-center gap-8">
              <Link to="/pricing" className="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Pricing</Link>
              <Link to="/docs" className="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Developers</Link>
              <Link to="/blog" className="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Blog</Link>
              <Link to="/login" className="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Login</Link>
              <Link to="/register" className="bg-indigo-600 text-white px-6 py-2.5 rounded-full text-sm font-semibold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                Create free account
              </Link>
            </div>

            <button className="md:hidden" onClick={() => setIsMenuOpen(!isMenuOpen)}>
              {isMenuOpen ? <X /> : <Menu />}
            </button>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div className="max-w-2xl">
              <h1 className="text-5xl lg:text-7xl font-bold tracking-tight text-slate-900 leading-[1.1] mb-6">
                Modern payments for <span className="text-indigo-600">ambitious</span> businesses.
              </h1>
              <p className="text-xl text-slate-600 mb-10 leading-relaxed">
                Payhub helps businesses in Africa get paid by anyone, anywhere in the world. 
                Start accepting payments in minutes with our robust API and no-code tools.
              </p>
              <div className="flex flex-col sm:flex-row gap-4">
                <Link to="/register" className="bg-indigo-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-200 flex items-center justify-center gap-2">
                  Get Started Now <ArrowRight className="w-5 h-5" />
                </Link>
                <button className="bg-slate-50 text-slate-900 px-8 py-4 rounded-full text-lg font-semibold hover:bg-slate-100 transition-all flex items-center justify-center gap-2">
                  Contact Sales
                </button>
              </div>
              <div className="mt-12 flex items-center gap-6 grayscale opacity-60">
                <img src="https://picsum.photos/seed/brand1/100/40" alt="Partner" className="h-8" referrerPolicy="no-referrer" />
                <img src="https://picsum.photos/seed/brand2/100/40" alt="Partner" className="h-8" referrerPolicy="no-referrer" />
                <img src="https://picsum.photos/seed/brand3/100/40" alt="Partner" className="h-8" referrerPolicy="no-referrer" />
              </div>
            </div>
            <div className="relative">
              <div className="absolute -inset-4 bg-indigo-100 rounded-[2rem] blur-3xl opacity-30 animate-pulse"></div>
              <img 
                src="https://picsum.photos/seed/dashboard/800/600" 
                alt="Dashboard Preview" 
                className="relative rounded-2xl shadow-2xl border border-slate-200"
                referrerPolicy="no-referrer"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-24 bg-slate-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-3xl mx-auto mb-20">
            <h2 className="text-sm font-bold text-indigo-600 uppercase tracking-widest mb-4">Why Payhub?</h2>
            <p className="text-4xl font-bold text-slate-900 mb-6">Everything you need to grow your business</p>
            <p className="text-lg text-slate-600">From startups to global corporations, Payhub provides the tools to scale your financial operations.</p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                icon: <Zap className="w-8 h-8 text-indigo-600" />,
                title: "Fast Integration",
                desc: "Get up and running in minutes with our well-documented APIs and SDKs."
              },
              {
                icon: <Shield className="w-8 h-8 text-indigo-600" />,
                title: "Secure Payments",
                desc: "PCI-DSS Level 1 compliant infrastructure with advanced fraud detection."
              },
              {
                icon: <BarChart3 className="w-8 h-8 text-indigo-600" />,
                title: "Deep Insights",
                desc: "Understand your customers with real-time analytics and custom reports."
              },
              {
                icon: <Globe className="w-8 h-8 text-indigo-600" />,
                title: "Global Reach",
                desc: "Accept payments in multiple currencies from customers worldwide."
              },
              {
                icon: <Smartphone className="w-8 h-8 text-indigo-600" />,
                title: "Mobile Ready",
                desc: "Optimized checkout experience for mobile devices and native apps."
              },
              {
                icon: <CheckCircle2 className="w-8 h-8 text-indigo-600" />,
                title: "Automated Payouts",
                desc: "Get your money settled into your bank account automatically and on time."
              }
            ].map((feature, i) => (
              <div key={i} className="bg-white p-10 rounded-3xl border border-slate-100 hover:shadow-xl transition-all group">
                <div className="mb-6 p-4 bg-indigo-50 rounded-2xl w-fit group-hover:scale-110 transition-transform">
                  {feature.icon}
                </div>
                <h3 className="text-xl font-bold text-slate-900 mb-4">{feature.title}</h3>
                <p className="text-slate-600 leading-relaxed">{feature.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-slate-900 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-4 gap-12 mb-16">
            <div className="col-span-2">
              <div className="flex items-center gap-2 mb-8">
                <div className="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                  <CreditCard className="text-white w-6 h-6" />
                </div>
                <span className="text-2xl font-bold tracking-tight">Payhub</span>
              </div>
              <p className="text-slate-400 max-w-md mb-8">
                The most reliable payment gateway for African businesses. 
                Built for speed, security, and scalability.
              </p>
            </div>
            <div>
              <h4 className="font-bold mb-6 text-white">Product</h4>
              <ul className="space-y-4 text-slate-400">
                <li><Link to="/pricing" className="hover:text-white transition-colors">Pricing</Link></li>
                <li><a href="#" className="hover:text-white transition-colors">Payment Pages</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Invoices</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-bold mb-6 text-white">Resources</h4>
              <ul className="space-y-4 text-slate-400">
                <li><Link to="/docs" className="hover:text-white transition-colors">Documentation</Link></li>
                <li><Link to="/api-reference" className="hover:text-white transition-colors">API Reference</Link></li>
                <li><Link to="/support" className="hover:text-white transition-colors">Support Center</Link></li>
                <li><Link to="/blog" className="hover:text-white transition-colors">Blog</Link></li>
              </ul>
            </div>
            <div>
              <h4 className="font-bold mb-6 text-white">Legal</h4>
              <ul className="space-y-4 text-slate-400">
                <li><Link to="/privacy" className="hover:text-white transition-colors">Privacy Policy</Link></li>
                <li><Link to="/terms" className="hover:text-white transition-colors">Terms of Service</Link></li>
              </ul>
            </div>
          </div>
          <div className="pt-8 border-t border-slate-800 flex flex-col md:row justify-between items-center gap-4 text-slate-500 text-sm">
            <p>© 2026 Payhub. All rights reserved.</p>
            <div className="flex gap-8">
              <a href="#" className="hover:text-white">Privacy Policy</a>
              <a href="#" className="hover:text-white">Terms of Service</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
