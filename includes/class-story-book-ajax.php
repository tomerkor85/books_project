<?php
if (!defined('ABSPATH')) exit;

class Story_Book_Ajax {
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        // File Upload Handlers
        add_action('wp_ajax_handle_file_upload', [$this, 'handleFileUpload']);
        add_action('wp_ajax_nopriv_handle_file_upload', [$this, 'handleFileUpload']);

        // Order Processing
        add_action('wp_ajax_process_story_book_order', [$this, 'processOrder']);
        add_action('wp_ajax_nopriv_process_story_book_order', [$this, 'processOrder']);

        // Status Updates
        add_action('wp_ajax_update_order_status', [$this, 'updateOrderStatus']);

        // Admin Actions
        add_action('wp_ajax_download_order_files', [$this, 'downloadOrderFiles']);
        add_action('wp_ajax_preview_book_details', [$this, 'previewBookDetails']);
    }

    public function handleFileUpload() {
        try {
            // Security Check
            check_ajax_referer('story-book-nonce', 'nonce');

            if (empty($_FILES['files'])) {
                throw new Exception(__('No files were uploaded', 'custom-story-book'));
            }

            // Initialize Upload Handler
            require_once(CSB_PLUGIN_DIR . 'includes/class-story-book-upload-handler.php');
            $upload_handler = new Story_Book_Upload_Handler();

            // Process Files
            $result = $upload_handler->processFiles($_FILES['files']);

            // Store upload data in session
            if (!session_id()) {
                session_start();
            }
            $_SESSION['csb_uploaded_files'] = $result['files'];
            $_SESSION['csb_upload_directory'] = $result['directory'];

            wp_send_json_success([
                'message' => __('Files uploaded successfully', 'custom-story-book'),
                'files' => $result['files'],
                'directory' => $result['directory']
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function processOrder() {
        try {
            // Security Check
            check_ajax_referer('story-book-nonce', 'nonce');

            // Validate Session Data
            if (!session_id()) {
                session_start();
            }

            if (!isset($_SESSION['csb_uploaded_files']) || !isset($_POST['form_data'])) {
                throw new Exception(__('Missing required data', 'custom-story-book'));
            }

            // Parse Form Data
            $form_data = json_decode(stripslashes($_POST['form_data']), true);
            if (!$this->validateFormData($form_data)) {
                throw new Exception(__('Invalid form data', 'custom-story-book'));
            }

            // Create/Update WooCommerce Order
            $order_id = $this->createWooCommerceOrder($form_data);

            // Process and Save Files
            $this->processOrderFiles($order_id);

            // Clear Session
            unset($_SESSION['csb_uploaded_files']);
            unset($_SESSION['csb_upload_directory']);

            wp_send_json_success([
                'message' => __('Order processed successfully', 'custom-story-book'),
                'order_id' => $order_id,
                'redirect' => wc_get_checkout_url() . "?order-id={$order_id}"
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function validateFormData($data) {
        $required_fields = ['childName', 'childNameEn', 'gender', 'age', 'bookType'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        return true;
    }

    private function createWooCommerceOrder($form_data) {
        // Get or Create Cart
        if (WC()->cart->is_empty()) {
            $product_id = get_option('csb_product_id');
            if (!$product_id) {
                throw new Exception(__('Product not configured', 'custom-story-book'));
            }

            WC()->cart->add_to_cart($product_id, 1, 0, [], [
                'csb_data' => $form_data
            ]);
        }

        // Create Order
        $checkout = WC()->checkout();
        $order_id = $checkout->create_order([
            'payment_method' => isset($_POST['payment_method']) ? $_POST['payment_method'] : '',
            'billing_email' => isset($_POST['billing_email']) ? $_POST['billing_email'] : '',
            'billing_phone' => isset($_POST['billing_phone']) ? $_POST['billing_phone'] : ''
        ]);

        if (is_wp_error($order_id)) {
            throw new Exception($order_id->get_error_message());
        }

        // Save Custom Data
        update_post_meta($order_id, '_csb_details', $form_data);
        
        return $order_id;
    }

    private function processOrderFiles($order_id) {
        if (empty($_SESSION['csb_uploaded_files'])) {
            return;
        }

        $upload_dir = wp_upload_dir();
        $order_dir = $upload_dir['basedir'] . '/story-books/orders/' . $order_id;

        if (!wp_mkdir_p($order_dir)) {
            throw new Exception(__('Failed to create order directory', 'custom-story-book'));
        }

        $processed_files = [];
        foreach ($_SESSION['csb_uploaded_files'] as $file) {
            if (isset($file['path']) && file_exists($file['path'])) {
                $new_path = $order_dir . '/' . basename($file['path']);
                if (copy($file['path'], $new_path)) {
                    $processed_files[] = [
                        'original_name' => $file['original_name'],
                        'path' => $new_path,
                        'url' => str_replace(
                            $upload_dir['basedir'],
                            $upload_dir['baseurl'],
                            $new_path
                        )
                    ];
                    // Clean up temp file
                    @unlink($file['path']);
                }
            }
        }

        update_post_meta($order_id, '_csb_files', $processed_files);
    }

    public function updateOrderStatus() {
        try {
            // Security Check
            check_ajax_referer('story-book-admin', 'nonce');

            if (!current_user_can('manage_woocommerce')) {
                throw new Exception(__('Permission denied', 'custom-story-book'));
            }

            $order_id = intval($_POST['order_id']);
            $status = sanitize_text_field($_POST['status']);

            $order = wc_get_order($order_id);
            if (!$order) {
                throw new Exception(__('Invalid order', 'custom-story-book'));
            }

            $order->update_status($status);

            // Log status change
            $user = wp_get_current_user();
            $log_entry = [
                'time' => current_time('mysql'),
                'user' => $user->display_name,
                'status' => $status
            ];

            $status_log = get_post_meta($order_id, '_csb_status_log', true) ?: [];
            $status_log[] = $log_entry;
            update_post_meta($order_id, '_csb_status_log', $status_log);

            wp_send_json_success([
                'message' => __('Status updated successfully', 'custom-story-book')
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function downloadOrderFiles() {
        try {
            // Security Check
            check_admin_referer('download_order_files');

            if (!current_user_can('manage_woocommerce')) {
                wp_die(__('Permission denied', 'custom-story-book'));
            }

            $order_id = intval($_GET['order_id']);
            $files = get_post_meta($order_id, '_csb_files', true);

            if (empty($files)) {
                wp_die(__('No files found', 'custom-story-book'));
            }

            require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
            $upload_dir = wp_upload_dir();
            $zip_file = $upload_dir['basedir'] . '/story-books/temp/order-' . $order_id . '.zip';

            $zip = new PclZip($zip_file);
            foreach ($files as $file) {
                if (file_exists($file['path'])) {
                    $zip->add($file['path'], PCLZIP_OPT_REMOVE_PATH, dirname($file['path']));
                }
            }

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="order-' . $order_id . '-files.zip"');
            header('Content-Length: ' . filesize($zip_file));
            readfile($zip_file);

            // Clean up
            @unlink($zip_file);
            exit;

        } catch (Exception $e) {
            wp_die($e->getMessage());
        }
    }

    public function previewBookDetails() {
        try {
            // Security Check
            check_ajax_referer('story-book-admin', 'nonce');

            if (!current_user_can('manage_woocommerce')) {
                throw new Exception(__('Permission denied', 'custom-story-book'));
            }

            $order_id = intval($_POST['order_id']);
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new Exception(__('Invalid order', 'custom-story-book'));
            }

            $book_details = get_post_meta($order_id, '_csb_details', true);
            $files = get_post_meta($order_id, '_csb_files', true);

            ob_start();
            include(CSB_PLUGIN_DIR . 'templates/admin/preview-book.php');
            $html = ob_get_clean();

            wp_send_json_success([
                'html' => $html
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}

// Initialize
Story_Book_Ajax::getInstance();
