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
    $secureshare_message = sanitize_text_field(wp_unslash($_GET['message']));
    switch ($secureshare_message) {
        case 'key_generated':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                 esc_html__('Encryption key regenerated successfully. All previous secrets are now unrecoverable.', 'secureshare') .
                 '</p></div>';
            break;
        case 'cleanup_done':
            $secureshare_secrets = isset($_GET['secrets_deleted']) ? intval($_GET['secrets_deleted']) : 0;
            $secureshare_rate_limits = isset($_GET['rate_limits_deleted']) ? intval($_GET['rate_limits_deleted']) : 0;
            echo '<div class="notice notice-success is-dismissible"><p>' .
                 /* translators: 1: number of expired secrets, 2: number of rate limit records */
                 sprintf(esc_html__('Cleanup completed: %1$d expired secrets and %2$d old rate limit records removed.', 'secureshare'), (int) $secureshare_secrets, (int) $secureshare_rate_limits) .
                 '</p></div>';
            break;
        case 'rate_limits_cleared':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                 esc_html__('All rate limit records cleared successfully.', 'secureshare') .
                 '</p></div>';
            break;
    }
}

settings_errors();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="?page=secureshare&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('General', 'secureshare'); ?>
        </a>
        <a href="?page=secureshare&tab=rate_limiting" class="nav-tab <?php echo $active_tab === 'rate_limiting' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Rate Limiting', 'secureshare'); ?>
        </a>
        <a href="?page=secureshare&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Advanced', 'secureshare'); ?>
        </a>
        <a href="?page=secureshare&tab=statistics" class="nav-tab <?php echo $active_tab === 'statistics' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Statistics', 'secureshare'); ?>
        </a>
    </h2>

    <?php if ($active_tab === 'general'): ?>
        <!-- General Settings Tab -->
        <form method="post" action="options.php">
            <?php
            settings_fields('secureshare_general');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="secureshare_encryption_key"><?php esc_html_e('Encryption Key', 'secureshare'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="secureshare_encryption_key" name="secureshare_encryption_key"
                               value="<?php echo esc_attr(get_option('secureshare_encryption_key')); ?>"
                               class="regular-text code" readonly />
                        <p class="description">
                            <?php esc_html_e('64-character hexadecimal key used for AES-256-CBC encryption.', 'secureshare'); ?>
                            <br>
                            <strong><?php esc_html_e('Warning:', 'secureshare'); ?></strong>
                            <?php esc_html_e('Changing this key will make all existing secrets unrecoverable.', 'secureshare'); ?>
                        </p>
                        <p style="margin-top: 10px;">
                            <button type="button" class="button button-secondary" id="secureshare-regenerate-key-btn">
                                <?php esc_html_e('Regenerate Key', 'secureshare'); ?>
                            </button>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secureshare_expiration_time"><?php esc_html_e('Expiration Time', 'secureshare'); ?></label>
                    </th>
                    <td>
                        <?php
                        $secureshare_expiration_seconds = get_option('secureshare_expiration_time', 86400);
                        $secureshare_expiration_hours = round($secureshare_expiration_seconds / 3600);
                        ?>
                        <input type="number" id="secureshare_expiration_time" name="secureshare_expiration_time"
                               value="<?php echo esc_attr($secureshare_expiration_hours); ?>"
                               min="1" step="1" class="small-text" />
                        <?php esc_html_e('hours', 'secureshare'); ?>
                        <p class="description">
                            <?php esc_html_e('How long secrets remain accessible before automatic deletion.', 'secureshare'); ?>
                            (<?php esc_html_e('Default: 24 hours', 'secureshare'); ?>)
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secureshare_max_secret_size"><?php esc_html_e('Max Secret Size', 'secureshare'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="secureshare_max_secret_size" name="secureshare_max_secret_size"
                               value="<?php echo esc_attr(get_option('secureshare_max_secret_size', 2000)); ?>"
                               min="100" step="1" class="small-text" />
                        <?php esc_html_e('characters', 'secureshare'); ?>
                        <p class="description">
                            <?php esc_html_e('Maximum character limit for secrets.', 'secureshare'); ?>
                            (<?php esc_html_e('Default: 2000', 'secureshare'); ?>)
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
            settings_fields('secureshare_rate_limiting');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="secureshare_rate_limit_enabled"><?php esc_html_e('Enable Rate Limiting', 'secureshare'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="secureshare_rate_limit_enabled" name="secureshare_rate_limit_enabled"
                                   value="1" <?php checked(get_option('secureshare_rate_limit_enabled', '1'), '1'); ?> />
                            <?php esc_html_e('Limit the number of secrets that can be created per IP address', 'secureshare'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Helps prevent abuse by limiting creation rate per IP address.', 'secureshare'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secureshare_rate_limit_max"><?php esc_html_e('Max Secrets Per Window', 'secureshare'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="secureshare_rate_limit_max" name="secureshare_rate_limit_max"
                               value="<?php echo esc_attr(get_option('secureshare_rate_limit_max', 5)); ?>"
                               min="1" step="1" class="small-text" />
                        <?php esc_html_e('secrets', 'secureshare'); ?>
                        <p class="description">
                            <?php esc_html_e('Maximum number of secrets one IP can create within the time window.', 'secureshare'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secureshare_rate_limit_window"><?php esc_html_e('Time Window', 'secureshare'); ?></label>
                    </th>
                    <td>
                        <?php
                        $secureshare_window_seconds = get_option('secureshare_rate_limit_window', 3600);
                        $secureshare_window_hours = round($secureshare_window_seconds / 3600);
                        ?>
                        <input type="number" id="secureshare_rate_limit_window" name="secureshare_rate_limit_window"
                               value="<?php echo esc_attr($secureshare_window_hours); ?>"
                               min="1" step="1" class="small-text" />
                        <?php esc_html_e('hours', 'secureshare'); ?>
                        <p class="description">
                            <?php esc_html_e('Time window for rate limiting.', 'secureshare'); ?>
                            (<?php esc_html_e('Default: 1 hour', 'secureshare'); ?>)
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <hr>

        <h2><?php esc_html_e('Current Rate Limits', 'secureshare'); ?></h2>
        <?php
        $secureshare_rate_limits = SecureShare_Admin::get_rate_limit_records();
        if (!empty($secureshare_rate_limits)):
        ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('IP Hash', 'secureshare'); ?></th>
                        <th><?php esc_html_e('Request Count', 'secureshare'); ?></th>
                        <th><?php esc_html_e('Window Start', 'secureshare'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($secureshare_rate_limits as $secureshare_record): ?>
                        <tr>
                            <td><code><?php echo esc_html(substr($secureshare_record['ip_hash'], 0, 16)) . '...'; ?></code></td>
                            <td><?php echo esc_html($secureshare_record['request_count']); ?></td>
                            <td><?php echo esc_html($secureshare_record['window_start']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 10px;">
                <input type="hidden" name="action" value="secureshare_clear_rate_limits" />
                <?php wp_nonce_field('secureshare_clear_rate_limits', 'secureshare_nonce'); ?>
                <button type="submit" class="button button-secondary" id="secureshare-clear-rate-limits">
                    <?php esc_html_e('Clear All Rate Limits', 'secureshare'); ?>
                </button>
            </form>
        <?php else: ?>
            <p><?php esc_html_e('No rate limit records found.', 'secureshare'); ?></p>
        <?php endif; ?>

    <?php elseif ($active_tab === 'advanced'): ?>
        <!-- Advanced Settings Tab -->
        <form method="post" action="options.php">
            <?php
            settings_fields('secureshare_advanced');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="secureshare_debug_mode"><?php esc_html_e('Debug Mode', 'secureshare'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="secureshare_debug_mode" name="secureshare_debug_mode"
                                   value="1" <?php checked(get_option('secureshare_debug_mode', '0'), '1'); ?> />
                            <?php esc_html_e('Enable debug logging', 'secureshare'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Log cleanup activities and errors to the PHP error log.', 'secureshare'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="secureshare_custom_css"><?php esc_html_e('Custom CSS', 'secureshare'); ?></label>
                    </th>
                    <td>
                        <textarea id="secureshare_custom_css" name="secureshare_custom_css"
                                  rows="10" class="large-text code"><?php echo esc_textarea(get_option('secureshare_custom_css', '')); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Add custom CSS to override default styling. Use .secureshare- prefix for specificity.', 'secureshare'); ?>
                            <br>
                            <strong><?php esc_html_e('Documentation:', 'secureshare'); ?></strong>
                            <?php
                            printf(
                                /* translators: %s: section name "CSS Customization Guide" */
                                esc_html__('See the %s section in readme.txt for comprehensive CSS customization examples, including brand colors, dark mode, typography, layouts, and more.', 'secureshare'),
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

        <h2><?php esc_html_e('Manual Cleanup', 'secureshare'); ?></h2>
        <p><?php esc_html_e('Manually trigger cleanup of expired secrets and old rate limit records. This runs automatically every hour via WP-Cron.', 'secureshare'); ?></p>

        <?php
        $secureshare_last_cleanup = get_option('secureshare_last_cleanup', 0);
        if ($secureshare_last_cleanup > 0) {
            $secureshare_time_ago = human_time_diff($secureshare_last_cleanup, current_time('timestamp'));
            echo '<p><strong>' . esc_html__('Last cleanup:', 'secureshare') . '</strong> ' .
                 /* translators: %s: human-readable time difference (e.g., "2 hours") */
                 sprintf(esc_html__('%s ago', 'secureshare'), esc_html($secureshare_time_ago)) . '</p>';
        }
        ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="secureshare_manual_cleanup" />
            <?php wp_nonce_field('secureshare_manual_cleanup', 'secureshare_nonce'); ?>
            <button type="submit" class="button button-secondary">
                <?php esc_html_e('Run Cleanup Now', 'secureshare'); ?>
            </button>
        </form>

    <?php elseif ($active_tab === 'statistics'): ?>
        <!-- Statistics Tab -->
        <?php
        $secureshare_stats = SecureShare_Admin::get_statistics();
        ?>

        <h2><?php esc_html_e('Plugin Statistics', 'secureshare'); ?></h2>

        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <th style="width: 300px;"><?php esc_html_e('Total Secrets in Database', 'secureshare'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($secureshare_stats['total_secrets'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Active Secrets (Not Expired)', 'secureshare'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($secureshare_stats['active_secrets'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Expired Secrets (Ready for Cleanup)', 'secureshare'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($secureshare_stats['expired_secrets'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Rate Limit Records (Last 7 Days)', 'secureshare'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($secureshare_stats['rate_limit_records'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Last Cleanup', 'secureshare'); ?></th>
                    <td>
                        <?php
                        if ($secureshare_stats['last_cleanup'] > 0) {
                            $secureshare_time_ago = human_time_diff($secureshare_stats['last_cleanup'], current_time('timestamp'));
                            /* translators: %s: human-readable time difference (e.g., "2 hours") */
                            echo sprintf(esc_html__('%s ago', 'secureshare'), esc_html($secureshare_time_ago));
                        } else {
                            esc_html_e('Never', 'secureshare');
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('Configuration Summary', 'secureshare'); ?></h2>

        <table class="wp-list-table widefat fixed striped">
            <tbody>
                <tr>
                    <th style="width: 300px;"><?php esc_html_e('Expiration Time', 'secureshare'); ?></th>
                    <td><?php echo esc_html(SecureShare_Admin::format_duration(get_option('secureshare_expiration_time', 86400))); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Max Secret Size', 'secureshare'); ?></th>
                    <td><?php echo esc_html(number_format_i18n(get_option('secureshare_max_secret_size', 2000))); ?> <?php esc_html_e('characters', 'secureshare'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Rate Limiting', 'secureshare'); ?></th>
                    <td>
                        <?php
                        if (get_option('secureshare_rate_limit_enabled', '1') === '1') {
                            $secureshare_max = get_option('secureshare_rate_limit_max', 5);
                            $secureshare_window = get_option('secureshare_rate_limit_window', 3600);
                            echo sprintf(
                                /* translators: 1: number of secrets allowed, 2: time period (e.g., "1 hour") */
                                esc_html__('Enabled: %1$d secrets per %2$s', 'secureshare'),
                                (int) $secureshare_max,
                                esc_html(SecureShare_Admin::format_duration($secureshare_window))
                            );
                        } else {
                            esc_html_e('Disabled', 'secureshare');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Debug Mode', 'secureshare'); ?></th>
                    <td><?php echo get_option('secureshare_debug_mode', '0') === '1' ? esc_html__('Enabled', 'secureshare') : esc_html__('Disabled', 'secureshare'); ?></td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('Shortcode Usage', 'secureshare'); ?></h2>
        <p><?php esc_html_e('Use these shortcodes to add SecureShare to your pages:', 'secureshare'); ?></p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Shortcode', 'secureshare'); ?></th>
                    <th><?php esc_html_e('Description', 'secureshare'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[secureshare]</code></td>
                    <td><?php esc_html_e('Smart shortcode that shows create form or view secret based on URL parameters', 'secureshare'); ?></td>
                </tr>
                <tr>
                    <td><code>[secureshare_create]</code></td>
                    <td><?php esc_html_e('Always shows the create secret form', 'secureshare'); ?></td>
                </tr>
                <tr>
                    <td><code>[secureshare_view]</code></td>
                    <td><?php esc_html_e('Shows the view secret interface (requires token parameter in URL)', 'secureshare'); ?></td>
                </tr>
            </tbody>
        </table>

    <?php endif; ?>
</div>
