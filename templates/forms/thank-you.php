<?php
if (!defined('ABSPATH')) exit;

$order_id = get_query_var('order-id');
$order = wc_get_order($order_id);

if (!$order) {
    wp_redirect(home_url());
    exit;
}

get_header();
?>

<div class="thank-you-page">
    <div class="thank-you-content">
        <div class="success-animation">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>

        <h1>תודה על ההזמנה!</h1>
        <h2>הזמנה מספר: <?php echo $order->get_order_number(); ?></h2>

        <div class="order-summary">
            <div class="order-details">
                <h3>פרטי ההזמנה</h3>
                <p>אנחנו מתחילים לעבוד על הספר המיוחד שלך.</p>
                <p>קיבלת אימייל עם פרטי ההזמנה המלאים.</p>
            </div>

            <div class="next-steps">
                <h3>מה הלאה?</h3>
                <ol>
                    <li>הצוות שלנו יבחן את התמונות והפרטים שהעברת</li>
                    <li>ניצור קשר במידה ונצטרך פרטים נוספים</li>
                    <li>נתחיל בעבודה על הספר המיוחד שלך</li>
                    <li>תקבל עדכון ברגע שהספר יהיה מוכן</li>
                </ol>
            </div>

            <div class="contact-info">
                <h3>יש לך שאלות?</h3>
                <p>אנחנו כאן בשבילך!</p>
                <div class="contact-methods">
                    <a href="tel:<?php echo get_theme_mod('phone_number'); ?>" class="contact-button">
                        <i class="phone-icon"></i>
                        התקשר אלינו
                    </a>
                    <a href="https://wa.me/<?php echo get_theme_mod('whatsapp_number'); ?>" class="contact-button whatsapp">
                        <i class="whatsapp-icon"></i>
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <div class="back-home">
            <a href="<?php echo home_url(); ?>" class="home-button">חזרה לדף הבית</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>