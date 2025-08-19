<?php

declare(strict_types=1);

namespace Illusion\Upload;

use RuntimeException;
use InvalidArgumentException;
use Illusion\Upload\UploadResult;

class Upload
{
    private array $files = [];
    private array $allowedTypes = [];
    private int $maxSize = 10485760; // 10MB default
    private string $storagePath = '';
    private string $renameStrategy = 'unique'; // 'unique', 'slug', 'original'
    private bool $createDirectories = true;
    
    // Image-specific properties
    private int $imageQuality = 75;
    private ?int $resizeWidth = null;
    private ?int $resizeHeight = null;
    private bool $preserveExif = false;
    private string $cropMode = 'center'; // 'center', 'top', 'bottom'
    private ?string $convertTo = null;
    
    // Validation properties
    private array $processedFiles = [];
    private array $errors = [];
    
    // Predefined file type groups
    private const FILE_TYPES = [
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt'],
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff'],
        'audio' => ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a', 'wma'],
        'video' => ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', '3gp'],
        'archives' => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz']
    ];
    
    // MIME type mappings for security
    private const MIME_TYPES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'rtf' => 'application/rtf',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
        'tiff' => 'image/tiff',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'flac' => 'audio/flac',
        'aac' => 'audio/aac',
        'm4a' => 'audio/mp4',
        'wma' => 'audio/x-ms-wma',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mkv' => 'video/x-matroska',
        'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'flv' => 'video/x-flv',
        'webm' => 'video/webm',
        '3gp' => 'video/3gpp',
        'zip' => 'application/zip',
        'rar' => 'application/vnd.rar',
        '7z' => 'application/x-7z-compressed',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        'bz2' => 'application/x-bzip2',
        'xz' => 'application/x-xz'
    ];

    private function __construct(array $files)
    {
        $this->files = $this->normalizeFiles($files);
    }

    /**
     * Create new Upload instance
     */
    public static function file(array $files): self
    {
        return new self($files);
    }

    /**
     * Set allowed file types
     */
    public function allowed(array $types): self
    {
        $allowedTypes = [];
        
        foreach ($types as $type) {
            if (isset(self::FILE_TYPES[$type])) {
                $allowedTypes = array_merge($allowedTypes, self::FILE_TYPES[$type]);
            } else {
                $allowedTypes[] = strtolower($type);
            }
        }
        
        $this->allowedTypes = array_unique($allowedTypes);
        return $this;
    }

    /**
     * Set maximum file size in bytes
     */
    public function maxSize(int $bytes): self
    {
        if ($bytes <= 0) {
            throw new InvalidArgumentException('Max size must be greater than 0');
        }
        
        $this->maxSize = $bytes;
        return $this;
    }

    /**
     * Set image compression quality (1-100)
     */
    public function compress(int $quality): self
    {
        if ($quality < 1 || $quality > 100) {
            throw new InvalidArgumentException('Quality must be between 1 and 100');
        }
        
        $this->imageQuality = $quality;
        return $this;
    }

    /**
     * Set image resize dimensions
     */
    public function resize(int $width, int $height, string $cropMode = 'center'): self
    {
        $this->resizeWidth = $width;
        $this->resizeHeight = $height;
        $this->cropMode = $cropMode;
        return $this;
    }

    /**
     * Convert images to specified format
     */
    public function convertTo(string $format): self
    {
        $supportedFormats = ['jpg', 'png', 'webp', 'gif'];
        
        if (!in_array(strtolower($format), $supportedFormats)) {
            throw new InvalidArgumentException('Unsupported conversion format');
        }
        
        $this->convertTo = strtolower($format);
        return $this;
    }

    /**
     * Preserve EXIF data in images
     */
    public function preserveExif(bool $preserve = true): self
    {
        $this->preserveExif = $preserve;
        return $this;
    }

    /**
     * Set file renaming strategy
     */
    public function rename(string $strategy): self
    {
        $validStrategies = ['unique', 'slug', 'original'];
        
        if (!in_array($strategy, $validStrategies)) {
            throw new InvalidArgumentException('Invalid rename strategy');
        }
        
        $this->renameStrategy = $strategy;
        return $this;
    }

    /**
     * Process all files (validation + optimization)
     */
    public function process(): self
    {
        $this->processedFiles = [];
        $this->errors = [];

        foreach ($this->files as $index => $file) {
            try {
                $processedFile = $this->processFile($file);
                $this->processedFiles[] = $processedFile;
            } catch (RuntimeException $e) {
                $this->errors[$index] = $e->getMessage();
            }
        }

        if (!empty($this->errors)) {
            throw new RuntimeException('File processing failed: ' . implode(', ', $this->errors));
        }

        return $this;
    }

    /**
     * Store processed files to destination
     */
    public function store(string $path): UploadResult
    {
        if (empty($this->processedFiles)) {
            throw new RuntimeException('No files processed. Call process() first.');
        }

        $this->storagePath = rtrim($path, '/\\');
        
        if ($this->createDirectories && !is_dir($this->storagePath)) {
            if (!mkdir($this->storagePath, 0755, true)) {
                throw new RuntimeException('Failed to create storage directory');
            }
        }

        $storedFiles = [];
        
        foreach ($this->processedFiles as $file) {
            $finalPath = $this->moveFileToStorage($file);
            $storedFiles[] = [
                'original_name' => $file['original_name'],
                'stored_name' => basename($finalPath),
                'path' => $finalPath,
                'size' => $file['size'],
                'type' => $file['type'],
                'extension' => $file['extension']
            ];
        }

        return new UploadResult($storedFiles);
    }

    /**
     * Normalize $_FILES array to consistent format
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];

        // Handle single file
        if (isset($files['tmp_name']) && !is_array($files['tmp_name'])) {
            return [$files];
        }

        // Handle multiple files
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
     * Process individual file
     */
    private function processFile(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->getUploadErrorMessage($file['error']));
        }

        // Validate file
        $this->validateFile($file);

        // Get file info
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $originalName = $file['name'];
        $tempPath = $file['tmp_name'];
        $size = $file['size'];
        $mimeType = $this->getFileMimeType($tempPath);

        // Process based on file type
        $processedTempPath = $this->processFileContent($tempPath, $extension, $mimeType);

        return [
            'original_name' => $originalName,
            'temp_path' => $processedTempPath,
            'extension' => $extension,
            'size' => filesize($processedTempPath),
            'type' => $mimeType,
            'processed' => $processedTempPath !== $tempPath
        ];
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(array $file): void
    {
        // Check file size
        if ($file['size'] > $this->maxSize) {
            throw new RuntimeException('File size exceeds maximum allowed size');
        }

        // Get extension and MIME type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = $this->getFileMimeType($file['tmp_name']);

        // Validate extension
        if (!empty($this->allowedTypes) && !in_array($extension, $this->allowedTypes)) {
            throw new RuntimeException('File type not allowed');
        }

        // Validate MIME type
        if (isset(self::MIME_TYPES[$extension])) {
            $expectedMimes = (array) self::MIME_TYPES[$extension];
            if (!in_array($mimeType, $expectedMimes)) {
                throw new RuntimeException('MIME type mismatch');
            }
        }

        // Security checks
        $this->performSecurityChecks($file);
    }

    /**
     * Perform security validation
     */
    private function performSecurityChecks(array $file): void
    {
        $filename = $file['name'];
        $tempPath = $file['tmp_name'];

        // Check for directory traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            throw new RuntimeException('Invalid filename detected');
        }

        // Check for executable files
        $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'scr', 'com', 'pif', 'vbs', 'js', 'jar', 'sh'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, $dangerousExtensions)) {
            throw new RuntimeException('Potentially dangerous file type detected');
        }

        // Check file content for malicious patterns
        if (is_readable($tempPath)) {
            $content = file_get_contents($tempPath, false, null, 0, 1024);
            $maliciousPatterns = ['<?php', '<?=', '<script', 'javascript:', 'vbscript:'];
            
            foreach ($maliciousPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    throw new RuntimeException('Malicious content detected');
                }
            }
        }
    }

    /**
     * Process file content (optimization, compression, etc.)
     */
    private function processFileContent(string $tempPath, string $extension, string $mimeType): string
    {
        // For images, apply optimization
        if ($this->isImageType($extension)) {
            return $this->processImage($tempPath, $extension);
        }

        // For other files, return original path
        return $tempPath;
    }

    /**
     * Process and optimize images
     */
    private function processImage(string $tempPath, string $extension): string
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD extension required for image processing');
        }

        // Create image resource
        $image = $this->createImageFromFile($tempPath, $extension);
        
        if (!$image) {
            throw new RuntimeException('Failed to create image resource');
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Apply resizing if specified
        if ($this->resizeWidth && $this->resizeHeight) {
            $image = $this->resizeImage($image, $originalWidth, $originalHeight);
        }

        // Determine output format
        $outputExtension = $this->convertTo ?: $extension;
        
        // Create processed file path
        $processedPath = tempnam(sys_get_temp_dir(), 'upload_processed_');
        
        // Save optimized image
        $this->saveImage($image, $processedPath, $outputExtension);
        
        // Clean up
        imagedestroy($image);

        return $processedPath;
    }

    /**
     * Create image resource from file
     */
    private function createImageFromFile(string $path, string $extension): \GdImage|false
    {
        return match ($extension) {
            'jpg', 'jpeg' => imagecreatefromjpeg($path),
            'png' => imagecreatefrompng($path),
            'gif' => imagecreatefromgif($path),
            'webp' => imagecreatefromwebp($path),
            'bmp' => imagecreatefrombmp($path),
            default => false
        };
    }

    /**
     * Resize image with proper aspect ratio handling
     */
    private function resizeImage(\GdImage $image, int $originalWidth, int $originalHeight): \GdImage
    {
        $targetWidth = $this->resizeWidth;
        $targetHeight = $this->resizeHeight;

        // Calculate aspect ratios
        $originalRatio = $originalWidth / $originalHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($originalRatio > $targetRatio) {
            // Original is wider
            $newWidth = $targetWidth;
            $newHeight = (int) ($targetWidth / $originalRatio);
        } else {
            // Original is taller
            $newWidth = (int) ($targetHeight * $originalRatio);
            $newHeight = $targetHeight;
        }

        // Create new image
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Preserve transparency for PNG and GIF
        if (in_array($this->getExtensionFromPath($image), ['png', 'gif'])) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefill($resized, 0, 0, $transparent);
        }

        // Calculate crop position
        $cropX = match ($this->cropMode) {
            'top' => 0,
            'bottom' => $targetWidth - $newWidth,
            default => (int) (($targetWidth - $newWidth) / 2)
        };
        
        $cropY = match ($this->cropMode) {
            'top' => 0,
            'bottom' => $targetHeight - $newHeight,
            default => (int) (($targetHeight - $newHeight) / 2)
        };

        // Copy and resize
        imagecopyresampled(
            $resized, $image,
            $cropX, $cropY, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        return $resized;
    }

    /**
     * Save image with optimization
     */
    private function saveImage(\GdImage $image, string $path, string $extension): void
    {
        $success = match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, $path, $this->imageQuality),
            'png' => imagepng($image, $path, (int) (9 - ($this->imageQuality / 11))),
            'gif' => imagegif($image, $path),
            'webp' => imagewebp($image, $path, $this->imageQuality),
            'bmp' => imagebmp($image, $path),
            default => false
        };

        if (!$success) {
            throw new RuntimeException('Failed to save processed image');
        }
    }

    /**
     * Move processed file to final storage location
     */
    private function moveFileToStorage(array $file): string
    {
        $newName = $this->generateFileName($file['original_name'], $file['extension']);
        $finalPath = $this->storagePath . DIRECTORY_SEPARATOR . $newName;

        // Ensure unique filename
        $counter = 1;
        $basePath = $finalPath;
        while (file_exists($finalPath)) {
            $pathInfo = pathinfo($basePath);
            $finalPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . 
                        $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
            $counter++;
        }

        if (!move_uploaded_file($file['temp_path'], $finalPath)) {
            // If not uploaded file (processed), use copy
            if (!copy($file['temp_path'], $finalPath)) {
                throw new RuntimeException('Failed to move file to storage');
            }
            // Clean up temp file
            if ($file['processed']) {
                unlink($file['temp_path']);
            }
        }

        // Set proper permissions
        chmod($finalPath, 0644);

        return $finalPath;
    }

    /**
     * Generate filename based on strategy
     */
    private function generateFileName(string $originalName, string $extension): string
    {
        $extension = $this->convertTo ?: $extension;
        
        return match ($this->renameStrategy) {
            'unique' => uniqid('upload_', true) . '.' . $extension,
            'slug' => $this->slugify(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension,
            'original' => $originalName,
            default => uniqid('upload_', true) . '.' . $extension
        };
    }

    /**
     * Create URL-friendly slug
     */
    private function slugify(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9\s-_]/', '', $text);
        $text = preg_replace('/[\s-_]+/', '-', $text);
        return trim(strtolower($text), '-');
    }

    /**
     * Get MIME type safely
     */
    private function getFileMimeType(string $path): string
    {
        if (function_exists('mime_content_type')) {
            return mime_content_type($path) ?: 'application/octet-stream';
        }
        
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path);
            finfo_close($finfo);
            return $mimeType ?: 'application/octet-stream';
        }

        return 'application/octet-stream';
    }

    /**
     * Check if file type is image
     */
    private function isImageType(string $extension): bool
    {
        return in_array($extension, self::FILE_TYPES['images']);
    }

    /**
     * Get extension from image resource (helper)
     */
    private function getExtensionFromPath(\GdImage $image): string
    {
        // This is a helper method for transparency handling
        // In real implementation, you'd track this separately
        return 'png'; // Default assumption for transparency
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $error): string
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

    /**
     * Get errors from last operation
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}