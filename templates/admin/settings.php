<?php
if (!defined('ABSPATH')) exit;

$options = get_option('csb_options', []);
?>

<div class="wrap">
    <h1><?php echo esc_html__('הגדרות ספרים מותאמים', 'custom-story-book'); ?></h1>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('csb_options_group');
        do_settings_sections('csb_settings');
        ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <?php _e('מינימום תמונות', 'custom-story-book'); ?>
                </th>
                <td>
                    <input type="number" 
                           name="csb_options[min_files]" 
                           value="<?php echo esc_attr($options['min_files'] ?? 10); ?>" 
                           min="1" 
                           max="50"
                           class="small-text">
                    <p class="description">
                        <?php _e('מספר מינימלי של תמונות שהלקוח חייב להעלות', 'custom-story-book'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('מקסימום תמונות', 'custom-story-book'); ?>
                </th>
                <td>
                    <input type="number" 
                           name="csb_options[max_files]" 
                           value="<?php echo esc_attr($options['max_files'] ?? 20); ?>" 
                           min="1" 
                           max="100"
                           class="small-text">
                    <p class="description">
                        <?php _e('מספר מקסימלי של תמונות שהלקוח יכול להעלות', 'custom-story-book'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('גודל מקסימלי לקובץ', 'custom-story-book'); ?>
                </th>
                <td>
                    <input type="number" 
                           name="csb_options[max_file_size]" 
                           value="<?php echo esc_attr(($options['max_file_size'] ?? 50 * 1024 * 1024) / (1024 * 1024)); ?>" 
                           min="1" 
                           max="100"
                           class="small-text">
                    <span class="description">MB</span>
                    <p class="description">
                        <?php _e('גודל מקסימלי בMB לכל תמונה שמועלית', 'custom-story-book'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('מחיר ספר', 'custom-story-book'); ?>
                </th>
                <td>
                    <input type="number" 
                           name="csb_options[product_price]" 
                           value="<?php echo esc_attr($options['product_price'] ?? 299); ?>" 
                           min="0" 
                           step="0.01"
                           class="regular-text">
                    <span class="description">₪</span>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('התראות במייל', 'custom-story-book'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="csb_options[email_notifications]" 
                               value="1" 
                               <?php checked(($options['email_notifications'] ?? true), true); ?>>
                        <?php _e('שלח התראות במייל על הזמנות חדשות', 'custom-story-book'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('אימייל למשלוח התראות', 'custom-story-book'); ?>
                </th>
                <td>
                    <input type="email" 
                           name="csb_options[admin_email]" 
                           value="<?php echo esc_attr($options['admin_email'] ?? get_option('admin_email')); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php _e('כתובת האימייל אליה ישלחו התראות על הזמנות חדשות', 'custom-story-book'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>