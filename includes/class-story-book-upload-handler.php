<?php
if (!defined('ABSPATH')) exit;

class Story_Book_Upload_Handler {
    private $config;
    private $errors = [];

    public function __construct() {
        $this->config = [
            'max_size' => Custom_Story_Book::getOption('max_file_size', 50 * 1024 * 1024), // 50MB
            'min_files' => Custom_Story_Book::getOption('min_files', 10),
            'max_files' => Custom_Story_Book::getOption('max_files', 20),
            'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png'],
            'allowed_extensions' => ['jpg', 'jpeg', 'png'],
            'upload_dir' => 'story-books',
            'image_quality' => 90
        ];
    }

    public function processFiles($files) {
        if (empty($files)) {
            throw new Exception(__('No files were uploaded', 'custom-story-book'));
        }

        // וידוא מספר קבצים
        if (count($files['name']) > $this->config['max_files']) {
            throw new Exception(sprintf(
                __('Maximum %d files allowed', 'custom-story-book'),
                $this->config['max_files']
            ));
        }

        // יצירת תיקיית העלאה זמנית
        $upload_dir = $this->createUploadDirectory();
        
        $processed_files = [];
        $errors = [];

        foreach ($files['name'] as $key => $filename) {
            try {
                if ($files['error'][$key] !== UPLOAD_ERR_OK) {
                    throw new Exception(sprintf(
                        __('Upload error for file %s: %s', 'custom-story-book'),
                        $filename,
                        $this->getUploadErrorMessage($files['error'][$key])
                    ));
                }

                // וידוא הקובץ
                $this->validateFile(
                    $files['tmp_name'][$key],
                    $files['size'][$key],
                    $files['type'][$key],
                    $filename
                );

                // עיבוד התמונה
                $processed_file = $this->processImage(
                    $files['tmp_name'][$key],
                    $filename,
                    $upload_dir['dir']
                );

                $processed_files[] = array_merge($processed_file, [
                    'original_name' => $filename,
                    'size' => $files['size'][$key],
                    'type' => $files['type'][$key]
                ]);

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                Custom_Story_Book::log(sprintf(
                    'File processing error for %s: %s',
                    $filename,
                    $e->getMessage()
                ));
            }
        }

        if (empty($processed_files)) {
            throw new Exception(__('No files were processed successfully', 'custom-story-book'));
        }

        return [
            'files' => $processed_files,
            'directory' => $upload_dir['relative_path'],
            'errors' => $errors
        ];
    }

    private function createUploadDirectory() {
        $upload_dir = wp_upload_dir();
        $relative_path = $this->config['upload_dir'] . '/temp/' . uniqid();
        $target_dir = $upload_dir['basedir'] . '/' . $relative_path;

        if (!wp_mkdir_p($target_dir)) {
            throw new Exception(__('Failed to create upload directory', 'custom-story-book'));
        }

        // יצירת קובץ .htaccess לאבטחה
        file_put_contents($target_dir . '/.htaccess', 'deny from all');

        return [
            'dir' => $target_dir,
            'url' => $upload_dir['baseurl'] . '/' . $relative_path,
            'relative_path' => $relative_path
        ];
    }

    private function validateFile($tmp_path, $size, $type, $filename) {
        // בדיקת גודל
        if ($size > $this->config['max_size']) {
            throw new Exception(sprintf(
                __('File %s is too large. Maximum size is %s', 'custom-story-book'),
                $filename,
                size_format($this->config['max_size'])
            ));
        }

        // בדיקת סוג קובץ
        if (!in_array($type, $this->config['allowed_types'])) {
            throw new Exception(sprintf(
                __('File type not allowed for %s', 'custom-story-book'),
                $filename
            ));
        }

        // בדיקת סיומת
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->config['allowed_extensions'])) {
            throw new Exception(sprintf(
                __('File extension not allowed for %s', 'custom-story-book'),
                $filename
            ));
        }

        // וידוא שזו באמת תמונה
        if (!getimagesize($tmp_path)) {
            throw new Exception(sprintf(
                __('File %s is not a valid image', 'custom-story-book'),
                $filename
            ));
        }

        // בדיקת MIME type אמיתי
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $tmp_path);
        finfo_close($finfo);

        if (!in_array($detected_type, $this->config['allowed_types'])) {
            throw new Exception(sprintf(
                __('Invalid file type detected for %s', 'custom-story-book'),
                $filename
            ));
        }
    }

    private function processImage($source_path, $original_filename, $target_dir) {
        // יצירת שם קובץ בטוח
        $filename = $this->getSafeFilename($original_filename);
        $target_path = $target_dir . '/' . $filename;

        // טעינת התמונה המקורית
        $image_info = getimagesize($source_path);
        if ($image_info === false) {
            throw new Exception(__('Failed to process image', 'custom-story-book'));
        }

        switch ($image_info[2]) {
            case IMAGETYPE_JPEG:
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source_image = imagecreatefrompng($source_path);
                break;
            default:
                throw new Exception(__('Unsupported image type', 'custom-story-book'));
        }

        if (!$source_image) {
            throw new Exception(__('Failed to create image resource', 'custom-story-book'));
        }

        // שמירת התמונה המעובדת
        $success = false;
        if ($image_info[2] === IMAGETYPE_JPEG) {
            $success = imagejpeg($source_image, $target_path, $this->config['image_quality']);
        } else {
            // שימור שקיפות ל-PNG
            imagesavealpha($source_image, true);
            $success = imagepng($source_image, $target_path, 9); // איכות מקסימלית ל-PNG
        }

        imagedestroy($source_image);

        if (!$success) {
            throw new Exception(__('Failed to save processed image', 'custom-story-book'));
        }

        // חישוב URL יחסי
        $upload_dir = wp_upload_dir();
        $relative_url = str_replace($upload_dir['basedir'], '', $target_path);
        $url = $upload_dir['baseurl'] . $relative_url;

        return [
            'name' => $filename,
            'path' => $target_path,
            'url' => $url
        ];
    }

    private function getSafeFilename($filename) {
        $filename = sanitize_file_name($filename);
        $info = pathinfo($filename);
        return sprintf(
            '%s-%s.%s',
            substr(sanitize_title($info['filename']), 0, 20),
            uniqid(),
            strtolower($info['extension'])
        );
    }

    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'custom-story-book');
            case UPLOAD_ERR_FORM_SIZE:
                return __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'custom-story-book');
            case UPLOAD_ERR_PARTIAL:
                return __('The uploaded file was only partially uploaded', 'custom-story-book');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded', 'custom-story-book');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing a temporary folder', 'custom-story-book');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk', 'custom-story-book');
            case UPLOAD_ERR_EXTENSION:
                return __('A PHP extension stopped the file upload', 'custom-story-book');
        }
        return __('Unknown upload error', 'custom-story-book');
    }

    public function cleanupTempFiles($directory) {
        if (empty($directory)) return;

        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/' . $directory;

        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($temp_dir);
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function hasErrors() {
        return !empty($this->errors);
    }
}
