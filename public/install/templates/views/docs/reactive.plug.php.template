@extends('layouts.docs')

@section('content')
<div data-aos="fade-up">
    <h1>Reactive Components</h1>
    <p class="lead text-muted">
        Reactive Components are PHP classes that manage their own state and can be updated instantly via AJAX using
        simple HTML directives.
    </p>

    <h2 id="defining-components">Defining Components</h2>
    <p>
        To create a reactive component, extend the <code>Plugs\View\ReactiveComponent</code> class. Define public
        properties to represent your component's state.
    </p>

    <pre class="bg-dark text-white p-3 rounded"><code>namespace App\Components;

use Plugs\View\ReactiveComponent;

class MyComponent extends ReactiveComponent {
    public int $count = 10;

    public function increment() {
        $this->count++;
    }

    public function render() {
        return 'components.my_component';
    }
}</code></pre>

    <h2 id="view-directives">Interactivity Directives</h2>
    <p>
        Plugs provides special attributes to trigger server-side methods directly from your HTML:
    </p>

    <ul>
        <li><code>p-click</code>: Triggered on element click.</li>
        <li><code>p-change</code>: Triggered on input value change.</li>
        <li><code>p-submit</code>: Triggered on form submission.</li>
    </ul>

    <h3>Example Template</h3>
    <pre class="bg-dark text-white p-3 rounded"><code>&lt;div&gt;
    &lt;span&gt;Value: {!! $count !!}&lt;/span&gt;
    &lt;button p-click="increment"&gt;+&lt;/button&gt;
&lt;/div&gt;</code></pre>

    <h2 id="state-management">State Management</h2>
    <p>
        The framework automatically serializes your component's state into the HTML. When an action is triggered, the
        state is sent back to the server, hydrated into the component class, the method is executed, and the updated
        HTML is returned.
    </p>

    <div class="alert alert-success border-0 shadow-sm">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Type Safety:</strong> Plugs uses PHP Reflection to ensure state is correctly cast to the property types
        (int, bool, float) during hydration.
    </div>
</div>
@endsection
