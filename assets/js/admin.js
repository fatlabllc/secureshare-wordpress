/**
 * SecretDrop Admin JavaScript
 * Handles admin panel functionality
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Regenerate encryption key button
        $('#secretdrop-regenerate-key-btn').on('click', function(e) {
            e.preventDefault();

            if (!confirm(secretdropAdmin.strings.confirmKeyRegen)) {
                return;
            }

            // Create form and submit
            var form = $('<form>', {
                'method': 'POST',
                'action': secretdropAdmin.adminPostUrl
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'secretdrop_generate_key'
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'secretdrop_nonce',
                'value': secretdropAdmin.generateKeyNonce
            }));

            $('body').append(form);
            form.submit();
        });

        // Manual cleanup button
        $('#secretdrop-manual-cleanup-btn').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to manually trigger cleanup of expired secrets?')) {
                return;
            }

            var form = $('<form>', {
                'method': 'POST',
                'action': secretdropAdmin.adminPostUrl
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'secretdrop_manual_cleanup'
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'secretdrop_nonce',
                'value': wp.ajax.settings.nonce
            }));

            $('body').append(form);
            form.submit();
        });

        // Clear rate limits button
        $('#secretdrop-clear-rate-limits-btn').on('click', function(e) {
            e.preventDefault();

            if (!confirm(secretdropAdmin.strings.confirmClearRateLimits)) {
                return;
            }

            var form = $('<form>', {
                'method': 'POST',
                'action': secretdropAdmin.adminPostUrl
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'secretdrop_clear_rate_limits'
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'secretdrop_nonce',
                'value': wp.ajax.settings.nonce
            }));

            $('body').append(form);
            form.submit();
        });

        // Confirm before regenerating or removing the API key
        $('form[data-secretdrop-confirm]').on('submit', function() {
            var message = secretdropAdmin.strings[$(this).data('secretdrop-confirm')];
            return message ? confirm(message) : true;
        });

        // Select the newly generated API key on click
        $('#secretdrop_new_api_key').on('click focus', function() {
            this.select();
        });

        // Copy encryption key to clipboard
        $('#secretdrop_encryption_key').on('click', function() {
            this.select();
            this.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                // Show temporary feedback
                var originalBg = $(this).css('background-color');
                $(this).css('background-color', '#d4edda');
                setTimeout(function() {
                    $('#secretdrop_encryption_key').css('background-color', originalBg);
                }, 500);
            } catch (err) {
                console.error('Copy failed:', err);
            }
        });

    });

})(jQuery);
