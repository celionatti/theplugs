@extends('layouts.docs')

@section('content')
<div data-aos="fade-up">
    <h1>SPA Bridge</h1>
    <p class="lead text-muted">
        The SPA Bridge is a lightweight functionality that intercepts internal links and form submissions to load
        content asynchronously.
    </p>

    <h2 id="how-it-works">How it Works</h2>
    <p>
        When you navigate to a link marked with SPA attributes, the framework fetches the next page in the background,
        extracts the <code>#app-content</code> (or a custom selector), and replaces it on the current page without a
        full reload.
    </p>

    <h2 id="usage">Usage</h2>

    <h3>Links</h3>
    <p>To enable SPA navigation for a link, simply add the <code>data-spa="true"</code> attribute:</p>
    <pre
        class="bg-dark text-white p-3 rounded"><code>&lt;a href="/about" data-spa="true"&gt;Navigate to About&lt;/a&gt;</code></pre>

    <h3>Forms</h3>
    <p>The bridge also works with forms. When submitted, the framework will send the data via AJAX and update the target
        content area with the response:</p>
    <pre class="bg-dark text-white p-3 rounded"><code>&lt;form action="/search" method="POST" data-spa="true"&gt;
    &lt;input type="text" name="query" /&gt;
    &lt;button type="submit"&gt;Search&lt;/button&gt;
&lt;/form&gt;</code></pre>

    <h2 id="custom-targets">Custom Targets</h2>
    <p>By default, the SPA bridge updates the <code>#app-content</code> element. You can specify a different target
        using <code>data-spa-target</code>:</p>
    <pre class="bg-dark text-white p-3 rounded"><code>&lt;a href="/sidebar-update" data-spa="true" data-spa-target="#sidebar"&gt;
    Update Sidebar Only
&lt;/a&gt;</code></pre>

    <div class="alert alert-warning border-0 shadow-sm">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Note:</strong> The server must be prepared to return partial HTML when requested with the
        <code>X-Plugs-SPA</code> header.
    </div>

    <h2 id="benefits">Benefits</h2>
    <ul>
        <li><strong>Performance:</strong> Only fragments of the page are transmitted and rendered.</li>
        <li><strong>State Persistence:</strong> Global JavaScript state (like audio players or persistent sidebars)
            remains intact.</li>
        <li><strong>Smoothness:</strong> Eliminates the "white flash" associated with full page reloads.</li>
    </ul>
</div>
@endsection
