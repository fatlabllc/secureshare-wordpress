<?php
/**
 * Template for the admin settings page.
 *
 * Available variables:
 * - $active_tab: The currently active tab
 */

if (!defined('WPINC')) {
    die;
}

// Display success/error messages
if (isset($_GET['message'])) {
    $secretdrop_message = sanitize_text_field(wp_unslash($_GET['message']));
    switch ($secretdrop_message) {
        case 'key_generated':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                 esc_html__('Encryption key regenerated successfully. All previous secrets are now unrecoverable.', 'secretdrop') .
                 '</p></div>';
            break;
        case 'cleanup_done':
            $secretdrop_secrets = isset($_GET['secrets_deleted']) ? intval($_GET['secrets_deleted']) : 0;
            $secretdrop_rate_limits = isset($_GET['rate_limits_deleted']) ? intval($_GET['rate_limits_deleted']) : 0;
            echo '<div class="notice notice-success is-dismissible"><p>' .
                 /* translators: 1: number of expired secrets, 2: number of rate limit records */
                 sprintf(esc_html__('Cleanup completed: %1$d expired secrets and %2$d old rate limit records removed.', 'secretdrop'), (int) $secretdrop_secrets, (int) $secretdrop_rate_limits) .
                 '</p></div>';
            break;
        case 'rate_limits_cleared':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                 esc_html__('All rate limit records cleared successfully.', 'secretdrop') .
                 '</p></div>';
            break;
    }
}

settings_errors();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="?page=secretdrop&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('General', 'secretdrop'); ?>
        </a>
        <a href="?page=secretdrop&tab=rate_limiting" class="nav-tab <?php echo $active_tab === 'rate_limiting' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Rate Limiting', 'secretdrop'); ?>
        </a>
        <a href="?page=secretdrop&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Advanced', 'secretdrop'); ?>
        </a>
        <a href="?page=secretdrop&tab=statistics" class="nav-tab <?php echo $active_tab === 'statistics' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Statistics', 'secretdrop'); ?>
        </a>
    </h2>

    <?php if ($active_tab === 'general'): ?>
        <!-- General Settings Tab -->
        <form method="post" action="options.php">
            <?php
            settings_fields('secretdrop_general');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="secretdrop_encryption_key"><?php esc_html_e('Encryption Key', 'secretdrop'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="secretdrop_encryption_key" name="secretdrop_encryption_key"
                               value="<?php echo esc_attr(get_option('secretdrop_encryption_key')); ?>"
                               class="regular-text code" readonly />
                        <p class="description">
                            <?php esc_html_e('64-character hexadecimal key used for AES-256-CBC encryption.', 'secretdrop'); ?>
                            <br>
                            <strong><?php esc_html_e('Warning:', 'secretdrop'); ?></strong>
                            <?php esc_html_e('Changing this key will make all existing secrets unrecoverable.', 'secretdrop'); ?>
                        </p>
                        <p style="margin-top: 10px;">
                            <button type="button" class="button button-secondary" id="secretdrop-regenerate-key-btn">
                                <?php esc_html_e('Regenerate Key', 'secretdrop'); ?>
                            </button>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secretdrop_expiration_time"><?php esc_html_e('Expiration Time', 'secretdrop'); ?></label>
                    </th>
                    <td>
                        <?php
                        $secretdrop_expiration_seconds = get_option('secretdrop_expiration_time', 86400);
                        $secretdrop_expiration_hours = round($secretdrop_expiration_seconds / 3600);
                        ?>
                        <input type="number" id="secretdrop_expiration_time" name="secretdrop_expiration_time"
                               value="<?php echo esc_attr($secretdrop_expiration_hours); ?>"
                               min="1" step="1" class="small-text" />
                        <?php esc_html_e('hours', 'secretdrop'); ?>
                        <p class="description">
                            <?php esc_html_e('How long secrets remain accessible before automatic deletion.', 'secretdrop'); ?>
                            (<?php esc_html_e('Default: 24 hours', 'secretdrop'); ?>)
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secretdrop_max_secret_size"><?php esc_html_e('Max Secret Size', 'secretdrop'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="secretdrop_max_secret_size" name="secretdrop_max_secret_size"
                               value="<?php echo esc_attr(get_option('secretdrop_max_secret_size', 2000)); ?>"
                               min="100" step="1" class="small-text" />
                        <?php esc_html_e('characters', 'secretdrop'); ?>
                        <p class="description">
                            <?php esc_html_e('Maximum character limit for secrets.', 'secretdrop'); ?>
                            (<?php esc_html_e('Default: 2000', 'secretdrop'); ?>)
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

    <?php elseif ($active_tab === 'rate_limiting'): ?>
        <!-- Rate Limiting Tab -->
        <form method="post" action="options.php">
            <?php
            settings_fields('secretdrop_rate_limiting');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="secretdrop_rate_limit_enabled"><?php esc_html_e('Enable Rate Limiting', 'secretdrop'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="secretdrop_rate_limit_enabled" name="secretdrop_rate_limit_enabled"
                                   value="1" <?php checked(get_option('secretdrop_rate_limit_enabled', '1'), '1'); ?> />
                            <?php esc_html_e('Limit the number of secrets that can be created per IP address', 'secretdrop'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Helps prevent abuse by limiting creation rate per IP address.', 'secretdrop'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secretdrop_rate_limit_max"><?php esc_html_e('Max Secrets Per Window', 'secretdrop'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="secretdrop_rate_limit_max" name="secretdrop_rate_limit_max"
                               value="<?php echo esc_attr(get_option('secretdrop_rate_limit_max', 5)); ?>"
                               min="1" step="1" class="small-text" />
                        <?php esc_html_e('secrets', 'secretdrop'); ?>
                        <p class="description">
                            <?php esc_html_e('Maximum number of secrets one IP can create within the time window.', 'secretdrop'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secretdrop_rate_limit_window"><?php esc_html_e('Time Window', 'secretdrop'); ?></label>
                    </th>
                    <td>
                        <?php
                        $secretdrop_window_seconds = get_option('secretdrop_rate_limit_window', 3600);
                        $secretdrop_window_hours = round($secretdrop_window_seconds / 3600);
                        ?>
                        <input type="number" id="secretdrop_rate_limit_window" name="secretdrop_rate_limit_window"
                               value="<?php echo esc_attr($secretdrop_window_hours); ?>"
                               min="1" step="1" class="small-text" />
                        <?php esc_html_e('hours', 'secretdrop'); ?>
                        <p class="description">
                            <?php esc_html_e('Time window for rate limiting.', 'secretdrop'); ?>
                            (<?php esc_html_e('Default: 1 hour', 'secretdrop'); ?>)
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <hr>

        <h2><?php esc_html_e('Current Rate Limits', 'secretdrop'); ?></h2>
        <?php
        $secretdrop_rate_limits = SecretDrop_Admin::get_rate_limit_records();
        if (!empty($secretdrop_rate_limits)):
        ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('IP Hash', 'secretdrop'); ?></th>
                        <th><?php esc_html_e('Request Count', 'secretdrop'); ?></th>
                        <th><?php esc_html_e('Window Start', 'secretdrop'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($secretdrop_rate_limits as $secretdrop_record): ?>
                        <tr>
                            <td><code><?php echo esc_html(substr($secretdrop_record['ip_hash'], 0, 16)) . '...'; ?></code></td>
                            <td><?php echo esc_html($secretdrop_record['request_count']); ?></td>
                            <td><?php echo esc_html($secretdrop_record['window_start']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 10px;">
                <input type="hidden" name="action" value="secretdrop_clear_rate_limits" />
                <?php wp_nonce_field('secretdrop_clear_rate_limits', 'secretdrop_nonce'); ?>
                <button type="submit" class="button button-secondary" id="secretdrop-clear-rate-limits">
                    <?php esc_html_e('Clear All Rate Limits', 'secretdrop'); ?>
                </button>
            </form>
        <?php else: ?>
            <p><?php esc_html_e('No rate limit records found.', 'secretdrop'); ?></p>
        <?php endif; ?>

    <?php elseif ($active_tab === 'advanced'): ?>
        <!-- Advanced Settings Tab -->
        <form method="post" action="options.php">
            <?php
            settings_fields('secretdrop_advanced');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="secretdrop_debug_mode"><?php esc_html_e('Debug Mode', 'secretdrop'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="secretdrop_debug_mode" name="secretdrop_debug_mode"
                                   value="1" <?php checked(get_option('secretdrop_debug_mode', '0'), '1'); ?> />
                            <?php esc_html_e('Enable debug logging', 'secretdrop'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Log cleanup activities and errors to the PHP error log.', 'secretdrop'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secretdrop_custom_css"><?php esc_html_e('Custom CSS', 'secretdrop'); ?></label>
                    </th>
                    <td>
                        <textarea id="secretdrop_custom_css" name="secretdrop_custom_css"
                                  rows="10" class="large-text code"><?php echo esc_textarea(get_option('secretdrop_custom_css', '')); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Add custom CSS to override default styling. Use .secretdrop- prefix for specificity.', 'secretdrop'); ?>
                            <br>
                            <strong><?php esc_html_e('Documentation:', 'secretdrop'); ?></strong>
                            <?php
                            printf(
                                /* translators: %s: section name "CSS Customization Guide" */
                                esc_html__('See the %s section in readme.txt for comprehensive CSS customization examples, including brand colors, dark mode, typography, layouts, and more.', 'secretdrop'),
                                '<strong>CSS Customization Guide</strong>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <hr>

        <h2><?php esc_html_e('Manual Cleanup', 'secretdrop'); ?></h2>
        <p><?php esc_html_e('Manually trigger cleanup of expired secrets and old rate limit records. This runs automatically every hour via WP-Cron.', 'secretdrop'); ?></p>

        <?php
        $secretdrop_last_cleanup = get_option('secretdrop_last_cleanup', 0);
        if ($secretdrop_last_cleanup > 0) {
            $secretdrop_time_ago = human_time_diff($secretdrop_last_cleanup, current_time('timestamp'));
            echo '<p><strong>' . esc_html__('Last cleanup:', 'secretdrop') . '</strong> ' .
                 /* translators: %s: human-readable time difference (e.g., "2 hours") */
                 sprintf(esc_html__('%s ago', 'secretdrop'), esc_html($secretdrop_time_ago)) . '</p>';
        }
        ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="secretdrop_manual_cleanup" />
            <?php wp_nonce_field('secretdrop_manual_cleanup', 'secretdrop_nonce'); ?>
            <button type="submit" class="button button-secondary">
                <?php esc_html_e('Run Cleanup Now', 'secretdrop'); ?>
            </button>
        </form>

    <?php elseif ($active_tab === 'statistics'): ?>
        <!-- Statistics Tab -->
        <?php
        $secretdrop_stats = SecretDrop_Admin::get_statistics();
        ?>

        <h2><?php esc_html_e('Plugin Statistics', 'secretdrop'); ?></h2>

        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <th style="width: 300px;"><?php esc_html_e('Total Secrets in Database', 'secretdrop'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($secretdrop_stats['total_secrets'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Active Secrets (Not Expired)', 'secretdrop'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($secretdrop_stats['active_secrets'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Expired Secrets (Ready for Cleanup)', 'secretdrop'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($secretdrop_stats['expired_secrets'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Rate Limit Records (Last 7 Days)', 'secretdrop'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($secretdrop_stats['rate_limit_records'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Last Cleanup', 'secretdrop'); ?></th>
                    <td>
                        <?php
                        if ($secretdrop_stats['last_cleanup'] > 0) {
                            $secretdrop_time_ago = human_time_diff($secretdrop_stats['last_cleanup'], current_time('timestamp'));
                            /* translators: %s: human-readable time difference (e.g., "2 hours") */
                            echo sprintf(esc_html__('%s ago', 'secretdrop'), esc_html($secretdrop_time_ago));
                        } else {
                            esc_html_e('Never', 'secretdrop');
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('Configuration Summary', 'secretdrop'); ?></h2>

        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <th style="width: 300px;"><?php esc_html_e('Expiration Time', 'secretdrop'); ?></th>
                    <td><?php echo esc_html(SecretDrop_Admin::format_duration(get_option('secretdrop_expiration_time', 86400))); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Max Secret Size', 'secretdrop'); ?></th>
                    <td><?php echo esc_html(number_format_i18n(get_option('secretdrop_max_secret_size', 2000))); ?> <?php esc_html_e('characters', 'secretdrop'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Rate Limiting', 'secretdrop'); ?></th>
                    <td>
                        <?php
                        if (get_option('secretdrop_rate_limit_enabled', '1') === '1') {
                            $secretdrop_max = get_option('secretdrop_rate_limit_max', 5);
                            $secretdrop_window = get_option('secretdrop_rate_limit_window', 3600);
                            echo sprintf(
                                /* translators: 1: number of secrets allowed, 2: time period (e.g., "1 hour") */
                                esc_html__('Enabled: %1$d secrets per %2$s', 'secretdrop'),
                                (int) $secretdrop_max,
                                esc_html(SecretDrop_Admin::format_duration($secretdrop_window))
                            );
                        } else {
                            esc_html_e('Disabled', 'secretdrop');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Debug Mode', 'secretdrop'); ?></th>
                    <td><?php echo get_option('secretdrop_debug_mode', '0') === '1' ? esc_html__('Enabled', 'secretdrop') : esc_html__('Disabled', 'secretdrop'); ?></td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('Shortcode Usage', 'secretdrop'); ?></h2>
        <p><?php esc_html_e('Use these shortcodes to add SecretDrop to your pages:', 'secretdrop'); ?></p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Shortcode', 'secretdrop'); ?></th>
                    <th><?php esc_html_e('Description', 'secretdrop'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[secretdrop]</code></td>
                    <td><?php esc_html_e('Smart shortcode that shows create form or view secret based on URL parameters', 'secretdrop'); ?></td>
                </tr>
                <tr>
                    <td><code>[secretdrop_create]</code></td>
                    <td><?php esc_html_e('Always shows the create secret form', 'secretdrop'); ?></td>
                </tr>
                <tr>
                    <td><code>[secretdrop_view]</code></td>
                    <td><?php esc_html_e('Shows the view secret interface (requires token parameter in URL)', 'secretdrop'); ?></td>
                </tr>
            </tbody>
        </table>

    <?php endif; ?>
</div>
