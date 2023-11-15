# WooCommerce Extension Developer Handbook

Want to create a plugin to extend WooCommerce? WooCommerce extensions are the same as regular WordPress plugins. For more information, visit [Writing a plugin](https://developer.wordpress.org/plugins/).

Your WooCommerce extension should:

- Adhere to all WordPress plugin coding standards, as well as [best practice guidelines](https://developer.wordpress.org/plugins/plugin-basics/best-practices/) for harmonious existence within WordPress and alongside other WordPress plugins.
- Have a single core purpose and use WooCommerce features as much as possible.
- Not do anything malicious, illegal, or dishonest — for example, inserting spam links or executable code via third-party systems if not part of the service or  explicitly permitted in the service’s terms of use.
- Adhere to WooCommerce [compatibility and interoperability guidelines](https://woo.com/document/marketplace-overview/#section-9).

Merchants make use of WooCommerce extensions daily, and should have a unified and pleasant experience while doing so without advertising invading their WP Admin or store.

Note: We provide this page as a best practice for developers.

## [Check if WooCommerce is active](https://woo.com/document/create-a-plugin/#section-1)

Most WooCommerce plugins do not need to run unless WooCommerce is already active. You can wrap your plugin in a check to see if WooCommerce is installed:

```php
// Test to see if WooCommerce is active (including network activated).

$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (

in_array( $plugin_path, wp_get_active_and_valid_plugins() )

|| in_array( $plugin_path, wp_get_active_network_plugins() )

) {

// Custom code here. WooCommerce is active, however it has not

// necessarily initialized (when that is important, consider

// using the \`woocommerce_init\` action).

}
```

Note that this check will fail if the WC plugin folder is named anything other than woocommerce.

## [Main file naming](https://woo.com/document/create-a-plugin/#section-2)

The main plugin file should adopt the name of the plugin, e.g., A plugin with the directory name plugin-name would have its main file named plugin-name.php.

## [Text domains](https://woo.com/document/create-a-plugin/#section-3)

Follow guidelines for [Internationalization for WordPress Developers](https://codex.wordpress.org/I18n_for_WordPress_Developers), the text domain should match your plugin directory name, e.g., A plugin with a directory name of plugin-name would have the text domain plugin-name. Do not use underscores.

## [Localization](https://woo.com/document/create-a-plugin/#section-4)

All text strings within the plugin code should be in English. This is the WordPress default locale, and English should always be the first language. If your plugin is intended for a specific market (e.g., Spain or Italy), include appropriate translation files for those languages within your plugin package. Learn more at [Using Makepot to translate your plugin](https://codex.wordpress.org/I18n_for_WordPress_Developers#Translating_Plugins_and_Themes).

## [Follow WordPress PHP Guidelines](https://woo.com/document/create-a-plugin/#section-5)

WordPress has a [set of guidelines](http://make.wordpress.org/core/handbook/coding-standards/php/) to keep all WordPress code consistent and easy to read. This includes quotes, indentation, brace style, shorthand php tags, yoda conditions, naming conventions, and more. Please review the guidelines.

Code conventions also prevent basic mistakes, as [Apple made with iOS 7.0.6](https://www.imperialviolet.org/2014/02/22/applebug.html).

## [Custom Database Tables & Data Storage](https://woo.com/document/create-a-plugin/#section-6)

Avoid creating custom database tables. Whenever possible, use WordPress [post types](http://codex.wordpress.org/Post_Types#Custom_Post_Types), [taxonomies](http://codex.wordpress.org/Taxonomies), and [options](http://codex.wordpress.org/Creating_Options_Pages).

Consider the permanence of your data. Here’s a quick primer:

- If the data may not always be present (i.e., it expires), use a transient.
- If the data is persistent but not always present, consider using the WP Cache.
- If the data is persistent and always present, consider the wp_options table.
- If the data type is an entity with n units, consider a post type.
- If the data is a means or sorting/categorizing an entity, consider a taxonomy.

Logs should be written to a file using the [WC_Logger](https://woo.com/wc-apidocs/class-WC_Logger.html) class.

## [Prevent Data Leaks](https://woo.com/document/create-a-plugin/#section-7)

Try to prevent direct access data leaks. Add this line of code after the opening PHP tag in each PHP file:

```php
if ( ! defined( 'ABSPATH' ) ) {
exit; // Exit if accessed directly
}
```

## [Readme](https://woo.com/document/create-a-plugin/#section-8)

All plugins need a [standard WordPress readme](http://wordpress.org/plugins/about/readme.txt).

Your readme might look something like this:

```php
=== Plugin Name ===
Contributors: (this should be a list of wordpress.org userid's)
Tags: comments, spam
Requires at least: 4.0.1
Tested up to: 4.3
Requires PHP: 5.6
Stable tag: 4.3
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html
```

## [Plugin Author Name](https://woo.com/document/create-a-plugin/#section-9)

To ensure a consistent experience for all WooCommerce users,including finding information on who to contact with queries, the following plugin headers should be in place:

- The Plugin Author isYourName/YourCompany
- The Developer header is YourName/YourCompany, with the Developer URI field listed as `http://yourdomain.com/`

For example:

```php
/**
* Plugin Name: WooCommerce Extension
* Plugin URI: https://woo.com/products/woocommerce-extension/
* Description: Your extension's description text.
* Version: 1.0.0
* Author: Your Name
* Author URI: http://yourdomain.com/
* Developer: Your Name
* Developer URI: http://yourdomain.com/
* Text Domain: woocommerce-extension
* Domain Path: /languages
*
* Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
* WC requires at least: 2.2
* WC tested up to: 2.3
*
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
```

## [Declaring required and supported WooCommerce version](https://woo.com/document/create-a-plugin/#section-10)

Use the follow headers to declare “required” and “tested up to” versions:

- WC requires at least
- WC tested up to

## [Plugin URI](https://woo.com/document/create-a-plugin/#section-11)

Ensure that the Plugin URI line of the above plugin header is provided. This line should contain the URL of the plugin’s product/sale page or to a dedicated page for the plugin on your website.

## [Make it Extensible](https://woo.com/document/create-a-plugin/#section-13)

Developers should use WordPress actions and filters to allow for modification/customization without requiring users to touch the plugin’s core code base.

If your plugin creates a front-end output, we recommend to having a templating engine in place so users can create custom template files in their theme’s WooCommerce folder to overwrite the plugin’s template files.

For more information, check out Pippin’s post on [Writing Extensible Plugins with Actions and Filters](http://code.tutsplus.com/tutorials/writing-extensible-plugins-with-actions-and-filters--wp-26759).

## [Use of External Libraries](https://woo.com/document/create-a-plugin/#section-14)

The use of entire external libraries is typically not suggested as this can open up the product to security vulnerabilities. If an external library is absolutely necessary, developers should be thoughtful about the code used and assume ownership as well as of responsibility for it. Try to  only include the strictly necessary part of the library, or use a WordPress-friendly version or opt to build your own version. For example, if needing to use a text editor such as TinyMCE, we recommend using the WordPress-friendly version, TinyMCE Advanced.

## [Remove Unused Code](https://woo.com/document/create-a-plugin/#section-15)

With version control, there’s no reason to leave commented-out code; it’s annoying to scroll through and read. Remove it and add it back later if needed.

## [Comment](https://woo.com/document/create-a-plugin/#section-16)

If you have a function, what does the function do? There should be comments for most if not all functions in your code. Someone/You may want to modify the plugin, and comments are helpful for that. We recommend using [PHP Doc Blocks](http://en.wikipedia.org/wiki/PHPDoc)  similar to [WooCommerce](https://github.com/woocommerce/woocommerce/).

## [Avoid God Objects](https://woo.com/document/create-a-plugin/#section-17)

[God Objects](http://en.wikipedia.org/wiki/God_object) are objects that know or do too much. The point of object-oriented programming is to take a large problem and break it into smaller parts. When functions do too much, it’s hard to follow their logic, making bugs harder to fix. Instead of having massive functions, break them down into smaller pieces.

## [Test Extension Quality & Security with Quality Insights Tool](https://woo.com/document/create-a-plugin/#section-18)

Integrate the [Quality Insights Toolkit (QIT)](https://woocommerce.github.io/qit-documentation/) into your development workflow to ensure your extension adheres to WordPress / WooCommerce quality and security standards. The QIT allows the ability to test your extensions against new releases of PHP, WooCommerce, and WordPress, as well as other active extensions, at the same time. The following tests are available today:

- [End-to-End](https://woocommerce.github.io/qit-documentation/#/test-types/e2e)
- [Activation](https://woocommerce.github.io/qit-documentation/#/test-types/activation)
- [Security](https://woocommerce.github.io/qit-documentation/#/test-types/security)
- [PHPStan](https://woocommerce.github.io/qit-documentation/#/test-types/phpstan)
- [API](https://woocommerce.github.io/qit-documentation/#/test-types/api)

## [Test Your Code with WP_DEBUG](https://woo.com/document/create-a-plugin/#section-19)

Always develop with [WP_DEBUG](http://codex.wordpress.org/Debugging_in_WordPress) mode on, so you can see all PHP warnings sent to the screen. This will flag things like making sure a variable is set before checking the value.

## [Separate Business Logic & Presentation Logic](https://woo.com/document/create-a-plugin/#section-20)

It’s a good practice to separate business logic (i.e., how the plugin works) from [presentation logic](http://en.wikipedia.org/wiki/Presentation_logic) (i.e., how it looks). Two separate pieces of logic are more easily maintained and swapped if necessary. An example is to have two different classes — one for displaying the end results, and one for the admin settings page.

## [Use Transients to Store Offsite Information](https://woo.com/document/create-a-plugin/#section-21)

If you provide a service via an API, it’s best to store that information so future queries can be done faster and the load on your service is lessened. [WordPress transients](http://codex.wordpress.org/Transients_API) can be used to store data for a certain amount of time.

## [Logging Data](https://woo.com/document/create-a-plugin/#section-22)

You may want to log data that can be useful for debugging purposes. This is great with two conditions:

- Allow any logging as an ‘opt in’.
- Use the [WC_Logger](https://woo.com/wc-apidocs/class-WC_Logger.html) class. A user can then view logs on their system status page.

If adding logging to your extension, here’s a snippet for presenting a link to the logs, in a way the extension user can easily make use of.

```php
$label = \_\_( 'Enable Logging', 'your-textdomain-here' );

$description = \_\_( 'Enable the logging of errors.', 'your-textdomain-here' );

if ( defined( 'WC_LOG_DIR' ) ) {

$log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );

$log_key = 'your-plugin-slug-here-' . sanitize_file_name( wp_hash( 'your-plugin-slug-here' ) ) . '-log';

$log_url = add_query_arg( 'log_file', $log_key, $log_url );

$label .= ' | ' . sprintf( \_\_( '%1$sView Log%2$s', 'your-textdomain-here' ), '<a href\="' . esc_url( $log_url ) . '">', '</a\>' );

}

$form_fields\['wc_yourpluginslug_debug'\] = array(

'title' => \_\_( 'Debug Log', 'your-textdomain-here' ),

'label' => $label,

'description' => $description,

'type' => 'checkbox',

'default' => 'no'

);
```
