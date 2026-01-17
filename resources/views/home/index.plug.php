@extends('layouts.default')

@section('title', 'ThePlugs Framework')

@section('content')
<Header />

<Hero />

<New />

<Features />

<Code />

<Docs />

<Footer />
@endsection

@push('scripts')
<script>
    console.log('Home page loaded');
</script>
@endpush