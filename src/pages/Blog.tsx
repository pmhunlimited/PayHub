import React from 'react';
import { Search } from 'lucide-react';

export default function Blog() {
  const [posts, setPosts] = React.useState<any[]>([]);
  const [searchQuery, setSearchQuery] = React.useState('');

  React.useEffect(() => {
    fetch('/api/blog').then(res => res.json()).then(setPosts);
  }, []);

  // SEO Meta Tags
  React.useEffect(() => {
    if (posts.length > 0) {
      const firstPost = posts[0];
      document.title = firstPost.meta_title || 'Payhub Blog - Latest Insights in Fintech';
      const metaDescription = document.querySelector('meta[name="description"]');
      if (metaDescription) {
        metaDescription.setAttribute('content', firstPost.meta_description || 'Stay updated with the latest trends, news, and insights in the fintech industry with Payhub.');
      }
    }
  }, [posts]);

  const filteredPosts = posts.filter(post => 
    post.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
    post.content.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="min-h-screen bg-slate-50 py-24 px-4">
      <div className="max-w-5xl mx-auto">
        <div className="text-center mb-16">
          <h1 className="text-5xl font-bold text-slate-900 mb-6">Payhub Blog</h1>
          <p className="text-slate-500 text-lg max-w-2xl mx-auto mb-10">Insights, updates, and stories from the team building the future of global payments.</p>
          
          <div className="relative max-w-xl mx-auto">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
            <input 
              type="text"
              placeholder="Search articles..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-12 pr-4 py-4 bg-white border border-slate-200 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
            />
          </div>
        </div>
        
        <div className="grid md:grid-cols-2 gap-12">
          {filteredPosts.map((post, i) => (
            <article key={i} className="bg-white rounded-3xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-md transition-all">
              <img src={`https://picsum.photos/seed/${post.slug}/800/400`} alt={post.title} className="w-full h-48 object-cover" referrerPolicy="no-referrer" />
              <div className="p-8">
                <h2 className="text-2xl font-bold text-slate-900 mb-4 hover:text-indigo-600 transition-colors cursor-pointer">
                  {post.title}
                </h2>
                <p className="text-slate-600 mb-6 line-clamp-3">{post.excerpt}</p>
                <div className="flex items-center justify-between">
                  <span className="text-sm text-slate-400">{new Date(post.created_at).toLocaleDateString()}</span>
                  <button className="text-indigo-600 font-bold text-sm">Read more →</button>
                </div>
              </div>
            </article>
          ))}
          {filteredPosts.length === 0 && (
            <div className="col-span-2 text-center py-20 bg-white rounded-3xl border border-dashed border-slate-300">
              <p className="text-slate-500">No blog posts found matching your search.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
