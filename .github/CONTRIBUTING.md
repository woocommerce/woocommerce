# How To Contribute

Community made patches, localizations, bug reports and contributions are always welcome and crucial to ensure WooCommerce remains the leading eCommerce platform for WordPress. WooCommerce currently powers 30% of all online stores across the internet, and your help making it even more awesome will be greatly appreciated :)

When contributing please ensure you follow the guidelines below to help us keep on top of things.

__Please Note:__

GitHub is for _bug reports and contributions only_ - if you have a support question or a request for a customization this is not the right place to post it. Use [WooThemes Support](https://support.woothemes.com) for customer support, [WordPress.org](https://wordpress.org/support/plugin/woocommerce) for community support, and for customizations we recommend one of the following services:

- [WooExperts](https://woocommerce.com/experts/)
- [Codeable](https://codeable.io/)

## Contributing To The Core

### Reporting Issues

Reporting issues is a great way to became a contributor as it doesn't require technical skills. In fact you don't even need to know a programming language or to be able to check the code itself, you just need to make sure that everything works as expected and [submit an issue report](https://github.com/woothemes/woocommerce/issues/new) if you spot a bug. Sound like something you're up for? Go for it!

#### How To Submit An Issue Report

If something isn't working, congratulations you've found a bug! Help us fix it by submitting an issue report:

* Make sure you have a [GitHub account](https://github.com/signup/free)
* Search the [Existing Issues](https://github.com/woothemes/woocommerce/issues) to be sure that the one you've noticed isn't already there
* Submit a report for your issue
  * Clearly describe the issue (including steps to reproduce it if it's a bug)
  * Make sure you fill in the earliest version that you know has the issue.

### Making Changes

Making changes to the core is a key way to help us improve WooCommerce. You will need some technical skills to make a change, like knowing a bit of PHP, CSS, SASS or JavaScript.

If you think something could be improved and you're able to do so, make your changes and submit a Pull Request. We'll be pleased to get it :)

#### How To Submit A PR

* Fork the repository on GitHub
* Make the changes to your forked repository
  * **Ensure you stick to the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/coding-standards/php/).**
  * Ensure you use LF line endings - no crazy Windows line endings please :)
* When committing, reference your issue number (#1234) and include a note about the fix
* Push the changes to your fork and submit a pull request on the master branch of the WooCommerce repository. Existing maintenance branches will be maintained by WooCommerce developers
* Please **don't** modify the changelog - this will be maintained by the WooCommerce developers.
* Please **don't** add your localizations or update the .pot files - these will also be maintained by the WooCommerce developers. To contribute to the localization of WooCommerce, please join the [translate.wordpress.org project](https://translate.wordpress.org/projects/wp-plugins/woocommerce). This is much needed, if you speak a language that needs translating consider yourself officially invited to the party.

After you follow the step above, the next stage will be waiting on us to merge your Pull Request. We review them all, and make suggestions and changes as and if necessary.

## Contribute To Localizing WooCommerce

Localization is a very important part of WooCommerce. We believe in net neutrality and want our platform to be available to everyone, everywhere with equal ease. When you localize WooCommerce, you are helping hundreds of people in the world, and all the people who speak your language. That's pretty neat.

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

### Translating The Core

We have a [project on translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/woocommerce). You can join the localization team of your language and help by translating WooCommerce. [Find more about using joining a language team and using GlotPress](https://make.wordpress.org/polyglots/handbook/tools/glotpress-translate-wordpress-org/).

If WooCommerce is already 100% translated for your language, join the team anyway! We regularly update our language files and there will definitely be need of your help soon.

### Translating Video Tutorials

Another valuable way to help is by translating our growing library of WooCommerce video tutorials. Check out our [Video SRTs project](https://www.transifex.com/projects/p/video-srts/) and join the team for your language. If there isn't one, please request the new language and we will add it for you.

By translating video tutorials you'll be helping non-English speaking users and people affected by disabilities to get to grips with using WooCommerce for the first time, and to go on and create their businesses and make a living! That's something to be proud of and if you choose to dive into this area, we salute you.

# Additional Resources

* [General GitHub documentation](https://help.github.com/)
* [GitHub pull request documentation](https://help.github.com/send-pull-requests/)
* [Translator Handbook](https://make.wordpress.org/polyglots/handbook/)
* [WooCommerce Docs](https://docs.woothemes.com/)
* [WooThemes Support](https://support.woothemes.com)
