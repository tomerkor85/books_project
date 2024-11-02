(function($) {
    'use strict';

    // File Upload Handler
    function handleFileUpload() {
        const $fileInput = $('#fileUpload');
        const $dropArea = $('.upload-area');
        const $fileList = $('#fileList');
        const $nextButton = $('#step2NextButton');
        const maxFiles = storyBookAdmin.maxFiles;
        const maxSize = storyBookAdmin.maxSize;

        // Drag & Drop events
        $dropArea.on('dragenter dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });

        $dropArea.on('dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });

        // File Drop
        $dropArea.on('drop', function(e) {
            const files = e.originalEvent.dataTransfer.files;
            processFiles(files);
        });

        // File Input Change
        $fileInput.on('change', function() {
            processFiles(this.files);
        });

        function processFiles(files) {
            if (files.length > maxFiles) {
                alert(storyBookAdmin.i18n.maxFilesError + ' ' + maxFiles);
                return;
            }

            const formData = new FormData();
            let validFiles = true;

            // Validate files
            Array.from(files).forEach((file, index) => {
                if (file.size > maxSize) {
                    alert(storyBookAdmin.i18n.maxSizeError + ' ' + (maxSize / 1024 / 1024) + 'MB');
                    validFiles = false;
                    return;
                }
                formData.append('files[]', file);
            });

            if (!validFiles) return;

            // Add required data
            formData.append('action', 'handle_file_upload');
            formData.append('nonce', storyBookAdmin.nonce);

            // Upload files
            $.ajax({
                url: storyBookAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        updateFileList(response.data.files);
                        if (response.data.files.length >= storyBookAdmin.minFiles) {
                            $nextButton.prop('disabled', false);
                        }
                    } else {
                        alert(response.data.message || storyBookAdmin.i18n.uploadError);
                    }
                },
                error: function() {
                    alert(storyBookAdmin.i18n.uploadError);
                }
            });
        }

        function updateFileList(files) {
            $fileList.empty();
            files.forEach(file => {
                const template = $('#fileItemTemplate').html();
                const $item = $(template);
                $item.find('img').attr('src', file.url);
                $item.find('.file-name').text(file.original_name);
                $item.find('.remove-file').data('file-id', file.id);
                $fileList.append($item);
            });
        }

        // Remove file
        $fileList.on('click', '.remove-file', function() {
            if (!confirm(storyBookAdmin.i18n.confirmDelete)) return;
            
            const fileId = $(this).data('file-id');
            $.ajax({
                url: storyBookAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'remove_story_book_file',
                    file_id: fileId,
                    nonce: storyBookAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateFileList(response.data.files);
                        if (response.data.files.length < storyBookAdmin.minFiles) {
                            $nextButton.prop('disabled', true);
                        }
                    }
                }
            });
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        handleFileUpload();
    });

})(jQuery);