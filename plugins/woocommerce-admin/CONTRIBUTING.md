# Contributing to WooCommerce Admin

Hi! Thank you for your interest in contributing to WooCommerce Admin. We appreciate it.

There are many ways to contribute – reporting bugs, feature suggestions, and fixing bugs.

## Reporting Bugs, Asking Questions, Sending Suggestions

Open [a GitHub issue](https://github.com/woocommerce/woocommerce-admin/issues/new/choose), that's all. If you have write access, add any appropriate labels.

If you're filing a bug, specific steps to reproduce are always helpful. Please include what you expected to see and what happened instead.

## We're Here To Help

We encourage you to ask for help. We want your first experience with WooCommerce Admin to be a good one, so don't be shy. If you're wondering why something is the way it is, or how a decision was made, you can tag issues with [Type] Question or prefix them with “Question:”

## Contributing Code

If you're a first-time code contributor to the repository, here's a quick guide to get started:

1. Fork the repo to your own account.
2. Clone your fork into the `wp-content/plugins` directory of your preferred WordPress development environment.
3. Don't forget to create a branch to keep your changes. (`git checkout -b add/my-cool-thing`).
4. From the `woocommerce-admin` plugin directory, build with `npm install` and `npm start`.
5. Visit your dev environment in the browser to enable the `WooCommerce Admin` plugin and try it out.

Tips:
- Try to keep each PR small (around 200-250 lines or less, if you can), and having multiple very small commits in each PR is preferable to one larger commit (especially if the PR is larger).
- Don't combine code formatting changes with meaningful ones. If there's formatting work that needs to be done en masse, do it all in one PR, then open another one for meaningful code changes.
- Add unit tests to your PR for better code coverage and review.

After you've made your updates, you're ready to commit:

1. Run a complete build via `npm run build`.
2. Do a `composer install` to ensure PHP dependencies can run on the pre-commit hook.
3. Create your commit. Write a descriptive, but short first line (e.g. "Reports: Reticulate the splines"), and add more details below. If your commit addresses a github issue, reference it by number here (e.g. "This commit fixes issue #123 by reticulating all the splines.")
4. Push the branch up to your local fork, then create a PR via the GitHub web interface.

## License

WooCommerce Admin is licensed under [GNU General Public License v3 (or later)](/license.txt).

All materials contributed should be compatible with the GPLv3. This means that if you own the material, you agree to license it under the GPLv3 license. If you are contributing code that is not your own, such as adding a component from another Open Source project, or adding an `npm` package, you need to make sure you follow these steps:

1. Check that the code has a license. If you can't find one, you can try to contact the original author and get permission to use, or ask them to release under a compatible Open Source license.
2. Check the license is compatible with [GPLv3](https://www.gnu.org/licenses/license-list.en.html#GPLCompatibleLicenses), note that the Apache 2.0 license is *not* compatible.
