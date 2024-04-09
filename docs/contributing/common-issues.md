---
post_title: Common issues
menu_title: Common issues
tags: reference
---

This page aims to document a comprehensive list of known issues, commonly encountered problems, and their solutions or workarounds. If you have encountered an issue that is not mentioned here and should be, please don't hesitate to add to the list.

## Composer error on `Automattic\Jetpack\Autoloader\AutoloadGenerator`

```bash
[ErrorException]
  Declaration of Automattic\Jetpack\Autoloader\AutoloadGenerator::dump(Composer\Config $config, Composer\Repository\Inst
  alledRepositoryInterface $localRepo, Composer\Package\PackageInterface $mainPackage, Composer\Installer\InstallationMa
  nager $installationManager, $targetDir, $scanPsrPackages = false, $suffix = NULL) should be compatible with Composer\A
  utoload\AutoloadGenerator::dump(Composer\Config $config, Composer\Repository\InstalledRepositoryInterface $localRepo,
  Composer\Package\RootPackageInterface $rootPackage, Composer\Installer\InstallationManager $installationManager, $targ
  etDir, $scanPsrPackages = false, $suffix = '')
```

A recent [change](https://github.com/composer/composer/commit/b574f10d9d68acfeb8e36cad0b0b25a090140a3b#diff-67d1dfefa9c7b1c7e0b04b07274628d812f82cd82fae635c0aeba643c02e8cd8) in composer 2.0.7 made our autoloader incompatible with the new `AutoloadGenerator` signature. Try to downgrading to composer 2.0.6 by using `composer self-update 2.0.6`.

## VVV: HostsUpdater vagrant plugin error

```bash
...vagrant-hostsupdater/HostsUpdater.rb:126:in ``digest': no implicit conversion of nil into String (TypeError)
```

You might be running an unsupported version of Vagrant. At the time of writing, VVV works with Vagrant 2.2.7. Please check VVV's [requirements](https://github.com/Varying-Vagrant-Vagrants/VVV#minimum-system-requirements).

## VVV: `install-wp-tests.sh` error

```bash
mysqladmin: CREATE DATABASE failed; error: 'Access denied for user 'wp'@'localhost' to database 'wordpress-one-tests''
```

To fix:

- Open MySQL with `sudo mysql`.
- Run `GRANT ALL PRIVILEGES ON * . * TO 'wp'@'localhost';`. Exit by typing `exit;`.
- Run the `install-wp-tests.sh` script again.

## Timeout / 404 errors while running e2e tests

```bash
 Store owner can complete onboarding wizard › can complete the product types section

    TimeoutError: waiting for function failed: timeout 30000ms exceeded

      1 | export const waitForElementCount = function ( page, domSelector, count ) {
    > 2 | 	return page.waitForFunction(
        | 	            ^
      3 | 		( domSelector, count ) => {
      4 | 			return document.querySelectorAll( domSelector ).length === count;
      5 | 		},
```

Timeouts or 404 errors in the e2e tests signal that the existing build might be broken. Run `npm install && npm run clean && npm run build` to generate a fresh build. It should also be noted that some of our npm scripts also remove the current build, so it's a good practice to always run a build before running e2e tests.

## Docker container couldn't be built when attempting e2e test

```bash
Thu Dec  3 11:55:56 +08 2020 - Docker container is still being built
Thu Dec  3 11:56:06 +08 2020 - Docker container is still being built
Thu Dec  3 11:56:16 +08 2020 - Docker container is still being built
Thu Dec  3 11:56:26 +08 2020 - Docker container couldn't be built
npm ERR! code ELIFECYCLE
npm ERR! errno 1
npm ERR! @woocommerce/e2e-environment@0.1.6 test:e2e: `bash ./bin/wait-for-build.sh && ./bin/e2e-test-integration.js`
npm ERR! Exit status 1
```

Ensure that Docker is running. While the script says `Docker container is still being built`, it is not actually responsible for running Docker; it's just waiting for an existing docker instance to respond. Run `npm run docker:up` if it's not.

## Set up WooCommerce Payments dev mode

Add this to `wp-config.php`:

```php
define( 'WCPAY_DEV_MODE', true );
```

Also see [this document](hhttps://woocommerce.com/document/woopayments/testing-and-troubleshooting/sandbox-mode/).

## WooCommerce Admin install timestamp

To get the install timestamp (used in `wc_admin_active_for()` in `NoteTraits` for example) try this SQL:

```sql
SELECT * FROM wp_options WHERE option_name = 'woocommerce_admin_install_timestamp'
```

## Reset the onboarding wizard

Delete the `woocommerce_onboarding_profile` option:

```sql
DELETE FROM wp_options WHERE option_name = 'woocommerce_onboarding_profile'
```

## Enable tracks debugging in the console

```javascript
localStorage.setItem( 'debug', 'wc-admin:tracks' );
```

and set Chrome's log level "verbose" to checked.

## Running PHP unit tests using Vagrant (VVV)

1. SSH into Vagrant box (`vagrant ssh`)
2. `cd /srv/www/<WP_INSTANCE>/public_html/wp-content/plugins/woocommerce-admin`
3. Set up: `bin/install-wp-tests.sh wc-admin-tests root root`
4. Fast tests: `./vendor/bin/phpunit --group fast`
5. All tests: `./vendor/bin/phpunit`

You might need to `composer install` if `phpunit` doesn't exist.

## Show the welcome modal again

Delete the option `woocommerce_task_list_welcome_modal_dismissed`:

```sql
DELETE FROM wp_options WHERE option_name = 'woocommerce_task_list_welcome_modal_dismissed'
```
