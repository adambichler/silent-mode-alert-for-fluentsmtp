<?php

namespace SilentModeAlertForFluentSMTP;

use DateTimeImmutable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Checks Fluent SMTP silent mode and delivers recurring notifications.
 */
class SilentModeAlert
{
    const OPTION = 'silent_mode_alert_for_fluentsmtp_settings';
    const CRON_HOOK = 'silent_mode_alert_for_fluentsmtp_check';
    const CRON_SCHEDULE = 'silent_mode_alert_for_fluentsmtp_interval';
    const DEFAULT_INTERVAL = 15;

    private static $instance;

    private static $pendingInterval;

    /**
     * Returns the shared plugin instance.
     *
     * @return self
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registers plugin hooks.
     *
     * @return void
     */
    public function register()
    {
        add_filter('cron_schedules', [$this, 'addCronSchedule']);
        add_action(self::CRON_HOOK, [$this, 'checkSilentMode']);
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_post_silent_mode_alert_for_fluentsmtp_test', [$this, 'handleTestNotification']);
        add_action('admin_notices', [$this, 'showMissingChannelNotice']);
    }

    /**
     * Schedules the initial check when the plugin is activated.
     *
     * @return void
     */
    public static function activate()
    {
        if (!get_option(self::OPTION)) {
            add_option(self::OPTION, self::defaults());
        }

        add_filter('cron_schedules', [self::instance(), 'addCronSchedule']);
        self::schedule();
    }

    /**
     * Clears the plugin's scheduled check when it is deactivated.
     *
     * @return void
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    /**
     * Returns default plugin settings.
     *
     * @return array
     */
    private static function defaults()
    {
        return [
            'interval' => self::DEFAULT_INTERVAL,
            'snooze_until' => 0,
        ];
    }

    /**
     * Returns sanitized settings with defaults applied.
     *
     * @return array
     */
    private function settings()
    {
        $settings = wp_parse_args((array) get_option(self::OPTION, []), self::defaults());

        if (!empty($settings['snooze_until']) && (int) $settings['snooze_until'] <= time()) {
            $settings['snooze_until'] = 0;
            update_option(self::OPTION, $settings);
        }

        return $settings;
    }

    /**
     * Adds the configured recurring interval to WP-Cron.
     *
     * @param array $schedules Existing cron schedules.
     * @return array
     */
    public function addCronSchedule($schedules)
    {
        $configuredInterval = self::$pendingInterval ?: $this->settings()['interval'];
        $interval = max(1, min(1440, absint($configuredInterval)));
        $schedules[self::CRON_SCHEDULE] = [
            'interval' => $interval * MINUTE_IN_SECONDS,
            'display' => sprintf(__('Every %d minutes', 'silent-mode-alert-for-fluentsmtp'), $interval),
        ];

        return $schedules;
    }

    /**
     * Schedules one recurring check using the current interval.
     *
     * @return void
     */
    private static function schedule()
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    /**
     * Registers the settings and sanitization callback.
     *
     * @return void
     */
    public function registerSettings()
    {
        register_setting('silent_mode_alert_for_fluentsmtp', self::OPTION, [
            'sanitize_callback' => [$this, 'sanitizeSettings'],
            'default' => self::defaults(),
        ]);
    }

    /**
     * Sanitizes settings submitted from the plugin settings page.
     *
     * @param array $input Submitted settings.
     * @return array
     */
    public function sanitizeSettings($input)
    {
        $input = is_array($input) ? $input : [];
        $interval = isset($input['interval']) ? absint($input['interval']) : self::DEFAULT_INTERVAL;
        $interval = max(1, min(1440, $interval));
        $snoozeUntil = 0;

        if (!empty($input['snooze_until'])) {
            $date = DateTimeImmutable::createFromFormat(
                'Y-m-d\\TH:i',
                sanitize_text_field($input['snooze_until']),
                wp_timezone()
            );

            if ($date instanceof DateTimeImmutable && $date->getTimestamp() > time()) {
                $snoozeUntil = $date->getTimestamp();
            }
        }

        $settings = [
            'interval' => $interval,
            'snooze_until' => $snoozeUntil,
        ];

        self::$pendingInterval = $interval;
        wp_clear_scheduled_hook(self::CRON_HOOK);
        wp_schedule_event(time() + MINUTE_IN_SECONDS, self::CRON_SCHEDULE, self::CRON_HOOK);
        self::$pendingInterval = null;

        return $settings;
    }

    /**
     * Adds the plugin settings screen.
     *
     * @return void
     */
    public function addSettingsPage()
    {
        add_options_page(
            __('Silent mode alert for FluentSMTP', 'silent-mode-alert-for-fluentsmtp'),
            __('Silent mode alert', 'silent-mode-alert-for-fluentsmtp'),
            'manage_options',
            'silent-mode-alert-for-fluentsmtp',
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Renders the plugin settings screen.
     *
     * @return void
     */
    public function renderSettingsPage()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->settings();
        $snooze = '';
        if (!empty($settings['snooze_until'])) {
            $snooze = wp_date('Y-m-d\\TH:i', (int) $settings['snooze_until'], wp_timezone());
        }

        $testResult = isset($_GET['silent_mode_alert_for_fluentsmtp_test'])
            ? sanitize_key(wp_unslash($_GET['silent_mode_alert_for_fluentsmtp_test']))
            : '';
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Silent mode alert for FluentSMTP', 'silent-mode-alert-for-fluentsmtp'); ?></h1>
            <?php if ($testResult === 'success') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('The test notification was sent to all active Fluent SMTP channels.', 'silent-mode-alert-for-fluentsmtp'); ?></p>
                </div>
            <?php elseif ($testResult === 'no_channels') : ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html__('The test notification could not be sent because no active Fluent SMTP channel is configured.', 'silent-mode-alert-for-fluentsmtp'); ?></p>
                </div>
            <?php elseif ($testResult === 'unavailable') : ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html__('The test notification could not be sent because Fluent SMTP is unavailable.', 'silent-mode-alert-for-fluentsmtp'); ?></p>
                </div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('silent_mode_alert_for_fluentsmtp'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label
                                for="silent-mode-alert-for-fluentsmtp-interval"><?php echo esc_html__('Check interval', 'silent-mode-alert-for-fluentsmtp'); ?></label>
                        </th>
                        <td>
                            <input id="silent-mode-alert-for-fluentsmtp-interval"
                                name="<?php echo esc_attr(self::OPTION); ?>[interval]" type="number" min="1" max="1440"
                                value="<?php echo esc_attr($settings['interval']); ?>" />
                            <span><?php echo esc_html__('minutes', 'silent-mode-alert-for-fluentsmtp'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label
                                for="silent-mode-alert-for-fluentsmtp-snooze"><?php echo esc_html__('Disable notifications until', 'silent-mode-alert-for-fluentsmtp'); ?></label>
                        </th>
                        <td>
                            <input id="silent-mode-alert-for-fluentsmtp-snooze"
                                name="<?php echo esc_attr(self::OPTION); ?>[snooze_until]" type="datetime-local"
                                value="<?php echo esc_attr($snooze); ?>" />
                            <p class="description">
                                <?php echo esc_html__('Leave empty to enable notifications immediately. The site timezone is used.', 'silent-mode-alert-for-fluentsmtp'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="silent_mode_alert_for_fluentsmtp_test" />
                <?php wp_nonce_field('silent_mode_alert_for_fluentsmtp_test'); ?>
                <?php submit_button(__('Send test notification', 'silent-mode-alert-for-fluentsmtp'), 'secondary', 'submit', false); ?>
            </form>
        </div>
    <?php
    }

    /**
     * Checks silent mode and sends a reminder to each active Fluent SMTP channel.
     *
     * @return void
     */
    public function checkSilentMode()
    {
        if (!function_exists('fluentMailGetSettings') || !class_exists('FluentMail\\App\\Services\\Notification\\Manager') || !class_exists('FluentMail\\App\\Services\\NotificationHelper')) {
            return;
        }

        $fluentSettings = fluentMailGetSettings([], false);
        if (empty($fluentSettings['misc']['simulate_emails']) || $fluentSettings['misc']['simulate_emails'] !== 'yes') {
            return;
        }

        $settings = $this->settings();
        if (!empty($settings['snooze_until']) && (int) $settings['snooze_until'] > time()) {
            return;
        }

        $manager = new \FluentMail\App\Services\Notification\Manager();
        $channels = $manager->getActiveChannels();
        if (!$channels) {
            return;
        }

        $message = sprintf(
            __('Fluent SMTP silent mode is still active on %s. Emails are currently not being sent.', 'silent-mode-alert-for-fluentsmtp'),
            home_url('/')
        );

        foreach ($channels as $channel) {
            $this->sendToChannel($channel, $message);
        }
    }

    /**
     * Sends a test notification to all active Fluent SMTP channels.
     *
     * @return void
     */
    public function handleTestNotification()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to send test notifications.', 'silent-mode-alert-for-fluentsmtp'));
        }

        check_admin_referer('silent_mode_alert_for_fluentsmtp_test');

        $result = 'unavailable';
        if (function_exists('fluentMailGetSettings') && class_exists('FluentMail\\App\\Services\\Notification\\Manager') && class_exists('FluentMail\\App\\Services\\NotificationHelper')) {
            $manager = new \FluentMail\App\Services\Notification\Manager();
            $channels = $manager->getActiveChannels();

            if ($channels) {
                $message = sprintf(
                    __('Fluent SMTP silent mode test notification from %s. Emails are currently not being sent.', 'silent-mode-alert-for-fluentsmtp'),
                    home_url('/')
                );

                foreach ($channels as $channel) {
                    $this->sendToChannel($channel, $message);
                }

                $result = 'success';
            } else {
                $result = 'no_channels';
            }
        }

        $redirect = wp_get_referer();
        if (!$redirect) {
            $redirect = admin_url('options-general.php?page=silent-mode-alert-for-fluentsmtp');
        }

        wp_safe_redirect(add_query_arg('silent_mode_alert_for_fluentsmtp_test', $result, $redirect));
        exit;
    }

    /**
     * Delivers a reminder through one Fluent SMTP notification channel.
     *
     * @param array $channel Active channel data.
     * @param string $message Reminder message.
     * @return void
     */
    private function sendToChannel($channel, $message)
    {
        $driver = isset($channel['driver']) ? $channel['driver'] : '';
        $channelSettings = isset($channel['settings']) && is_array($channel['settings']) ? $channel['settings'] : [];
        $helper = 'FluentMail\\App\\Services\\NotificationHelper';

        if ($driver === 'telegram') {
            $helper::sendFailedNotificationTele([
                'token_id' => isset($channelSettings['token']) ? $channelSettings['token'] : '',
                'provider' => 'Fluent SMTP',
                'error_message' => $message,
            ]);
        } elseif ($driver === 'slack' && !empty($channelSettings['webhook_url'])) {
            $helper::sendSlackMessage($message, $channelSettings['webhook_url'], false);
        } elseif ($driver === 'discord' && !empty($channelSettings['webhook_url'])) {
            $helper::sendDiscordMessage($message, $channelSettings['webhook_url'], false);
        } elseif ($driver === 'pushover' && !empty($channelSettings['api_token']) && !empty($channelSettings['user_key'])) {
            $helper::sendPushoverMessage($message, $channelSettings['api_token'], $channelSettings['user_key'], false, 1);
        }
    }

    /**
     * Shows an admin dashboard warning when silent mode has no delivery channel.
     *
     * @return void
     */
    public function showMissingChannelNotice()
    {
        if (!current_user_can('manage_options') || !function_exists('fluentMailGetSettings')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'dashboard') {
            return;
        }

        $fluentSettings = fluentMailGetSettings([], false);
        if (empty($fluentSettings['misc']['simulate_emails']) || $fluentSettings['misc']['simulate_emails'] !== 'yes' || !class_exists('FluentMail\\App\\Services\\Notification\\Manager')) {
            return;
        }

        $manager = new \FluentMail\App\Services\Notification\Manager();
        if ($manager->getActiveChannels()) {
            return;
        }
    ?>
        <div class="notice notice-error" role="alert">
            <p>
                <?php
                echo wp_kses_post(sprintf(
                    __('<strong>Attention:</strong> Fluent SMTP silent mode is active, but no notification channel is configured. <a href="%s">Configure a channel</a> to receive reminders.', 'silent-mode-alert-for-fluentsmtp'),
                    esc_url(admin_url('options-general.php?page=fluent-mail#/notification-settings'))
                ));
                ?>
            </p>
        </div>
<?php
    }
}
