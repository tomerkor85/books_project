<?php
if (!defined('ABSPATH')) exit;

$order_id = $order->get_id();
$book_details = get_post_meta($order_id, '_story_book_details', true);
$book_files = get_post_meta($order_id, '_story_book_files', true);
?>

<div class="story-book-order-details">
    <h3>פרטי הספר המותאם אישית</h3>
    
    <?php if ($book_details) : ?>
    <div class="book-details">
        <table class="widefat fixed">
            <tbody>
                <tr>
                    <th>שם הילד/ה:</th>
                    <td><?php echo esc_html($book_details['childName']); ?></td>
                </tr>
                <tr>
                    <th>שם באנגלית:</th>
                    <td><?php echo esc_html($book_details['childNameEn']); ?></td>
                </tr>
                <tr>
                    <th>מגדר:</th>
                    <td><?php echo $book_details['gender'] === 'boy' ? 'ילד' : 'ילדה'; ?></td>
                </tr>
                <tr>
                    <th>גיל:</th>
                    <td><?php echo esc_html($book_details['age']); ?></td>
                </tr>
                <tr>
                    <th>סוג הספר:</th>
                    <td><?php echo $book_details['bookType'] === 'realistic' ? 'ספר ריאליסטי' : 'ספר מצוייר'; ?></td>
                </tr>
                <tr>
                    <th>סיפור הילד/ה:</th>
                    <td><?php echo nl2br(esc_html($book_details['childStory'])); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($book_files) : ?>
    <div class="book-files">
        <h4>תמונות שהועלו</h4>
        <div class="files-grid">
            <?php foreach ($book_files as $file) : ?>
                <div class="file-item">
                    <div class="file-preview">
                        <img src="<?php echo esc_url($file['url']); ?>" 
                             alt="<?php echo esc_attr($file['original_name']); ?>">
                    </div>
                    <div class="file-info">
                        <span class="file-name"><?php echo esc_html($file['original_name']); ?></span>
                        <a href="<?php echo esc_url($file['url']); ?>" 
                           class="button button-secondary"
                           download>
                            הורד
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bulk-download">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('download_story_book_files'); ?>
                <input type="hidden" name="action" value="download_story_book_files">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <button type="submit" class="button button-primary">
                    הורד את כל התמונות (ZIP)
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="order-actions">
        <select name="story_book_status" id="story_book_status">
            <option value="">עדכן סטטוס ספר</option>
            <option value="processing">בעיבוד</option>
            <option value="design">בעיצוב</option>
            <option value="review">בבדיקה</option>
            <option value="ready">מוכן להדפסה</option>
            <option value="printing">בהדפסה</option>
        </select>
        <button type="button" class="button update-status">עדכן סטטוס</button>
    </div>
</div>

<style>
.story-book-order-details {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.book-details table {
    margin: 15px 0;
}

.book-details th {
    width: 150px;
    font-weight: 600;
}

.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.file-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.file-preview img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.file-info {
    padding: 8px;
}

.file-name {
    display: block;
    font-size: 12px;
    margin-bottom: 5px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.bulk-download {
    margin-top: 20px;
}

.order-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.order-actions select {
    margin-right: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.update-status').on('click', function() {
        const status = $('#story_book_status').val();
        if (!status) return;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_story_book_status',
                order_id: <?php echo $order_id; ?>,
                status: status,
                nonce: '<?php echo wp_create_nonce('update_story_book_status'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('אירעה שגיאה בעדכון הסטטוס');
                }
            }
        });
    });
});
</script>