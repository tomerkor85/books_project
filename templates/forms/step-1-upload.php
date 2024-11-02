<?php if (!defined('ABSPATH')) exit; ?>

<section id="step-1" class="step-container">
    <div class="background-container">
        <div class="background-image"></div>
    </div>

    <div class="content-wrapper">
        <div class="text-container">
            <div class="info-box">
                <h2>העלאת תמונות לספר האישי שלך</h2>
                <div class="guidelines">
                    <p class="highlight">כדי שנוכל ליצור את הספר המושלם, חשוב לשלוח תמונות איכותיות:</p>
                    <ul>
                        <li>
                            <span class="icon">📸</span>
                            <strong>15-20 תמונות</strong> - להפקת ספר מושלם
                        </li>
                        <li>
                            <span class="icon">👤</span>
                            תמונות ברורות של הפנים, ללא כיסוי
                        </li>
                        <li>
                            <span class="icon">👶</span>
                            תמונות של הילד/ה בלבד
                        </li>
                        <li>
                            <span class="icon">📱</span>
                            שילוב של תמונות פנים ותמונות גוף מלא
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="upload-container">
            <!-- שינוי ראשון: ID של אזור הגרירה -->
            <div id="upload-area" class="upload-area">
                <div class="upload-content">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/upload.svg" 
                         alt="העלאת תמונות" 
                         class="upload-icon">
                    
                    <h3 class="upload-title">העלאת תמונות</h3>
                    <p class="upload-description">גררו לכאן את התמונות או לחצו לבחירה</p>

                    <!-- שינוי שני: ID של input הקבצים -->
                    <input type="file" 
                           id="fileUpload" 
                           multiple 
                           accept=".jpg,.jpeg,.png" 
                           class="file-input hidden">
                    
                    <label for="fileUpload" class="btn btn-primary upload-button">
                        בחירת תמונות מהמחשב
                    </label>

                    <div class="file-info hidden">
                        <div class="file-counter">
                            <span id="uploadedFilesCount">0</span> תמונות נבחרו
                        </div>
                        <button type="button" id="resetUpload" class="btn btn-ghost">
                            איפוס בחירה
                        </button>
                    </div>
                </div>

                <!-- שינוי שלישי: ID של רשימת הקבצים -->
                <div id="fileList" class="file-grid"></div>

                <div class="upload-actions">
                    <!-- שינוי רביעי: ID של כפתור ההמשך -->
                    <button type="button" 
                            id="nextStep" 
                            class="btn btn-primary btn-lg hidden"
                            disabled>
                        המשך לשלב הבא
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Template for file item -->
    <template id="fileItemTemplate">
        <div class="file-item">
            <div class="file-preview">
                <img src="" alt="תצוגה מקדימה">
            </div>
            <div class="file-details">
                <span class="file-name"></span>
                <button type="button" class="btn btn-icon remove-file">
                    <svg viewBox="0 0 24 24" width="24" height="24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
        </div>
    </template>
</section>