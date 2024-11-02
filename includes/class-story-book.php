<?php
if (!defined('ABSPATH')) exit;

class Story_Book {
    private static $instance = null;
    private $errors = [];
    private $config;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initConfig();
        $this->addHooks();
    }

    private function initConfig() {
        $this->config = [
            'upload' => [
                'max_size' => Custom_Story_Book::getOption('max_file_size', 50 * 1024 * 1024),
                'min_files' => Custom_Story_Book::getOption('min_files', 10),
                'max_files' => Custom_Story_Book::getOption('max_files', 20),
                'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png'],
                'dir' => 'story-books'
            ],
            'form_steps' => [
                'upload' => [
                    'title' => __('העלאת תמונות', 'custom-story-book'),
                    'description' => __('בחרו את התמונות הטובות ביותר', 'custom-story-book')
                ],
                'details' => [
                    'title' => __('פרטי הילד/ה', 'custom-story-book'),
                    'description' => __('ספרו לנו על הילד/ה', 'custom-story-book')
                ],
                'checkout' => [
                    'title' => __('תשלום', 'custom-story-book'),
                    'description' => __('השלמת ההזמנה', 'custom-story-book')
                ]
            ]
        ];
    }

    private function addHooks() {
        // Shortcodes
        add_shortcode('story_book_form', [$this, 'renderForm']);

        // Ajax handlers
        add_action('wp_ajax_upload_story_files', [$this, 'handleFileUpload']);
        add_action('wp_ajax_nopriv_upload_story_files', [$this, 'handleFileUpload']);
        add_action('wp_ajax_validate_story_form', [$this, 'validateForm']);
        add_action('wp_ajax_nopriv_validate_story_form', [$this, 'validateForm']);

        // Form processing
        add_action('init', [$this, 'processForm']);
        
        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets() {
        // Only load assets when needed
        if (!$this->shouldLoadAssets()) {
            return;
        }

        // Styles
        wp_enqueue_style(
            'custom-story-book',
            Custom_Story_Book::getAssetUrl('css/public.css'),
            [],
            CSB_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'custom-story-book',
            Custom_Story_Book::getAssetUrl('js/public.js'),
            ['jquery'],
            CSB_VERSION,
            true
        );

        // Localize script
        wp_localize_script('custom-story-book', 'csbConfig', [
            'ajaxUrl' => admin_url('ajax.php'),
            'nonce' => wp_create_nonce('csb-nonce'),
            'uploadConfig' => $this->config['upload'],
            'i18n' => [
                'uploadError' => __('שגיאה בהעלאת הקבצים', 'custom-story-book'),
                'validationError' => __('אנא מלאו את כל השדות הנדרשים', 'custom-story-book'),
                'processing' => __('מעבד...', 'custom-story-book')
            ]
        ]);
    }

    private function shouldLoadAssets() {
        // Check if current page contains our shortcode or is our custom endpoint
        global $post;
        return has_shortcode($post->post_content, 'story_book_form') || 
               is_page('story-book-form') ||
               is_page('story-book-thank-you');
    }

    public function renderForm($atts = [], $content = null) {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<p class="error">' . __('נדרשת התקנת WooCommerce', 'custom-story-book') . '</p>';
        }

        // Get current step
        $step = isset($_GET['step']) ? absint($_GET['step']) : 1;
        
        // Start output buffering
        ob_start();

        // Include progress bar template
        include Custom_Story_Book::getTemplatePath('public/progress-bar.php');

        // Include step template
        switch ($step) {
            case 1:
                include Custom_Story_Book::getTemplatePath('public/form-steps/upload.php');
                break;
            case 2:
                include Custom_Story_Book::getTemplatePath('public/form-steps/details.php');
                break;
            case 3:
                include Custom_Story_Book::getTemplatePath('public/form-steps/checkout.php');
                break;
            default:
                include Custom_Story_Book::getTemplatePath('public/form-steps/upload.php');
        }

        // Return buffered content
        return ob_get_clean();
    }

    public function handleFileUpload() {
        check_ajax_referer('csb-nonce', 'nonce');

        try {
            if (empty($_FILES['files'])) {
                throw new Exception(__('לא נבחרו קבצים', 'custom-story-book'));
            }

            $files = $this->processFiles($_FILES['files']);
            
            wp_send_json_success([
                'files' => $files,
                'message' => __('הקבצים הועלו בהצלחה', 'custom-story-book')
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function processFiles($files) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/story-books/temp/' . uniqid();
        
        if (!wp_mkdir_p($temp_dir)) {
            throw new Exception(__('שגיאה ביצירת תיקייה זמנית', 'custom-story-book'));
        }

        $processed = [];
        $errors = [];

        foreach ($files['name'] as $key => $filename) {
            try {
                // Validate file
                $this->validateFile($files['tmp_name'][$key], $files['size'][$key], $files['type'][$key]);

                // Process file
                $safe_filename = $this->getSafeFilename($filename);
                $target_file = $temp_dir . '/' . $safe_filename;

                if (!move_uploaded_file($files['tmp_name'][$key], $target_file)) {
                    throw new Exception("Failed to move uploaded file: $filename");
                }

                $processed[] = [
                    'name' => $safe_filename,
                    'original_name' => $filename,
                    'path' => $target_file,
                    'url' => str_replace(
                        $upload_dir['basedir'],
                        $upload_dir['baseurl'],
                        $target_file
                    ),
                    'size' => $files['size'][$key],
                    'type' => $files['type'][$key]
                ];

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                Custom_Story_Book::log("File processing error: " . $e->getMessage());
            }
        }

        if (empty($processed)) {
            throw new Exception(__('לא הצלחנו לעבד את הקבצים', 'custom-story-book'));
        }

        // Store files data in session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['csb_uploaded_files'] = $processed;

        return $processed;
    }

    private function validateFile($tmp_path, $size, $type) {
        // Size check
        if ($size > $this->config['upload']['max_size']) {
            throw new Exception(__('הקובץ גדול מדי', 'custom-story-book'));
        }

        // Type check
        if (!in_array($type, $this->config['upload']['allowed_types'])) {
            throw new Exception(__('סוג קובץ לא נתמך', 'custom-story-book'));
        }

        // Additional security checks
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $tmp_path);
        finfo_close($finfo);

        if (!in_array($detected_type, $this->config['upload']['allowed_types'])) {
            throw new Exception(__('סוג הקובץ אינו תואם', 'custom-story-book'));
        }
    }

    private function getSafeFilename($filename) {
        $filename = sanitize_file_name($filename);
        return uniqid() . '-' . $filename;
    }

    public function validateForm() {
        check_ajax_referer('csb-nonce', 'nonce');

        $data = $_POST['formData'] ?? [];
        $errors = [];

        // Validate required fields
        $required_fields = [
            'childName' => __('שם הילד/ה נדרש', 'custom-story-book'),
            'childNameEn' => __('שם באנגלית נדרש', 'custom-story-book'),
            'gender' => __('יש לבחור מגדר', 'custom-story-book'),
            'age' => __('גיל נדרש', 'custom-story-book'),
            'bookType' => __('יש לבחור סוג ספר', 'custom-story-book')
        ];

        foreach ($required_fields as $field => $message) {
            if (empty($data[$field])) {
                $errors[$field] = $message;
            }
        }

        if (!empty($errors)) {
            wp_send_json_error(['errors' => $errors]);
            return;
        }

        // Store form data in session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['csb_form_data'] = $data;

        wp_send_json_success([
            'message' => __('הטופס תקין', 'custom-story-book'),
            'redirect' => add_query_arg('step', '3', get_permalink())
        ]);
    }

    public function processForm() {
        if (!isset($_POST['csb_submit'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['csb_nonce'], 'csb_form_submit')) {
            wp_die(__('אבטחה: הטופס אינו תקף', 'custom-story-book'));
        }

        try {
            // Process form submission
            $order_id = $this->createOrder();
            
            // Redirect to thank you page
            wp_redirect(add_query_arg([
                'order' => $order_id,
                'key' => get_post_meta($order_id, '_order_key', true)
            ], get_permalink(get_option('csb_thank_you_page'))));
            exit;

        } catch (Exception $e) {
            wp_die($e->getMessage());
        }
    }

    private function createOrder() {
        // Implementation handled by WooCommerce integration class
        return Story_Book_Woo::getInstance()->createOrder();
    }
}
