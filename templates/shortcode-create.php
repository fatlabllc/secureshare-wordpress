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

$secretdrop_max_size = $data['max_secret_size'];
$secretdrop_expiration_hours = round($data['expiration_time'] / 3600);
?>

<div class="secretdrop-container">
    <div class="secretdrop-header">
        <h2><?php esc_html_e('Share a Secret Securely', 'secretdrop'); ?></h2>
        <p class="secretdrop-description">
            <?php
            printf(
                /* translators: %d: number of hours until link expires */
                esc_html__('Create an encrypted link that expires in %d hours. Perfect for sharing passwords, API keys, or other sensitive information.', 'secretdrop'),
                (int) $secretdrop_expiration_hours
            ); ?>
        </p>
    </div>

    <form id="secretdrop-form" class="secretdrop-form" method="post">
        <div class="secretdrop-form-group">
            <label for="secretdrop-secret">
                <?php esc_html_e('Your Secret', 'secretdrop'); ?>
            </label>
            <textarea
                id="secretdrop-secret"
                name="secret"
                class="secretdrop-textarea"
                placeholder="<?php esc_attr_e('Enter your password, API key, or other sensitive information...', 'secretdrop'); ?>"
                maxlength="<?php echo esc_attr($secretdrop_max_size); ?>"
                required
            ></textarea>
            <div class="secretdrop-char-counter">
                <span id="secretdrop-char-count">0</span> / <?php echo esc_html($secretdrop_max_size); ?> <?php esc_html_e('characters', 'secretdrop'); ?>
            </div>
        </div>

        <div class="secretdrop-info-box">
            <strong><?php esc_html_e('Security Features:', 'secretdrop'); ?></strong>
            <ul>
                <li><?php esc_html_e('AES-256-CBC encryption', 'secretdrop'); ?></li>
                <li><?php
                printf(
                    /* translators: %d: number of hours until secret expires */
                    esc_html__('Automatically expires in %d hours', 'secretdrop'),
                    (int) $secretdrop_expiration_hours
                ); ?></li>
                <li><?php esc_html_e('Unique encrypted link generated', 'secretdrop'); ?></li>
                <li><?php esc_html_e('No permanent storage', 'secretdrop'); ?></li>
            </ul>
        </div>

        <button type="submit" class="secretdrop-button secretdrop-button-primary">
            <?php esc_html_e('Create Secure Link', 'secretdrop'); ?>
        </button>

        <div id="secretdrop-message" class="secretdrop-message" style="display: none;"></div>
    </form>

    <div id="secretdrop-result" class="secretdrop-result" style="display: none;">
        <div class="secretdrop-success-icon">&#10003;</div>
        <h3><?php esc_html_e('Secure Link Created!', 'secretdrop'); ?></h3>
        <p><?php esc_html_e('Share this link with the recipient. It will expire automatically.', 'secretdrop'); ?></p>

        <div class="secretdrop-url-container">
            <input
                type="text"
                id="secretdrop-url"
                class="secretdrop-url-input"
                readonly
            />
            <button type="button" id="secretdrop-copy-btn" class="secretdrop-button secretdrop-button-secondary">
                <?php esc_html_e('Copy Link', 'secretdrop'); ?>
            </button>
        </div>

        <button type="button" id="secretdrop-create-another" class="secretdrop-button secretdrop-button-link">
            <?php esc_html_e('Create Another Secret', 'secretdrop'); ?>
        </button>
    </div>
</div>
