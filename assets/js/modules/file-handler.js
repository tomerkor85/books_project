(function($) {
    'use strict';

    // קונפיגורציה ל-FileHandler
    const fileHandlerConfig = {
        maxFiles: 5,
        maxSize: 5 * 1024 * 1024, // מגבלת גודל בקילובייט
        allowedTypes: ['image/jpeg', 'image/png', 'image/jpg'],
        minFiles: 1
    };

    // הגדרת מחלקת FileHandler
    class FileHandler {
        constructor(config, onChange) {
            this.config = config;
            this.onChange = onChange;
            this.files = [];
            this.elements = {
                dropArea: document.querySelector('.upload-area'),
                fileInput: document.querySelector('#fileUpload'),
                fileList: document.querySelector('.file-list'),
                counter: document.querySelector('.file-counter')
            };
            this.init();
        }

        init() {
            if (!this.elements.dropArea) return;
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                this.elements.dropArea.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                this.elements.dropArea.addEventListener(eventName, () => {
                    this.elements.dropArea.classList.add('drag-over');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                this.elements.dropArea.addEventListener(eventName, () => {
                    this.elements.dropArea.classList.remove('drag-over');
                });
            });

            this.elements.dropArea.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                this.handleFiles(files);
            });

            this.elements.fileInput?.addEventListener('change', (e) => {
                this.handleFiles(e.target.files);
            });

            this.fileTemplate = document.getElementById('fileItemTemplate');
        }

        handleFiles(newFiles) {
            const validFiles = Array.from(newFiles).filter(file => this.validateFile(file));
            if (this.files.length + validFiles.length > this.config.maxFiles) {
                this.showError(`ניתן להעלות מקסימום ${this.config.maxFiles} תמונות`);
                return;
            }

            validFiles.forEach(file => {
                const preview = this.createPreview(file);
                this.files.push({
                    file,
                    preview,
                    id: this.generateUniqueId()
                });
            });

            this.updateUI();
            this.onChange(this.files);
        }

        validateFile(file) {
            if (file.size > this.config.maxSize) {
                this.showError(`הקובץ ${file.name} גדול מדי. הגודל המקסימלי המותר הוא ${this.formatSize(this.config.maxSize)}`);
                return false;
            }

            if (!this.config.allowedTypes.includes(file.type)) {
                this.showError('ניתן להעלות רק קבצי JPG, JPEG או PNG');
                return false;
            }

            return true;
        }

        createPreview(file) {
            if (!this.fileTemplate) return null;
            const preview = this.fileTemplate.content.cloneNode(true);
            const container = preview.querySelector('.file-item');
            const img = preview.querySelector('img');
            const name = preview.querySelector('.file-name');
            const removeBtn = preview.querySelector('.remove-file');

            const fileId = this.generateUniqueId();
            container.dataset.fileId = fileId;

            const reader = new FileReader();
            reader.onload = (e) => {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);

            name.textContent = this.truncateFileName(file.name);
            name.title = file.name;
            removeBtn.addEventListener('click', () => this.removeFile(fileId));

            return container;
        }

        removeFile(fileId) {
            const index = this.files.findIndex(f => f.id === fileId);
            if (index === -1) return;
            this.files[index].preview?.remove();
            this.files.splice(index, 1);
            this.updateUI();
            this.onChange(this.files);
        }

        updateUI() {
            if (this.elements.counter) {
                this.elements.counter.textContent = this.files.length > 0 ? `${this.files.length} תמונות נבחרו` : '';
            }

            if (this.elements.fileList) {
                this.elements.fileList.innerHTML = '';
                this.files.forEach(file => {
                    if (file.preview) {
                        this.elements.fileList.appendChild(file.preview);
                    }
                });
            }

            if (this.elements.dropArea) {
                this.elements.dropArea.classList.toggle('has-files', this.files.length > 0);
            }
        }

        async uploadFiles() {
            const totalFiles = this.files.length;

            try {
                const formData = new FormData();
                this.files.forEach((fileObj, index) => {
                    formData.append(`files[${index}]`, fileObj.file);
                });
                formData.append('action', 'handle_file_upload');
                formData.append('nonce', window.storyBookConfig.nonce);

                const response = await fetch(window.storyBookConfig.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.data?.message || 'Upload failed');
                }

                return result.data;

            } catch (error) {
                console.error('Upload error:', error);
                throw error;
            }
        }

        validateFiles() {
            if (this.files.length < this.config.minFiles) {
                this.showError(`יש להעלות לפחות ${this.config.minFiles} תמונות`);
                return false;
            }
            return true;
        }

        showError(message) {
            if (window.storyBook) {
                window.storyBook.showNotification(message, 'error');
            } else {
                console.error(message);
            }
        }

        generateUniqueId() {
            return `file-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        }

        truncateFileName(name, maxLength = 20) {
            if (name.length <= maxLength) return name;
            const ext = name.split('.').pop();
            const nameWithoutExt = name.slice(0, -(ext.length + 1));
            return `${nameWithoutExt.slice(0, maxLength - 3)}...${ext}`;
        }

        formatSize(bytes) {
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            if (bytes === 0) return '0 Byte';
            const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
        }

        reset() {
            this.files = [];
            if (this.elements.fileInput) {
                this.elements.fileInput.value = '';
            }
            this.updateUI();
            this.onChange([]);
        }
    }

    // יצירת מופע חדש של FileHandler
    const fileHandler = new FileHandler(fileHandlerConfig, function(updatedFiles) {
        $('#uploadedFilesCount').text(updatedFiles.length + ' קבצים נבחרו');
    });

    // Initialize file upload handlers
    function handleFileUpload() {
        $('#fileUpload').on('change', (e) => fileHandler.handleFiles(e.target.files));
        $('#resetUpload').on('click', () => fileHandler.reset());
        $('#nextStep').on('click', async () => {
            if (fileHandler.validateFiles()) {
                try {
                    const result = await fileHandler.uploadFiles();
                    console.log('העלאה הושלמה:', result);
                } catch (error) {
                    console.error('שגיאת העלאה:', error);
                }
            }
        });
    }

    $(document).ready(function() {
        handleFileUpload();
    });

})(jQuery);
