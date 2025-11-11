@extends('layouts.default')

@section('title', 'ThePlugs Framework')

@section('content')
<Header />

<Hero />

<section class="features" id="features">
    <div class="section-header">
        <h2>Why Theplugs?</h2>
        <p>Built for modern PHP development with performance and simplicity at its core</p>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <span class="feature-icon">⚡</span>
            <h3>Lightning Fast</h3>
            <p>Optimized routing and minimal overhead ensure your applications run at peak performance with response
                times under 10ms.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🔒</span>
            <h3>Secure by Default</h3>
            <p>Built-in protection against XSS, CSRF, and SQL injection. Security is baked in, not bolted on.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🧩</span>
            <h3>Modular Architecture</h3>
            <p>Plug in only what you need. Keep your application lean and maintainable with our component system.
            </p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">📦</span>
            <h3>Zero Config</h3>
            <p>Get started in seconds with sensible defaults. Configure only when you need to customize.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🎯</span>
            <h3>Developer Friendly</h3>
            <p>Clean, intuitive API that makes sense. Comprehensive documentation and helpful error messages.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🚀</span>
            <h3>Modern PHP</h3>
            <p>Built for PHP 8.2+ with full type safety, attributes, and all the latest language features.</p>
        </div>
    </div>
</section>

<section class="code-section" id="docs">
    <div class="code-container">
        <div class="code-info">
            <h2>Simple & Elegant</h2>
            <p>Write clean, expressive code that's easy to read and maintain. Theplugs makes complex tasks simple.
            </p>
            <p>Our intuitive API design means less boilerplate and more productivity. Get your ideas into production
                faster.</p>
        </div>
        <div class="code-box">
            <pre><span class="comment">// Create a new route</span>
<span class="keyword">Route</span>::get(<span class="string">'/api/users'</span>, <span class="keyword">function</span>() {
    <span class="keyword">return</span> User::all();
});

<span class="comment">// With middleware</span>
<span class="keyword">Route</span>::post(<span class="string">'/api/posts'</span>, <span class="keyword">function</span>() {
    <span class="keyword">return</span> Post::create(Request::all());
})->middleware(<span class="string">'auth'</span>);

<span class="comment">// Database queries made easy</span>
<span class="keyword">$users</span> = DB::table(<span class="string">'users'</span>)
    ->where(<span class="string">'active'</span>, <span class="keyword">true</span>)
    ->orderBy(<span class="string">'created_at'</span>)
    ->get();</pre>
        </div>
    </div>
</section>

<section class="stats">
    <div class="section-header">
        <h2>Trusted by Developers</h2>
        <p>Join thousands of developers building amazing applications with Theplugs</p>
    </div>
    <div class="stats-grid">
        <div class="stat-item">
            <h3>50K+</h3>
            <p>Active Developers</p>
        </div>
        <div class="stat-item">
            <h3>15K+</h3>
            <p>GitHub Stars</p>
        </div>
        <div class="stat-item">
            <h3>99.9%</h3>
            <p>Uptime</p>
        </div>
        <div class="stat-item">
            <h3>24/7</h3>
            <p>Support</p>
        </div>
    </div>
</section>

<section class="cta-section" id="community">
    <h2>Ready to Get Started?</h2>
    <p>Join our growing community and start building amazing PHP applications today. It's free and open source.</p>
    <div class="cta-buttons">
        <a href="#" class="cta-button">Get Started Now</a>
        <a href="#" class="btn-secondary">View Documentation</a>
    </div>
</section>

<Footer />
@endsection

@push('scripts')
<script>
    console.log('Home page loaded');
</script>
@endpush