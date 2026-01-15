@extends('layouts.default')

@section('title', 'About ThePlugs Framework')

@section('content')
<Header />

<section class="about-section" id="about">
    <div class="about-container">
        <div class="about-info">
            <h2>About ThePlugs Framework</h2>
            <p>ThePlugs is a modern PHP framework designed for developers who value performance, simplicity, and
                flexibility. Built from the ground up with the latest PHP features, ThePlugs empowers you to create
                robust web applications with ease.</p>
            <p>With a focus on clean code, modular architecture, and developer experience, ThePlugs provides all the
                tools you need to build scalable applications without the bloat. Whether you're building a small
                website or a complex enterprise application, ThePlugs has you covered.</p>
        </div>
        <div class="about-image">
            <img src="{{ asset('assets/images/about-illustration.png') }}" alt="About ThePlugs Framework">
        </div>
    </div>
</section>
@endsection