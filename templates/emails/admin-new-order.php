<?php
if (!defined('ABSPATH')) exit;

/**
 * Email template for new order notification to admin
 * 
 * Variables available:
 * $order - WC_Order object
 * $book_details - array of book details
 * $uploaded_files - array of uploaded files
 */

$order_id = $order->get_id();
$customer_name = $order->get_formatted_billing_full_name();
$customer_email = $order->get_billing_email();
$customer_phone = $order->get_billing_phone();
$order_date = $order->get_date_created()->date_i18n('d/m/Y H:i');
?>

<div style="text-align: center; margin-bottom: 30px;">
    <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px;">
        <h2 style="margin: 0;">🎉 התקבלה הזמנה חדשה! 🎉</h2>
    </div>
</div>

<!-- Order Summary -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <h3 style="color: #cd9fa4; margin-bottom: 15px;">פרטי ההזמנה</h3>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>מספר הזמנה:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                #<?php echo $order_id; ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>תאריך:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo $order_date; ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>סכום:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo $order->get_formatted_order_total(); ?>
            </td>
        </tr>
    </table>
</div>

<!-- Customer Details -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <h3 style="color: #cd9fa4; margin-bottom: 15px;">פרטי הלקוח</h3>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>שם:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo esc_html($customer_name); ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>אימייל:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <a href="mailto:<?php echo esc_attr($customer_email); ?>" 
                   style="color: #cd9fa4; text-decoration: none;">
                    <?php echo esc_html($customer_email); ?>
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>טלפון:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <a href="tel:<?php echo esc_attr($customer_phone); ?>"
                   style="color: #cd9fa4; text-decoration: none;">
                    <?php echo esc_html($customer_phone); ?>
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>כתובת:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo $order->get_formatted_billing_address(); ?>
            </td>
        </tr>
    </table>
</div>

<!-- Book Details -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <h3 style="color: #cd9fa4; margin-bottom: 15px;">פרטי הספר</h3>
    
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>שם הילד/ה:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo esc_html($book_details['childName']); ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>שם באנגלית:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo esc_html($book_details['childNameEn']); ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>גיל:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo esc_html($book_details['age']); ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>מגדר:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo $book_details['gender'] === 'boy' ? 'ילד' : 'ילדה'; ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <strong>סוג ספר:</strong>
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">
                <?php echo $book_details['bookType'] === 'realistic' ? 'ספר ריאליסטי' : 'ספר מצוייר'; ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px;" colspan="2">
                <strong>סיפור אישי:</strong><br>
                <?php echo nl2br(esc_html($book_details['childStory'])); ?>
            </td>
        </tr>
    </table>
</div>

<!-- Uploaded Files -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <h3 style="color: #cd9fa4; margin-bottom: 15px;">תמונות שהועלו</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">
        <?php foreach ($uploaded_files as $file) : ?>
            <div style="background: white; padding: 5px; border-radius: 4px; text-align: center;">
                <img src="<?php echo esc_url($file['url']); ?>" 
                     alt="<?php echo esc_attr($file['original_name']); ?>"
                     style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">
                <div style="font-size: 12px; margin-top: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?php echo esc_html($file['original_name']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p style="margin-top: 15px; text-align: center;">
        סה"כ <?php echo count($uploaded_files); ?> תמונות הועלו
    </p>
</div>

<!-- Quick Actions -->
<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo admin_url('post.php?post=' . $order_id . '&action=edit'); ?>" 
       style="display: inline-block; padding: 12px 24px; background: #cd9fa4; color: white; 
              text-decoration: none; border-radius: 4px; margin: 0 10px;">
        צפה בהזמנה במערכת
    </a>
    
    <a href="<?php echo esc_url(wp_nonce_url(
        admin_url('admin-ajax.php?action=download_order_files&order_id=' . $order_id),
        'download_files'
    )); ?>" 
       style="display: inline-block; padding: 12px 24px; background: #f8f9fa; color: #3c434a;
              text-decoration: none; border-radius: 4px; margin: 0 10px;">
        הורד את כל התמונות
    </a>
</div>

<!-- Important Notes -->
<div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 4px; color: #856404;">
    <p style="margin: 0;">
        <strong>שים לב:</strong> יש להתחיל בעיבוד ההזמנה בהקדם האפשרי.<br>
        זמן SLA מרגע קבלת ההזמנה: 24 שעות.
    </p>
</div>
