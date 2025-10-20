@extends('layouts.default')

@section('title', 'ThePlugs Framework')

@push('styles')
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html,
    body {
        overflow-x: hidden;
        width: 100%;
    }

    body {
        background: #f8f9fa;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 60px 40px;
    }

    .content-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 80px;
        align-items: center;
    }

    .left-section {
        padding-right: 40px;
    }

    .version-badge {
        display: inline-block;
        background: #e9ecef;
        color: #495057;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 30px;
    }

    .main-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 30px;
        line-height: 1.2;
    }

    .subtitle {
        font-size: 1.125rem;
        color: #6c757d;
        line-height: 1.7;
        margin-bottom: 40px;
    }

    .action-list {
        list-style: none;
        margin-bottom: 40px;
    }

    .action-item {
        display: flex;
        align-items: center;
        margin-bottom: 24px;
        font-size: 1.125rem;
    }

    .action-item .circle {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #dee2e6;
        margin-right: 20px;
        flex-shrink: 0;
    }

    .action-link {
        color: #dc143c;
        text-decoration: none;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        transition: border-color 0.2s ease;
    }

    .action-link:hover {
        border-bottom-color: #dc143c;
    }

    .action-link::after {
        content: " ↗";
        font-size: 0.9em;
    }

    .btn-deploy {
        background: #212529;
        color: white;
        padding: 14px 32px;
        border-radius: 6px;
        border: none;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-block;
        text-decoration: none;
    }

    .btn-deploy:hover {
        background: #000;
        transform: translateY(-1px);
        color: white;
    }

    .right-section {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 500px;
        overflow: hidden;
    }

    .logo-text {
        font-size: 8rem;
        font-weight: 800;
        color: #dc143c;
        letter-spacing: -0.02em;
        line-height: 1.1;
        position: relative;
        z-index: 10;
        user-select: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .version-number {
        font-size: 10rem;
        font-weight: 900;
        color: #14b8a6;
        line-height: 1;
        text-shadow: 0 4px 20px rgba(20, 184, 166, 0.3);
    }

    .geometric-shape {
        position: absolute;
        opacity: 0.15;
        z-index: 1;
        pointer-events: none;
    }

    .shape-1 {
        width: 200px;
        height: 200px;
        background: linear-gradient(135deg, #ffc0cb 0%, #ffb6c1 100%);
        clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
        top: 20px;
        right: 20px;
        animation: float 6s ease-in-out infinite;
    }

    .shape-2 {
        width: 180px;
        height: 180px;
        background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        clip-path: polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%);
        bottom: 20px;
        left: 20px;
        animation: float 8s ease-in-out infinite reverse;
    }

    .shape-3 {
        width: 150px;
        height: 150px;
        background: linear-gradient(135deg, #14b8a6 0%, #5eead4 100%);
        border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        top: 50%;
        left: 10px;
        transform: translateY(-50%);
        animation: morph 10s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-15px) rotate(5deg);
        }
    }

    @keyframes morph {

        0%,
        100% {
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            transform: translateY(0px);
        }

        50% {
            border-radius: 70% 30% 30% 70% / 70% 70% 30% 30%;
            transform: translateY(-10px);
        }
    }

    /* Tablet and smaller desktops */
    @media (max-width: 1200px) {
        .content-wrapper {
            gap: 60px;
        }

        .logo-text {
            font-size: 6rem;
        }

        .version-number {
            font-size: 9rem;
        }

        .shape-1 {
            width: 160px;
            height: 160px;
        }

        .shape-2 {
            width: 140px;
            height: 140px;
        }

        .shape-3 {
            width: 120px;
            height: 120px;
        }
    }

    /* Tablets */
    @media (max-width: 992px) {
        .content-wrapper {
            grid-template-columns: 1fr;
            gap: 60px;
        }

        .left-section {
            padding-right: 0;
            text-align: center;
        }

        .right-section {
            min-height: 350px;
        }

        .logo-text {
            font-size: 5rem;
        }

        .version-number {
            font-size: 6rem;
        }

        .main-title {
            font-size: 2rem;
        }

        .action-list {
            display: inline-block;
            text-align: left;
        }

        .shape-1 {
            width: 140px;
            height: 140px;
            top: 10px;
            right: 10px;
        }

        .shape-2 {
            width: 120px;
            height: 120px;
            bottom: 10px;
            left: 10px;
        }

        .shape-3 {
            width: 100px;
            height: 100px;
            left: -30px;
        }
    }

    /* Mobile devices */
    @media (max-width: 768px) {
        .main-container {
            padding: 40px 20px;
        }

        .logo-text {
            font-size: 4rem;
        }

        .version-number {
            font-size: 6rem;
        }

        .main-title {
            font-size: 1.75rem;
        }

        .subtitle {
            font-size: 1rem;
        }

        .action-item {
            font-size: 1rem;
        }

        .right-section {
            min-height: 250px;
        }
    }

    /* Small mobile devices */
    @media (max-width: 576px) {
        .main-container {
            padding: 30px 16px;
        }

        .content-wrapper {
            gap: 40px;
        }

        .logo-text {
            font-size: 3rem;
        }

        .version-number {
            font-size: 4.5rem;
        }

        .main-title {
            font-size: 1.5rem;
        }

        .subtitle {
            font-size: 0.95rem;
            margin-bottom: 30px;
        }

        .action-item {
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .action-item .circle {
            width: 10px;
            height: 10px;
            margin-right: 16px;
        }

        .btn-deploy {
            padding: 12px 28px;
            font-size: 0.95rem;
        }

        .right-section {
            min-height: 200px;
        }

        .shape-1 {
            width: 100px;
            height: 100px;
            top: 20px;
            right: -10px;
        }

        .shape-2 {
            width: 90px;
            height: 90px;
            bottom: 20px;
            left: -10px;
        }

        .shape-3 {
            width: 70px;
            height: 70px;
            left: -20px;
        }
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <!-- Left Section -->
    <div class="left-section">
        <div class="version-badge">Version 1.0</div>

        <h1 class="main-title">Let's get started</h1>

        <p class="subtitle">
            ThePlugs has an incredibly rich ecosystem. We suggest starting with the following.
        </p>

        <ul class="action-list">
            <li class="action-item">
                <span class="circle"></span>
                <span>Read the <a href="#" class="action-link">Documentation</a></span>
            </li>
            <li class="action-item">
                <span class="circle"></span>
                <span>Watch video tutorials at <a href="#" class="action-link">PlugCasts</a></span>
            </li>
        </ul>

        <a href="#" class="btn-deploy">Deploy now</a>
    </div>

    <!-- Right Section -->
    <div class="right-section">
        <div class="geometric-shape shape-1"></div>
        <div class="geometric-shape shape-2"></div>
        <div class="geometric-shape shape-3"></div>
        <div class="logo-text">ThePlugs</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    console.log('Home page loaded');
</script>
@endpush