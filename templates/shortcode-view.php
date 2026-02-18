<?php
/**
 * Template for the view secret shortcode.
 *
 * Available variables:
 * - $data['secret']: The decrypted secret
 * - $data['created_at']: Creation timestamp
 * - $data['expires_at']: Expiration timestamp
 * - $data['token']: The secret token
 */

if (!defined('WPINC')) {
    die;
}

$secretdrop_secret = $data['secret'];
$secretdrop_expires_at = strtotime($data['expires_at']);
$secretdrop_time_remaining = $secretdrop_expires_at - current_time('timestamp');
?>

<div class="secretdrop-container">
    <div class="secretdrop-view-header">
        <div class="secretdrop-lock-icon">&#128274;</div>
        <h2><?php esc_html_e('Secret Message', 'secretdrop'); ?></h2>
    </div>

    <div class="secretdrop-warning-box">
        <strong><?php esc_html_e('Important:', 'secretdrop'); ?></strong>
        <?php esc_html_e('This secret is encrypted and will be automatically deleted when it expires. Make sure to copy it now.', 'secretdrop'); ?>
    </div>

    <div class="secretdrop-secret-box">
        <label><?php esc_html_e('Your Secret:', 'secretdrop'); ?></label>
        <div class="secretdrop-secret-content"><?php echo esc_html($secretdrop_secret); ?></div>
        <button type="button" id="secretdrop-copy-secret" class="secretdrop-button secretdrop-button-secondary">
            <?php esc_html_e('Copy Secret', 'secretdrop'); ?>
        </button>
    </div>

    <div class="secretdrop-expiry-info">
        <?php if ($secretdrop_time_remaining > 0): ?>
            <p>
                <strong><?php esc_html_e('Expires in:', 'secretdrop'); ?></strong>
                <?php
                $secretdrop_hours = floor($secretdrop_time_remaining / 3600);
                $secretdrop_minutes = floor(($secretdrop_time_remaining % 3600) / 60);

                if ($secretdrop_hours > 0) {
                    printf(
                        /* translators: 1: number of hours, 2: number of minutes */
                        esc_html__('%1$d hours, %2$d minutes', 'secretdrop'),
                        (int) $secretdrop_hours,
                        (int) $secretdrop_minutes
                    );
                } else {
                    printf(
                        /* translators: %d: number of minutes */
                        esc_html__('%d minutes', 'secretdrop'),
                        (int) $secretdrop_minutes
                    );
                }
                ?>
            </p>
            <p class="secretdrop-expiry-time">
                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $secretdrop_expires_at)); ?>
            </p>
        <?php else: ?>
            <p class="secretdrop-expired">
                <?php esc_html_e('This secret has expired.', 'secretdrop'); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="secretdrop-security-notice">
        <p><?php esc_html_e('This message was encrypted with AES-256-CBC encryption and transmitted securely.', 'secretdrop'); ?></p>
        <p><?php esc_html_e('For your security, do not share this URL after viewing. The secret will be automatically deleted after expiration.', 'secretdrop'); ?></p>
    </div>
</div>

<script>
// Copy secret to clipboard
document.addEventListener('DOMContentLoaded', function() {
    var copyBtn = document.getElementById('secretdrop-copy-secret');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            var secret = '<?php echo esc_js($secretdrop_secret); ?>';

            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(secret).then(function() {
                    copyBtn.textContent = '<?php echo esc_js(__('Copied!', 'secretdrop')); ?>';
                    copyBtn.classList.add('secretdrop-button-success');
                    setTimeout(function() {
                        copyBtn.textContent = '<?php echo esc_js(__('Copy Secret', 'secretdrop')); ?>';
                        copyBtn.classList.remove('secretdrop-button-success');
                    }, 2000);
                }).catch(function(err) {
                    console.error('Copy failed:', err);
                    alert('<?php echo esc_js(__('Failed to copy to clipboard', 'secretdrop')); ?>');
                });
            } else {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = secret;
                textArea.style.position = 'fixed';
                textArea.style.top = '0';
                textArea.style.left = '0';
                textArea.style.width = '1px';
                textArea.style.height = '1px';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();

                try {
                    document.execCommand('copy');
                    copyBtn.textContent = '<?php echo esc_js(__('Copied!', 'secretdrop')); ?>';
                    copyBtn.classList.add('secretdrop-button-success');
                    setTimeout(function() {
                        copyBtn.textContent = '<?php echo esc_js(__('Copy Secret', 'secretdrop')); ?>';
                        copyBtn.classList.remove('secretdrop-button-success');
                    }, 2000);
                } catch (err) {
                    alert('<?php echo esc_js(__('Failed to copy to clipboard', 'secretdrop')); ?>');
                }

                document.body.removeChild(textArea);
            }
        });
    }
});
</script>
