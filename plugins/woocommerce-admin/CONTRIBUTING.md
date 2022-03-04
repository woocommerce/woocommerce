# Contributing to WooCommerce Admin

Hi! Thank you for your interest in contributing to WooCommerce Admin. We appreciate it.

There are many ways to contribute – reporting bugs, adding translations, feature suggestions, and fixing bugs.

## Reporting Bugs, Asking Questions, Sending Suggestions

Open [a GitHub issue](https://github.com/woocommerce/woocommerce-admin/issues/new/choose), that's all. If you have write access, add any appropriate labels.

If you're filing a bug, specific steps to reproduce are always helpful. Please include what you expected to see and what happened instead.

## Localizing WooCommerce Admin

To translate WooCommerce Admin in your locale or language, [select your locale here](https://translate.wordpress.org/projects/wp-plugins/woocommerce-admin) and translate *Development* (which contains the plugin's string) and/or *Development Readme* (please translate what you see in the Details tab of the [plugin page](https://wordpress.org/plugins/woocommerce-admin/)).

A Global Translation Editor (GTE) or Project Translation Editor (PTE) with suitable rights will process your translations in due time.

Language packs are automatically generated once 95% of the plugin's strings are translated and approved for a locale.

### Testing translations in development without language packs

1. Requires `WP-CLI` version 2.1.0 or greater.
1. Generate a translation file with `pnpm run i18n xx_YY` (Where xx_YY is your locale, like it_IT).
1. Generate needed JSON files for JavaScript-based strings: `pnpm run i18n:json`.
1. Generate needed `woocommerce-admin-xx_YY.mo` file using your translation tool.
1. Move `.mo` and `.json` files to `/wp-content/languages/plugins`.

## We're Here To Help

We encourage you to ask for help. We want your first experience with WooCommerce Admin to be a good one, so don't be shy. If you're wondering why something is the way it is, or how a decision was made, you can tag issues with [Type] Question or prefix them with “Question:”

## Contributing Code

If you're a first-time code contributor to the repository, here's a quick guide to get started:

1. Fork the repo to your own account.
2. Clone your fork into the `wp-content/plugins` directory of your preferred WordPress development environment.
3. Don't forget to create a branch to keep your changes. (`git checkout -b add/my-cool-thing`).
4. From the `woocommerce-admin` plugin directory, build with `pnpm install` and `pnpm start`.
5. Visit your dev environment in the browser to enable the `WooCommerce Admin` plugin and try it out.

Tips:
- Try to keep each PR small (around 200-250 lines or less, if you can), and having multiple very small commits in each PR is preferable to one larger commit (especially if the PR is larger).
- Don't combine code formatting changes with meaningful ones. If there's formatting work that needs to be done en masse, do it all in one PR, then open another one for meaningful code changes.
- Add unit tests to your PR for better code coverage and review.

After you've made your updates, you're ready to commit:

1. Run a complete build via `pnpm run build`.
2. Do a `composer install` to ensure PHP dependencies can run on the pre-commit hook.
3. Create your commit. Write a descriptive, but short first line (e.g. "Reports: Reticulate the splines"), and add more details below. If your commit addresses a github issue, reference it by number here (e.g. "This commit fixes issue #123 by reticulating all the splines.")
4. Push the branch up to your local fork, then create a PR via the GitHub web interface.

## Creating a Pull Request

The pull request template will remind you of some of the details you need to fill out in your pull request, but there are 2 critical pieces of information that may be needed, the changelog and the testing instructions.

### Changelog Entry

For many pull requests a changelog entry is required. We make use of the [Jetpack Changelogger tool](https://packagist.org/packages/automattic/jetpack-changelogger) to handle our changelogs.

To create a changelog entry run `pnpm run changelogger -- add` and answer the questions. This will create a changelog entry in the [./changelogs](./changelogs) directory with the data you provided. Upon our next release this will be added to our [changelog.txt](./changelog.txt).

In most cases you'll have to provide a changelog entry (the last question), be sure to add your PR number at the end, in the format below:

`<Description of change>. #<PR Number>`

For example:

`a cool new feature. #1234`

The types we use currently are: "Fix", "Add", "Update", "Dev", "Tweak", "Performance" and "Enhancement"

-   `Fix`. For bugfixes minor and major. e.g. "Fix a crash when the user selected 0 for revenue."
-   `Add`. This is reserved for new features and functionality. e.g. "A new page for payment settings."
-   `Update`. This is used interchangeably with `Add` at the moment. Use your best discretion to choose.
-   `Dev` is for a code change that doesn't have an obvious user facing benefit. e.g. "Refactor a class to be single responsibility."
-   `Tweak`. For minor changes to user facing functionality. e.g. "Styling updates to the site footer."
-   `Performance`. For changes that improve the performance of the application. e.g. "Optimized SQL query to run 5x faster.".
-   `Enhancement`. This is used interchangeably with `Tweak` at the moment. Use your best discretion to choose.

### Testing Instructions

Every release we do some manual testing of new features, workflows and major bugfixes. For these kind of changes we need to include
testing instructions. If your pull request requires testing instructions you'll need to add them under the `## Unreleased` heading in
`TESTING-INSTRUCTIONS.md`. Add a detailed set of testing instructions to test your change.

### When to Add Testing Instructions

**DO** Add testing instructions for:

-   Significant new features and workflows being added.
-   Major bugs and regressions. (This does not include fatal crashes on main screens though, these are covered by general testing).

**DON'T** Add testing instructions for:

-   Visual issues and changes.
-   Minor bugs.
-   Tweaks
-   Analytics tracking

Please make testing instructions as comprehensive as possible as testers may not have context of how to test some aspects
of the system.

For example an instruction like: `Enable new navigation` should be `Toggle on the new navigation under WooCommerce->Settings->Advanced->Features`.
Assume the tester does not have context on how to test the feature except for a basic understanding of Wordpress.

## PHP Unit tests

### Setting up PHP unit tests using [VVV](https://github.com/Varying-Vagrant-Vagrants/VVV)

1. SSH into the Vagrant box:
    1. `cd` down to the Vagrant root (where `www` lives) 
    2. `vagrant ssh`
2. `cd /srv/www/<name of wp install>/public_html/wp-content/plugins/woocommerce-admin`
3. Set up test environment: `bin/install-wp-tests.sh wc-admin-tests root root`
4. Generate feature config: `php bin/generate-feature-config.php`

*Note: A WooCommerce development environment is required to live within the same `plugins` folder. Follow these [steps](https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment) to do so.*

### Running tests

1. SSH into the Vagrant box (`vagrant ssh`)
2. `cd /srv/www/<name of wp install>/public_html/wp-content/plugins/woocommerce-admin`
3. `composer test` to actually run the test suite

#### Filtering tests

You can restrict the test cases run using `phpunit`'s filter command line argument.

For example, to just run Order Report Stats tests:

`composer test -- --filter="WC_Tests_Reports_Orders_Stats"`

## Helper Scripts

There are a number of helper scripts exposed via our `package.json` (below list is not exhaustive, you can view the [`package.json` file directly to see all](https://github.com/woocommerce/woocommerce-admin/blob/main/package.json)):

 - `pnpm run lint` : Run eslint over the javascript files
 - `pnpm run i18n` : A multi-step process, used to create a pot file from both the JS and PHP gettext calls. First it runs `i18n:js`, which creates a temporary `.pot` file from the JS files. Next it runs `i18n:php`, which converts that `.pot` file to a PHP file. Lastly, it runs `i18n:pot`, which creates the final `.pot` file from all the PHP files in the plugin (including the generated one with the JS strings).
 - `pnpm test` : Run the JS test suite
 - `pnpm run docs`: Runs the script for generating/updating docs.

## Debugging

### Debugging synced lookup information:

To debug synced lookup information in the database, you can bypass the action scheduler and immediately sync order and customer information by using the `woocommerce_analytics_disable_action_scheduling` hook.

```php
add_filter( 'woocommerce_analytics_disable_action_scheduling', '__return_true' );
```

### Using `debug` package.

Currently, the [debug package](https://github.com/visionmedia/debug) is utilized to provide additional debugging for various systems. This tool outputs additional debugging information in the browser console when it is activated.

To activate, open up your browser console and add this:

```js
localStorage.setItem( 'debug', 'wc-admin:*' );
```

## License

WooCommerce Admin is licensed under [GNU General Public License v3 (or later)](/license.txt).

All materials contributed should be compatible with the GPLv3. This means that if you own the material, you agree to license it under the GPLv3 license. If you are contributing code that is not your own, such as adding a component from another Open Source project, or adding an `pnpm` package, you need to make sure you follow these steps:

1. Check that the code has a license. If you can't find one, you can try to contact the original author and get permission to use, or ask them to release under a compatible Open Source license.
2. Check the license is compatible with [GPLv3](https://www.gnu.org/licenses/license-list.en.html#GPLCompatibleLicenses), note that the Apache 2.0 license is *not* compatible.
