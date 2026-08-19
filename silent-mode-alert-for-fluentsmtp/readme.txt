=== Silent mode alert for FluentSMTP ===
Contributors: adambichler
Tags: fluent smtp, silent mode, email notifications, smtp, alerts
Requires at least: 6.5
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Receive recurring alerts when FluentSMTP silent mode is active.

== Description ==

Silent mode alert for FluentSMTP helps WordPress administrators notice when FluentSMTP is configured to stop sending emails.

When FluentSMTP silent mode is enabled, the plugin checks the setting on a configurable schedule and sends a reminder through every active FluentSMTP notification channel. Supported channels are Telegram, Slack, Discord, and Pushover.

The plugin also displays a dashboard warning when silent mode is active but no FluentSMTP notification channel has been configured.

This is an independent plugin and is not affiliated with or endorsed by FluentSMTP.

== Author ==

Silent mode alert for FluentSMTP is developed and maintained by **Adam Bichler**.

For support, use the support section on the plugin's WordPress.org page. The source code is maintained at [GitHub](https://github.com/adambichler/silent-mode-alert-for-fluentsmtp).

Copyright 2026 Adam Bichler.

== Features ==

* Detects Fluent SMTP silent mode automatically.
* Sends reminders through all active Fluent SMTP notification channels.
* Supports Telegram, Slack, Discord, and Pushover through Fluent SMTP.
* Configures the check interval in minutes, defaulting to 15 minutes.
* Temporarily disables reminders until a selected date and time.
* Automatically clears an expired notification pause.
* Provides a test notification button in the plugin settings.
* Shows a prominent dashboard warning when no notification channel is available.
* Uses English as the default language and is ready for translation.

== Requirements ==

* WordPress 6.5 or newer.
* PHP 7.4 or newer.
* FluentSMTP installed and active.
* At least one active FluentSMTP notification channel for remote alerts.

== Installation ==

1. Upload the `silent-mode-alert-for-fluentsmtp` folder to `/wp-content/plugins/`.
2. Activate **Silent mode alert for FluentSMTP** from the **Plugins** screen.
3. Make sure FluentSMTP is installed and active.
4. Configure at least one notification channel in FluentSMTP.
5. Open **Settings > Silent Mode alert** to configure the interval and notification pause.
6. Use **Send test notification** to verify the configured channels.

== Frequently Asked Questions ==

= Does this plugin send email? =

No. The plugin uses FluentSMTP's configured notification channels. It does not send the reminder by email.

= What happens when no notification channel is configured? =

A WordPress dashboard warning is shown while silent mode is active. The warning links directly to FluentSMTP's notification settings.

= What is the default check interval? =

The default interval is 15 minutes. The interval can be changed from 1 to 1,440 minutes.

= What does the notification pause do? =

It suppresses remote reminders until the selected date and time in the WordPress site timezone. Checks continue during the pause. The field is cleared automatically after the pause expires, and reminders resume on the next eligible check.

= Does the test button depend on silent mode being active? =

No. The test button sends a test notification to the active Fluent SMTP channels regardless of the current silent mode setting or notification pause.

= Does the plugin replace Fluent SMTP? =

No. FluentSMTP remains responsible for email delivery and notification-channel configuration. This plugin only monitors the silent mode setting and sends reminders through FluentSMTP.

== Privacy ==

This plugin does not collect, transmit to its own servers, or store personal data. It stores only the configured interval and notification pause timestamp in the WordPress options table.

When FluentSMTP silent mode is active, or when an administrator uses the test button, this plugin asks FluentSMTP to send a fixed notification message through every channel the administrator has already enabled. The message contains the site's home URL and states that FluentSMTP silent mode is active. The selected service receives that message under the administrator's existing FluentSMTP configuration.

The plugin can use the following third-party services only when the administrator has configured the corresponding FluentSMTP notification channel:

* [FluentSMTP](https://fluentsmtp.com/) - [Privacy Policy](https://fluentsmtp.com/privacy-policy/)
* [Telegram](https://telegram.org/) - [Privacy Policy](https://telegram.org/privacy)
* [Slack](https://slack.com/) - [Privacy Policy](https://slack.com/privacy-policy)
* [Discord](https://discord.com/) - [Privacy Policy](https://discord.com/privacy)
* [Pushover](https://pushover.net/) - [Privacy Policy](https://pushover.net/privacy)

Refer to the selected service's terms and privacy policy before enabling its channel.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added silent mode checks and configurable WP-Cron interval.
* Added notification pause and automatic expiry cleanup.
* Added Fluent SMTP channel delivery and test notifications.
* Added dashboard warning and translation support.
