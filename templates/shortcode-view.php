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

$secret = $data['secret'];
$expires_at = strtotime($data['expires_at']);
$time_remaining = $expires_at - current_time('timestamp');
?>

<div class="secureshare-container">
    <div class="secureshare-view-header">
        <div class="secureshare-lock-icon">🔒</div>
        <h2><?php esc_html_e('Secret Message', 'secureshare'); ?></h2>
    </div>

    <div class="secureshare-warning-box">
        <strong><?php esc_html_e('Important:', 'secureshare'); ?></strong>
        <?php esc_html_e('This secret is encrypted and will be automatically deleted when it expires. Make sure to copy it now.', 'secureshare'); ?>
    </div>

    <div class="secureshare-secret-box">
        <label><?php esc_html_e('Your Secret:', 'secureshare'); ?></label>
        <div class="secureshare-secret-content"><?php echo esc_html($secret); ?></div>
        <button type="button" id="secureshare-copy-secret" class="secureshare-button secureshare-button-secondary">
            <?php esc_html_e('Copy Secret', 'secureshare'); ?>
        </button>
    </div>

    <div class="secureshare-expiry-info">
        <?php if ($time_remaining > 0): ?>
            <p>
                <strong><?php esc_html_e('Expires in:', 'secureshare'); ?></strong>
                <?php
                $hours = floor($time_remaining / 3600);
                $minutes = floor(($time_remaining % 3600) / 60);

                if ($hours > 0) {
                    printf(
                        esc_html__('%d hours, %d minutes', 'secureshare'),
                        $hours,
                        $minutes
                    );
                } else {
                    printf(
                        esc_html__('%d minutes', 'secureshare'),
                        $minutes
                    );
                }
                ?>
            </p>
            <p class="secureshare-expiry-time">
                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $expires_at)); ?>
            </p>
        <?php else: ?>
            <p class="secureshare-expired">
                <?php esc_html_e('This secret has expired.', 'secureshare'); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="secureshare-security-notice">
        <p><?php esc_html_e('This message was encrypted with AES-256-CBC encryption and transmitted securely.', 'secureshare'); ?></p>
        <p><?php esc_html_e('For your security, do not share this URL after viewing. The secret will be automatically deleted after expiration.', 'secureshare'); ?></p>
    </div>
</div>

<script>
// Copy secret to clipboard
document.addEventListener('DOMContentLoaded', function() {
    var copyBtn = document.getElementById('secureshare-copy-secret');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            var secret = '<?php echo esc_js($secret); ?>';

            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(secret).then(function() {
                    copyBtn.textContent = '<?php echo esc_js(__('Copied!', 'secureshare')); ?>';
                    copyBtn.classList.add('secureshare-button-success');
                    setTimeout(function() {
                        copyBtn.textContent = '<?php echo esc_js(__('Copy Secret', 'secureshare')); ?>';
                        copyBtn.classList.remove('secureshare-button-success');
                    }, 2000);
                }).catch(function(err) {
                    console.error('Copy failed:', err);
                    alert('<?php echo esc_js(__('Failed to copy to clipboard', 'secureshare')); ?>');
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
                    copyBtn.textContent = '<?php echo esc_js(__('Copied!', 'secureshare')); ?>';
                    copyBtn.classList.add('secureshare-button-success');
                    setTimeout(function() {
                        copyBtn.textContent = '<?php echo esc_js(__('Copy Secret', 'secureshare')); ?>';
                        copyBtn.classList.remove('secureshare-button-success');
                    }, 2000);
                } catch (err) {
                    alert('<?php echo esc_js(__('Failed to copy to clipboard', 'secureshare')); ?>');
                }

                document.body.removeChild(textArea);
            }
        });
    }
});
</script>
