@extends('layouts.docs')

@section('content')
<div data-aos="fade-up">
    <h1 class="display-4 mb-4">Introduction to Modern Plugs</h1>

    <p class="lead text-muted">
        Plugs v3.0 introduces a seamless bridge between traditional server-side rendering and modern single-page
        application feel.
    </p>

    <div class="row g-4 my-5">
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm p-3">
                <div class="card-body">
                    <div class="h3 text-primary mb-3">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h4>The SPA Bridge</h4>
                    <p class="text-muted small">
                        Navigate between pages without full browser reloads. Just add <code>data-spa="true"</code> to
                        your links.
                    </p>
                    <a href="/docs/spa" class="btn btn-outline-primary btn-sm" data-spa="true">Learn more</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm p-3">
                <div class="card-body">
                    <div class="h3 text-success mb-3">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4>Reactive Components</h4>
                    <p class="text-muted small">
                        Build interactive parts of your page that update instantly via AJAX, with automatic state
                        management.
                    </p>
                    <a href="/docs/reactive" class="btn btn-outline-success btn-sm" data-spa="true">Learn more</a>
                </div>
            </div>
        </div>
    </div>

    <h2>Getting Started</h2>
    <p>
        The core philosophy of Plugs is <strong>Simplicity without Sacrifice</strong>.
        You don't need to learn a complex JavaScript framework to build highly interactive web applications.
        With Plugs, you write standard PHP and Blade-like templates, and the framework handles the "magic" of
        interactivity.
    </p>

    <div class="alert alert-info bg-light border-0 shadow-sm d-flex align-items-center">
        <i class="fas fa-info-circle me-3 text-primary h4 mb-0"></i>
        <div>
            <strong>Did you know?</strong> Plugs v3.0 uses the <code>Fetch API</code> for all SPA transitions, making it
            lightweight and lightning fast.
        </div>
    </div>
</div>
@endsection
