---
post_title: WooCommerce extension development best practices
menu_title: Best Practices
---

Want to create a plugin to extend WooCommerce? You're in the right place.

WooCommerce extensions are the same as regular WordPress plugins. For more information, visit [Writing a plugin](https://developer.wordpress.org/plugins/).

Your WooCommerce extension should:

- Adhere to all WordPress plugin coding standards, as well as [best practice guidelines](https://developer.wordpress.org/plugins/plugin-basics/best-practices/) for harmonious existence within WordPress and alongside other WordPress plugins.
- Have a single core purpose and use WooCommerce features as much as possible.
- Not do anything malicious, illegal, or dishonest - for example, inserting spam links or executable code via third-party systems if not part of the service or  explicitly permitted in the service's terms of use.
- Adhere to WooCommerce [compatibility and interoperability guidelines](woocommerce.com/document/marketplace-overview/#section-9).

Merchants make use of WooCommerce extensions daily, and should have a unified and pleasant experience while doing so without advertising invading their WP Admin or store.

## Best Practices

1. **Check if WooCommerce is active**. Most WooCommerce plugins do not need to run unless WooCommerce is already active. [Learn how to check if WooCommerce is active](./check-if-woo-is-active.md).
2. **The main plugin file should adopt the name of the plugin**. For example: A plugin with the directory name `plugin-name` would have its main file named `plugin-name.php`.
3. **The text domain should match your plugin directory name**. For example: A plugin with a directory name of `plugin-name` would have the text domain `plugin-name`. Do not use underscores. 
4. **Internationalization**: Follow guidelines for [Internationalization for WordPress Developers](https://codex.wordpress.org/I18n_for_WordPress_Developers)
5. **Localization**: All text strings within the plugin code should be in English. This is the WordPress default locale, and English should always be the first language. If your plugin is intended for a specific market (e.g., Spain or Italy), include appropriate translation files for those languages within your plugin package. Learn more at [Using Makepot to translate your plugin](https://codex.wordpress.org/I18n_for_WordPress_Developers#Translating_Plugins_and_Themes).
6. **Follow WordPress PHP Guidelines**. WordPress has a [set of guidelines](http://make.wordpress.org/core/handbook/coding-standards/php/) to keep all WordPress code consistent and easy to read. This includes quotes, indentation, brace style, shorthand php tags, yoda conditions, naming conventions, and more. Please review the guidelines.
7. **Avoid creating custom database tables**. Whenever possible, use WordPress [post types](http://codex.wordpress.org/Post_Types#Custom_Post_Types), [taxonomies](http://codex.wordpress.org/Taxonomies), and [options](http://codex.wordpress.org/Creating_Options_Pages). For more, check out our [primer on data storage](./data-storage.md).
8. **Prevent Data Leaks** by ensuring you aren't providing direct access to PHP files. [Find out how](../security/prevent-data-leaks.md). 
9. **All plugins need a [standard WordPress README](http://wordpress.org/plugins/about/readme.txt)**. See an example in the [WordPress plugin README file standard](https://wordpress.org/plugins/readme.txt).
10. **Follow our conventions for your Plugin header comment**. View our [example WordPress plugin header comment](./example-plugin-header-comment.md) and follow the conventions listed, including: `Author:`,  `Author URI:` , `Developer:`, `Developer URI`, `WC requires at least:`and `WC tested up to:`, and `Plugin URI:`.
11. **Make it extensible**: use WordPress actions and filters to allow for modification/customization, and if your plugin creates a front-end output, we recommend having a templating engine in place so users can create custom template files in their theme's WooCommerce folder to overwrite the plugin's template files.For more information, check out Pippin's post on [Writing Extensible Plugins with Actions and Filters](http://code.tutsplus.com/tutorials/writing-extensible-plugins-with-actions-and-filters--wp-26759).
12. **Avoid external libraries**. The use of entire external libraries is typically not suggested as this can open up the product to security vulnerabilities. If an external library is absolutely necessary, developers should be thoughtful about the code used and assume ownership as well as of responsibility for it. Try to  only include the strictly necessary part of the library, or use a WordPress-friendly version or opt to build your own version. For example, if needing to use a text editor such as TinyMCE, we recommend using the WordPress-friendly version, TinyMCE Advanced.
13. **Remove unused code**. With version control, there's no reason to leave commented-out code; it's annoying to scroll through and read. Remove it and add it back later if needed.
14. **Use Comments** to describe the functions of your code. If you have a function, what does the function do? There should be comments for most if not all functions in your code. Someone/You may want to modify the plugin, and comments are helpful for that. We recommend using [PHP Doc Blocks](http://en.wikipedia.org/wiki/PHPDoc) similar to [WooCommerce](https://github.com/woocommerce/woocommerce/).
15. **Avoid God Objects**. [God Objects](http://en.wikipedia.org/wiki/God_object) are objects that know or do too much. The point of object-oriented programming is to take a large problem and break it into smaller parts. When functions do too much, it's hard to follow their logic, making bugs harder to fix. Instead of having massive functions, break them down into smaller pieces.
16. **Test Your Code with [WP_DEBUG](http://codex.wordpress.org/Debugging_in_WordPress)** mode on, so you can see all PHP warnings sent to the screen. This will flag things like making sure a variable is set before checking the value.
17. **Separate Business Logic & Presentation Logic.** It's a good practice to separate business logic (i.e., how the plugin works) from [presentation logic](http://en.wikipedia.org/wiki/Presentation_logic) (i.e., how it looks). Two separate pieces of logic are more easily maintained and swapped if necessary. An example is to have two different classes - one for displaying the end results, and one for the admin settings page.
18. **Use Transients to Store Offsite Information**. If you provide a service via an API, it's best to store that information so future queries can be done faster and the load on your service is lessened. [WordPress transients](http://codex.wordpress.org/Transients_API) can be used to store data for a certain amount of time.
19. **Log data that can be useful for debugging purposes**, with two conditions: Allow any logging as an 'opt in', and  Use the [WC_Logger](woocommerce.com/wc-apidocs/class-WC_Logger.html) class. A user can then view logs on their system status page. Learn [how to add a link to logged data](.docs/link-to-logged-data) in your extension. 
