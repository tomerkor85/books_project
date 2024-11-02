export class FormValidator {
    constructor(onValidationChange) {
        this.onValidationChange = onValidationChange;
        
        this.form = document.getElementById('childDetailsForm');
        this.validationRules = {
            childName: {
                required: true,
                minLength: 2,
                maxLength: 50,
                pattern: /^[\u0590-\u05FF\s'"-]{2,}$/,
                messages: {
                    required: 'נא להזין את שם הילד/ה',
                    minLength: 'השם חייב להכיל לפחות 2 תווים',
                    maxLength: 'השם לא יכול להכיל יותר מ-50 תווים',
                    pattern: 'נא להזין שם בעברית בלבד'
                }
            },
            childNameEn: {
                required: true,
                minLength: 2,
                maxLength: 50,
                pattern: /^[a-zA-Z\s'"-]{2,}$/,
                messages: {
                    required: 'נא להזין את השם באנגלית',
                    minLength: 'השם חייב להכיל לפחות 2 תווים',
                    maxLength: 'השם לא יכול להכיל יותר מ-50 תווים',
                    pattern: 'נא להזין שם באנגלית בלבד'
                }
            },
            gender: {
                required: true,
                messages: {
                    required: 'נא לבחור מגדר'
                }
            },
            age: {
                required: true,
                min: 0,
                max: 18,
                pattern: /^\d+$/,
                messages: {
                    required: 'נא להזין גיל',
                    min: 'הגיל לא יכול להיות שלילי',
                    max: 'הגיל לא יכול להיות גבוה מ-18',
                    pattern: 'נא להזין מספר שלם'
                }
            },
            bookType: {
                required: true,
                messages: {
                    required: 'נא לבחור סוג ספר'
                }
            },
            childStory: {
                required: true,
                minLength: 20,
                maxLength: 1000,
                messages: {
                    required: 'נא לספר לנו על הילד/ה',
                    minLength: 'התיאור חייב להכיל לפחות 20 תווים',
                    maxLength: 'התיאור לא יכול להכיל יותר מ-1000 תווים'
                }
            }
        };

        this.errors = new Map();
        this.init();
    }

    init() {
        if (!this.form) return;

        // Add validation to all form fields
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('input', () => this.validateField(field));
            field.addEventListener('blur', () => this.validateField(field));
            
            // Add error display element
            const errorDiv = document.createElement('div');
            errorDiv.className = 'form-error hidden';
            field.parentNode.appendChild(errorDiv);
        });

        // Prevent default form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.validateForm();
        });

        // Initial validation
        this.validateForm();
    }

    validateField(field) {
        const rules = this.validationRules[field.name];
        if (!rules) return true;

        const errors = [];

        // Required check
        if (rules.required && !field.value.trim()) {
            errors.push(rules.messages.required);
        }

        if (field.value.trim()) {
            // Min length
            if (rules.minLength && field.value.length < rules.minLength) {
                errors.push(rules.messages.minLength);
            }

            // Max length
            if (rules.maxLength && field.value.length > rules.maxLength) {
                errors.push(rules.messages.maxLength);
            }

            // Pattern
            if (rules.pattern && !rules.pattern.test(field.value)) {
                errors.push(rules.messages.pattern);
            }

            // Min value (for numbers)
            if (rules.min !== undefined && Number(field.value) < rules.min) {
                errors.push(rules.messages.min);
            }

            // Max value (for numbers)
            if (rules.max !== undefined && Number(field.value) > rules.max) {
                errors.push(rules.messages.max);
            }
        }

        this.updateFieldStatus(field, errors);
        return errors.length === 0;
    }

    updateFieldStatus(field, errors) {
        const container = field.parentNode;
        const errorDiv = container.querySelector('.form-error');
        
        if (errors.length > 0) {
            this.errors.set(field.name, errors);
            field.classList.add('error');
            if (errorDiv) {
                errorDiv.textContent = errors[0];
                errorDiv.classList.remove('hidden');
            }
        } else {
            this.errors.delete(field.name);
            field.classList.remove('error');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
        }

        // Update form status
        const isValid = this.errors.size === 0;
        this.onValidationChange(isValid);
        
        // Update next button
        const nextButton = document.getElementById('step2NextButton');
        if (nextButton) {
            nextButton.disabled = !isValid;
        }
    }

    validateForm() {
        if (!this.form) return false;

        let isValid = true;
        const fields = this.form.querySelectorAll('input, select, textarea');

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    getFormData() {
        if (!this.form) return null;

        const formData = new FormData(this.form);
        return Object.fromEntries(formData.entries());
    }

    highlightErrors() {
        this.errors.forEach((errors, fieldName) => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                field.focus();
                return false; // Break the loop after first error
            }
        });
    }

    reset() {
        if (!this.form) return;

        this.form.reset();
        this.errors.clear();
        
        this.form.querySelectorAll('.form-error').forEach(errorDiv => {
            errorDiv.classList.add('hidden');
        });

        this.form.querySelectorAll('.error').forEach(field => {
            field.classList.remove('error');
        });

        this.onValidationChange(false);
    }

    setInitialValues(values) {
        if (!this.form || !values) return;

        Object.entries(values).forEach(([key, value]) => {
            const field = this.form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = value;
                this.validateField(field);
            }
        });
    }

    isValid() {
        return this.errors.size === 0;
    }

    getErrors() {
        return Object.fromEntries(this.errors);
    }
}

export default FormValidator;