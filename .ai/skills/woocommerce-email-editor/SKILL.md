---
name: woocommerce-email-editor
description: Setup and develop the WooCommerce block email editor. Use when working on email templates, transactional emails, or the email editor feature.
---

# WooCommerce Email Editor Development

This skill provides guidance for developing the WooCommerce block email editor.

## When to Use This Skill

Invoke this skill when:

- Setting up local development for the email editor
- Working on email templates or transactional emails
- Modifying the email editor PHP or JS packages
- Testing email sending functionality

## Development Environment Setup

### 1. Start WooCommerce wp-env

```sh
pnpm --filter=@woocommerce/plugin-woocommerce -- wp-env start
```

Site runs at `http://localhost:8888` by default. If port 8888 is unavailable, wp-env assigns a different port. Check the startup output or run `wp-env info` to see your actual URL.

### 2. Start the watcher

Watches and builds admin JS files (including the email editor JS package) and syncs Email Editor PHP package changes:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce watch:build:admin
```

### 3. Enable the block email editor feature

Visit <http://localhost:8888/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features> and enable the block email editor.

### 4. Edit transactional emails

Visit <http://localhost:8888/wp-admin/admin.php?page=wc-settings&tab=email>

## Email Testing with Mailpit

To test email sending locally, use Mailpit as a local SMTP server.

### Configure SMTP

Add to `plugins/woocommerce/.wp-env.override.json`:

```json
{
    "lifecycleScripts": {
        "afterStart": "./tests/e2e-pw/bin/test-env-setup.sh && wp-env run cli wp plugin install wp-mail-smtp --activate"
    },
    "config": {
        "WPMS_ON": true,
        "WPMS_MAILER": "smtp",
        "WPMS_SMTP_HOST": "host.docker.internal",
        "WPMS_SMTP_PORT": "1025",
        "WPMS_SMTP_AUTH": false,
        "WPMS_SMTP_SECURE": ""
    }
}
```

### Start Mailpit

```sh
docker run --rm --name=mailpit -p 1025:1025 -p 8025:8025 -d axllent/mailpit
```

Or if the container already exists:

```sh
docker start mailpit
```

### View emails

Open <http://localhost:8025> to see captured emails.

### Restart wp-env

After creating or modifying `.wp-env.override.json`, restart the environment:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce -- wp-env start --update
```

## Key Paths

| Path | Description |
| ---- | ----------- |
| `packages/php/email-editor/` | Email Editor PHP package |
| `packages/js/email-editor/` | Email Editor JS package |
| `plugins/woocommerce/.wp-env.override.json` | Local wp-env config (gitignored) |

## Building the Email Editor Package

To rebuild the JS package after changes:

```sh
pnpm --filter=@woocommerce/email-editor build
```

## Testing the PHP Package

Run tests from the `packages/php/email-editor/` directory.

### Setup

```sh
cd packages/php/email-editor
wp-env start
composer dump-autoload
```

### Run integration tests

```sh
wp-env run tests-cli --env-cwd=wp-content/plugins/email-editor ./vendor/bin/phpunit --configuration phpunit-integration.xml.dist
```

### Run a specific test

```sh
wp-env run tests-cli --env-cwd=wp-content/plugins/email-editor ./vendor/bin/phpunit --configuration phpunit-integration.xml.dist --filter Table_Test
```

## Linting the PHP Package

Run from the `packages/php/email-editor/` directory.

### PHPStan

```sh
cd tasks/phpstan && ./run-phpstan.sh
```

For PHP 7 compatibility:

```sh
cd tasks/phpstan && ./run-phpstan.sh php7
```

### PHPCS

```sh
composer run phpcs
```
