<?php if (!defined('ABSPATH')) exit; ?>

<section id="step-2" class="step-container">
    <div class="content-wrapper">
        <div class="form-container">
            <div class="form-header">
                <h2>ספרו לנו על הילד/ה שלכם</h2>
                <p class="form-subtitle">כדי שנוכל ליצור ספר מותאם במיוחד, נשמח להכיר טוב יותר</p>
            </div>

            <form id="childDetailsForm" class="story-form">
                <div class="form-grid">
                    <!-- שם הילד/ה -->
                    <div class="form-group">
                        <label for="childName" class="form-label required">
                            שם הילד/ה
                        </label>
                        <input type="text" 
                               id="childName" 
                               name="childName" 
                               class="form-input"
                               required
                               minlength="2"
                               maxlength="50">
                    </div>

                    <!-- שם באנגלית -->
                    <div class="form-group">
                        <label for="childNameEn" class="form-label required">
                            שם באנגלית
                        </label>
                        <input type="text" 
                               id="childNameEn" 
                               name="childNameEn" 
                               class="form-input"
                               required
                               pattern="[A-Za-z\s]+"
                               title="אנא השתמשו באותיות באנגלית בלבד">
                    </div>

                    <!-- מגדר -->
                    <div class="form-group half">
                        <label for="gender" class="form-label required">
                            מגדר
                        </label>
                        <select id="gender" 
                                name="gender" 
                                class="form-select"
                                required>
                            <option value="">בחר/י</option>
                            <option value="boy">ילד</option>
                            <option value="girl">ילדה</option>
                        </select>
                    </div>

                    <!-- גיל -->
                    <div class="form-group half">
                        <label for="age" class="form-label required">
                            גיל
                        </label>
                        <input type="number" 
                               id="age" 
                               name="age" 
                               class="form-input"
                               min="0" 
                               max="18" 
                               required>
                    </div>

                    <!-- סוג הספר -->
                    <div class="form-group">
                        <label for="bookType" class="form-label required">
                            סוג הספר
                        </label>
                        <select id="bookType" 
                                name="bookType" 
                                class="form-select"
                                required>
                            <option value="">בחר/י</option>
                            <option value="realistic">ספר ריאליסטי</option>
                            <option value="illustrated">ספר מצוייר</option>
                        </select>
                    </div>

                    <!-- תיאור הילד/ה -->
                    <div class="form-group full">
                        <label for="childStory" class="form-label required">
                            ספרו לנו על הילד/ה
                        </label>
                        <textarea id="childStory" 
                                  name="childStory" 
                                  class="form-textarea"
                                  required
                                  minlength="20"
                                  rows="6"
                                  placeholder="תחביבים, תחומי עניין, אופי, חלומות וכל מה שמיוחד בילד/ה שלכם..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary back-button">
                        חזרה
                    </button>
                    <button type="button" 
                            id="step2NextButton" 
                            class="btn btn-primary"
                            disabled>
                        המשך לתשלום
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>