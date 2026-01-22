<style>
    .docs-sidebar {
        position: sticky;
        top: 2rem;
        height: calc(100vh - 4rem);
        overflow-y: auto;
    }

    .docs-sidebar .list-group-item a:hover {
        color: var(--primary) !important;
    }
</style>

<!-- Docs Sidebar -->
<div class="docs-sidebar">
    <div class="list-group list-group-flush">
        <div class="list-group-item bg-transparent border-0 ps-0">
            <h6 class="text-uppercase fw-bold small text-muted mb-3">Core</h6>
            <a href="/docs"
                class="d-flex align-items-center text-decoration-none mb-2 {{ request()->is('docs') ? 'text-primary fw-bold' : 'text-dark' }}">
                <i class="fas fa-rocket me-2 small"></i> Introduction
            </a>
            <a href="/docs/spa"
                class="d-flex align-items-center text-decoration-none mb-2 {{ request()->is('docs/spa') ? 'text-primary fw-bold' : 'text-dark' }}"
                data-spa=true>
                <i class="fas fa-bolt me-2 small"></i> SPA Bridge
            </a>
            <a href="/docs/reactive"
                class="d-flex align-items-center text-decoration-none mb-2 {{ request()->is('docs/reactive') ? 'text-primary fw-bold' : 'text-dark' }}"
                data-spa="true">
                <i class="fas fa-sync-alt me-2 small"></i> Reactive Components
            </a>
        </div>

        <div class="list-group-item bg-transparent border-0 ps-0 mt-4">
            <h6 class="text-uppercase fw-bold small text-muted mb-3">Advanced</h6>
            <a href="/docs/examples"
                class="d-flex align-items-center text-decoration-none mb-2 {{ request()->is('docs/examples') ? 'text-primary fw-bold' : 'text-dark' }}"
                data-spa="true">
                <i class="fas fa-laptop-code me-2 small"></i> Interactive Examples
            </a>
        </div>

        <div class="list-group-item bg-transparent border-0 ps-0 mt-4">
            <h6 class="text-uppercase fw-bold small text-muted mb-3">Links</h6>
            <a href="/" class="d-flex align-items-center text-dark text-decoration-none mb-2">
                <i class="fas fa-home me-2 small"></i> Back to Home
            </a>
            <a href="https://github.com/celionatti/plugs"
                class="d-flex align-items-center text-dark text-decoration-none mb-2" target="_blank">
                <i class="fab fa-github me-2 small"></i> GitHub
            </a>
        </div>
    </div>
</div>
