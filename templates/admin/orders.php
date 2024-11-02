<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php echo esc_html__('הזמנות ספרים', 'custom-story-book'); ?></h1>

    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="order_status" id="filter-by-status">
                <option value=""><?php _e('כל הסטטוסים', 'custom-story-book'); ?></option>
                <?php
                $statuses = wc_get_order_statuses();
                foreach ($statuses as $status => $label) {
                    echo '<option value="' . esc_attr($status) . '">' . esc_html($label) . '</option>';
                }
                ?>
            </select>
            <input type="submit" class="button" value="<?php _e('סנן', 'custom-story-book'); ?>">
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-order-number">
                    <?php _e('מספר הזמנה', 'custom-story-book'); ?>
                </th>
                <th scope="col" class="manage-column column-order-date">
                    <?php _e('תאריך', 'custom-story-book'); ?>
                </th>
                <th scope="col" class="manage-column column-order-status">
                    <?php _e('סטטוס', 'custom-story-book'); ?>
                </th>
                <th scope="col" class="manage-column column-order-total">
                    <?php _e('סכום', 'custom-story-book'); ?>
                </th>
                <th scope="col" class="manage-column column-order-actions">
                    <?php _e('פעולות', 'custom-story-book'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php
            $orders = wc_get_orders(['limit' => 20]);
            
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($order->get_id())); ?>">
                                <?php echo '#' . $order->get_order_number(); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo $order->get_date_created()->date_i18n(get_option('date_format')); ?>
                        </td>
                        <td>
                            <?php echo wc_get_order_status_name($order->get_status()); ?>
                        </td>
                        <td>
                            <?php echo $order->get_formatted_order_total(); ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($order->get_id())); ?>" class="button button-small">
                                <?php _e('ערוך', 'custom-story-book'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="5"><?php _e('אין הזמנות.', 'custom-story-book'); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>