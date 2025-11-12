/**
 * SecureShare - Admin JavaScript
 *
 * Handles admin panel interactions and confirmations.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Confirm and submit encryption key regeneration
        $('#secureshare-regenerate-key-btn').on('click', function(e) {
            e.preventDefault();

            if (!confirm(secureshareAdmin.strings.confirmKeyRegen)) {
                return false;
            }

            // Create a hidden form and submit it
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

        // Confirm rate limits clearing
        $('#secureshare-clear-rate-limits').on('click', function(e) {
            if (!confirm(secureshareAdmin.strings.confirmClearRateLimits)) {
                e.preventDefault();
                return false;
            }
        });

        // Copy encryption key to clipboard (if button exists)
        $(document).on('click', '#secureshare_encryption_key', function() {
            this.select();
            try {
                document.execCommand('copy');
                // Optional: Show a quick notification
                $(this).after('<span class="secureshare-copied-notice">' +
                    secureshareAdmin.strings.keyCopied + '</span>');
                setTimeout(function() {
                    $('.secureshare-copied-notice').fadeOut(function() {
                        $(this).remove();
                    });
                }, 2000);
            } catch (err) {
                // Silent fail - user can still manually copy
            }
        });
    });

})(jQuery);
