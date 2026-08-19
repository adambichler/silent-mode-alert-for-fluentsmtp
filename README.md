# Silent mode alert for FluentSMTP

Silent mode alert for FluentSMTP monitors FluentSMTP silent mode and sends recurring reminders through the notification channels already configured in FluentSMTP.

Supported channels are Telegram, Slack, Discord, and Pushover. The plugin requires WordPress 6.5 or newer, PHP 7.4 or newer, and an active FluentSMTP installation.

## Development

The WordPress.org readme is [silent-mode-alert-for-fluentsmtp/readme.txt](silent-mode-alert-for-fluentsmtp/readme.txt). Directory artwork is stored in [assets/](assets/).

Before opening a pull request, run:

```sh
php -l silent-mode-alert-for-fluentsmtp/silent-mode-alert-for-fluentsmtp.php
php -l silent-mode-alert-for-fluentsmtp/includes/class-silent-mode-alert-for-fluentsmtp.php
```

Test changes with WordPress and FluentSMTP installed and active. Verify activation, deactivation, cron scheduling, notification delivery, the test-notification action, snooze expiry, permissions, and the dashboard warning.

## Support

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/silent-mode-alert-for-fluentsmtp/) for user support and bug reports. Security issues should be reported privately through the repository's security reporting process.

## License

This plugin is licensed under the [GNU General Public License, version 2 or later](LICENSE).