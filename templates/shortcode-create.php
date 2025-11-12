<?php
/**
 * Template for the create secret shortcode.
 *
 * Available variables:
 * - $data['max_secret_size']: Maximum character limit
 * - $data['expiration_time']: Expiration time in seconds
 */

if (!defined('WPINC')) {
    die;
}

$max_size = $data['max_secret_size'];
$expiration_hours = round($data['expiration_time'] / 3600);
?>

<div class="secureshare-container">
    <div class="secureshare-header">
        <h2><?php esc_html_e('Share a Secret Securely', 'secureshare'); ?></h2>
        <p class="secureshare-description">
            <?php
            /* translators: %d: number of hours until link expires */
            printf(
                esc_html__('Create an encrypted link that expires in %d hours. Perfect for sharing passwords, API keys, or other sensitive information.', 'secureshare'),
                $expiration_hours
            ); ?>
        </p>
    </div>

    <form id="secureshare-form" class="secureshare-form" method="post">
        <div class="secureshare-form-group">
            <label for="secureshare-secret">
                <?php esc_html_e('Your Secret', 'secureshare'); ?>
            </label>
            <textarea
                id="secureshare-secret"
                name="secret"
                class="secureshare-textarea"
                placeholder="<?php esc_attr_e('Enter your password, API key, or other sensitive information...', 'secureshare'); ?>"
                maxlength="<?php echo esc_attr($max_size); ?>"
                required
            ></textarea>
            <div class="secureshare-char-counter">
                <span id="secureshare-char-count">0</span> / <?php echo esc_html($max_size); ?> <?php esc_html_e('characters', 'secureshare'); ?>
            </div>
        </div>

        <div class="secureshare-info-box">
            <strong><?php esc_html_e('Security Features:', 'secureshare'); ?></strong>
            <ul>
                <li><?php esc_html_e('AES-256-CBC encryption', 'secureshare'); ?></li>
                <li><?php
                /* translators: %d: number of hours until secret expires */
                printf(
                    esc_html__('Automatically expires in %d hours', 'secureshare'),
                    $expiration_hours
                ); ?></li>
                <li><?php esc_html_e('Unique encrypted link generated', 'secureshare'); ?></li>
                <li><?php esc_html_e('No permanent storage', 'secureshare'); ?></li>
            </ul>
        </div>

        <button type="submit" class="secureshare-button secureshare-button-primary">
            <?php esc_html_e('Create Secure Link', 'secureshare'); ?>
        </button>

        <div id="secureshare-message" class="secureshare-message" style="display: none;"></div>
    </form>

    <div id="secureshare-result" class="secureshare-result" style="display: none;">
        <div class="secureshare-success-icon">✓</div>
        <h3><?php esc_html_e('Secure Link Created!', 'secureshare'); ?></h3>
        <p><?php esc_html_e('Share this link with the recipient. It will expire automatically.', 'secureshare'); ?></p>

        <div class="secureshare-url-container">
            <input
                type="text"
                id="secureshare-url"
                class="secureshare-url-input"
                readonly
            />
            <button type="button" id="secureshare-copy-btn" class="secureshare-button secureshare-button-secondary">
                <?php esc_html_e('Copy Link', 'secureshare'); ?>
            </button>
        </div>

        <button type="button" id="secureshare-create-another" class="secureshare-button secureshare-button-link">
            <?php esc_html_e('Create Another Secret', 'secureshare'); ?>
        </button>
    </div>
</div>
