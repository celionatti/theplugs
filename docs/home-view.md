<?php
declare(strict_types=1);
?>

@extends('layouts.default')

{{-- Set page-specific SEO --}}
@title('Home - Your Tagline Here')
@description('Welcome to our website. Discover amazing products and services tailored for you.')
@keywords(['home', 'products', 'services'])

@section('content')
    <h1>{{ $title }}</h1>
    <div class="container">
        <h1>Welcome to {{ $siteName }}</h1>
        <p>Your content here...</p>
    </div>
@endsection
