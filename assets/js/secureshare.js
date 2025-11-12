/**
 * SecureShare - Client-side JavaScript for WordPress
 *
 * Handles form submission, character counting, and clipboard operations.
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Character counter for create form
        const secretInput = document.getElementById('secureshare-secret');
        const charCount = document.getElementById('secureshare-char-count');

        if (secretInput && charCount) {
            secretInput.addEventListener('input', function() {
                charCount.textContent = this.value.length.toLocaleString();
            });
        }

        // Form submission for creating secrets
        const secretForm = document.getElementById('secureshare-form');
        if (secretForm) {
            secretForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const messageDiv = document.getElementById('secureshare-message');
                const resultDiv = document.getElementById('secureshare-result');
                const submitBtn = this.querySelector('button[type="submit"]');

                // Reset display
                messageDiv.style.display = 'none';
                resultDiv.style.display = 'none';

                // Disable button and show loading
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.textContent = secureshareData.strings.creating || 'Creating...';

                try {
                    // Prepare request data
                    const formData = new FormData();
                    formData.append('secret', secretInput.value);
                    formData.append('nonce', secureshareData.nonce);

                    // Send request to REST API
                    const response = await fetch(secureshareData.restUrl + 'create', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        // Show success result
                        document.getElementById('secureshare-url').value = data.url;
                        resultDiv.style.display = 'block';

                        // Clear the form
                        secretInput.value = '';
                        charCount.textContent = '0';

                        // Hide the form
                        secretForm.style.display = 'none';

                        // Scroll to result
                        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        // Show error
                        showMessage(messageDiv, data.message || 'An error occurred', 'error');
                    }
                } catch (error) {
                    // Network or parsing error
                    showMessage(messageDiv, 'Failed to create secret. Please try again.', 'error');
                    console.error('SecureShare Error:', error);
                } finally {
                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        }

        // Copy URL to clipboard
        const copyBtn = document.getElementById('secureshare-copy-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                const urlInput = document.getElementById('secureshare-url');
                copyToClipboard(urlInput.value, this);
            });
        }

        // Create another secret button
        const createAnotherBtn = document.getElementById('secureshare-create-another');
        if (createAnotherBtn) {
            createAnotherBtn.addEventListener('click', function() {
                const form = document.getElementById('secureshare-form');
                const result = document.getElementById('secureshare-result');

                if (form && result) {
                    result.style.display = 'none';
                    form.style.display = 'block';
                    secretInput.focus();
                }
            });
        }

        // Auto-select URL on focus
        const urlInput = document.getElementById('secureshare-url');
        if (urlInput) {
            urlInput.addEventListener('focus', function() {
                this.select();
            });
        }
    });

    /**
     * Show a message to the user.
     *
     * @param {HTMLElement} messageDiv The message container element.
     * @param {string} text The message text.
     * @param {string} type The message type ('success' or 'error').
     */
    function showMessage(messageDiv, text, type) {
        messageDiv.textContent = text;
        messageDiv.className = 'secureshare-message secureshare-' + type;
        messageDiv.style.display = 'block';
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Copy text to clipboard with modern API and fallback.
     *
     * @param {string} text The text to copy.
     * @param {HTMLElement} button The button element to update.
     */
    function copyToClipboard(text, button) {
        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess(button);
            }).catch(function(err) {
                console.error('Clipboard API failed:', err);
                fallbackCopy(text, button);
            });
        } else {
            fallbackCopy(text, button);
        }
    }

    /**
     * Fallback copy method for older browsers.
     *
     * @param {string} text The text to copy.
     * @param {HTMLElement} button The button element to update.
     */
    function fallbackCopy(text, button) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.width = '1px';
        textArea.style.height = '1px';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess(button);
            } else {
                alert(secureshareData.strings.copyError || 'Failed to copy');
            }
        } catch (err) {
            alert(secureshareData.strings.copyError || 'Failed to copy');
            console.error('Fallback copy failed:', err);
        }

        document.body.removeChild(textArea);
    }

    /**
     * Show copy success feedback on button.
     *
     * @param {HTMLElement} button The button element to update.
     */
    function showCopySuccess(button) {
        const originalText = button.textContent;
        const originalClass = button.className;

        button.textContent = secureshareData.strings.copySuccess || 'Copied!';
        button.className = button.className.replace('secureshare-button-secondary', 'secureshare-button-success');

        setTimeout(function() {
            button.textContent = originalText;
            button.className = originalClass;
        }, 2000);
    }

})();
