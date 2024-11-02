<?php
if (!defined('ABSPATH')) exit;

class Story_Book_Cleanup {
    private static $instance = null;
    private $config;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->config = [
            'temp_lifetime' => 24 * HOUR_IN_SECONDS, // 24 שעות
            'completed_orders_lifetime' => 30 * DAY_IN_SECONDS, // 30 ימים
            'failed_orders_lifetime' => 7 * DAY_IN_SECONDS, // 7 ימים
            'batch_size' => 50 // כמות פריטים לטיפול בכל פעם
        ];

        $this->init();
    }

    private function init() {
        // הוספת Cron Events
        add_action('init', [$this, 'scheduleTasks']);
        
        // Handlers לניקוי
        add_action('csb_cleanup_temp_files', [$this, 'cleanupTempFiles']);
        add_action('csb_cleanup_old_orders', [$this, 'cleanupOldOrders']);
        
        // ניקוי בעת ביטול הזמנה
        add_action('woocommerce_order_status_cancelled', [$this, 'cleanupCancelledOrder']);
        add_action('woocommerce_order_status_failed', [$this, 'cleanupFailedOrder']);
        
        // ניקוי בעת מחיקת הזמנה
        add_action('before_delete_post', [$this, 'cleanupDeletedOrder']);
    }

    public function scheduleTasks() {
        if (!wp_next_scheduled('csb_cleanup_temp_files')) {
            wp_schedule_event(time(), 'hourly', 'csb_cleanup_temp_files');
        }

        if (!wp_next_scheduled('csb_cleanup_old_orders')) {
            wp_schedule_event(time(), 'daily', 'csb_cleanup_old_orders');
        }
    }

    public function cleanupTempFiles() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/story-books/temp';

        if (!is_dir($temp_dir)) {
            return;
        }

        Custom_Story_Book::log('Starting temp files cleanup');
        $cleaned = 0;
        $errors = 0;

        // סריקת תיקיות זמניות
        $temp_folders = glob($temp_dir . '/*', GLOB_ONLYDIR);
        
        foreach ($temp_folders as $folder) {
            try {
                // בדיקת גיל התיקייה
                $folder_time = filemtime($folder);
                if ((time() - $folder_time) > $this->config['temp_lifetime']) {
                    if ($this->removeDirectory($folder)) {
                        $cleaned++;
                    } else {
                        $errors++;
                    }
                }
            } catch (Exception $e) {
                Custom_Story_Book::log("Cleanup error: " . $e->getMessage());
                $errors++;
            }
        }

        Custom_Story_Book::log(sprintf(
            'Temp cleanup completed: %d folders cleaned, %d errors',
            $cleaned,
            $errors
        ));
    }

    public function cleanupOldOrders() {
        Custom_Story_Book::log('Starting old orders cleanup');
        
        // קבלת הזמנות ישנות
        $completed_orders = $this->getOldOrders('completed', $this->config['completed_orders_lifetime']);
        $failed_orders = $this->getOldOrders('failed', $this->config['failed_orders_lifetime']);

        $cleaned = 0;
        $errors = 0;

        // ניקוי הזמנות שהושלמו
        foreach ($completed_orders as $order) {
            try {
                if ($this->cleanupOrderFiles($order->get_id(), true)) {
                    $cleaned++;
                }
            } catch (Exception $e) {
                Custom_Story_Book::log("Order cleanup error: " . $e->getMessage());
                $errors++;
            }
        }

        // ניקוי הזמנות שנכשלו
        foreach ($failed_orders as $order) {
            try {
                if ($this->cleanupOrderFiles($order->get_id())) {
                    $cleaned++;
                }
            } catch (Exception $e) {
                Custom_Story_Book::log("Failed order cleanup error: " . $e->getMessage());
                $errors++;
            }
        }

        Custom_Story_Book::log(sprintf(
            'Orders cleanup completed: %d orders cleaned, %d errors',
            $cleaned,
            $errors
        ));
    }

    private function getOldOrders($status, $lifetime) {
        return wc_get_orders([
            'status' => $status,
            'date_created' => '<' . (time() - $lifetime),
            'limit' => $this->config['batch_size']
        ]);
    }

    public function cleanupCancelledOrder($order_id) {
        $this->cleanupOrderFiles($order_id);
    }

    public function cleanupFailedOrder($order_id) {
        $this->cleanupOrderFiles($order_id);
    }

    public function cleanupDeletedOrder($post_id) {
        if (get_post_type($post_id) === 'shop_order') {
            $this->cleanupOrderFiles($post_id);
        }
    }

    private function cleanupOrderFiles($order_id, $keep_meta = false) {
        $upload_dir = wp_upload_dir();
        $order_dir = $upload_dir['basedir'] . '/story-books/orders/' . $order_id;

        // ניקוי קבצים
        if (is_dir($order_dir)) {
            if (!$this->removeDirectory($order_dir)) {
                return false;
            }
        }

        // ניקוי מטא אם נדרש
        if (!$keep_meta) {
            delete_post_meta($order_id, '_csb_files');
            delete_post_meta($order_id, '_csb_details');
        }

        return true;
    }

    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        return @rmdir($dir);
    }

    public function forceCleanup() {
        // עבור שימוש ידני או דרך הממשק המנהל
        $this->cleanupTempFiles();
        $this->cleanupOldOrders();
    }

    public function updateConfig($new_config) {
        $this->config = array_merge($this->config, $new_config);
        
        // עדכון זמני Cron אם נדרש
        if (isset($new_config['temp_lifetime']) || isset($new_config['completed_orders_lifetime'])) {
            $this->rescheduleCleanupTasks();
        }
    }

    private function rescheduleCleanupTasks() {
        // הסרת המשימות הקיימות
        wp_clear_scheduled_hook('csb_cleanup_temp_files');
        wp_clear_scheduled_hook('csb_cleanup_old_orders');
        
        // תזמון מחדש
        $this->scheduleTasks();
    }

    public static function deactivate() {
        // הסרת משימות Cron בעת כיבוי התוסף
        wp_clear_scheduled_hook('csb_cleanup_temp_files');
        wp_clear_scheduled_hook('csb_cleanup_old_orders');
    }
}

// Initialize
Story_Book_Cleanup::getInstance();

// Deactivation
register_deactivation_hook(CSB_PLUGIN_FILE, ['Story_Book_Cleanup', 'deactivate']);
