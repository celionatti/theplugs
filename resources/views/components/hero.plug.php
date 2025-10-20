@php

@endphp

<!-- Hero Section -->
<section class="pt-32 pb-20 px-6 lg:px-8 min-h-screen flex items-center geometric-bg">
    <div class="max-w-7xl mx-auto w-full">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <!-- Left Content -->
            <div class="fade-in">
                <h5 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-8 leading-tight">
                    Let's Get Started
                </h5>
                <p class="text-xl text-gray-600 mb-12 leading-relaxed max-w-lg">
                    The PHP Framework for building modern web applications with elegant syntax, powerful features, and a vibrant ecosystem.
                </p>

                <div class="space-y-6 mb-12">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-red-500 flex items-center justify-center mt-1">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                        </div>
                        <div>
                            <p class="text-gray-900 font-medium">Read the <a href="#" class="text-red-600 hover:text-red-700 underline">Documentation</a></p>
                        </div>
                    </div>
                    <!-- <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-red-500 flex items-center justify-center mt-1">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                        </div>
                        <div>
                            <p class="text-gray-900 font-medium">Watch video tutorials at <a href="#" class="text-red-600 hover:text-red-700 underline">Plugcasts</a></p>
                        </div>
                    </div> -->
                </div>

                <button class="px-8 py-4 bg-gray-900 text-white rounded-md hover:bg-gray-800 transition font-medium text-lg">
                    Deploy now
                </button>
            </div>

            <!-- Right Geometric Design -->
            <div class="hidden lg:block relative">
                <svg viewBox="0 0 600 600" class="w-full h-auto">
                    <!-- Background circles -->
                    <circle cx="300" cy="300" r="280" fill="none" stroke="#fecaca" stroke-width="1" opacity="0.3" />
                    <circle cx="300" cy="300" r="240" fill="none" stroke="#fed7aa" stroke-width="1" opacity="0.3" />

                    <!-- Main geometric shapes -->
                    <g transform="translate(300, 300)">
                        <!-- Pink triangular sections -->
                        <path d="M -150,-100 L -50,-150 L 50,-100 L -150,-100 Z" fill="#fecaca" opacity="0.6" />
                        <path d="M 50,-100 L 150,-50 L 100,50 L 50,-100 Z" fill="#fecaca" opacity="0.7" />
                        <path d="M -150,100 L -50,150 L -100,50 L -150,100 Z" fill="#fecaca" opacity="0.6" />

                        <!-- Yellow/gold sections -->
                        <path d="M -100,50 L 0,100 L -50,150 L -100,50 Z" fill="#fcd34d" opacity="0.8" />
                        <path d="M 0,-150 L 100,-100 L 50,-50 L 0,-150 Z" fill="#fcd34d" opacity="0.7" />
                        <path d="M -150,-50 L -100,0 L -150,50 L -150,-50 Z" fill="#fcd34d" opacity="0.8" />

                        <!-- Black accent sections -->
                        <path d="M -50,150 L 50,150 L 0,100 L -50,150 Z" fill="#1f2937" opacity="0.9" />
                        <path d="M 100,50 L 150,100 L 100,150 L 100,50 Z" fill="#1f2937" opacity="0.9" />

                        <!-- Grid lines overlay -->
                        <line x1="-200" y1="-200" x2="200" y2="200" stroke="#374151" stroke-width="0.5" opacity="0.3" />
                        <line x1="-200" y1="200" x2="200" y2="-200" stroke="#374151" stroke-width="0.5" opacity="0.3" />
                        <line x1="-200" y1="0" x2="200" y2="0" stroke="#374151" stroke-width="0.5" opacity="0.3" />
                        <line x1="0" y1="-200" x2="0" y2="200" stroke="#374151" stroke-width="0.5" opacity="0.3" />

                        <!-- Additional triangular details -->
                        <polygon points="0,0 40,40 -40,40" fill="#ef4444" opacity="0.7" />
                        <polygon points="0,-50 30,-80 -30,-80" fill="#06b6d4" opacity="0.6" />
                    </g>

                    <!-- Top text (large) -->
                    <text x="20" y="80" font-size="100" font-weight="bold" fill="#ef4444" font-family="sans-serif">THEPLUGS</text>
                    <text x="40" y="150" font-size="70" font-weight="light" fill="#fecaca" font-family="sans-serif">Framework</text>
                </svg>
            </div>
        </div>
    </div>
</section>