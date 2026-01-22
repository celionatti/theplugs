<nav id="mainNav" class="navbar navbar-expand-lg fixed-top navbar-glass">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">
            <span class="brand-font">The Plugs</span>
        </a>

        <!-- Right controls -->
        <div class="d-flex align-items-center gap-2 order-lg-2">
            <button id="themeToggle" class="btn btn-icon mx-2" aria-label="Toggle theme">
                🌙
            </button>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <!-- Menu -->
        <div class="collapse navbar-collapse order-lg-1" id="navMenu">
            <ul class="navbar-nav ms-lg-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#whats-new">What's New</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#code">Examples</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#docs">Documentation</a>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="btn btn-primary btn-sm px-4" href="#get-started">Get Started</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
