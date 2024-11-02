# מערכת הזמנת ספרים מותאמים אישית

## דרישות מערכת
- WordPress 5.8 ומעלה
- WooCommerce 6.0 ומעלה
- PHP 7.4 ומעלה
- MySQL 5.7 ומעלה
- תמיכה ב-ZIP extension
- הרשאות כתיבה לתיקיית Uploads

## התקנה

### 1. העלאת הקבצים
```bash
# בתיקיית הפלאגינים של WordPress
cd wp-content/plugins/
# העתקת הקבצים
git clone [repository-url] custom-story-book
# או העלאה ידנית של קבצי הזיפ
```

### 2. הפעלת התוסף
1. היכנס לפאנל הניהול של WordPress
2. עבור ל-'תוספים' > 'תוספים מותקנים'
3. מצא את "Custom Story Book" והפעל

### 3. הגדרות ראשוניות
1. עבור ל-'ספרים מותאמים' > 'הגדרות'
2. הגדר את הפרטים הבאים:
   - כתובת אימייל לקבלת הזמנות
   - טלפון תמיכה
   - מחיר הספר
   - הגדרות העלאת קבצים
   - תבניות מיילים

### 4. יצירת עמודים
יש ליצור את העמודים הבאים:
1. עמוד הזמנה: הוסף את הקיצור `[story_book_form]`
2. עמוד תודה: הגדר בהגדרות WooCommerce

```php
// דוגמה לעמוד הזמנה
[story_book_form]
```

### 5. הרשאות תיקיות
```bash
# הגדרת הרשאות לתיקיות ההעלאה
chmod 755 wp-content/uploads/story-books
chmod 755 wp-content/uploads/story-books/orders
chmod 755 wp-content/uploads/story-books/temp
```

## שימוש

### הוספת טופס הזמנה
```php
// בתבנית העמוד
echo do_shortcode('[story_book_form]');
```

### התאמת עיצוב
1. העתק את תיקיית `templates` לתבנית האתר שלך
2. התאם את הקבצים לפי הצורך

### התאמת תבניות מייל
1. העתק את הקבצים מ-`templates/emails`
2. התאם את העיצוב והתוכן

## טיפול בבעיות נפוצות

### בעיות העלאה
1. בדוק הגדרות PHP:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```

2. בדוק הרשאות תיקיות:
```bash
ls -la wp-content/uploads/story-books
```

### בעיות תצוגה
1. נקה קאש:
```bash
wp cache flush
```

2. רענן Permalinks:
```bash
wp rewrite flush
```

## הגדרות מתקדמות

### שינוי הגדרות העלאה
```php
add_filter('story_book_upload_config', function($config) {
    $config['max_files'] = 25;
    $config['min_files'] = 5;
    return $config;
});
```

### הוספת סוגי ספרים
```php
add_filter('story_book_book_types', function($types) {
    $types['fantasy'] = 'ספר פנטזיה';
    return $types;
});
```

### התאמת תבניות מייל
```php
add_filter('story_book_email_template', function($template, $type) {
    if ($type === 'status_update') {
        $template = get_stylesheet_directory() . '/templates/emails/custom-status.php';
    }
    return $template;
}, 10, 2);
```

## תחזוקה

### ניקוי קבצים זמניים
```php
// ניקוי ידני
StoryBook_Cleanup::getInstance()->forceCleanup();
```

### גיבוי
1. גבה את תיקיית ההעלאות:
```bash
tar -czf story-books-backup.tar.gz wp-content/uploads/story-books
```

2. גבה את בסיס הנתונים:
```bash
wp db export story-books-backup.sql
```

## שדרוג

1. גבה את הקבצים והנתונים
2. השבת את התוסף
3. העלה את הגרסה החדשה
4. הפעל מחדש את התוסף
5. בדוק את ההגדרות

## תמיכה

- מדריכים: [קישור למדריכים]
- באגים: [קישור לדיווח באגים]
- תמיכה טכנית: support@example.com

## רישוי
כל הזכויות שמורות © 2024
