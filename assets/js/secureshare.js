/**
 * SecureShare Frontend JavaScript
 * Handles secret creation form submission and copy-to-clipboard functionality
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {

        // Character counter
        const secretTextarea = document.getElementById('secureshare-secret');
        const charCount = document.getElementById('secureshare-char-count');

        if (secretTextarea && charCount) {
            secretTextarea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
        }

        // Form submission
        const form = document.getElementById('secureshare-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const secret = secretTextarea.value.trim();
            const submitButton = form.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('secureshare-message');
            const resultDiv = document.getElementById('secureshare-result');

            // Validate
            if (!secret) {
                showMessage(messageDiv, secureshareData.strings.error + ': Secret cannot be empty', 'error');
                return;
            }

            if (secret.length > secureshareData.maxSecretSize) {
                showMessage(messageDiv, secureshareData.strings.error + ': Secret is too long', 'error');
                return;
            }

            // Disable submit button
            submitButton.disabled = true;
            submitButton.textContent = secureshareData.strings.creating;

            // Send to REST API
            fetch(secureshareData.restUrl + 'create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    secret: secret
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.url) {
                    // Show success result
                    form.style.display = 'none';
                    messageDiv.style.display = 'none';
                    resultDiv.style.display = 'block';

                    // Set the URL
                    const urlInput = document.getElementById('secureshare-url');
                    urlInput.value = data.url;

                    // Setup copy button
                    setupCopyButton();

                    // Setup "create another" button
                    const createAnotherBtn = document.getElementById('secureshare-create-another');
                    createAnotherBtn.addEventListener('click', function() {
                        form.style.display = 'block';
                        resultDiv.style.display = 'none';
                        secretTextarea.value = '';
                        charCount.textContent = '0';
                        submitButton.disabled = false;
                        submitButton.textContent = submitButton.getAttribute('data-original-text') || 'Create Secure Link';
                    });
                } else {
                    // Show error
                    const errorMessage = data.message || 'Failed to create secure link';
                    showMessage(messageDiv, secureshareData.strings.error + ': ' + errorMessage, 'error');
                    submitButton.disabled = false;
                    submitButton.textContent = submitButton.getAttribute('data-original-text') || 'Create Secure Link';
                }
            })
            .catch(error => {
                console.error('SecureShare error:', error);
                showMessage(messageDiv, secureshareData.strings.error + ': ' + error.message, 'error');
                submitButton.disabled = false;
                submitButton.textContent = submitButton.getAttribute('data-original-text') || 'Create Secure Link';
            });
        });

        // Store original button text
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.setAttribute('data-original-text', submitButton.textContent);
        }
    });

    /**
     * Setup copy button functionality
     */
    function setupCopyButton() {
        const copyBtn = document.getElementById('secureshare-copy-btn');
        const urlInput = document.getElementById('secureshare-url');

        if (!copyBtn || !urlInput) return;

        copyBtn.addEventListener('click', function() {
            urlInput.select();
            urlInput.setSelectionRange(0, 99999); // For mobile devices

            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(urlInput.value)
                    .then(function() {
                        showCopySuccess(copyBtn);
                    })
                    .catch(function(err) {
                        console.error('Copy failed:', err);
                        // Fallback to execCommand
                        fallbackCopy(urlInput, copyBtn);
                    });
            } else {
                // Fallback for older browsers
                fallbackCopy(urlInput, copyBtn);
            }
        });
    }

    /**
     * Fallback copy method for older browsers
     */
    function fallbackCopy(input, button) {
        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            alert(secureshareData.strings.copyError);
        }
    }

    /**
     * Show copy success feedback
     */
    function showCopySuccess(button) {
        const originalText = button.textContent;
        button.textContent = secureshareData.strings.copySuccess;
        button.classList.add('secureshare-button-success');

        setTimeout(function() {
            button.textContent = originalText;
            button.classList.remove('secureshare-button-success');
        }, 2000);
    }

    /**
     * Show message in message div
     */
    function showMessage(messageDiv, text, type) {
        if (!messageDiv) return;

        messageDiv.textContent = text;
        messageDiv.className = 'secureshare-message secureshare-' + type;
        messageDiv.style.display = 'block';

        // Auto-hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }

})();
