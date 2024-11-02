<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php echo esc_html__('ניהול ספרים מותאמים', 'custom-story-book'); ?></h1>
    
    <div class="welcome-panel">
        <div class="welcome-panel-content">
            <h2><?php _e('ברוכים הבאים למערכת ניהול הספרים המותאמים!', 'custom-story-book'); ?></h2>
            <p class="about-description">
                <?php _e('כאן תוכלו לנהל את ההזמנות והגדרות המערכת.', 'custom-story-book'); ?>
            </p>
            
            <?php
            // סטטיסטיקה בסיסית
            $stats = [
                'new_orders' => 0,
                'total_orders' => 0,
                'revenue' => 0
            ];
            
            if (class_exists('WooCommerce')) {
                $orders = wc_get_orders([
                    'status' => 'any',
                    'limit' => -1,
                    'return' => 'ids',
                ]);
                
                $stats['total_orders'] = count($orders);
                
                $new_orders = wc_get_orders([
                    'status' => 'processing',
                    'limit' => -1,
                    'return' => 'ids',
                ]);
                
                $stats['new_orders'] = count($new_orders);
            }
            ?>
            
            <div class="welcome-panel-column-container">
                <div class="welcome-panel-column">
                    <h3><?php _e('סטטיסטיקה', 'custom-story-book'); ?></h3>
                    <ul>
                        <li><?php printf(__('הזמנות חדשות: %d', 'custom-story-book'), $stats['new_orders']); ?></li>
                        <li><?php printf(__('סה"כ הזמנות: %d', 'custom-story-book'), $stats['total_orders']); ?></li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column">
                    <h3><?php _e('פעולות מהירות', 'custom-story-book'); ?></h3>
                    <ul>
                        <li>
                            <a href="<?php echo admin_url('admin.php?page=story-book-orders'); ?>" class="button button-primary">
                                <?php _e('צפה בהזמנות חדשות', 'custom-story-book'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo admin_url('admin.php?page=story-book-settings'); ?>" class="button">
                                <?php _e('הגדרות', 'custom-story-book'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>