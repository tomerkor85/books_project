<?php
if (!defined('ABSPATH')) exit;

class Story_Book_Woo {
    private static $instance = null;
    private $core;
    private $product_id;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->core = Story_Book::getInstance();
        $this->product_id = get_option('csb_product_id');
        $this->initHooks();
    }

    private function initHooks() {
        // Setup/maintenance
        add_action('init', [$this, 'ensureProductExists']);
        
        // Cart modifications
        add_filter('woocommerce_add_cart_item_data', [$this, 'addCustomCartData'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'displayCustomCartData'], 10, 2);
        add_filter('woocommerce_cart_needs_shipping', '__return_false');

        // Checkout customization
        add_filter('woocommerce_checkout_fields', [$this, 'customizeCheckoutFields']);
        add_action('woocommerce_checkout_process', [$this, 'validateCheckout']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveOrderMeta']);

        // Order processing
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
        add_filter('woocommerce_order_status_changed_message', [$this, 'customizeStatusChangeMessage'], 10, 3);

        // Admin interface
        if (is_admin()) {
            add_action('add_meta_boxes', [$this, 'addOrderMetaBox']);
            add_filter('woocommerce_admin_order_actions', [$this, 'addCustomOrderActions'], 10, 2);
        }

        // Access control
        add_action('template_redirect', [$this, 'restrictWooPages']);
    }

    public function ensureProductExists() {
        if (!$this->product_id || !wc_get_product($this->product_id)) {
            $this->createProduct();
        }
    }

    private function createProduct() {
        $product = new WC_Product_Simple();
        $product->set_name(__('ספר מותאם אישית', 'custom-story-book'));
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_price(Custom_Story_Book::getOption('product_price', 299));
        $product->set_regular_price(Custom_Story_Book::getOption('product_price', 299));
        $product->set_virtual(true);
        $product->set_sold_individually(true);

        // Add custom meta
        $product->update_meta_data('_csb_product', 'yes');
        
        $this->product_id = $product->save();
        update_option('csb_product_id', $this->product_id);
    }

    public function handleCheckout() {
        // Verify session data exists
        if (!isset($_SESSION['csb_uploaded_files']) || !isset($_SESSION['csb_form_data'])) {
            wp_redirect(add_query_arg('step', 'upload', get_permalink()));
            exit;
        }

        // Clear existing cart
        WC()->cart->empty_cart();

        // Add our product
        WC()->cart->add_to_cart($this->product_id, 1, 0, [], [
            'csb_data' => [
                'form_data' => $_SESSION['csb_form_data'],
                'files_count' => count($_SESSION['csb_uploaded_files'])
            ]
        ]);

        // Proceed to checkout
        wp_redirect(wc_get_checkout_url());
        exit;
    }

    public function addCustomCartData($cart_item_data, $product_id, $variation_id) {
        if ($product_id != $this->product_id) {
            return $cart_item_data;
        }

        if (!session_id()) {
            session_start();
        }

        if (isset($_SESSION['csb_form_data'])) {
            $cart_item_data['csb_data'] = $_SESSION['csb_form_data'];
        }

        return $cart_item_data;
    }

    public function displayCustomCartData($item_data, $cart_item) {
        if (!isset($cart_item['csb_data'])) {
            return $item_data;
        }

        $data = $cart_item['csb_data'];

        $item_data[] = [
            'key' => __('שם הילד/ה', 'custom-story-book'),
            'value' => $data['childName']
        ];

        $item_data[] = [
            'key' => __('סוג ספר', 'custom-story-book'),
            'value' => $data['bookType'] === 'realistic' ? 
                __('ספר ריאליסטי', 'custom-story-book') : 
                __('ספר מצוייר', 'custom-story-book')
        ];

        return $item_data;
    }

    public function customizeCheckoutFields($fields) {
        // Remove unnecessary fields
        unset($fields['order']['order_comments']);
        unset($fields['shipping']);

        // Add custom fields
        $fields['billing']['preferred_contact'] = [
            'type' => 'select',
            'label' => __('דרך תקשורת מועדפת', 'custom-story-book'),
            'required' => true,
            'options' => [
                'phone' => __('טלפון', 'custom-story-book'),
                'email' => __('אימייל', 'custom-story-book'),
                'whatsapp' => __('וואטסאפ', 'custom-story-book')
            ],
            'priority' => 100
        ];

        return $fields;
    }

    public function validateCheckout() {
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['csb_uploaded_files']) || !isset($_SESSION['csb_form_data'])) {
            wc_add_notice(
                __('אירעה שגיאה בתהליך ההזמנה. אנא התחל מחדש.', 'custom-story-book'), 
                'error'
            );
        }
    }

    public function saveOrderMeta($order_id) {
        if (!session_id()) {
            session_start();
        }

        try {
            // Save form data
            if (isset($_SESSION['csb_form_data'])) {
                update_post_meta($order_id, '_csb_details', $_SESSION['csb_form_data']);
            }

            // Process and save files
            if (isset($_SESSION['csb_uploaded_files'])) {
                $this->processOrderFiles($order_id, $_SESSION['csb_uploaded_files']);
            }

            // Add order note
            $order = wc_get_order($order_id);
            $order->add_order_note(
                __('הזמנת ספר מותאם אישית התקבלה', 'custom-story-book')
            );

            // Set initial status
            $order->update_status('csb-new');

            // Clear session
            unset($_SESSION['csb_uploaded_files']);
            unset($_SESSION['csb_form_data']);

        } catch (Exception $e) {
            Custom_Story_Book::log("Error saving order meta: " . $e->getMessage());
            wp_die(__('אירעה שגיאה בשמירת ההזמנה', 'custom-story-book'));
        }
    }

    private function processOrderFiles($order_id, $files) {
        $upload_dir = wp_upload_dir();
        $order_dir = $upload_dir['basedir'] . '/story-books/orders/' . $order_id;

        if (!wp_mkdir_p($order_dir)) {
            throw new Exception(__('שגיאה ביצירת תיקיית הזמנה', 'custom-story-book'));
        }

        $processed_files = [];
        foreach ($files as $file) {
            if (file_exists($file['path'])) {
                $new_path = $order_dir . '/' . basename($file['path']);
                copy($file['path'], $new_path);
                
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

        update_post_meta($order_id, '_csb_files', $processed_files);
    }

    public function handleOrderStatusChange($order_id, $old_status, $new_status, $order) {
        // Only handle our product
        if (!$this->isStoryBookOrder($order)) {
            return;
        }

        switch ($new_status) {
            case 'processing':
                $this->startBookProduction($order);
                break;

            case 'completed':
                $this->finalizeOrder($order);
                break;

            case 'cancelled':
                $this->cleanupOrder($order);
                break;
        }

        // Send notifications
        if (Custom_Story_Book::getOption('email_notifications', true)) {
            $this->sendStatusNotification($order, $new_status);
        }
    }

    private function isStoryBookOrder($order) {
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() === $this->product_id) {
                return true;
            }
        }
        return false;
    }

    public function restrictWooPages() {
        if (!is_admin() && 
            (is_shop() || is_product_category() || is_product_tag() || 
            (is_cart() && !is_checkout()))) {
            wp_redirect(home_url());
            exit;
        }
    }

    // Custom order statuses
    public static function getCustomStatuses() {
        return [
            'csb-new' => [
                'label' => __('הזמנה חדשה', 'custom-story-book'),
                'color' => '#f8d7da'
            ],
            'csb-design' => [
                'label' => __('בעיצוב', 'custom-story-book'),
                'color' => '#cce5ff'
            ],
            'csb-review' => [
                'label' => __('בבדיקה', 'custom-story-book'),
                'color' => '#fff3cd'
            ],
            'csb-ready' => [
                'label' => __('מוכן להדפסה', 'custom-story-book'),
                'color' => '#d4edda'
            ],
            'csb-printing' => [
                'label' => __('בהדפסה', 'custom-story-book'),
                'color' => '#e2e3e5'
            ]
        ];
    }
}
