@extends('layouts.default')

@section('title', 'ThePlugs Framework')

@push('styles')
<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in {
        animation: fadeIn 0.6s ease-out forwards;
    }

    .geometric-bg {
        background-image:
            linear-gradient(135deg, rgba(6, 182, 212, 0.05) 0%, rgba(220, 38, 38, 0.05) 100%);
    }
</style>
@endpush

@section('content')
<Hero />

<!-- Features Section -->
<section class="py-24 px-6 lg:px-8 bg-gray-50">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Why developers choose ThePlugs</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">A framework designed to help you build applications faster with elegant syntax and powerful features.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-lg border border-gray-200">
                <div class="text-3xl mb-4">⚡</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Lightning Fast</h3>
                <p class="text-gray-600">Optimized for speed with efficient routing and minimal overhead. Build high-performance applications that scale.</p>
            </div>

            <div class="bg-white p-8 rounded-lg border border-gray-200">
                <div class="text-3xl mb-4">🎨</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Elegant Syntax</h3>
                <p class="text-gray-600">Write clean, expressive code that's easy to understand and maintain. Coding should be a joy, not a chore.</p>
            </div>

            <div class="bg-white p-8 rounded-lg border border-gray-200">
                <div class="text-3xl mb-4">🔒</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Secure</h3>
                <p class="text-gray-600">Built-in security features protect your application from common vulnerabilities right out of the box.</p>
            </div>

            <div class="bg-white p-8 rounded-lg border border-gray-200">
                <div class="text-3xl mb-4">📦</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Rich Ecosystem</h3>
                <p class="text-gray-600">Access thousands of packages and extensions to add functionality to your applications effortlessly.</p>
            </div>

            <div class="bg-white p-8 rounded-lg border border-gray-200">
                <div class="text-3xl mb-4">🧪</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Testing Made Easy</h3>
                <p class="text-gray-600">Built-in testing support helps you write reliable applications with confidence and ease.</p>
            </div>

            <div class="bg-white p-8 rounded-lg border border-gray-200">
                <div class="text-3xl mb-4">👥</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Active Community</h3>
                <p class="text-gray-600">Join thousands of developers who are building amazing applications and helping each other succeed.</p>
            </div>
        </div>
    </div>
</section>

<!-- Code Example Section -->
<section class="py-24 px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Simple, powerful syntax</h2>
            <p class="text-lg text-gray-600">Write less code to accomplish more</p>
        </div>

        <div class="bg-gray-900 rounded-xl p-8 shadow-2xl">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <pre class="text-sm text-gray-300 overflow-x-auto"><code><span class="text-purple-400">use</span> <span class="text-gray-300">Plugs\Facades\Route;</span>
<span class="text-purple-400">use</span> <span class="text-gray-300">Plugs\Http\ResponseFactory;</span>

<span class="text-gray-300">Route::</span><span class="text-cyan-400">get</span><span class="text-gray-300">(</span><span class="text-green-400">'/api/users'</span><span class="text-gray-300">,</span> <span class="text-purple-400">function</span><span class="text-gray-300">() {</span>
    <span class="text-purple-400">return</span> <span class="text-gray-300">ResponseFactory::</span><span class="text-red-400">json</span><span class="text-gray-300">([</span>
        <span class="text-green-400">'users'</span> <span class="text-gray-300">=> User::</span><span class="text-cyan-400">all</span><span class="text-gray-300">()</span>
    <span class="text-gray-300">],</span> <span class="text-orange-400">200</span><span class="text-gray-300">);</span>
<span class="text-gray-300">});</span></code></pre>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-24 px-6 lg:px-8 bg-gray-900 text-white">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-4xl font-bold mb-6">Start building amazing applications</h2>
        <p class="text-xl text-gray-300 mb-10">Get up and running in minutes with ThePlugs Framework</p>

        <div class="bg-gray-800 rounded-lg p-6 mb-10 inline-block">
            <code class="text-cyan-400 text-lg">composer create-project theplugs/theplugs</code>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button class="px-8 py-4 bg-white text-gray-900 rounded-md hover:bg-gray-100 transition font-medium text-lg">
                Read Documentation
            </button>
            <button class="px-8 py-4 bg-red-600 text-white rounded-md hover:bg-red-700 transition font-medium text-lg">
                Watch Video Tutorials
            </button>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    console.log('Home page loaded');
</script>
@endpush