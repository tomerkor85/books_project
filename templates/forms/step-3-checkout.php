<?php 
if (!defined('ABSPATH')) exit;

// Ensure WooCommerce is active
if (!class_exists('WooCommerce')) {
    echo '<p class="error">נדרשת התקנת WooCommerce</p>';
    return;
}

// Add product to cart if needed
if (WC()->cart->is_empty()) {
    $product_id = get_option('story_book_product_id');
    WC()->cart->add_to_cart($product_id);
}
?>

<section id="step-3" class="step-container">
    <div class="content-wrapper">
        <div class="checkout-container">
            <div class="order-summary">
                <h2>סיכום הזמנה</h2>
                <div class="summary-content">
                    <div class="summary-item">
                        <span class="label">סוג הספר:</span>
                        <span class="value" id="summaryBookType"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">מספר תמונות:</span>
                        <span class="value" id="summaryPhotoCount"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">שם הילד/ה:</span>
                        <span class="value" id="summaryChildName"></span>
                    </div>
                </div>
            </div>

            <div class="checkout-form">
                <h2>פרטי תשלום</h2>
                <?php 
                // Display WooCommerce checkout form
                echo do_shortcode('[woocommerce_checkout]'); 
                ?>
            </div>

            <div class="secure-checkout">
                <div class="secure-icons">
                    <svg class="secure-icon" viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                    </svg>
                    <span class="secure-text">אתר מאובטח</span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update summary from session storage
    const formData = JSON.parse(sessionStorage.getItem('formData') || '{}');
    const uploadData = JSON.parse(sessionStorage.getItem('uploadData') || '{}');

    document.getElementById('summaryBookType').textContent = 
        formData.bookType === 'realistic' ? 'ספר ריאליסטי' : 'ספר מצוייר';
    
    document.getElementById('summaryPhotoCount').textContent = 
        uploadData.files ? uploadData.files.length + ' תמונות' : '0 תמונות';
    
    document.getElementById('summaryChildName').textContent = 
        formData.childName || '';

    // Add form data to checkout form
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'story_book_data';
    hiddenInput.value = JSON.stringify({
        formData: formData,
        uploadData: uploadData
    });

    document.querySelector('form.checkout').appendChild(hiddenInput);
});
</script>