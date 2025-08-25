<?php

// Simple test to debug your view issue
// Run this script to see what's happening

echo "=== PLUGS VIEW DEBUG TEST ===\n\n";

// 1. Check file system first
echo "1. File System Check:\n";
echo "--------------------\n";

$possiblePaths = [
    __DIR__ . '/resources/views/about.plug.php',
    __DIR__ . '/views/about.plug.php',
    __DIR__ . '/app/views/about.plug.php',
    'resources/views/about.plug.php',
    'views/about.plug.php'
];

$foundFiles = [];
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $foundFiles[] = realpath($path);
        echo "✓ FOUND: $path\n";
        echo "  Real path: " . realpath($path) . "\n";
        echo "  Size: " . filesize($path) . " bytes\n";
        
        // Show first few lines
        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        echo "  First 2 lines:\n";
        for ($i = 0; $i < min(2, count($lines)); $i++) {
            echo "    " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
        }
        echo "\n";
    } else {
        echo "✗ Missing: $path\n";
    }
}

if (empty($foundFiles)) {
    echo "\n❌ NO about.plug.php FILES FOUND!\n";
    echo "Please create the file first. Example:\n\n";
    echo "File: resources/views/about.plug.php\n";
    echo "Content:\n";
    echo "<h1>{{ \$title ?? 'About Page' }}</h1>\n";
    echo "<p>{{ \$message ?? 'Welcome to the about page!' }}</p>\n\n";
    exit(1);
}

// 2. Test your view system
echo "2. Testing View System:\n";
echo "-----------------------\n";

try {
    // Try to include your autoloader
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    } elseif (file_exists(__DIR__ . '/../../autoload.php')) {
        require_once __DIR__ . '/../../autoload.php';
    } else {
        echo "❌ Could not find autoloader\n";
        echo "Please run from your project root or adjust the path\n";
        exit(1);
    }

    echo "✓ Autoloader loaded\n";

    // Create a simple view finder manually
    use Plugs\View\ViewFinder;
    use Plugs\View\Engines\EngineResolver;
    use Plugs\View\Compiler\ViewCompiler;
    use Plugs\View\View;

    echo "✓ Classes available\n";

    // Set up manually to test
    $finder = new ViewFinder();
    
    // Add the paths where we found files
    foreach ($foundFiles as $file) {
        $dir = dirname($file);
        $finder->addPath($dir);
        echo "✓ Added path: $dir\n";
    }
    
    // Ensure extensions are set
    $finder->addExtension('.plug.php');
    $finder->addExtension('.php');
    $finder->addExtension('.html');
    
    echo "✓ Extensions added: " . implode(', ', $finder->getExtensions()) . "\n";
    
    // Create resolver and compiler
    $resolver = new EngineResolver();
    $compiler = new ViewCompiler();
    
    // Configure View class
    View::setFinder($finder);
    View::setEngineResolver($resolver);
    View::setCompiler($compiler);
    
    echo "✓ View system configured\n";

    // Test finding the view
    echo "\n3. Finding 'about' view:\n";
    echo "------------------------\n";
    
    try {
        $path = $finder->find('about');
        echo "✓ View found at: $path\n";
        
        // Test creating view
        $view = View::make('about', ['title' => 'Test Title', 'message' => 'Test Message']);
        echo "✓ View instance created\n";
        
        // Test rendering
        $output = $view->render();
        echo "✓ View rendered successfully\n";
        echo "Output length: " . strlen($output) . " characters\n";
        echo "First 200 chars:\n" . substr($output, 0, 200) . "\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }

} catch (Exception $e) {
    echo "❌ Setup error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";