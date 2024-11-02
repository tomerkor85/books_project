<?php
if (!defined('ABSPATH')) exit;

/**
 * Email template for order status updates
 * 
 * Variables available:
 * $order - WC_Order
 * $status - current status
 * $book_details - array of book details
 * $next_steps - array of next steps
 */

$status_map = [
    'csb-design' => [
        'icon' => '🎨',
        'color' => '#cce5ff',
        'text_color' => '#004085',
        'title' => __('הספר שלך נמצא בעיצוב!', 'custom-story-book'),
        'message' => __('הצוות שלנו עובד על יצירת הספר המושלם עבורך.', 'custom-story-book'),
        'eta' => __('זמן משוער: 3-5 ימי עבודה', 'custom-story-book')
    ],
    'csb-review' => [
        'icon' => '📝',
        'color' => '#fff3cd',
        'text_color' => '#856404',
        'title' => __('הספר שלך בבדיקה סופית', 'custom-story-book'),
        'message' => __('אנחנו בודקים את הספר לפני שליחה להדפסה.', 'custom-story-book'),
        'eta' => __('זמן משוער: 1-2 ימי עבודה', 'custom-story-book')
    ],
    'csb-ready' => [
        'icon' => '✨',
        'color' => '#d4edda',
        'text_color' => '#155724',
        'title' => __('הספר שלך מוכן להדפסה!', 'custom-story-book'),
        'message' => __('הספר עבר את כל הבדיקות ומוכן להדפסה.', 'custom-story-book'),
        'eta' => __('זמן משוער להדפסה: 5-7 ימי עבודה', 'custom-story-book')
    ],
    'csb-printing' => [
        'icon' => '🖨️',
        'color' => '#e2e3e5',
        'text_color' => '#383d41',
        'title' => __('הספר שלך בהדפסה', 'custom-story-book'),
        'message' => __('הספר נשלח להדפסה ובקרוב יהיה מוכן!', 'custom-story-book'),
        'eta' => __('זמן משוער למשלוח: 3-4 ימי עבודה', 'custom-story-book')
    ]
];

$current_status = $status_map[$status] ?? [
    'icon' => '📦',
    'color' => '#f8f9fa',
    'text_color' => '#3c434a',
    'title' => __('עדכון סטטוס הזמנה', 'custom-story-book'),
    'message' => '',
    'eta' => ''
];
?>

<div style="text-align: center; margin-bottom: 30px;">
    <!-- Status Icon -->
    <div style="font-size: 48px; margin-bottom: 10px;">
        <?php echo $current_status['icon']; ?>
    </div>

    <!-- Status Title -->
    <h2 style="color: <?php echo $current_status['text_color']; ?>; margin-bottom: 15px;">
        <?php echo esc_html($current_status['title']); ?>
    </h2>

    <!-- Status Message -->
    <div style="background: <?php echo $current_status['color']; ?>; 
                color: <?php echo $current_status['text_color']; ?>; 
                padding: 15px; 
                border-radius: 8px; 
                margin: 20px 0;">
        <p><?php echo esc_html($current_status['message']); ?></p>
        <?php if ($current_status['eta']) : ?>
            <p style="margin-top: 10px; font-weight: 500;">
                <?php echo esc_html($current_status['eta']); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details -->
<div class="book-details">
    <h3><?php _e('פרטי ההזמנה', 'custom-story-book'); ?></h3>
    
    <div class="detail-row">
        <span><?php _e('מספר הזמנה:', 'custom-story-book'); ?></span>
        <strong>#<?php echo $order->get_id(); ?></strong>
    </div>

    <div class="detail-row">
        <span><?php _e('שם הילד/ה:', 'custom-story-book'); ?></span>
        <strong><?php echo esc_html($book_details['childName']); ?></strong>
    </div>

    <div class="detail-row">
        <span><?php _e('סוג ספר:', 'custom-story-book'); ?></span>
        <strong>
            <?php echo $book_details['bookType'] === 'realistic' ? 
                __('ספר ריאליסטי', 'custom-story-book') : 
                __('ספר מצוייר', 'custom-story-book'); ?>
        </strong>
    </div>
</div>

<!-- Next Steps -->
<?php if (!empty($next_steps)) : ?>
<div class="next-steps">
    <h3><?php _e('מה הלאה?', 'custom-story-book'); ?></h3>
    <ul>
        <?php foreach ($next_steps as $step) : ?>
            <li><?php echo esc_html($step); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- View Order -->
<div style="text-align: center; margin: 30px 0;">
    <a href="<?php echo esc_url($order->get_view_order_url()); ?>" 
       class="button">
        <?php _e('צפייה בפרטי ההזמנה המלאים', 'custom-story-book'); ?>
    </a>
</div>

<!-- Progress Track -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 30px;">
    <h3 style="text-align: center; margin-bottom: 20px;">
        <?php _e('מעקב התקדמות', 'custom-story-book'); ?>
    </h3>
    
    <?php
    $steps = [
        'new' => __('הזמנה התקבלה', 'custom-story-book'),
        'design' => __('עיצוב', 'custom-story-book'),
        'review' => __('בדיקה', 'custom-story-book'),
        'ready' => __('מוכן להדפסה', 'custom-story-book'),
        'printing' => __('בהדפסה', 'custom-story-book')
    ];

    $current_step_found = false;
    foreach ($steps as $step_key => $step_label) :
        $is_current = $status === 'csb-' . $step_key;
        $is_completed = !$current_step_found && !$is_current;
        if ($is_current) $current_step_found = true;
    ?>
        <div style="display: flex; align-items: center; margin-bottom: 10px;">
            <div style="width: 24px; height: 24px; border-radius: 50%; 
                        background: <?php echo $is_completed ? '#cd9fa4' : ($is_current ? '#fff3cd' : '#eee'); ?>; 
                        color: <?php echo $is_completed || $is_current ? '#fff' : '#666'; ?>;
                        display: flex; align-items: center; justify-content: center; margin-left: 10px;">
                <?php echo $is_completed ? '✓' : ($is_current ? '•' : ''); ?>
            </div>
            <span style="color: <?php echo $is_current ? '#856404' : ($is_completed ? '#155724' : '#666'); ?>;">
                <?php echo esc_html($step_label); ?>
            </span>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($status === 'csb-ready' || $status === 'csb-printing') : ?>
    <!-- Shipping Note -->
    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 8px; color: #856404;">
        <p style="margin: 0;">
            <strong><?php _e('שים לב:', 'custom-story-book'); ?></strong>
            <?php _e('ברגע שהספר יצא למשלוח, תקבל/י הודעה עם מספר מעקב.', 'custom-story-book'); ?>
        </p>
    </div>
<?php endif; ?>
[
                'order' => $this->object,
                'book_details' => $this->book_details,
                'status' => $this->status,
                'status_message' => $this->get_status_message(),
                'next_steps' => $this->get_next_steps($this->status),
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this
            ],
            '',
            $this->template_base
        );
    }

    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'order' => $this->object,
                'book_details' => $this->book_details,
                'status' => $this->status,
                'status_message' => $this->get_status_message(),
                'next_steps' => $this->get_next_steps($this->status),
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this
            ],
            '',
            $this->template_base
        );
    }

    public function get_default_additional_content() {
        return __(
            'אנחנו כאן לכל שאלה! ניתן ליצור איתנו קשר במייל חוזר או בטלפון: ' . 
            Custom_Story_Book::getOption('support_phone', ''),
            'custom-story-book'
        );
    }

    protected function get_estimated_completion($status) {
        $estimates = [
            'csb-design' => __('כ-3 ימי עבודה', 'custom-story-book'),
            'csb-review' => __('כ-24 שעות', 'custom-story-book'),
            'csb-ready' => __('כ-48 שעות', 'custom-story-book'),
            'csb-printing' => __('4-7 ימי עבודה', 'custom-story-book')
        ];

        return isset($estimates[$status]) ? $estimates[$status] : '';
    }

    protected function get_progress_percentage($status) {
        $percentages = [
            'csb-new' => 0,
            'csb-design' => 25,
            'csb-review' => 50,
            'csb-ready' => 75,
            'csb-printing' => 90,
            'completed' => 100
        ];

        return isset($percentages[$status]) ? $percentages[$status] : 0;
    }
}

return new Story_Book_Status_Email();
