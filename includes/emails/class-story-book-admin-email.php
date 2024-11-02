<?php
if (!defined('ABSPATH')) exit;

class Story_Book_Admin_Email extends Story_Book_Email {
    public function __construct() {
        parent::__construct();

        $this->id = 'story_book_admin_new';
        $this->title = __('ספר מותאם - הזמנה חדשה', 'custom-story-book');
        $this->description = __('התראות אדמין על הזמנות חדשות של ספרים מותאמים', 'custom-story-book');

        // מקבלי המייל
        $this->recipient = $this->get_option('recipient', get_option('admin_email'));

        // תבנית המייל
        $this->template_html = 'admin-new-order.php';
        $this->template_plain = 'plain/admin-new-order.php';

        // Triggers
        add_action('csb_new_order_notification', [$this, 'trigger'], 10, 2);

        // Default settings
        $this->heading = __('התקבלה הזמנה חדשה לספר מותאם', 'custom-story-book');
        $this->subject = __('הזמנה חדשה #{order_number} - ספר מותאם אישית', 'custom-story-book');

        $this->init_form_fields();
        $this->init_settings();
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => __('אפשר', 'custom-story-book'),
                'type' => 'checkbox',
                'label' => __('אפשר מייל זה', 'custom-story-book'),
                'default' => 'yes'
            ],
            'recipient' => [
                'title' => __('נמען', 'custom-story-book'),
                'type' => 'text',
                'description' => __('הפרד כתובות מייל מרובות בפסיקים', 'custom-story-book'),
                'placeholder' => get_option('admin_email'),
                'default' => get_option('admin_email')
            ],
            'subject' => [
                'title' => __('נושא', 'custom-story-book'),
                'type' => 'text',
                'description' => __('ניתן להשתמש ב: {order_number}', 'custom-story-book'),
                'placeholder' => $this->subject,
                'default' => $this->subject
            ],
            'heading' => [
                'title' => __('כותרת', 'custom-story-book'),
                'type' => 'text',
                'description' => __('כותרת המייל', 'custom-story-book'),
                'placeholder' => $this->heading,
                'default' => $this->heading
            ],
            'additional_content' => [
                'title' => __('תוכן נוסף', 'custom-story-book'),
                'type' => 'textarea',
                'description' => __('טקסט שיופיע מתחת לתוכן המייל', 'custom-story-book'),
                'placeholder' => __('הזן טקסט נוסף (אופציונלי)', 'custom-story-book'),
                'default' => ''
            ]
        ];
    }

    public function trigger($order_id, $order = null) {
        if (!$this->is_enabled() || !$order_id) {
            return;
        }

        if (!$order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        $this->object = $order;
        $this->book_details = $this->get_book_details($order);
        $this->uploaded_files = $this->get_uploaded_files($order);

        $this->recipient = $this->get_option('recipient', get_option('admin_email'));

        $this->placeholders['{order_number}'] = $order->get_order_number();

        // שליחת המייל
        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
    }

    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            [
                'order' => $this->object,
                'book_details' => $this->book_details,
                'uploaded_files' => $this->uploaded_files,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => true,
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
                'uploaded_files' => $this->uploaded_files,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => true,
                'plain_text' => true,
                'email' => $this
            ],
            '',
            $this->template_base
        );
    }

    public function get_default_additional_content() {
        return __('זהו מייל אוטומטי שנשלח כאשר מתקבלת הזמנה חדשה לספר מותאם אישית.', 'custom-story-book');
    }

    public function get_custom_color() {
        return '#cd9fa4'; // צבע מותאם למיילי אדמין
    }

    protected function get_additional_order_details() {
        if (!$this->object) {
            return [];
        }

        return [
            'customer_ip' => $this->object->get_customer_ip_address(),
            'user_agent' => $this->object->get_customer_user_agent(),
            'payment_method' => $this->object->get_payment_method_title(),
            'created_via' => $this->object->get_created_via()
        ];
    }

    protected function get_preview_order() {
        // יצירת הזמנה לתצוגה מקדימה
        $order = new WC_Order();
        $order->set_status('pending');
        $order->set_customer_ip_address('127.0.0.1');
        $order->set_customer_user_agent('Mozilla/5.0');
        $order->set_payment_method_title('תשלום בכרטיס אשראי');
        $order->set_created_via('web');

        return $order;
    }
}

return new Story_Book_Admin_Email();
