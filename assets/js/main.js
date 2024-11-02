// Main Story Book Handler
class StoryBookHandler {
    constructor() {
        // Configuration
        this.config = {
            minFiles: window.storyBookConfig?.minFiles || 10,
            maxFiles: window.storyBookConfig?.maxFiles || 20,
            maxSize: window.storyBookConfig?.maxSize || 50 * 1024 * 1024, // 50MB
            allowedTypes: window.storyBookConfig?.allowedTypes || ['image/jpeg', 'image/jpg', 'image/png']
        };

        // State
        this.state = {
            currentStep: 1,
            isUploading: false,
            formValidated: false,
            files: []
        };

        // Modules
        this.fileHandler = null;
        this.formValidator = null;
        this.navigationHandler = null;

        // Elements
        this.elements = {
            progressBar: document.querySelector('.progress-line-active'),
            progressSteps: document.querySelectorAll('.progress-step'),
            loader: document.querySelector('.fullscreen-loader'),
            loaderProgress: document.querySelector('.loader-progress-bar'),
            loaderText: document.querySelector('.loader-text'),
            nextButton: document.getElementById('step2NextButton')
        };

        this.init();
    }

    async init() {
        try {
            await this.loadModules();
            this.attachEventListeners();
            this.updateUI();
            console.log('Story Book Handler initialized successfully');
        } catch (error) {
            console.error('Initialization error:', error);
            this.showNotification('אירעה שגיאה בטעינת המערכת', 'error');
        }
    }

    async loadModules() {
        // Load and initialize modules
        try {
            const { FileHandler } = await import('./modules/file-handler.js');
            const { FormValidator } = await import('./modules/form-validator.js');
            const { NavigationHandler } = await import('./modules/navigation.js');

            this.fileHandler = new FileHandler(this.config, this.handleFileChange.bind(this));
            this.formValidator = new FormValidator(this.handleFormValidation.bind(this));
            this.navigationHandler = new NavigationHandler(this.handleNavigation.bind(this));

        } catch (error) {
            console.error('Error loading modules:', error);
            throw error;
        }
    }

    attachEventListeners() {
        // Navigation buttons
        document.querySelectorAll('[data-step]').forEach(button => {
            button.addEventListener('click', (e) => {
                const targetStep = parseInt(e.target.dataset.step);
                this.handleStepNavigation(targetStep);
            });
        });

        // Next step button
        this.elements.nextButton?.addEventListener('click', () => {
            console.log("Next step button clicked.");
            this.handleStepNavigation(2);
        });

        // Form submission
        document.getElementById('childDetailsForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFormSubmission();
        });

        // Window events
        window.addEventListener('beforeunload', (e) => {
            if (this.state.isUploading) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    async handleStepNavigation(targetStep) {
        if (targetStep === this.state.currentStep) return;

        // Validate current step before proceeding
        if (!await this.validateStep(this.state.currentStep)) {
            return;
        }

        // Handle step transition
        this.navigationHandler.navigateToStep(this.state.currentStep, targetStep);
        this.state.currentStep = targetStep;
        this.updateProgress();
    }

    async validateStep(step) {
        switch(step) {
            case 1:
                return this.fileHandler.validateFiles();
            case 2:
                return this.formValidator.validateForm();
            default:
                return true;
        }
    }

    handleFileChange(files) {
        this.state.files = files;
        this.updateUI();
    }

    handleFormValidation(isValid) {
        this.state.formValidated = isValid;
        this.updateUI();
    }

    updateUI() {
        this.updateProgress();
        this.updateButtons();
    }

    updateProgress() {
        if (!this.elements.progressBar) return;

        const progress = ((this.state.currentStep - 1) / 2) * 100;
        this.elements.progressBar.style.width = `${progress}%`;

        this.elements.progressSteps.forEach((step, index) => {
            step.classList.remove('active', 'completed');
            if (index + 1 === this.state.currentStep) {
                step.classList.add('active');
            } else if (index + 1 < this.state.currentStep) {
                step.classList.add('completed');
            }
        });
    }

    updateButtons() {
        const nextButton = this.elements.nextButton;
        if (nextButton) {
            if (this.state.currentStep === 1) {
                // Show button if enough files are uploaded
                nextButton.classList.toggle('hidden', this.state.files.length < this.config.minFiles);
            } else if (this.state.currentStep === 2) {
                // Show button if form is validated
                nextButton.classList.toggle('hidden', !this.state.formValidated);
            }
        }
    }

    async handleFormSubmission() {
        if (!this.validateAllSteps()) {
            return;
        }

        try {
            this.state.isUploading = true;
            this.showLoader('מעלה את הקבצים...');

            // Upload files
            const uploadResult = await this.fileHandler.uploadFiles();

            // Submit form data
            const formData = this.formValidator.getFormData();
            const submissionResult = await this.submitOrder(uploadResult, formData);

            if (submissionResult.success) {
                window.location.href = submissionResult.redirect;
            } else {
                throw new Error(submissionResult.message);
            }

        } catch (error) {
            console.error('Submission error:', error);
            this.showNotification('אירעה שגיאה בשליחת הטופס', 'error');
        } finally {
            this.state.isUploading = false;
            this.hideLoader();
        }
    }

    async submitOrder(uploadData, formData) {
        const data = new FormData();
        data.append('action', 'submit_story_book_order');
        data.append('nonce', window.storyBookConfig.nonce);
        data.append('upload_data', JSON.stringify(uploadData));
        data.append('form_data', JSON.stringify(formData));

        const response = await fetch(window.storyBookConfig.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        });

        return await response.json();
    }

    validateAllSteps() {
        return this.state.files.length >= this.config.minFiles && this.state.formValidated;
    }

    showLoader(message) {
        if (this.elements.loader) {
            this.elements.loader.classList.add('active');
            if (this.elements.loaderText) {
                this.elements.loaderText.textContent = message;
            }
        }
    }

    hideLoader() {
        if (this.elements.loader) {
            this.elements.loader.classList.remove('active');
        }
    }

    updateLoaderProgress(current, total) {
        if (this.elements.loaderProgress) {
            const percentage = (current / total) * 100;
            this.elements.loaderProgress.style.width = `${percentage}%`;
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Add show class after a small delay for animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Remove after delay
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.storyBook = new StoryBookHandler();
});

// Export for module usage
export default StoryBookHandler;
