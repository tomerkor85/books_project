<?php
/**
 * Plugin Name: Custom Story Book
 * Plugin URI: https://example.com/custom-story-book
 * Description: מערכת להזמנת ספרים מותאמים אישית עם העלאת תמונות ואינטגרציה עם WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: custom-story-book
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

// Block direct access
if (!defined('ABSPATH')) {
    exit;
}

function custom_story_book_enqueue_scripts() {
    wp_enqueue_script(
        'storybook-main-js', 
        plugin_dir_url(__FILE__) . 'assets/js/main.js', 
        array('jquery'), 
        '1.0.0', 
        true
    );
}
add_action('wp_enqueue_scripts', 'custom_story_book_enqueue_scripts');

// Define plugin constants
define('CSB_VERSION', '1.0.0');
define('CSB_PLUGIN_FILE', __FILE__);
define('CSB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CSB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CSB_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Composer autoloader if exists
if (file_exists(CSB_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once CSB_PLUGIN_DIR . 'vendor/autoload.php';
}

// Main plugin class
if (!class_exists('Custom_Story_Book')) {
    class Custom_Story_Book {
        private static $instance = null;
        private $modules = [];

        public static function getInstance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            // Initialize plugin
            add_action('plugins_loaded', [$this, 'initPlugin']);
        }

        public function initPlugin() {
            // Check requirements first
            if (!$this->checkRequirements()) {
                return;
            }

            // Load text domain
            $this->loadTextDomain();

            // Initialize core functionality
            $this->initCore();

            // Register activation/deactivation hooks
            register_activation_hook(CSB_PLUGIN_FILE, [$this, 'activate']);
            register_deactivation_hook(CSB_PLUGIN_FILE, [$this, 'deactivate']);
        }

        private function checkRequirements() {
            $errors = [];

            // Check PHP version
            if (version_compare(PHP_VERSION, '7.4', '<')) {
                $errors[] = sprintf(
                    __('Custom Story Book requires PHP 7.4 or higher. Current version is: %s', 'custom-story-book'),
                    PHP_VERSION
                );
            }

            // Check WordPress version
            if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
                $errors[] = __('Custom Story Book requires WordPress 5.8 or higher.', 'custom-story-book');
            }

            // Check if WooCommerce is active
            if (!$this->isWooCommerceActive()) {
                $errors[] = __('Custom Story Book requires WooCommerce to be installed and activated.', 'custom-story-book');
            }

            // Check ZIP extension
            if (!extension_loaded('zip')) {
                $errors[] = __('Custom Story Book requires PHP ZIP extension to be installed.', 'custom-story-book');
            }

            // If there are errors, display them and return false
            if (!empty($errors)) {
                add_action('admin_notices', function() use ($errors) {
                    echo '<div class="error">';
                    foreach ($errors as $error) {
                        echo '<p>' . esc_html($error) . '</p>';
                    }
                    echo '</div>';
                });
                return false;
            }

            return true;
        }

        private function isWooCommerceActive() {
            return class_exists('WooCommerce');
        }

        private function loadTextDomain() {
            load_plugin_textdomain(
                'custom-story-book',
                false,
                dirname(CSB_PLUGIN_BASENAME) . '/languages'
            );
        }

        private function initCore() {
            // Register and enqueue the JavaScript file
            wp_enqueue_script('file-handler', CSB_PLUGIN_URL . 'assets/js/modules/file-handler.js', [], CSB_VERSION, true);
        
            // Pass AJAX URL and nonce to JavaScript
            wp_localize_script('file-handler', 'storyBookConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('story_book_nonce')
            ]);
        
            // Optional: Enqueue main CSS file if needed
            wp_enqueue_style('story-book-main-style', CSB_PLUGIN_URL . 'assets/css/main.css', [], CSB_VERSION);
            
            // Include necessary files and initialize modules
            $this->includeFiles();
            $this->initModules();
        
            // Register shortcodes
            $this->registerShortcodes();
        }
        
        private function includeFiles() {
            $files = [
                'includes/class-story-book.php',
                'includes/class-story-book-public.php',
                'includes/class-story-book-woo.php',
                'includes/class-story-book-upload-handler.php',
                'includes/class-story-book-cleanup.php',
                'includes/class-story-book-ajax.php'
            ];

            foreach ($files as $file) {
                $filepath = CSB_PLUGIN_DIR . $file;
                if (file_exists($filepath)) {
                    require_once $filepath;
                }
            }

            // Admin files only when needed
            if (is_admin()) {
                require_once CSB_PLUGIN_DIR . 'includes/class-story-book-admin.php';
            }
        }

        private function initModules() {
            // Initialize core modules
            $this->modules['core'] = Story_Book::getInstance();
            $this->modules['public'] = Story_Book_Public::getInstance();
            $this->modules['woo'] = Story_Book_Woo::getInstance();
            $this->modules['upload'] = new Story_Book_Upload_Handler();
            $this->modules['cleanup'] = Story_Book_Cleanup::getInstance();
            $this->modules['ajax'] = Story_Book_Ajax::getInstance();

            // Initialize admin module if in admin
            if (is_admin()) {
                $this->modules['admin'] = StoryBookAdmin::getInstance();
            }
        }

        // Register shortcodes
        public function registerShortcodes() {
            add_shortcode('story_book_form', [$this, 'renderStoryBookForm']);
        }

        // Render the form based on step
        public function renderStoryBookForm($atts) {
            ob_start();

            // Get step from URL
            $step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

            // Load the correct template based on the step
            switch ($step) {
                case 1:
                    include self::getTemplatePath('forms/step-1-upload.php');
                    break;
                case 2:
                    include self::getTemplatePath('forms/step-2-details.php');
                    break;
                case 3:
                    include self::getTemplatePath('forms/step-3-checkout.php');
                    break;
                default:
                    echo __('Invalid step', 'custom-story-book');
                    break;
            }

            return ob_get_clean();
        }

        public function activate() {
            try {
                // Create required directories
                $this->createDirectories();

                // Set default options
                $this->setDefaultOptions();

                // Clear permalinks
                flush_rewrite_rules();

            } catch (Exception $e) {
                error_log('Custom Story Book activation error: ' . $e->getMessage());
                wp_die(
                    esc_html__('Error activating Custom Story Book. Please check error logs.', 'custom-story-book'),
                    '',
                    ['back_link' => true]
                );
            }
        }

        private function createDirectories() {
            $upload_dir = wp_upload_dir();
            $dirs = [
                $upload_dir['basedir'] . '/story-books',
                $upload_dir['basedir'] . '/story-books/orders',
                $upload_dir['basedir'] . '/story-books/temp'
            ];

            foreach ($dirs as $dir) {
                if (!wp_mkdir_p($dir)) {
                    throw new Exception(sprintf(
                        __('Failed to create directory: %s', 'custom-story-book'),
                        $dir
                    ));
                }

                // Create .htaccess for security
                file_put_contents($dir . '/.htaccess', 'deny from all');
            }
        }

        private function setDefaultOptions() {
            $default_options = [
                'min_files' => 10,
                'max_files' => 20,
                'max_file_size' => 50 * 1024 * 1024, // 50MB
                'product_price' => 299,
                'email_notifications' => true,
                'admin_email' => get_option('admin_email')
            ];

            add_option('csb_options', $default_options);
            add_option('csb_version', CSB_VERSION);
        }

        public function deactivate() {
            flush_rewrite_rules();
        }

        // Helper methods
        public static function getOption($key, $default = '') {
            $options = get_option('csb_options', []);
            return isset($options[$key]) ? $options[$key] : $default;
        }

        public static function updateOption($key, $value) {
            $options = get_option('csb_options', []);
            $options[$key] = $value;
            update_option('csb_options', $options);
        }

        public static function getTemplatePath($template) {
            $theme_path = get_stylesheet_directory() . '/custom-story-book/' . $template;
            return file_exists($theme_path) ? $theme_path : CSB_PLUGIN_DIR . 'templates/' . $template;
        }

        public static function getAssetUrl($path) {
            return CSB_PLUGIN_URL . 'assets/' . ltrim($path, '/');
        }

        public static function log($message, $type = 'info') {
            if (WP_DEBUG) {
                error_log(sprintf(
                    'Custom Story Book [%s]: %s',
                    $type,
                    is_array($message) || is_object($message) ? print_r($message, true) : $message
                ));
            }
        }
    }
}

// Initialize plugin
Custom_Story_Book::getInstance();
