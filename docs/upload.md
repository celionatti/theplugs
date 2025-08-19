# ThePlugs PHP File Upload Class Documentation

A comprehensive, secure, and framework-agnostic file upload library for PHP 8.0+ with advanced image processing, validation, and optimization features.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Basic Usage](#basic-usage)
- [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Security](#security)
- [Error Handling](#error-handling)
- [Framework Integration](#framework-integration)
- [Examples](#examples)
- [Troubleshooting](#troubleshooting)

## Features

### ✅ Core Features

- **PSR-4 Compatible** - Follows PHP standards with proper namespacing
- **Framework-Agnostic** - Works with any PHP framework or vanilla PHP
- **Multiple File Support** - Handle single or multiple file uploads
- **Fluent API** - Chainable methods for easy configuration
- **Type Safety** - Strict types and comprehensive validation

### 🛡️ Security Features

- **MIME Type Validation** - Double-check file types using extension and MIME
- **Malicious Content Detection** - Scans for dangerous patterns
- **Directory Traversal Protection** - Prevents path manipulation attacks
- **Size Restrictions** - Configurable file size limits
- **Executable File Blocking** - Stops potentially harmful uploads

### 🖼️ Image Processing

- **Smart Compression** - Optimizes images without visible quality loss
- **Automatic Resizing** - Resize and crop images to specific dimensions
- **Format Conversion** - Convert between JPG, PNG, WebP, GIF
- **EXIF Handling** - Optional metadata preservation
- **Quality Control** - Configurable compression levels

### 📁 File Type Support

- **Documents**: PDF, DOCX, XLSX, TXT, CSV, RTF, ODT
- **Images**: JPG, PNG, WebP, GIF, SVG, BMP, TIFF
- **Audio**: MP3, WAV, OGG, FLAC, AAC, M4A
- **Video**: MP4, AVI, MKV, MOV, WMV, WebM
- **Archives**: ZIP, RAR, 7Z, TAR, GZ, BZ2

## Requirements

- **PHP**: 8.0 or higher
- **Extensions**:
  - `gd` (for image processing)
  - `fileinfo` (for MIME detection)
  - `mbstring` (recommended)
- **Memory**: 128MB+ recommended for image processing
- **Disk Space**: Adequate temp and storage space

## Installation

### Via Composer (if published)

```bash
composer require your-vendor/upload-class
```

### Manual Installation

1. Download the `Upload.php` file
2. Place it in your project directory
3. Include or autoload the class:

```php
// Direct include
require_once 'path/to/Upload.php';

// Or with autoloading
use Framework\Upload\Upload;
```

### Setup Directory Permissions

```bash
# Create upload directories
mkdir -p uploads/{documents,images,avatars,temp}

# Set proper permissions
chmod 755 uploads/
chmod 755 uploads/*
```

## Quick Start

Here's a simple example to get you started:

```php
<?php
use Framework\Upload\Upload;

// Upload a single document
try {
    $result = Upload::file($_FILES['document'])
        ->allowed(['pdf', 'docx', 'xlsx'])
        ->maxSize(5 * 1024 * 1024) // 5MB
        ->process()
        ->store('uploads/documents');
    
    echo "File uploaded: " . $result->getPath();
    
} catch (RuntimeException $e) {
    echo "Upload failed: " . $e->getMessage();
}
?>
```

## Basic Usage

### Single File Upload

```php
// Simple document upload
$result = Upload::file($_FILES['resume'])
    ->allowed(['pdf'])
    ->maxSize(2 * 1024 * 1024) // 2MB
    ->rename('unique')
    ->process()
    ->store('uploads/resumes');

// Get uploaded file info
$file = $result->getFile();
echo "Uploaded: {$file['stored_name']} ({$file['size']} bytes)";
```

### Multiple File Upload

```php
// Upload multiple images
$result = Upload::file($_FILES['gallery']) // $_FILES['gallery'] is array
    ->allowed(['jpg', 'png', 'webp'])
    ->maxSize(10 * 1024 * 1024) // 10MB per file
    ->process()
    ->store('uploads/gallery');

// Process all uploaded files
foreach ($result->getFiles() as $file) {
    echo "Image: {$file['stored_name']}\n";
}
```

### Image Processing

```php
// Upload and optimize avatar
$result = Upload::file($_FILES['avatar'])
    ->allowed(['jpg', 'png'])
    ->maxSize(5 * 1024 * 1024)
    ->compress(85)              // 85% quality
    ->resize(400, 400)          // Resize to 400x400
    ->convertTo('webp')         // Convert to WebP format
    ->process()                 // All processing happens here
    ->store('uploads/avatars');

$avatar = $result->getFile();
echo "Avatar saved as: {$avatar['stored_name']}";
```

## Advanced Usage

### Using File Type Groups

Instead of specifying individual extensions, use predefined groups:

```php
$result = Upload::file($_FILES['attachments'])
    ->allowed(['documents', 'images']) // Multiple groups
    ->maxSize(20 * 1024 * 1024)
    ->process()
    ->store('uploads/mixed');
```

Available groups:

- `documents` - PDF, DOC, DOCX, XLS, XLSX, etc.
- `images` - JPG, PNG, WebP, GIF, SVG, etc.
- `audio` - MP3, WAV, OGG, FLAC, etc.
- `video` - MP4, AVI, MKV, MOV, etc.
- `archives` - ZIP, RAR, 7Z, TAR, etc.

### Advanced Image Processing

```php
$result = Upload::file($_FILES['photo'])
    ->allowed(['images'])
    ->maxSize(50 * 1024 * 1024)
    ->compress(90)                    // High quality
    ->resize(1920, 1080, 'center')    // Resize with center crop
    ->preserveExif(true)              // Keep metadata
    ->convertTo('jpg')                // Convert to JPG
    ->process()
    ->store('uploads/photos');
```

### Custom Renaming Strategies

```php
// Unique names (default)
->rename('unique')  // Results in: upload_64f8a1b2c3d4e.jpg

// Slug-based names
->rename('slug')    // Results in: my-awesome-photo.jpg

// Keep original names
->rename('original') // Results in: My Awesome Photo.jpg
```

### Conditional Processing

```php
$upload = Upload::file($_FILES['file'])
    ->allowed(['documents', 'images'])
    ->maxSize(10 * 1024 * 1024);

// Add image-specific processing only for images
if ($this->isImageFile($_FILES['file'])) {
    $upload->compress(80)->resize(1200, 800);
}

$result = $upload->process()->store('uploads');
```

## API Reference

### Static Methods

#### `Upload::file(array $files)`

Creates a new Upload instance.

- **Parameters**: `$files` - $_FILES array (single or multiple files)
- **Returns**: `Upload` instance
- **Example**: `Upload::file($_FILES['document'])`

### Configuration Methods

#### `allowed(array $types)`

Set allowed file types or type groups.

- **Parameters**: `$types` - Array of extensions or group names
- **Returns**: `Upload` instance
- **Example**: `->allowed(['pdf', 'jpg', 'documents'])`

#### `maxSize(int $bytes)`

Set maximum file size in bytes.

- **Parameters**: `$bytes` - Maximum file size
- **Returns**: `Upload` instance
- **Example**: `->maxSize(5 * 1024 * 1024)` // 5MB

#### `rename(string $strategy)`

Set file renaming strategy.

- **Parameters**: `$strategy` - 'unique', 'slug', or 'original'
- **Returns**: `Upload` instance
- **Example**: `->rename('unique')`

### Image Processing Methods

#### `compress(int $quality)`

Set image compression quality.

- **Parameters**: `$quality` - Quality level (1-100)
- **Returns**: `Upload` instance
- **Example**: `->compress(85)`

#### `resize(int $width, int $height, string $cropMode = 'center')`

Resize images to specified dimensions.

- **Parameters**:
  - `$width` - Target width
  - `$height` - Target height
  - `$cropMode` - 'center', 'top', or 'bottom'
- **Returns**: `Upload` instance
- **Example**: `->resize(800, 600, 'center')`

#### `convertTo(string $format)`

Convert images to specified format.

- **Parameters**: `$format` - Target format ('jpg', 'png', 'webp', 'gif')
- **Returns**: `Upload` instance
- **Example**: `->convertTo('webp')`

#### `preserveExif(bool $preserve = true)`

Preserve or remove EXIF data.

- **Parameters**: `$preserve` - Whether to keep EXIF data
- **Returns**: `Upload` instance
- **Example**: `->preserveExif(false)`

### Processing Methods

#### `process()`

Process all files (validation + optimization).

- **Returns**: `Upload` instance
- **Throws**: `RuntimeException` on validation/processing errors
- **Note**: Must be called before `store()`

#### `store(string $path)`

Store processed files to destination.

- **Parameters**: `$path` - Storage directory path
- **Returns**: `UploadResult` instance
- **Example**: `->store('uploads/documents')`

### Result Methods (UploadResult)

#### `getFiles()`

Get array of all uploaded files.

- **Returns**: `array` of file information

#### `getFile()`

Get first uploaded file info.

- **Returns**: `array|null` of file information

#### `getPaths()`

Get array of all file paths.

- **Returns**: `array` of file paths

#### `getPath()`

Get first file path.

- **Returns**: `string|null` file path

#### `count()`

Get number of uploaded files.

- **Returns**: `int` file count

#### `isSuccess()`

Check if upload was successful.

- **Returns**: `bool` success status

## Configuration

### Using Predefined Configurations

The library includes several predefined configurations:

```php
// Basic configurations
$config = UploadConfig::documents(); // For documents
$config = UploadConfig::images();    // For images  
$config = UploadConfig::media();     // For audio/video
$config = UploadConfig::avatars();   // For profile pics

// Apply configuration
$result = Upload::file($_FILES['file'])
    ->allowed($config['allowed'])
    ->maxSize($config['maxSize'])
    // ... other config options
    ->process()
    ->store('uploads');
```

### Extended Configurations

```php
// E-commerce product images
$config = ExtendedUploadConfig::productImages();

// Blog post attachments  
$config = ExtendedUploadConfig::blogAttachments();

// Profile documents
$config = ExtendedUploadConfig::profileDocuments();

// Video thumbnails
$config = ExtendedUploadConfig::videoThumbnails();

// Message attachments
$config = ExtendedUploadConfig::messageAttachments();
```

### Custom Configuration

```php
class MyUploadConfig extends UploadConfig
{
    public static function newsArticles(): array
    {
        return [
            'allowed' => ['pdf', 'jpg', 'png', 'docx'],
            'maxSize' => 15 * 1024 * 1024, // 15MB
            'compress' => 85,
            'resize' => [1200, 900],
            'rename' => 'slug'
        ];
    }
}
```

## Security

### Built-in Security Features

1. **Double Validation**: Checks both file extension and MIME type
2. **Content Scanning**: Detects malicious patterns in file content
3. **Path Protection**: Prevents directory traversal attacks
4. **Executable Blocking**: Blocks potentially dangerous file types
5. **Size Limits**: Enforces maximum file size restrictions

### Security Best Practices

```php
// Always validate file types
$result = Upload::file($_FILES['file'])
    ->allowed(['jpg', 'png']) // Only allow specific types
    ->maxSize(2 * 1024 * 1024) // Reasonable size limits
    ->process()
    ->store('uploads/secure');

// Store uploads outside web root
->store('/var/uploads/private');

// Use unique names to prevent conflicts
->rename('unique');

// Remove EXIF data for privacy
->preserveExif(false);
```

### Server Configuration

```apache
# .htaccess - Deny execution in upload directory
<Files "*">
    SetHandler none
    SetHandler default-handler
    Options -ExecCGI
    RemoveHandler .php .phtml .php3 .php4 .php5
</Files>
```

```nginx
# nginx.conf - Block PHP execution in uploads
location /uploads/ {
    location ~ \.php$ {
        deny all;
    }
}
```

## Error Handling

### Exception Types

All errors throw `RuntimeException` with descriptive messages:

```php
try {
    $result = Upload::file($_FILES['file'])
        ->allowed(['pdf'])
        ->maxSize(1024 * 1024) // 1MB
        ->process()
        ->store('uploads');
        
} catch (RuntimeException $e) {
    // Handle specific error types
    $message = $e->getMessage();
    
    if (strpos($message, 'File size exceeds') !== false) {
        echo "File is too large";
    } elseif (strpos($message, 'File type not allowed') !== false) {
        echo "Invalid file type";
    } else {
        echo "Upload error: " . $message;
    }
}
```

### Error Validation Before Processing

```php
// Pre-validate before processing
$errors = UploadValidator::preValidate($_FILES['files'], [
    'allowed' => ['pdf', 'jpg'],
    'maxSize' => 5 * 1024 * 1024
]);

if (!empty($errors)) {
    foreach ($errors as $index => $fileErrors) {
        echo "File $index errors: " . implode(', ', $fileErrors);
    }
    return;
}

// Process if validation passed
$result = Upload::file($_FILES['files'])
    ->allowed(['pdf', 'jpg'])
    ->maxSize(5 * 1024 * 1024)
    ->process()
    ->store('uploads');
```

### Common Error Messages

- `"File exceeds upload_max_filesize directive"` - PHP configuration limit
- `"File size exceeds maximum allowed size"` - Your size limit
- `"File type not allowed"` - Extension not in allowed list
- `"MIME type mismatch"` - File content doesn't match extension
- `"Malicious content detected"` - Dangerous patterns found
- `"Failed to create storage directory"` - Permission issue
- `"GD extension required for image processing"` - Missing extension

## Framework Integration

### Laravel Integration

```php
// Controller
class FileUploadController extends Controller
{
    public function uploadDocument(Request $request)
    {
        try {
            $result = Upload::file($_FILES['document'])
                ->allowed(['pdf', 'docx'])
                ->maxSize(config('filesystems.max_size', 10485760))
                ->process()
                ->store(storage_path('app/uploads'));
            
            // Save to database
            Document::create([
                'filename' => $result->getFile()['stored_name'],
                'path' => $result->getPath(),
                'size' => $result->getFile()['size'],
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'file' => $result->getFile()
            ]);
            
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    public function uploadAvatar(Request $request)
    {
        try {
            $result = Upload::file($_FILES['avatar'])
                ->allowed(['jpg', 'png'])
                ->maxSize(2 * 1024 * 1024)
                ->compress(85)
                ->resize(300, 300)
                ->convertTo('webp')
                ->process()
                ->store(public_path('uploads/avatars'));
            
            // Update user avatar
            auth()->user()->update([
                'avatar' => 'uploads/avatars/' . $result->getFile()['stored_name']
            ]);
            
            return response()->json([
                'success' => true,
                'avatar_url' => asset('uploads/avatars/' . $result->getFile()['stored_name'])
            ]);
            
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

// Route
Route::post('/upload/document', [FileUploadController::class, 'uploadDocument']);
Route::post('/upload/avatar', [FileUploadController::class, 'uploadAvatar']);
```

### Symfony Integration

```php
// Controller
class UploadController extends AbstractController
{
    #[Route('/upload', methods: ['POST'])]
    public function upload(): JsonResponse
    {
        try {
            $result = Upload::file($_FILES['file'])
                ->allowed(['pdf', 'jpg', 'png'])
                ->maxSize($this->getParameter('max_upload_size'))
                ->process()
                ->store($this->getParameter('upload_directory'));
            
            return $this->json([
                'success' => true,
                'file' => $result->getFile()
            ]);
            
        } catch (RuntimeException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

### CodeIgniter Integration

```php
// Controller
class Upload extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
    }
    
    public function document()
    {
        try {
            $result = \Framework\Upload\Upload::file($_FILES['document'])
                ->allowed(['pdf', 'docx', 'xlsx'])
                ->maxSize(5 * 1024 * 1024)
                ->process()
                ->store(APPPATH . '../uploads/documents');
            
            $this->session->set_flashdata('success', 'Document uploaded successfully');
            
            echo json_encode([
                'success' => true,
                'file' => $result->getFile()
            ]);
            
        } catch (RuntimeException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### Vanilla PHP Integration

```php
<?php
// upload.php
session_start();
require_once 'Upload.php';

use Framework\Upload\Upload;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES)) {
    try {
        // Determine upload type based on form field
        if (isset($_FILES['avatar'])) {
            $result = Upload::file($_FILES['avatar'])
                ->allowed(['jpg', 'png', 'webp'])
                ->maxSize(2 * 1024 * 1024)
                ->compress(85)
                ->resize(400, 400)
                ->convertTo('webp')
                ->process()
                ->store('uploads/avatars');
                
        } elseif (isset($_FILES['documents'])) {
            $result = Upload::file($_FILES['documents'])
                ->allowed(['documents'])
                ->maxSize(10 * 1024 * 1024)
                ->process()
                ->store('uploads/documents');
        }
        
        $_SESSION['success'] = 'Files uploaded successfully';
        $_SESSION['files'] = $result->getFiles();
        
    } catch (RuntimeException $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: upload_form.php');
    exit;
}
?>
```

## Examples

### Complete Upload Form with Progress

```html
<!DOCTYPE html>
<html>
<head>
    <title>File Upload Example</title>
    <style>
        .upload-form { max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        .progress { width: 100%; height: 20px; background: #f0f0f0; }
        .progress-bar { height: 100%; background: #007bff; width: 0%; }
        .file-list { margin-top: 20px; }
        .file-item { padding: 10px; border: 1px solid #ddd; margin: 5px 0; }
    </style>
</head>
<body>
    <div class="upload-form">
        <h2>File Upload Demo</h2>
        
        <!-- Document Upload -->
        <form id="documentForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Upload Document:</label>
                <input type="file" name="document" accept=".pdf,.doc,.docx,.xlsx">
                <button type="submit">Upload Document</button>
            </div>
        </form>
        
        <!-- Image Upload -->
        <form id="imageForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Upload Images:</label>
                <input type="file" name="images[]" multiple accept="image/*">
                <button type="submit">Upload Images</button>
            </div>
        </form>
        
        <!-- Avatar Upload -->
        <form id="avatarForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Upload Avatar:</label>
                <input type="file" name="avatar" accept="image/jpeg,image/png">
                <button type="submit">Upload Avatar</button>
            </div>
        </form>
        
        <!-- Progress Bar -->
        <div class="progress" style="display: none;">
            <div class="progress-bar"></div>
        </div>
        
        <!-- Results -->
        <div id="results"></div>
    </div>

    <script>
        // Handle form submissions
        document.getElementById('documentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            uploadFiles(this, 'upload_document.php');
        });
        
        document.getElementById('imageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            uploadFiles(this, 'upload_images.php');
        });
        
        document.getElementById('avatarForm').addEventListener('submit', function(e) {
            e.preventDefault();
            uploadFiles(this, 'upload_avatar.php');
        });
        
        function uploadFiles(form, endpoint) {
            const formData = new FormData(form);
            const progressBar = document.querySelector('.progress');
            const progressBarFill = document.querySelector('.progress-bar');
            const results = document.getElementById('results');
            
            progressBar.style.display = 'block';
            progressBarFill.style.width = '0%';
            
            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                progressBarFill.style.width = '100%';
                
                if (data.success) {
                    results.innerHTML = '<div style="color: green;">✓ Upload successful!</div>';
                    if (data.files) {
                        data.files.forEach(file => {
                            results.innerHTML += `<div class="file-item">📁 ${file.stored_name} (${file.size} bytes)</div>`;
                        });
                    }
                } else {
                    results.innerHTML = `<div style="color: red;">✗ Error: ${data.error}</div>`;
                }
                
                setTimeout(() => {
                    progressBar.style.display = 'none';
                }, 1000);
            })
            .catch(error => {
                results.innerHTML = `<div style="color: red;">✗ Network error: ${error.message}</div>`;
                progressBar.style.display = 'none';
            });
        }
    </script>
</body>
</html>
```

### Backend Handlers

```php
<?php
// upload_document.php
require_once 'Upload.php';
use Framework\Upload\Upload;

header('Content-Type: application/json');

try {
    $result = Upload::file($_FILES['document'])
        ->allowed(['pdf', 'doc', 'docx', 'xlsx', 'txt'])
        ->maxSize(5 * 1024 * 1024) // 5MB
        ->rename('unique')
        ->process()
        ->store('uploads/documents');
    
    echo json_encode([
        'success' => true,
        'file' => $result->getFile()
    ]);
    
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

```php
<?php
// upload_images.php
require_once 'Upload.php';
use Framework\Upload\Upload;

header('Content-Type: application/json');

try {
    $result = Upload::file($_FILES['images'])
        ->allowed(['jpg', 'png', 'webp', 'gif'])
        ->maxSize(10 * 1024 * 1024) // 10MB per image
        ->compress(80)
        ->resize(1200, 800)
        ->process()
        ->store('uploads/images');
    
    echo json_encode([
        'success' => true,
        'files' => $result->getFiles(),
        'count' => $result->count()
    ]);
    
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

```php
<?php
// upload_avatar.php
require_once 'Upload.php';
use Framework\Upload\Upload;

header('Content-Type: application/json');

try {
    $result = Upload::file($_FILES['avatar'])
        ->allowed(['jpg', 'png'])
        ->maxSize(2 * 1024 * 1024) // 2MB
        ->compress(90)
        ->resize(300, 300, 'center')
        ->convertTo('webp')
        ->preserveExif(false) // Remove metadata for privacy
        ->process()
        ->store('uploads/avatars');
    
    $file = $result->getFile();
    
    echo json_encode([
        'success' => true,
        'avatar_url' => 'uploads/avatars/' . $file['stored_name'],
        'file' => $file
    ]);
    
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

### Batch Processing Example

```php
<?php
// batch_upload.php
require_once 'Upload.php';
use Framework\Upload\Upload;

class BatchUploadProcessor
{
    public static function processUserUploads($userId, $uploadType)
    {
        $results = [];
        $errors = [];
        
        try {
            switch ($uploadType) {
                case 'profile':
                    $result = self::processProfileUploads();
                    break;
                    
                case 'gallery':
                    $result = self::processGalleryUploads();
                    break;
                    
                case 'documents':
                    $result = self::processDocumentUploads();
                    break;
                    
                default:
                    throw new RuntimeException('Invalid upload type');
            }
            
            // Log successful uploads
            self::logUpload($userId, $uploadType, $result);
            
            return [
                'success' => true,
                'files' => $result->getFiles(),
                'count' => $result->count()
            ];
            
        } catch (RuntimeException $e) {
            // Log errors
            self::logError($userId, $uploadType, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private static function processProfileUploads()
    {
        return Upload::file($_FILES['profile_files'])
            ->allowed(['jpg', 'png', 'pdf'])
            ->maxSize(5 * 1024 * 1024)
            ->compress(85)
            ->resize(400, 400)
            ->process()
            ->store('uploads/profiles');
    }
    
    private static function processGalleryUploads()
    {
        return Upload::file($_FILES['gallery_images'])
            ->allowed(['images'])
            ->maxSize(20 * 1024 * 1024)
            ->compress(80)
            ->resize(1920, 1080)
            ->convertTo('webp')
            ->process()
            ->store('uploads/gallery');
    }
    
    private static function processDocumentUploads()
    {
        return Upload::file($_FILES['documents'])
            ->allowed(['documents'])
            ->maxSize(50 * 1024 * 1024)
            ->process()
            ->store('uploads/documents');
    }
    
    private static function logUpload($userId, $type, $result)
    {
        $logEntry = [
            'user_id' => $userId,
            'type' => $type,
            'files' => $result->getFiles(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents('logs/uploads.log', 
            json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    private static function logError($userId, $type, $error)
    {
        $logEntry = [
            'user_id' => $userId,
            'type' => $type,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents('logs/upload_errors.log', 
            json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
}

// Usage
$result = BatchUploadProcessor::processUserUploads($_SESSION['user_id'], 'gallery');
echo json_encode($result);
?>
```

## Troubleshooting

### Common Issues

#### 1. "GD extension required for image processing"

```bash
# Install GD extension
sudo apt-get install php-gd  # Ubuntu/Debian
sudo yum install php-gd      # CentOS/RHEL

# Or enable in php.ini
extension=gd

# Restart web server
sudo systemctl restart apache2  # or nginx
```

#### 2. "Failed to create storage directory"

```bash
# Check directory permissions
ls -la uploads/

# Fix permissions
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/

# Create directory manually
mkdir -p uploads/{documents,images,avatars}
```

#### 3. "File size exceeds upload_max_filesize directive"

```ini
; php.ini settings
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 120
max_input_time = 120
memory_limit = 256M
```

#### 4. "MIME type mismatch"

This occurs when file extension doesn't match content. Common causes:

- Renamed files with wrong extension
- Corrupted files
- Files with multiple extensions

```php
// Debug MIME type issues
$upload = Upload::file($_FILES['file']);

// Check actual MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$actualMime = finfo_file($finfo, $_FILES['file']['tmp_name']);
echo "Detected MIME: " . $actualMime;

// Check expected MIME for extension
$extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
echo "Expected MIME for .$extension";
```

#### 5. Memory issues with large images

```php
// Check memory usage
echo "Memory limit: " . ini_get('memory_limit') . "\n";
echo "Current usage: " . memory_get_usage(true) . "\n";

// Increase memory for large images
ini_set('memory_limit', '512M');

// Or process images in smaller batches
$files = array_chunk($_FILES['images']['tmp_name'], 5); // Process 5 at a time
```

#### 6. Temporary directory issues

```php
// Check temp directory
echo "Temp dir: " . sys_get_temp_dir() . "\n";
echo "Writable: " . (is_writable(sys_get_temp_dir()) ? 'Yes' : 'No') . "\n";

// Set custom temp directory
$customTemp = '/path/to/custom/temp';
if (!is_dir($customTemp)) {
    mkdir($customTemp, 0755, true);
}
```

### Debug Mode

Enable debug mode to get detailed information:

```php
class UploadDebug extends Upload
{
    private static bool $debugMode = false;
    
    public static function enableDebug(): void
    {
        self::$debugMode = true;
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
    
    protected function debugLog(string $message): void
    {
        if (self::$debugMode) {
            $timestamp = date('Y-m-d H:i:s');
            echo "[$timestamp] DEBUG: $message\n";
        }
    }
    
    // Override processFile to add debug info
    protected function processFile(array $file): array
    {
        $this->debugLog("Processing file: {$file['name']}");
        $this->debugLog("Size: {$file['size']} bytes");
        $this->debugLog("Type: {$file['type']}");
        $this->debugLog("Temp path: {$file['tmp_name']}");
        
        return parent::processFile($file);
    }
}

// Usage
UploadDebug::enableDebug();
$result = UploadDebug::file($_FILES['file'])
    ->allowed(['jpg', 'png'])
    ->process()
    ->store('uploads');
```

### Performance Optimization

#### 1. Optimize for Large Files

```php
// Increase limits for large file processing
ini_set('max_execution_time', 300);     // 5 minutes
ini_set('memory_limit', '1G');          // 1GB memory
ini_set('max_input_time', 300);         // 5 minutes input time

// Process large files with lower quality for speed
$result = Upload::file($_FILES['large_image'])
    ->allowed(['jpg', 'png'])
    ->compress(60)                       // Lower quality = faster processing
    ->resize(1920, 1080)                // Reasonable size
    ->process()
    ->store('uploads/large');
```

#### 2. Batch Processing for Multiple Files

```php
class BatchProcessor
{
    public static function processInBatches(array $files, int $batchSize = 5): array
    {
        $results = [];
        $batches = array_chunk($files, $batchSize);
        
        foreach ($batches as $batch) {
            $batchResults = self::processBatch($batch);
            $results = array_merge($results, $batchResults);
            
            // Optional: Add delay between batches to prevent server overload
            usleep(100000); // 0.1 second delay
        }
        
        return $results;
    }
    
    private static function processBatch(array $batch): array
    {
        $results = [];
        
        foreach ($batch as $file) {
            try {
                $result = Upload::file(['file' => $file])
                    ->allowed(['images'])
                    ->compress(80)
                    ->process()
                    ->store('uploads/batch');
                    
                $results[] = $result->getFile();
                
            } catch (RuntimeException $e) {
                error_log("Batch processing error: " . $e->getMessage());
            }
        }
        
        return $results;
    }
}
```

#### 3. Async Processing (with Queue)

```php
// For heavy processing, use a queue system
class AsyncUploadProcessor
{
    public static function queueForProcessing(array $fileData): string
    {
        $jobId = uniqid('upload_job_', true);
        
        // Store job data
        file_put_contents(
            "queue/jobs/$jobId.json",
            json_encode([
                'id' => $jobId,
                'files' => $fileData,
                'status' => 'pending',
                'created_at' => time()
            ])
        );
        
        return $jobId;
    }
    
    public static function processQueue(): void
    {
        $jobFiles = glob('queue/jobs/*.json');
        
        foreach ($jobFiles as $jobFile) {
            $jobData = json_decode(file_get_contents($jobFile), true);
            
            if ($jobData['status'] === 'pending') {
                try {
                    // Mark as processing
                    $jobData['status'] = 'processing';
                    file_put_contents($jobFile, json_encode($jobData));
                    
                    // Process files
                    $result = Upload::file($jobData['files'])
                        ->allowed(['images'])
                        ->compress(80)
                        ->resize(1200, 800)
                        ->process()
                        ->store('uploads/processed');
                    
                    // Mark as completed
                    $jobData['status'] = 'completed';
                    $jobData['result'] = $result->getFiles();
                    $jobData['completed_at'] = time();
                    
                    file_put_contents($jobFile, json_encode($jobData));
                    
                } catch (RuntimeException $e) {
                    // Mark as failed
                    $jobData['status'] = 'failed';
                    $jobData['error'] = $e->getMessage();
                    file_put_contents($jobFile, json_encode($jobData));
                }
            }
        }
    }
}
```

### Testing

#### Unit Tests Example (PHPUnit)

```php
<?php
use PHPUnit\Framework\TestCase;
use Framework\Upload\Upload;

class UploadTest extends TestCase
{
    private string $testUploadDir;
    
    protected function setUp(): void
    {
        $this->testUploadDir = __DIR__ . '/test_uploads';
        if (!is_dir($this->testUploadDir)) {
            mkdir($this->testUploadDir, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        $files = glob($this->testUploadDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->testUploadDir);
    }
    
    public function testValidDocumentUpload(): void
    {
        $mockFile = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $this->createMockPDF(),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        
        $result = Upload::file($mockFile)
            ->allowed(['pdf'])
            ->maxSize(2 * 1024 * 1024)
            ->process()
            ->store($this->testUploadDir);
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(1, $result->count());
        $this->assertFileExists($result->getPath());
    }
    
    public function testInvalidFileType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File type not allowed');
        
        $mockFile = [
            'name' => 'test.exe',
            'type' => 'application/x-executable',
            'tmp_name' => $this->createMockFile('exe'),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        
        Upload::file($mockFile)
            ->allowed(['pdf'])
            ->process();
    }
    
    public function testFileSizeLimit(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size');
        
        $mockFile = [
            'name' => 'large.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $this->createMockPDF(),
            'error' => UPLOAD_ERR_OK,
            'size' => 10 * 1024 * 1024 // 10MB
        ];
        
        Upload::file($mockFile)
            ->allowed(['pdf'])
            ->maxSize(1 * 1024 * 1024) // 1MB limit
            ->process();
    }
    
    public function testImageProcessing(): void
    {
        $mockImage = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->createMockImage(800, 600),
            'error' => UPLOAD_ERR_OK,
            'size' => 50000
        ];
        
        $result = Upload::file($mockImage)
            ->allowed(['jpg'])
            ->compress(80)
            ->resize(400, 300)
            ->process()
            ->store($this->testUploadDir);
        
        $this->assertTrue($result->isSuccess());
        
        $uploadedFile = $result->getPath();
        $this->assertFileExists($uploadedFile);
        
        // Check image dimensions
        [$width, $height] = getimagesize($uploadedFile);
        $this->assertEquals(400, $width);
        $this->assertEquals(300, $height);
    }
    
    private function createMockPDF(): string
    {
        $content = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n>>\nendobj\nxref\n0 2\ntrailer\n<<\n/Size 2\n/Root 1 0 R\n>>\n%%EOF";
        $tempFile = tempnam(sys_get_temp_dir(), 'test_pdf_');
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
    
    private function createMockImage(int $width, int $height): string
    {
        $image = imagecreate($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, 10, 10, 'Test Image', $black);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test_img_');
        imagejpeg($image, $tempFile);
        imagedestroy($image);
        
        return $tempFile;
    }
    
    private function createMockFile(string $type): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), "test_$type");
        file_put_contents($tempFile, "Mock $type content");
        return $tempFile;
    }
}
```

### Monitoring and Logging

#### Upload Analytics

```php
class UploadAnalytics
{
    private static string $logFile = 'logs/upload_analytics.log';
    
    public static function trackUpload(UploadResult $result, float $processingTime): void
    {
        $data = [
            'timestamp' => date('c'),
            'files_count' => $result->count(),
            'total_size' => array_sum(array_column($result->getFiles(), 'size')),
            'processing_time' => $processingTime,
            'file_types' => array_count_values(array_column($result->getFiles(), 'extension')),
            'memory_usage' => memory_get_peak_usage(true),
            'server_load' => sys_getloadavg()[0] ?? 0
        ];
        
        file_put_contents(
            self::$logFile,
            json_encode($data) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
    
    public static function getStats(int $days = 7): array
    {
        $cutoff = strtotime("-$days days");
        $lines = file(self::$logFile, FILE_IGNORE_NEW_LINES);
        
        $stats = [
            'total_uploads' => 0,
            'total_files' => 0,
            'total_size' => 0,
            'avg_processing_time' => 0,
            'file_types' => [],
            'daily_counts' => []
        ];
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (!$data || strtotime($data['timestamp']) < $cutoff) continue;
            
            $stats['total_uploads']++;
            $stats['total_files'] += $data['files_count'];
            $stats['total_size'] += $data['total_size'];
            $stats['avg_processing_time'] += $data['processing_time'];
            
            foreach ($data['file_types'] as $type => $count) {
                $stats['file_types'][$type] = ($stats['file_types'][$type] ?? 0) + $count;
            }
            
            $date = date('Y-m-d', strtotime($data['timestamp']));
            $stats['daily_counts'][$date] = ($stats['daily_counts'][$date] ?? 0) + 1;
        }
        
        if ($stats['total_uploads'] > 0) {
            $stats['avg_processing_time'] /= $stats['total_uploads'];
        }
        
        return $stats;
    }
}

// Usage with upload
$startTime = microtime(true);

$result = Upload::file($_FILES['files'])
    ->allowed(['images'])
    ->process()
    ->store('uploads');

$processingTime = microtime(true) - $startTime;
UploadAnalytics::trackUpload($result, $processingTime);
```

### Production Deployment Checklist

#### Server Configurations

```bash
# 1. Set proper file permissions
sudo chown -R www-data:www-data /path/to/uploads
sudo chmod -R 755 /path/to/uploads

# 2. Configure PHP limits
# /etc/php/8.1/apache2/php.ini or /etc/php/8.1/fpm/php.ini
upload_max_filesize = 50M
post_max_size = 60M
max_execution_time = 300
memory_limit = 512M
max_input_vars = 3000

# 3. Set up log rotation
# /etc/logrotate.d/upload-logs
/path/to/logs/upload*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    copytruncate
}

# 4. Configure web server security
# Apache .htaccess in upload directory
<Files "*">
    SetHandler none
    SetHandler default-handler
    Options -ExecCGI
    RemoveHandler .php .phtml .php3 .php4 .php5
    php_flag engine off
</Files>

# Nginx configuration
location /uploads/ {
    location ~ \.php$ {
        deny all;
    }
    # Optional: serve files with proper headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
}
```

#### Environment Variables

```php
// .env or config file
UPLOAD_MAX_SIZE=52428800          // 50MB
UPLOAD_STORAGE_PATH=/var/uploads
UPLOAD_TEMP_PATH=/var/tmp/uploads
UPLOAD_LOG_LEVEL=error
UPLOAD_ENABLE_ANALYTICS=true

// Load configuration
class UploadConfig
{
    public static function fromEnv(): array
    {
        return [
            'max_size' => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
            'storage_path' => $_ENV['UPLOAD_STORAGE_PATH'] ?? 'uploads',
            'temp_path' => $_ENV['UPLOAD_TEMP_PATH'] ?? sys_get_temp_dir(),
            'log_level' => $_ENV['UPLOAD_LOG_LEVEL'] ?? 'error',
            'analytics' => filter_var($_ENV['UPLOAD_ENABLE_ANALYTICS'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ];
    }
}
```

#### Health Checks

```php
class UploadHealthCheck
{
    public static function check(): array
    {
        $checks = [
            'php_version' => self::checkPHPVersion(),
            'extensions' => self::checkExtensions(),
            'permissions' => self::checkPermissions(),
            'disk_space' => self::checkDiskSpace(),
            'memory' => self::checkMemory(),
            'upload_limits' => self::checkUploadLimits()
        ];
        
        return [
            'status' => in_array(false, $checks) ? 'error' : 'ok',
            'checks' => $checks
        ];
    }
    
    private static function checkPHPVersion(): bool
    {
        return version_compare(PHP_VERSION, '8.0.0', '>=');
    }
    
    private static function checkExtensions(): array
    {
        $required = ['gd', 'fileinfo', 'mbstring'];
        $results = [];
        
        foreach ($required as $ext) {
            $results[$ext] = extension_loaded($ext);
        }
        
        return $results;
    }
    
    private static function checkPermissions(): array
    {
        $paths = ['uploads', 'uploads/temp', 'logs'];
        $results = [];
        
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $results[$path] = is_writable($path);
        }
        
        return $results;
    }
    
    private static function checkDiskSpace(): bool
    {
        $free = disk_free_space('.');
        $total = disk_total_space('.');
        return ($free / $total) > 0.1; // At least 10% free
    }
    
    private static function checkMemory(): bool
    {
        $limit = ini_get('memory_limit');
        return self::parseBytes($limit) >= 128 * 1024 * 1024; // 128MB minimum
    }
    
    private static function checkUploadLimits(): array
    {
        return [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
    }
    
    private static function parseBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        
        return $val;
    }
}

// Health check endpoint
// health.php
header('Content-Type: application/json');
echo json_encode(UploadHealthCheck::check());
```

## Conclusion

This PHP File Upload class provides a comprehensive solution for handling file uploads in modern PHP applications. With its focus on security, performance, and ease of use, it's suitable for everything from simple document uploads to complex image processing workflows.

Key benefits:

- **Security-first approach** with multiple validation layers
- **Process-before-store architecture** ensures only optimized files are saved
- **Fluent API** makes it easy to configure and use
- **Framework-agnostic design** works with any PHP project
- **Comprehensive image processing** with optimization and conversion
- **Detailed error handling** and debugging capabilities
- **Production-ready** with monitoring and health checks

For support and updates, refer to the GitHub repository or contact the maintainers.

Last updated: December 2024
