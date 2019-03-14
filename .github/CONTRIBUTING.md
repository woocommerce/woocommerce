# Contributing to WooCommerce ✨

There are many ways to contribute to the WooCommerce project!

- Translating strings into your language.
- Answering questions on GitHub and within the various WooCommerce communities.
- Submitting fixes, improvements, and enhancements.

WooCommerce currently powers 30% of all online stores across the internet, and your help making it even more awesome will be greatly appreciated :)

If you think something can be improved and you wish to contribute code,
[fork](https://help.github.com/articles/fork-a-repo/) WooCommerce, commit your changes,
and [send a pull request](https://help.github.com/articles/using-pull-requests/). We'll be happy to review your changes!

## Feature Requests

Feature requests can be [submitted to our issue tracker](https://github.com/woocommerce/woocommerce/issues/new?template=Feature_request.md). Be sure to include a description of the expected behavior and use case, and before submitting a request, please search for similar ones in the closed issues.

Feature request issues will remain closed until we see sufficient interest via comments and [👍 reactions](https://help.github.com/articles/about-discussions-in-issues-and-pull-requests/) from the community.

You can see a [list of current feature requests which require votes here](https://github.com/woocommerce/woocommerce/issues?q=label%3A%22votes+needed%22+label%3Aenhancement+sort%3Areactions-%2B1-desc+is%3Aclosed).

## Technical Support / Questions

We don't offer technical support on GitHub so we recommend using the following:

**Reading our documentation**
Usage docs can be found here: https://docs.woocommerce.com/

If you have a problem, you may want to start with the self help guide here: https://docs.woocommerce.com/document/woocommerce-self-service-guide/

**Technical support for premium extensions or if you're a WooCommerce.com customer**
 from a human being - submit a ticket via the helpdesk
https://woocommerce.com/contact-us/ 

**General usage and development questions**
- WooCommerce Slack Community: https://woocommerce.com/community-slack/
- WordPress.org Forums: https://wordpress.org/support/plugin/woocommerce
- The WooCommerce Help and Share Facebook group

**Customizations**
- [WooExperts](https://woocommerce.com/experts/)
- [Codeable](https://codeable.io/)

## Coding Guidelines

- **Ensure you stick to the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)**
- Install our pre-commit hook using composer. It'll help with the Coding Standards. To install run `composer install` from the command line within the woocommerce plugin directory.
- Ensure you use LF line endings in your code editor. Use [EditorConfig](http://editorconfig.org/) if your editor supports it so that indentation, line endings and other settings are auto configured.
- When committing, reference your issue number (#1234) and include a note about the fix.
- Ensure that your code is compatible with PHP 5.2+.
- Push the changes to your fork and submit a pull request on the master branch of the WooCommerce repository. Existing maintenance branches will be maintained by WooCommerce developers.

Please **don't** modify the changelog or update the .pot files. These will be maintained by the WooCommerce team.

## Translating WooCommerce

We have a [project on translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce). You can join the localization team of your language and help by translating WooCommerce. [Find more about using joining a language team and using GlotPress](https://make.wordpress.org/polyglots/handbook/tools/glotpress-translate-wordpress-org/).

If WooCommerce is already 100% translated for your language, join the team anyway! We regularly update our language files and there will definitely be need of your help soon.

### Glossary & Style Guide

Please refer to this page on the [Translator Handbook](https://make.wordpress.org/polyglots/handbook/translating/glossary-style-guide/) for information about the glossary and the style guide.

We maintain the WooCommerce glossary [on this shared Google Sheet](https://docs.google.com/spreadsheets/d/1Pobl2nNWieaSpZND9-Bwa4G8pnMU7QYceKsXuWCwSxQ/edit?usp=sharing). You can use it as a template for creating your own glossary.
Please download the file by going to **File > Download as > Comma-separated values (.csv, current sheet)** and save it on your computer/Mac. Open it with your favourite CSV editor (or re-upload it on your own Google Drive) and edit it.

Make sure to edit the second column’s header by using your own language’s code (eg. for Italian you would use `it`, for Portuguese (Brazil) you would use `pt-BR`).

Write the translated entry in this column and translate the entry description as well.
Don’t change other columns headers and value, but feel free to add new entries.

When your CSV is ready, import it on GlotPress.

_**Warning**: Importing a CSV does not replace existing items, they will be created again. We suggest to import them only when first creating the glossary._

Each translation editor will take care of updating the glossary on GlotPress by editing/adding items when needed.

_**Note**: Only editors can create/import and edit glossaries and glossary items on GlotPress. Anyone can suggest new items to add to the glossary or translate them._

**Style Guides Available**

We don’t have a Style Guide template available, so feel free to create your own. Here are the style guides available at the moment:

* [Italian](https://docs.google.com/document/d/1rspopHOiTL-5-PjyG5eJxjkYk6JkzqVbyS24OdA052o/edit?usp=sharing)

If you created a style guide for your language, please let us know so we can add it in the list above. You can also add it by yourself by submitting a PR for this file.

### Translating Video Tutorials

Another valuable way to help is by translating our growing library of WooCommerce video tutorials. Check out the [Translating  Our Videos](https://docs.woocommerce.com/document/translating-our-videos/) doc and join in!

By translating video tutorials you'll be helping non-English speaking users and people affected by disabilities to get to grips with using WooCommerce for the first time, and to go on and create their businesses and make a living! That's something to be proud of and if you choose to dive into this area, we salute you.
