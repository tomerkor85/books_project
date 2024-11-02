<?php
/**
 * Base email template for all Story Book emails
 * This template is used as a wrapper for all other email templates
 */

if (!defined('ABSPATH')) exit;

$header_img = Custom_Story_Book::getAssetUrl('images/email-header.png');
$logo = Custom_Story_Book::getAssetUrl('images/logo.png');
$footer_text = Custom_Story_Book::getOption('email_footer_text', __('תודה שבחרת בנו!', 'custom-story-book'));
?>
<!DOCTYPE html>
<html dir="rtl" lang="he-IL">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_bloginfo('name'); ?></title>
    <style>
        /* Reset */
        body, div, p, h1, h2, h3, h4, h5, h6 { 
            margin: 0;
            padding: 0;
        }
        
        /* Base Styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            direction: rtl;
        }

        /* Container */
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        /* Header */
        .email-header {
            background: #cd9fa4;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }

        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }

        /* Content */
        .email-content {
            padding: 30px;
            background: #ffffff;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin: 10px 0;
        }

        .status-new { background: #f8d7da; color: #721c24; }
        .status-design { background: #cce5ff; color: #004085; }
        .status-review { background: #fff3cd; color: #856404; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-printing { background: #e2e3e5; color: #383d41; }

        /* Book Details */
        .book-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        /* Next Steps */
        .next-steps {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .next-steps h3 {
            color: #856404;
            margin-bottom: 15px;
        }

        .next-steps ul {
            margin: 0;
            padding-right: 20px;
        }

        .next-steps li {
            margin-bottom: 10px;
        }

        /* Buttons */
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #cd9fa4;
            color: #ffffff;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px 0;
            text-align: center;
        }

        .button:hover {
            background: #c39298;
        }

        /* Footer */
        .email-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            font-size: 14px;
            color: #666666;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-link {
            display: inline-block;
            margin: 0 5px;
            color: #666666;
            text-decoration: none;
        }

        .contact-info {
            margin: 15px 0;
        }

        .contact-info a {
            color: #cd9fa4;
            text-decoration: none;
        }

        /* Responsive */
        @media screen and (max-width: 600px) {
            .email-container {
                margin: 10px;
            }

            .email-header,
            .email-content,
            .email-footer {
                padding: 20px;
            }

            .detail-row {
                flex-direction: column;
            }

            .button {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <img src="<?php echo esc_url($logo); ?>" 
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>" 
                 class="logo">
            <h1><?php echo esc_html($email_heading); ?></h1>
        </div>

        <!-- Content -->
        <div class="email-content">
            <?php echo $content; ?>

            <?php if (!empty($additional_content)) : ?>
                <div class="additional-content">
                    <?php echo wp_kses_post(wpautop(wptexturize($additional_content))); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <?php if (!$sent_to_admin) : ?>
                <div class="contact-info">
                    <?php if ($phone = Custom_Story_Book::getOption('support_phone')) : ?>
                        <div class="contact-item">
                            טלפון: <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                        </div>
                    <?php endif; ?>

                    <?php if ($email = Custom_Story_Book::getOption('support_email')) : ?>
                        <div class="contact-item">
                            אימייל: <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                        </div>
                    <?php endif; ?>

                    <?php if ($whatsapp = Custom_Story_Book::getOption('support_whatsapp')) : ?>
                        <div class="contact-item">
                            WhatsApp: <a href="https://wa.me/<?php echo esc_attr($whatsapp); ?>"><?php echo esc_html($whatsapp); ?></a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="social-links">
                    <?php
                    $social_links = [
                        'facebook' => Custom_Story_Book::getOption('facebook_url'),
                        'instagram' => Custom_Story_Book::getOption('instagram_url'),
                        'tiktok' => Custom_Story_Book::getOption('tiktok_url')
                    ];

                    foreach ($social_links as $platform => $url) :
                        if ($url) :
                    ?>
                        <a href="<?php echo esc_url($url); ?>" class="social-link" target="_blank">
                            <?php echo esc_html(ucfirst($platform)); ?>
                        </a>
                    <?php 
                        endif;
                    endforeach;
                    ?>
                </div>
            <?php endif; ?>

            <div class="footer-text">
                <?php echo wp_kses_post(wpautop($footer_text)); ?>
            </div>

            <div class="copyright">
                © <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>
            </div>
        </div>
    </div>
</body>
</html>
