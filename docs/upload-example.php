<?php

/**
 * Example Usage and Test Helper
 */
class UploadExample
{
    /**
     * Example: Upload a single document
     */
    public static function uploadDocument(): void
    {
        try {
            $result = Upload::file($_FILES['document'])
                ->allowed(['pdf', 'docx', 'xlsx'])
                ->maxSize(5 * 1024 * 1024) // 5MB
                ->rename('slug')
                ->process()
                ->store('uploads/documents');

            echo "Document uploaded: " . $result->getPath() . "\n";
            
        } catch (RuntimeException $e) {
            echo "Upload failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example: Upload and optimize images
     */
    public static function uploadImage(): void
    {
        try {
            $result = Upload::file($_FILES['photos'])
                ->allowed(['images'])
                ->maxSize(10 * 1024 * 1024) // 10MB
                ->compress(80)
                ->resize(1200, 800)
                ->convertTo('webp')
                ->process()
                ->store('uploads/images');

            foreach ($result->getFiles() as $file) {
                echo "Image uploaded: {$file['stored_name']} ({$file['size']} bytes)\n";
            }
            
        } catch (RuntimeException $e) {
            echo "Upload failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example: Upload avatar with specific optimization
     */
    public static function uploadAvatar(): void
    {
        try {
            $result = Upload::file($_FILES['avatar'])
                ->allowed(['jpg', 'png'])
                ->maxSize(2 * 1024 * 1024) // 2MB
                ->compress(85)
                ->resize(300, 300, 'center')
                ->convertTo('webp')
                ->preserveExif(false)
                ->process()
                ->store('uploads/avatars');

            $file = $result->getFile();
            echo "Avatar uploaded: {$file['stored_name']}\n";
            
        } catch (RuntimeException $e) {
            echo "Upload failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example: Bulk upload with mixed file types
     */
    public static function uploadMixed(): void
    {
        try {
            $result = Upload::file($_FILES['mixed_files'])
                ->allowed(['documents', 'images', 'archives'])
                ->maxSize(20 * 1024 * 1024) // 20MB
                ->compress(75) // Only affects images
                ->process()
                ->store('uploads/mixed');

            echo "Uploaded {$result->count()} files:\n";
            foreach ($result->getFiles() as $file) {
                echo "- {$file['stored_name']} ({$file['type']})\n";
            }
            
        } catch (RuntimeException $e) {
            echo "Upload failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example: Using predefined configurations
     */
    public static function uploadWithConfig(): void
    {
        $config = UploadConfig::avatars();
        
        try {
            $upload = Upload::file($_FILES['avatar'])
                ->allowed($config['allowed'])
                ->maxSize($config['maxSize'])
                ->compress($config['compress'])
                ->resize(...$config['resize'])
                ->convertTo($config['convertTo'])
                ->rename($config['rename']);

            $result = $upload->process()->store('uploads/avatars');
            
            echo "Avatar uploaded with config: " . $result->getPath() . "\n";
            
        } catch (RuntimeException $e) {
            echo "Upload failed: " . $e->getMessage() . "\n";
        }
    }
}

// ===============================================
// HTML FORM EXAMPLES FOR TESTING
// ===============================================

/**
 * HTML Forms for testing the upload functionality
 */
class UploadFormExamples
{
    public static function singleFileForm(): string
    {
        return '
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <label for="document">Choose document:</label>
            <input type="file" id="document" name="document" accept=".pdf,.doc,.docx,.xlsx">
            <button type="submit">Upload Document</button>
        </form>';
    }

    public static function multipleImageForm(): string
    {
        return '
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <label for="photos">Choose photos:</label>
            <input type="file" id="photos" name="photos[]" multiple accept="image/*">
            <button type="submit">Upload Photos</button>
        </form>';
    }

    public static function avatarUploadForm(): string
    {
        return '
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <label for="avatar">Choose avatar:</label>
            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png">
            <button type="submit">Upload Avatar</button>
        </form>';
    }

    public static function mixedFileForm(): string
    {
        return '
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <label for="mixed_files">Choose files:</label>
            <input type="file" id="mixed_files" name="mixed_files[]" multiple>
            <button type="submit">Upload Files</button>
        </form>';
    }
}

// ===============================================
// CONTROLLER EXAMPLE FOR MVC FRAMEWORKS
// ===============================================

/**
 * Example controller methods for common frameworks
 */
class UploadController
{
    /**
     * Handle document upload (Laravel-style)
     */
    public function uploadDocument()
    {
        try {
            $result = Upload::file($_FILES['document'])
                ->allowed(['documents'])
                ->maxSize(10 * 1024 * 1024)
                ->process()
                ->store('uploads/documents');

            return [
                'success' => true,
                'message' => 'Document uploaded successfully',
                'file' => $result->getFile()
            ];

        } catch (RuntimeException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle image gallery upload
     */
    public function uploadGallery()
    {
        try {
            $result = Upload::file($_FILES['images'])
                ->allowed(['jpg', 'png', 'webp'])
                ->maxSize(5 * 1024 * 1024)
                ->compress(85)
                ->resize(1920, 1080)
                ->convertTo('webp')
                ->process()
                ->store('uploads/gallery');

            return [
                'success' => true,
                'message' => "Uploaded {$result->count()} images",
                'files' => $result->getFiles()
            ];

        } catch (RuntimeException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle profile avatar upload with JSON response
     */
    public function uploadAvatar()
    {
        header('Content-Type: application/json');

        try {
            $result = Upload::file($_FILES['avatar'])
                ->allowed(['jpg', 'png'])
                ->maxSize(2 * 1024 * 1024)
                ->compress(90)
                ->resize(400, 400, 'center')
                ->convertTo('webp')
                ->process()
                ->store('uploads/avatars');

            $file = $result->getFile();
            
            echo json_encode([
                'success' => true,
                'avatar_url' => '/uploads/avatars/' . $file['stored_name'],
                'file_info' => $file
            ]);

        } catch (RuntimeException $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Upload with progress tracking (AJAX)
     */
    public function uploadWithProgress()
    {
        // Set up session for progress tracking
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $result = Upload::file($_FILES['file'])
                ->allowed(['documents', 'images'])
                ->maxSize(50 * 1024 * 1024)
                ->compress(80)
                ->process()
                ->store('uploads/progress');

            echo json_encode([
                'success' => true,
                'files' => $result->getFiles(),
                'progress' => 100
            ]);

        } catch (RuntimeException $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'progress' => 0
            ]);
        }
    }
}

// ===============================================
// CONFIGURATION CLASS EXTENSIONS
// ===============================================

/**
 * Extended configuration presets for specific use cases
 */
class ExtendedUploadConfig extends UploadConfig
{
    /**
     * Configuration for product images in e-commerce
     */
    public static function productImages(): array
    {
        return [
            'allowed' => ['jpg', 'png', 'webp'],
            'maxSize' => 8 * 1024 * 1024, // 8MB
            'resize' => [1200, 1200],
            'compress' => 85,
            'convertTo' => 'webp',
            'rename' => 'unique',
            'preserveExif' => false
        ];
    }

    /**
     * Configuration for blog post attachments
     */
    public static function blogAttachments(): array
    {
        return [
            'allowed' => ['documents', 'images'],
            'maxSize' => 15 * 1024 * 1024, // 15MB
            'compress' => 80,
            'rename' => 'slug'
        ];
    }

    /**
     * Configuration for user profile documents
     */
    public static function profileDocuments(): array
    {
        return [
            'allowed' => ['pdf', 'jpg', 'png'],
            'maxSize' => 5 * 1024 * 1024, // 5MB
            'rename' => 'unique'
        ];
    }

    /**
     * Configuration for video thumbnails
     */
    public static function videoThumbnails(): array
    {
        return [
            'allowed' => ['jpg', 'png'],
            'maxSize' => 3 * 1024 * 1024, // 3MB
            'resize' => [640, 360],
            'compress' => 85,
            'convertTo' => 'jpg',
            'rename' => 'unique'
        ];
    }

    /**
     * Configuration for file attachments in messaging
     */
    public static function messageAttachments(): array
    {
        return [
            'allowed' => ['documents', 'images', 'audio'],
            'maxSize' => 25 * 1024 * 1024, // 25MB
            'compress' => 75,
            'rename' => 'unique'
        ];
    }
}

// ===============================================
// VALIDATION AND ERROR HANDLING HELPERS
// ===============================================

/**
 * Advanced validation and error handling utilities
 */
class UploadValidator
{
    /**
     * Validate file before processing
     */
    public static function preValidate(array $files, array $rules): array
    {
        $errors = [];
        $normalizedFiles = self::normalizeFiles($files);

        foreach ($normalizedFiles as $index => $file) {
            $fileErrors = [];

            // Check file upload error
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $fileErrors[] = self::getUploadErrorMessage($file['error']);
            }

            // Check file size
            if (isset($rules['maxSize']) && $file['size'] > $rules['maxSize']) {
                $fileErrors[] = 'File size exceeds maximum allowed (' . 
                              self::formatBytes($rules['maxSize']) . ')';
            }

            // Check file extension
            if (isset($rules['allowed'])) {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, $rules['allowed'])) {
                    $fileErrors[] = 'File type not allowed. Allowed: ' . 
                                  implode(', ', $rules['allowed']);
                }
            }

            if (!empty($fileErrors)) {
                $errors[$index] = $fileErrors;
            }
        }

        return $errors;
    }

    /**
     * Format bytes to human readable format
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get upload limits from PHP configuration
     */
    public static function getUploadLimits(): array
    {
        $maxFilesize = self::parseSize(ini_get('upload_max_filesize'));
        $maxPostSize = self::parseSize(ini_get('post_max_size'));
        $maxInputTime = (int) ini_get('max_input_time');
        $maxExecutionTime = (int) ini_get('max_execution_time');

        return [
            'max_filesize' => $maxFilesize,
            'max_post_size' => $maxPostSize,
            'max_input_time' => $maxInputTime,
            'max_execution_time' => $maxExecutionTime,
            'effective_limit' => min($maxFilesize, $maxPostSize)
        ];
    }

    /**
     * Parse size string to bytes
     */
    private static function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;

        return match ($last) {
            'g' => $size * 1024 * 1024 * 1024,
            'm' => $size * 1024 * 1024,
            'k' => $size * 1024,
            default => $size
        };
    }

    /**
     * Normalize files array (copy of Upload class method for external use)
     */
    private static function normalizeFiles(array $files): array
    {
        $normalized = [];

        if (isset($files['tmp_name']) && !is_array($files['tmp_name'])) {
            return [$files];
        }

        if (isset($files['tmp_name']) && is_array($files['tmp_name'])) {
            $fileCount = count($files['tmp_name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $normalized[] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
            }
        }

        return $normalized;
    }

    /**
     * Get upload error message (copy for external use)
     */
    private static function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown upload error'
        };
    }
}