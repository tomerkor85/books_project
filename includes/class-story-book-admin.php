<?php
// includes/class-story-book-admin.php

if (!defined('ABSPATH')) exit;

class StoryBookAdmin {
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initHooks();
    }

    private function initHooks() {
        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function addAdminMenus() {
        add_menu_page(
            __('ניהול ספרים מותאמים', 'custom-story-book'),
            __('ספרים מותאמים', 'custom-story-book'),
            'manage_options',
            'story-book-manager',
            [$this, 'renderMainPage'],
            'dashicons-book',
            56
        );

        add_submenu_page(
            'story-book-manager',
            __('הזמנות חדשות', 'custom-story-book'),
            __('הזמנות חדשות', 'custom-story-book'),
            'manage_options',
            'story-book-orders',
            [$this, 'renderOrdersPage']
        );

        add_submenu_page(
            'story-book-manager',
            __('הגדרות', 'custom-story-book'),
            __('הגדרות', 'custom-story-book'),
            'manage_options',
            'story-book-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function enqueueAdminAssets($hook) {
        // הוסף תמיכה בכל העמודים של הפלאגין וגם בעמוד ההזמנה
        if (strpos($hook, 'story-book') !== false || get_post_type() === 'shop_order') {
            // CSS
            wp_enqueue_style(
                'story-book-admin',
                CSB_PLUGIN_URL . 'assets/css/admin.css',
                [],
                CSB_VERSION
            );

            // JS
            wp_enqueue_script(
                'story-book-file-handler',
                CSB_PLUGIN_URL . 'assets/js/file-handler.js',
                ['jquery'],
                CSB_VERSION,
                true
            );

            wp_enqueue_script(
                'story-book-admin',
                CSB_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery', 'story-book-file-handler'],
                CSB_VERSION,
                true
            );

            // Pass data to JavaScript
            wp_localize_script('story-book-admin', 'storyBookAdmin', [
                'ajaxUrl' => admin_url('ajax.php'),
                'nonce' => wp_create_nonce('story-book-admin'),
                'pluginUrl' => CSB_PLUGIN_URL,
                'options' => [
                    'maxFiles' => get_option('csb_options')['max_files'] ?? 20,
                    'minFiles' => get_option('csb_options')['min_files'] ?? 10,
                    'maxFileSize' => get_option('csb_options')['max_file_size'] ?? (50 * 1024 * 1024)
                ],
                'i18n' => [
                    'confirmDelete' => __('האם אתה בטוח שברצונך למחוק?', 'custom-story-book'),
                    'saving' => __('שומר...', 'custom-story-book'),
                    'saved' => __('נשמר בהצלחה', 'custom-story-book'),
                    'error' => __('אירעה שגיאה', 'custom-story-book'),
                    'uploadError' => __('שגיאה בהעלאת הקובץ', 'custom-story-book'),
                    'maxFilesError' => __('חרגת ממספר הקבצים המקסימלי', 'custom-story-book'),
                    'maxFileSizeError' => __('גודל הקובץ חורג מהמותר', 'custom-story-book')
                ]
            ]);
        }
    }

    public function initHooks() {
        // Add existing hooks
        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // Add new hooks for settings
        add_action('admin_init', [$this, 'initSettings']);

        // Add Ajax handlers
        add_action('wp_ajax_handle_file_upload', [$this, 'handleFileUpload']);
        add_action('wp_ajax_remove_story_book_file', [$this, 'removeFile']);
        add_action('wp_ajax_update_story_book_status', [$this, 'updateOrderStatus']);
    }

    public function initSettings() {
        register_setting(
            'csb_options_group',
            'csb_options',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeOptions']
            ]
        );

        add_settings_section(
            'csb_general_settings',
            __('הגדרות כלליות', 'custom-story-book'),
            null,
            'csb_settings'
        );

        $this->addSettingsFields();
    }

    private function addSettingsFields() {
        $fields = [
            'min_files' => [
                'title' => __('מינימום תמונות', 'custom-story-book'),
                'type' => 'number',
                'default' => 10
            ],
            'max_files' => [
                'title' => __('מקסימום תמונות', 'custom-story-book'),
                'type' => 'number',
                'default' => 20
            ],
            'max_file_size' => [
                'title' => __('גודל מקסימלי לקובץ (MB)', 'custom-story-book'),
                'type' => 'number',
                'default' => 50
            ],
            'product_price' => [
                'title' => __('מחיר ספר', 'custom-story-book'),
                'type' => 'number',
                'default' => 299
            ]
        ];

        foreach ($fields as $field_id => $field) {
            add_settings_field(
                'csb_' . $field_id,
                $field['title'],
                [$this, 'renderField'],
                'csb_settings',
                'csb_general_settings',
                [
                    'field_id' => $field_id,
                    'type' => $field['type'],
                    'default' => $field['default']
                ]
            );
        }
    }

    public function renderMainPage() {
        $template_path = CSB_PLUGIN_DIR . 'templates/admin/dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->renderDefaultDashboard();
        }
    }

    public function renderOrdersPage() {
        $template_path = CSB_PLUGIN_DIR . 'templates/admin/orders.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->renderDefaultOrders();
        }
    }

    public function renderSettingsPage() {
        $template_path = CSB_PLUGIN_DIR . 'templates/admin/settings.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->renderDefaultSettings();
        }
    }

    private function renderDefaultDashboard() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('ניהול ספרים מותאמים', 'custom-story-book'); ?></h1>
            
            <div class="welcome-panel">
                <div class="welcome-panel-content">
                    <h2><?php _e('ברוכים הבאים למערכת ניהול הספרים המותאמים!', 'custom-story-book'); ?></h2>
                    <p class="about-description">
                        <?php _e('כאן תוכלו לנהל את ההזמנות והגדרות המערכת.', 'custom-story-book'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderDefaultOrders() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('הזמנות', 'custom-story-book'); ?></h1>
            <div class="card">
                <h2 class="title"><?php _e('הזמנות אחרונות', 'custom-story-book'); ?></h2>
                <?php
                if (class_exists('WooCommerce')) {
                    $orders = wc_get_orders(['status' => 'any', 'limit' => 10]);
                    if (!empty($orders)) {
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr>';
                        echo '<th>' . __('מספר הזמנה', 'custom-story-book') . '</th>';
                        echo '<th>' . __('תאריך', 'custom-story-book') . '</th>';
                        echo '<th>' . __('סטטוס', 'custom-story-book') . '</th>';
                        echo '<th>' . __('סכום', 'custom-story-book') . '</th>';
                        echo '</tr></thead><tbody>';
                        
                        foreach ($orders as $order) {
                            echo '<tr>';
                            echo '<td>#' . $order->get_id() . '</td>';
                            echo '<td>' . $order->get_date_created()->date_i18n(get_option('date_format')) . '</td>';
                            echo '<td>' . wc_get_order_status_name($order->get_status()) . '</td>';
                            echo '<td>' . $order->get_formatted_order_total() . '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                    } else {
                        echo '<p>' . __('אין הזמנות עדיין.', 'custom-story-book') . '</p>';
                    }
                } else {
                    echo '<p>' . __('WooCommerce לא מותקן או לא פעיל.', 'custom-story-book') . '</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function renderDefaultSettings() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('הגדרות ספרים מותאמים', 'custom-story-book'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('csb_options_group');
                $options = get_option('csb_options', []);
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="min_files"><?php _e('מינימום תמונות', 'custom-story-book'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="min_files" 
                                   name="csb_options[min_files]" 
                                   value="<?php echo esc_attr($options['min_files'] ?? 10); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_files"><?php _e('מקסימום תמונות', 'custom-story-book'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="max_files" 
                                   name="csb_options[max_files]" 
                                   value="<?php echo esc_attr($options['max_files'] ?? 20); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product_price"><?php _e('מחיר ספר', 'custom-story-book'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="product_price" 
                                   name="csb_options[product_price]" 
                                   value="<?php echo esc_attr($options['product_price'] ?? 299); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('המחיר בשקלים', 'custom-story-book'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize admin
if (is_admin()) {
    StoryBookAdmin::getInstance();
}
