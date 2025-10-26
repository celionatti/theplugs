@extends('layouts.default')

@section('title', 'ThePlugs Framework')

@section('content')
<Header/>

<Hero/>
<!-- Features Section -->
<section class="features">
    <div class="container">
        <h2 class="section-title">Why ThePlugs?</h2>
        <div class="row">
            <div class="col-md-4 feature">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3>Lightning Fast</h3>
                <p>Built for performance with optimized code and efficient architecture that scales with your needs.</p>
            </div>
            <div class="col-md-4 feature">
                <div class="feature-icon">
                    <i class="fas fa-puzzle-piece"></i>
                </div>
                <h3>Modular Design</h3>
                <p>Plug and play components that work seamlessly together. Customize your stack with ease.</p>
            </div>
            <div class="col-md-4 feature">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Vibrant Community</h3>
                <p>Join thousands of developers building amazing projects with ThePlugs framework.</p>
            </div>
        </div>
    </div>
</section>

<!-- Code Showcase Section -->
<section class="code-showcase">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="section-title text-start text-white">Clean Code by Design</h2>
                <p class="text-light mb-4">ThePlugs values simplicity and elegance. Our intuitive syntax makes development a pleasure while maintaining powerful functionality.</p>
                <ul class="text-light">
                    <li>Modular component system</li>
                    <li>Intuitive API design</li>
                    <li>Built-in testing suite</li>
                    <li>Comprehensive documentation</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="code-block">
                    <code>
                        <span style="color: #ff79c6">const</span> <span style="color: #8be9fd">app</span> = <span style="color: #50fa7b">new</span> <span style="color: #8be9fd">ThePlugs</span>();<br><br>

                        <span style="color: #8be9fd">app</span>.<span style="color: #50fa7b">plug</span>(<span style="color: #f1fa8c">'auth'</span>);<br>
                        <span style="color: #8be9fd">app</span>.<span style="color: #50fa7b">plug</span>(<span style="color: #f1fa8c">'database'</span>);<br>
                        <span style="color: #8be9fd">app</span>.<span style="color: #50fa7b">plug</span>(<span style="color: #f1fa8c">'api'</span>);<br><br>

                        <span style="color: #8be9fd">app</span>.<span style="color: #50fa7b">start</span>();
                    </code>
                </div>
            </div>
        </div>
    </div>
</section>

<Footer/>
@endsection

@push('scripts')
<script>
    console.log('Home page loaded');
</script>
@endpush