# Contributing to WooCommerce ✨

There are many ways to contribute to the WooCommerce project!

- Translating strings into your language.
- Answering questions on GitHub and within the various WooCommerce communities.
- Submitting fixes, improvements, and enhancements.

WooCommerce currently powers 30% of all online stores across the internet, and your help making it even more awesome will be greatly appreciated :)

If you think something can be improved and you wish to contribute code,
[fork](https://help.github.com/articles/fork-a-repo/) WooCommerce, commit your changes,
and [send a pull request](https://help.github.com/articles/using-pull-requests/). We'll be happy to review your changes!

## Feature Requests 🚀

Feature requests can be [submitted to our issue tracker](https://github.com/woocommerce/woocommerce/issues/new?template=Feature_request.md). Be sure to include a description of the expected behavior and use case, and before submitting a request, please search for similar ones in the closed issues.

Feature request issues will remain closed until we see sufficient interest via comments and [👍 reactions](https://help.github.com/articles/about-discussions-in-issues-and-pull-requests/) from the community.

You can see a [list of current feature requests which require votes here](https://github.com/woocommerce/woocommerce/issues?q=label%3A%22votes+needed%22+label%3Aenhancement+sort%3Areactions-%2B1-desc+is%3Aclosed).

## Coding Guidelines and Development 🛠

- **Ensure you stick to the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)**
- Run our build process described in the section above, it will install our pre-commit hook, code sniffs, dependencies, and more.
- Ensure you use LF line endings in your code editor. Use [EditorConfig](http://editorconfig.org/) if your editor supports it so that indentation, line endings and other settings are auto configured.
- When committing, reference your issue number (#1234) and include a note about the fix.
- Ensure that your code supports the minimum supported versions of PHP and WordPress; this is shown at the top of the `readme.txt` file.
- Push the changes to your fork and submit a pull request on the master branch of the WooCommerce repository.

Please avoid modifying the change-log directly or updating the .pot files. These will be updated by the WooCommerce team.

If you are contributing code to the REST API or editor blocks, these are developed in external packages.
- [WooCommerce REST API package](https://github.com/woocommerce/woocommerce-rest-api)
- [Blocks](https://github.com/woocommerce/woocommerce-gutenberg-products-block)
