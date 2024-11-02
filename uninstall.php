<?php
// אם לא נקרא ישירות מ-WordPress - צא
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// הגדרת קבוע לשמירת מידע (אם מוגדר בהגדרות התוסף)
$keep_data = get_option('csb_keep_data', false);

// אם לא לשמור מידע - נקה הכל
if (!$keep_data) {
    // מחיקת הגדרות
    delete_option('csb_options');
    delete_option('csb_product_id');
    delete_option('csb_version');
    delete_option('csb_flush_rules');
    
    // מחיקת מטא דאטה מהזמנות
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_csb_%'");

    // מחיקת קבצים
    $upload_dir = wp_upload_dir();
    $story_books_dir = $upload_dir['basedir'] . '/story-books';

    // פונקציה רקורסיבית למחיקת תיקיות
    function csb_recursive_rmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        csb_recursive_rmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    // מחיקת תיקיית האחסון
    if (is_dir($story_books_dir)) {
        csb_recursive_rmdir($story_books_dir);
    }

    // מחיקת המוצר
    $product_id = get_option('csb_product_id');
    if ($product_id) {
        wp_delete_post($product_id, true);
    }

    // מחיקת תרגומים
    $locale = get_locale();
    $mofile = WP_LANG_DIR . '/plugins/custom-story-book-' . $locale . '.mo';
    if (file_exists($mofile)) {
        unlink($mofile);
    }

    // מחיקת סטטוסים מותאמים אישית מההזמנות
    $custom_statuses = array('csb-new', 'csb-design', 'csb-review', 'csb-ready', 'csb-printing');
    foreach ($custom_statuses as $status) {
        $orders = wc_get_orders(array(
            'status' => $status,
            'limit' => -1
        ));

        foreach ($orders as $order) {
            $order->update_status('processing');
        }
    }

    // איפוס רולים וקאפביליטיז שהתוסף הוסיף
    $role = get_role('administrator');
    if ($role) {
        $role->remove_cap('manage_story_books');
    }
}

// ניקוי transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_csb_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_csb_%'");

// רענון rewrite rules
flush_rewrite_rules();
