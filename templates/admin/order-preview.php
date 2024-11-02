<?php
if (!defined('ABSPATH')) exit;

$order_id = $order->get_id();
$book_details = get_post_meta($order_id, '_csb_details', true);
$book_files = get_post_meta($order_id, '_csb_files', true);
?>

<div class="order-preview">
    <h2><?php printf(__('תצוגה מקדימה של הזמנה #%s', 'custom-story-book'), $order_id); ?></h2>

    <div class="order-meta">
        <p>
            <strong><?php _e('תאריך:', 'custom-story-book'); ?></strong>
            <?php echo $order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?>
        </p>
        <p>
            <strong><?php _e('סטטוס:', 'custom-story-book'); ?></strong>
            <span class="status-badge status-<?php echo $order->get_status(); ?>">
                <?php echo wc_get_order_status_name($order->get_status()); ?>
            </span>
        </p>
    </div>

    <div class="customer-info">
        <h3><?php _e('פרטי לקוח', 'custom-story-book'); ?></h3>
        <p>
            <strong><?php _e('שם:', 'custom-story-book'); ?></strong>
            <?php echo $order->get_formatted_billing_full_name(); ?>
        </p>
        <p>
            <strong><?php _e('אימייל:', 'custom-story-book'); ?></strong>
            <a href="mailto:<?php echo $order->get_billing_email(); ?>">
                <?php echo $order->get_billing_email(); ?>
            </a>
        </p>
        <p>
            <strong><?php _e('טלפון:', 'custom-story-book'); ?></strong>
            <a href="tel:<?php echo $order->get_billing_phone(); ?>">
                <?php echo $order->get_billing_phone(); ?>
            </a>
        </p>
    </div>

    <?php if ($book_details) : ?>
    <div class="book-details">
        <h3><?php _e('פרטי הספר', 'custom-story-book'); ?></h3>
        <table class="widefat">
            <tr>
                <th><?php _e('שם הילד/ה:', 'custom-story-book'); ?></th>
                <td><?php echo esc_html($book_details['childName']); ?></td>
            </tr>
            <tr>
                <th><?php _e('שם באנגלית:', 'custom-story-book'); ?></th>
                <td><?php echo esc_html($book_details['childNameEn']); ?></td>
            </tr>
            <tr>
                <th><?php _e('מגדר:', 'custom-story-book'); ?></th>
                <td><?php echo $book_details['gender'] === 'boy' ? __('בן', 'custom-story-book') : __('בת', 'custom-story-book'); ?></td>
            </tr>
            <tr>
                <th><?php _e('גיל:', 'custom-story-book'); ?></th>
                <td><?php echo esc_html($book_details['age']); ?></td>
            </tr>
            <tr>
                <th><?php _e('סוג הספר:', 'custom-story-book'); ?></th>
                <td><?php echo $book_details['bookType'] === 'realistic' ? __('ספר ריאליסטי', 'custom-story-book') : __('ספר מצוייר', 'custom-story-book'); ?></td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($book_files) : ?>
    <div class="book-files">
        <h3><?php _e('תמונות', 'custom-story-book'); ?></h3>
        <div class="files-grid">
            <?php foreach ($book_files as $file) : ?>
                <div class="file-item">
                    <img src="<?php echo esc_url($file['url']); ?>" 
                         alt="<?php echo esc_attr($file['original_name']); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.order-preview {
    padding: 20px;
}

.order-meta {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.customer-info,
.book-details {
    margin-bottom: 20px;
}

.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
}

.file-item img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
}
</style>