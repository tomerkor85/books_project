<?php
if (!defined('ABSPATH')) exit;

class Story_Book_Public {
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initHooks();
    }

    private function initHooks() {
        // Handle styles
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_head', [$this, 'injectDynamicStyles'], 100);
    }

    public function enqueueAssets() {
        if (!$this->isPluginPage()) {
            return;
        }

        // Base CSS with all imports
        wp_enqueue_style(
            'story-book-styles',
            CSB_PLUGIN_URL . 'assets/css/public/style.css',
            [],
            CSB_VERSION
        );

        // JavaScript Dependencies
        wp_enqueue_script('jquery');

        // Main JavaScript
        wp_enqueue_script(
            'story-book-main',
            CSB_PLUGIN_URL . 'assets/js/main.js',
            ['jquery'],
            CSB_VERSION,
            true
        );

        // Feature-specific JavaScript
        if ($this->isUploadPage()) {
            wp_enqueue_script(
                'story-book-file-handler',
                CSB_PLUGIN_URL . 'assets/js/modules/file-handler.js',
                ['jquery', 'story-book-main'],
                CSB_VERSION,
                true
            );
        }

        // Localize Scripts
        wp_localize_script('story-book-main', 'storyBookConfig', [
            'ajaxUrl' => admin_url('ajax.php'),
            'nonce' => wp_create_nonce('story-book-nonce'),
            'maxFiles' => $this->getOption('max_files', 20),
            'minFiles' => $this->getOption('min_files', 10),
            'maxFileSize' => $this->getOption('max_file_size', 50 * 1024 * 1024)
        ]);
    }

    public function injectDynamicStyles() {
        if (!$this->isPluginPage()) {
            return;
        }

        $upload_dir = wp_upload_dir();
        ?>
        <style>
            @font-face {
                font-family: 'YehudaCLM';
                src: url('<?php echo esc_url($upload_dir['baseurl']); ?>/2024/10/yehudaclm-light-webfont.woff') format('woff');
                font-weight: normal;
                font-style: normal;
            }

            #step-1 {
                background-image: url('<?php echo esc_url($upload_dir['baseurl']); ?>/2024/10/bbook_85912_httpss.mj_.run6wgGq3qb8ok_eras_-ar_9151_-v_6.1_c54e80c5-9dda-44f0-a48e-33e51fd7bd7c.webp');
            }

            #step-2 {
                background-image: url('<?php echo esc_url($upload_dir['baseurl']); ?>/2024/10/bbook_85912_erase_-ar_169_-v_6.1_ed4f769d-fc23-40e0-9b6d-ac0f0f84276d-scaled.webp');
            }
        </style>
        <?php
    }

    private function isPluginPage() {
        global $post;
        return (
            is_a($post, 'WP_Post') && 
            (
                has_shortcode($post->post_content, 'story_book_form') ||
                strpos($post->post_content, '<!-- wp:story-book/form -->') !== false
            )
        );
    }

    private function isUploadPage() {
        return isset($_GET['step']) && $_GET['step'] === '1';
    }

    private function getOption($key, $default = '') {
        $options = get_option('csb_options', []);
        return isset($options[$key]) ? $options[$key] : $default;
    }
}