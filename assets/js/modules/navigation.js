export class NavigationHandler {
    constructor(onNavigate) {
        this.onNavigate = onNavigate;
        this.currentStep = 1;
        
        this.elements = {
            steps: {
                1: document.getElementById('step-1'),
                2: document.getElementById('step-2'),
                3: document.getElementById('step-3')
            },
            progressBar: document.querySelector('.progress-line-active'),
            progressSteps: document.querySelectorAll('.progress-step')
        };

        this.animations = {
            duration: 300,
            timing: 'cubic-bezier(0.4, 0, 0.2, 1)'
        };

        this.init();
    }

    init() {
        // בדיקת URL לצורך קביעת השלב הנוכחי
        this.setInitialStep();

        // הוספת מאזינים לכפתורי ניווט
        this.attachNavigationListeners();

        // מאזין לשינויי היסטוריה
        window.addEventListener('popstate', (event) => {
            if (event.state && event.state.step) {
                this.navigateToStep(this.currentStep, event.state.step, false);
            }
        });
    }

    setInitialStep() {
        const urlParams = new URLSearchParams(window.location.search);
        const stepParam = urlParams.get('step');
        if (stepParam) {
            const step = parseInt(stepParam);
            if (step >= 1 && step <= 3) {
                this.currentStep = step;
                this.updateUI();
            }
        }
    }

    attachNavigationListeners() {
        // כפתורי הבא
        document.querySelectorAll('.next-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const targetStep = this.currentStep + 1;
                if (targetStep <= 3) {
                    this.navigateToStep(this.currentStep, targetStep);
                }
            });
        });

        // כפתורי חזרה
        document.querySelectorAll('.back-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const targetStep = this.currentStep - 1;
                if (targetStep >= 1) {
                    this.navigateToStep(this.currentStep, targetStep);
                }
            });
        });
    }

    async navigateToStep(fromStep, toStep, updateHistory = true) {
        if (fromStep === toStep) return;

        // שמירת הצעד הנוכחי
        const prevStep = this.currentStep;
        this.currentStep = toStep;

        try {
            // עדכון היסטוריה
            if (updateHistory) {
                this.updateHistory(toStep);
            }

            // אנימציית מעבר
            await this.animateTransition(
                this.elements.steps[fromStep], 
                this.elements.steps[toStep],
                fromStep < toStep ? 'forward' : 'backward'
            );

            // עדכון UI
            this.updateUI();

            // הודעה לפונקצית Callback
            if (this.onNavigate) {
                this.onNavigate(toStep);
            }

        } catch (error) {
            console.error('Navigation error:', error);
            // שחזור הצעד הקודם במקרה של שגיאה
            this.currentStep = prevStep;
            this.updateUI();
        }
    }

    async animateTransition(fromElement, toElement, direction) {
        if (!fromElement || !toElement) return;

        const animations = {
            forward: {
                out: [
                    { opacity: 1, transform: 'translateX(0)' },
                    { opacity: 0, transform: 'translateX(-20px)' }
                ],
                in: [
                    { opacity: 0, transform: 'translateX(20px)' },
                    { opacity: 1, transform: 'translateX(0)' }
                ]
            },
            backward: {
                out: [
                    { opacity: 1, transform: 'translateX(0)' },
                    { opacity: 0, transform: 'translateX(20px)' }
                ],
                in: [
                    { opacity: 0, transform: 'translateX(-20px)' },
                    { opacity: 1, transform: 'translateX(0)' }
                ]
            }
        };

        // Hide all steps except current and target
        Object.values(this.elements.steps).forEach(step => {
            if (step !== fromElement && step !== toElement) {
                step.style.display = 'none';
            }
        });

        // Animate out current step
        fromElement.style.display = 'block';
        await fromElement.animate(animations[direction].out, {
            duration: this.animations.duration,
            easing: this.animations.timing,
            fill: 'forwards'
        }).finished;

        // Switch display
        fromElement.style.display = 'none';
        toElement.style.display = 'block';

        // Animate in new step
        await toElement.animate(animations[direction].in, {
            duration: this.animations.duration,
            easing: this.animations.timing,
            fill: 'forwards'
        }).finished;

        // Scroll to top smoothly
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    updateUI() {
        // עדכון פס התקדמות
        if (this.elements.progressBar) {
            const progress = ((this.currentStep - 1) / 2) * 100;
            this.elements.progressBar.style.width = `${progress}%`;
        }

        // עדכון צעדים
        this.elements.progressSteps.forEach((step, index) => {
            step.classList.remove('active', 'completed');
            if (index + 1 === this.currentStep) {
                step.classList.add('active');
            } else if (index + 1 < this.currentStep) {
                step.classList.add('completed');
            }
        });

        // עדכון נראות צעדים
        Object.entries(this.elements.steps).forEach(([step, element]) => {
            if (element) {
                element.style.display = parseInt(step) === this.currentStep ? 'block' : 'none';
            }
        });

        // עדכון כפתורי ניווט
        this.updateNavigationButtons();
    }

    updateNavigationButtons() {
        // כפתורי הבא
        document.querySelectorAll('.next-button').forEach(button => {
            const targetStep = parseInt(button.dataset.step || this.currentStep + 1);
            button.disabled = targetStep > 3;
        });

        // כפתורי חזרה
        document.querySelectorAll('.back-button').forEach(button => {
            button.style.display = this.currentStep > 1 ? 'inline-block' : 'none';
        });
    }

    updateHistory(step) {
        const url = new URL(window.location);
        url.searchParams.set('step', step);
        window.history.pushState({ step }, '', url);
    }

    getCurrentStep() {
        return this.currentStep;
    }

    isFirstStep() {
        return this.currentStep === 1;
    }

    isLastStep() {
        return this.currentStep === 3;
    }

    canNavigateBack() {
        return this.currentStep > 1;
    }

    canNavigateForward() {
        return this.currentStep < 3;
    }

    reset() {
        this.navigateToStep(this.currentStep, 1);
    }
}

export default NavigationHandler;
