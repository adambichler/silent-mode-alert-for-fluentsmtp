<?php

/**
 * Plugin Name: Silent mode alert for FluentSMTP
 * Description: Alerts through Fluent SMTP channels while silent mode is active.
 * Version: 1.0.0
 * Author: Adam Bichler
 * License: GPL-2.0-or-later
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: fluent-smtp
 * Text Domain: silent-mode-alert-for-fluentsmtp
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-silent-mode-alert-for-fluentsmtp.php';

register_activation_hook(__FILE__, ['SilentModeAlertForFluentSMTP\SilentModeAlert', 'activate']);
register_deactivation_hook(__FILE__, ['SilentModeAlertForFluentSMTP\SilentModeAlert', 'deactivate']);

add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        'silent-mode-alert-for-fluentsmtp',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    SilentModeAlertForFluentSMTP\SilentModeAlert::instance()->register();
});
