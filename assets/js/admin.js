/**
 * SecureShare Admin JavaScript
 * Handles admin panel functionality
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Regenerate encryption key button
        $('#secureshare-regenerate-key-btn').on('click', function(e) {
            e.preventDefault();

            if (!confirm(secureshareAdmin.strings.confirmKeyRegen)) {
                return;
            }

            // Create form and submit
            var form = $('<form>', {
                'method': 'POST',
                'action': secureshareAdmin.adminPostUrl
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'secureshare_generate_key'
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'secureshare_nonce',
                'value': secureshareAdmin.generateKeyNonce
            }));

            $('body').append(form);
            form.submit();
        });

        // Manual cleanup button
        $('#secureshare-manual-cleanup-btn').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to manually trigger cleanup of expired secrets?')) {
                return;
            }

            var form = $('<form>', {
                'method': 'POST',
                'action': secureshareAdmin.adminPostUrl
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'secureshare_manual_cleanup'
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'secureshare_nonce',
                'value': wp.ajax.settings.nonce
            }));

            $('body').append(form);
            form.submit();
        });

        // Clear rate limits button
        $('#secureshare-clear-rate-limits-btn').on('click', function(e) {
            e.preventDefault();

            if (!confirm(secureshareAdmin.strings.confirmClearRateLimits)) {
                return;
            }

            var form = $('<form>', {
                'method': 'POST',
                'action': secureshareAdmin.adminPostUrl
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'secureshare_clear_rate_limits'
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'secureshare_nonce',
                'value': wp.ajax.settings.nonce
            }));

            $('body').append(form);
            form.submit();
        });

        // Copy encryption key to clipboard
        $('#secureshare_encryption_key').on('click', function() {
            this.select();
            this.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                // Show temporary feedback
                var originalBg = $(this).css('background-color');
                $(this).css('background-color', '#d4edda');
                setTimeout(function() {
                    $('#secureshare_encryption_key').css('background-color', originalBg);
                }, 500);
            } catch (err) {
                console.error('Copy failed:', err);
            }
        });

    });

})(jQuery);
