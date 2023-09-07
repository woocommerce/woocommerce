# WooCommerce Extension Developer Handbook

Want to create a plugin to extend WooCommerce? WooCommerce extensions are the same as regular WordPress plugins. For more information, visit [Writing a plugin](https://www.google.com/url?q=https://developer.wordpress.org/plugins/&sa=D&source=editors&ust=1692724061394513&usg=AOvVaw1PmatucFlJ3lI0z15KYBFq).

Your WooCommerce extension should:

- Adhere to all WordPress plugin coding standards, as well as [best practice guidelines](https://www.google.com/url?q=https://developer.wordpress.org/plugins/plugin-basics/best-practices/&sa=D&source=editors&ust=1692724061394795&usg=AOvVaw1vZcSq6JuW0VNm3HhUSb9s) for harmonious existence within WordPress and alongside other WordPress plugins.
- Have a single core purpose and use WooCommerce features as much as possible.
- Not do anything malicious, illegal, or dishonest — for example, inserting spam links or executable code via third-party systems if not part of the service or  explicitly permitted in the service’s terms of use.
- Adhere to WooCommerce [compatibility and interoperability guidelines](https://www.google.com/url?q=https://woocommerce.com/document/marketplace-overview/%23section-9&sa=D&source=editors&ust=1692724061395243&usg=AOvVaw2qsdAnXBb2o2dmrTg_QKaa).

Merchants make use of WooCommerce extensions daily, and should have a unified and pleasant experience while doing so without advertising invading their WP Admin or store.

Note: We provide this page as a best practice for developers.

## [Check if WooCommerce is active](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-1&sa=D&source=editors&ust=1692724061395542&usg=AOvVaw2bTUi1Q7fivFhe-Gc3VULl)

Most WooCommerce plugins do not need to run unless WooCommerce is already active. You can wrap your plugin in a check to see if WooCommerce is installed:

```
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

## [Main file naming](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-2&sa=D&source=editors&ust=1692724061396656&usg=AOvVaw0bg5CY1zbmVRBUpvcbFoWc)

The main plugin file should adopt the name of the plugin, e.g., A plugin with the directory name plugin-name would have its main file named plugin-name.php.

## [Text domains](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-3&sa=D&source=editors&ust=1692724061397135&usg=AOvVaw2mG8ZyvrV7HLq35afWjwcw)

Follow guidelines for [Internationalization for WordPress Developers](https://www.google.com/url?q=https://codex.wordpress.org/I18n_for_WordPress_Developers&sa=D&source=editors&ust=1692724061397498&usg=AOvVaw3sWMtUFCwi2CM4BnCL9T3w), the text domain should match your plugin directory name, e.g., A plugin with a directory name of plugin-name would have the text domain plugin-name. Do not use underscores.

## [Localization](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-4&sa=D&source=editors&ust=1692724061397875&usg=AOvVaw0aN3CAxkWHDXAaOEAt_XLg)

All text strings within the plugin code should be in English. This is the WordPress default locale, and English should always be the first language. If your plugin is intended for a specific market (e.g., Spain or Italy), include appropriate translation files for those languages within your plugin package. Learn more at [Using Makepot to translate your plugin](https://www.google.com/url?q=https://codex.wordpress.org/I18n_for_WordPress_Developers%23Translating_Plugins_and_Themes&sa=D&source=editors&ust=1692724061398312&usg=AOvVaw1KI1tPNBz1PhXghD6EPeFX).

## [Follow WordPress PHP Guidelines](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-5&sa=D&source=editors&ust=1692724061398556&usg=AOvVaw3r1rBtiLQqnd09_uqcbgN1)

WordPress has a [set of guidelines](https://www.google.com/url?q=http://make.wordpress.org/core/handbook/coding-standards/php/&sa=D&source=editors&ust=1692724061398880&usg=AOvVaw32UFCkh2lVnQ1P11WK5917) to keep all WordPress code consistent and easy to read. This includes quotes, indentation, brace style, shorthand php tags, yoda conditions, naming conventions, and more. Please review the guidelines.

Code conventions also prevent basic mistakes, as [Apple made with iOS 7.0.6](https://www.google.com/url?q=https://www.imperialviolet.org/2014/02/22/applebug.html&sa=D&source=editors&ust=1692724061399164&usg=AOvVaw0U7fB5ITS8uXELL3MgR3zx).

## [Custom Database Tables & Data Storage](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-6&sa=D&source=editors&ust=1692724061399464&usg=AOvVaw2baqakEqCZi76lbxB3zjh9)

Avoid creating custom database tables. Whenever possible, use WordPress [post types](https://www.google.com/url?q=http://codex.wordpress.org/Post_Types%23Custom_Post_Types&sa=D&source=editors&ust=1692724061399803&usg=AOvVaw0flq0h728aDmJWR23oNv0V), [taxonomies](https://www.google.com/url?q=http://codex.wordpress.org/Taxonomies&sa=D&source=editors&ust=1692724061399949&usg=AOvVaw1qbvRfl8wcPI35lvSboCwi), and [options](https://www.google.com/url?q=http://codex.wordpress.org/Creating_Options_Pages&sa=D&source=editors&ust=1692724061400101&usg=AOvVaw3H8WjoRljUHd6q5s8X_Pdi).

Consider the permanence of your data. Here’s a quick primer:

- If the data may not always be present (i.e., it expires), use a transient.
- If the data is persistent but not always present, consider using the WP Cache.
- If the data is persistent and always present, consider the wp_options table.
- If the data type is an entity with n units, consider a post type.
- If the data is a means or sorting/categorizing an entity, consider a taxonomy.

Logs should be written to a file using the [WC_Logger](https://www.google.com/url?q=https://woocommerce.com/wc-apidocs/class-WC_Logger.html&sa=D&source=editors&ust=1692724061401335&usg=AOvVaw3mxPgYSD7oL2sCoQNcN1BO) class.

## [Prevent Data Leaks](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-7&sa=D&source=editors&ust=1692724061401572&usg=AOvVaw3xUKNB9qgJDqnd9RwlY8iT)

Try to prevent direct access data leaks. Add this line of code after the opening PHP tag in each PHP file:

```
if ( ! defined( 'ABSPATH' ) ) {
exit; // Exit if accessed directly
}
```

## [Readme](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-8&sa=D&source=editors&ust=1692724061402226&usg=AOvVaw0phoD93bjkbxKs01VSxbm_)

All plugins need a [standard WordPress readme](https://www.google.com/url?q=http://wordpress.org/plugins/about/readme.txt&sa=D&source=editors&ust=1692724061402537&usg=AOvVaw0CxV8gQGI6n0FztcJ_yxwr).

Your readme might look something like this:

```
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

## [Plugin Author Name](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-9&sa=D&source=editors&ust=1692724061403627&usg=AOvVaw0C49AD_KHbRbjBvuZif55T)

To ensure a consistent experience for all WooCommerce users,including finding information on who to contact with queries, the following plugin headers should be in place:

- The Plugin Author isYourName/YourCompany
- The Developer header is YourName/YourCompany, with the Developer URI field listed as http://yourdomain.com/

For example:

```
/**
* Plugin Name: WooCommerce Extension
* Plugin URI: http://woocommerce.com/products/woocommerce-extension/
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

## [Declaring required and supported WooCommerce version](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-10&sa=D&source=editors&ust=1692724061406115&usg=AOvVaw17Ag30ypAdPnc0BXtUdyUo)

Use the follow headers to declare “required” and “tested up to” versions:

- WC requires at least
- WC tested up to

## [Plugin URI](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-11&sa=D&source=editors&ust=1692724061406678&usg=AOvVaw2A80jh9ZfkI6nLGIa93Hpm)

Ensure that the Plugin URI line of the above plugin header is provided. This line should contain the URL of the plugin’s product/sale page or to a dedicated page for the plugin on your website.

## [Make it Extensible](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-13&sa=D&source=editors&ust=1692724061407164&usg=AOvVaw3UxTnE6_-W2mM5rVyBk6BY)

Developers should use WordPress actions and filters to allow for modification/customization without requiring users to touch the plugin’s core code base.

If your plugin creates a front-end output, we recommend to having a templating engine in place so users can create custom template files in their theme’s WooCommerce folder to overwrite the plugin’s template files.

For more information, check out Pippin’s post on [Writing Extensible Plugins with Actions and Filters](https://www.google.com/url?q=http://code.tutsplus.com/tutorials/writing-extensible-plugins-with-actions-and-filters--wp-26759&sa=D&source=editors&ust=1692724061407755&usg=AOvVaw1RO30KUvw73kAb73j2Mjxs).

## [Use of External Libraries](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-14&sa=D&source=editors&ust=1692724061408050&usg=AOvVaw064nKRX-btaU6-rP2nDvPR)

The use of entire external libraries is typically not suggested as this can open up the product to security vulnerabilities. If an external library is absolutely necessary, developers should be thoughtful about the code used and assume ownership as well as of responsibility for it. Try to  only include the strictly necessary part of the library, or use a WordPress-friendly version or opt to build your own version. For example, if needing to use a text editor such as TinyMCE, we recommend using the WordPress-friendly version, TinyMCE Advanced.

## [Remove Unused Code](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-15&sa=D&source=editors&ust=1692724061408520&usg=AOvVaw1xpjcmMrZLm46Jgpa_VJdb)

With version control, there’s no reason to leave commented-out code; it’s annoying to scroll through and read. Remove it and add it back later if needed.

## [Comment](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-16&sa=D&source=editors&ust=1692724061408902&usg=AOvVaw1ZMYYAAWMyPDO1S4YMKRLL)

If you have a function, what does the function do? There should be comments for most if not all functions in your code. Someone/You may want to modify the plugin, and comments are helpful for that. We recommend using [PHP Doc Blocks](https://www.google.com/url?q=http://en.wikipedia.org/wiki/PHPDoc&sa=D&source=editors&ust=1692724061409214&usg=AOvVaw0pK1khHhpHhP1aU6Wfgg7l)  similar to [WooCommerce](https://www.google.com/url?q=https://github.com/woocommerce/woocommerce/&sa=D&source=editors&ust=1692724061409366&usg=AOvVaw3UOb2ML3qmjH-MUwEYxwZN).

## [Avoid God Objects](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-17&sa=D&source=editors&ust=1692724061409596&usg=AOvVaw1jhRY0Ozls9pwiMi12lNkG)

[God Objects](https://www.google.com/url?q=http://en.wikipedia.org/wiki/God_object&sa=D&source=editors&ust=1692724061409851&usg=AOvVaw0XP2zyCLmDVwGMwNj0XxF8) are objects that know or do too much. The point of object-oriented programming is to take a large problem and break it into smaller parts. When functions do too much, it’s hard to follow their logic, making bugs harder to fix. Instead of having massive functions, break them down into smaller pieces.

## [Test Extension Quality & Security with Quality Insights Tool](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-18&sa=D&source=editors&ust=1692724061410124&usg=AOvVaw3WxIdaWb-loeDgk5idgYh7)

Integrate the [Quality Insights Toolkit (QIT)](https://www.google.com/url?q=https://href.li/?https://woocommerce.github.io/qit-documentation/%23/&sa=D&source=editors&ust=1692724061410435&usg=AOvVaw3-rM1B3-aofWDpJB5y-9Qs) into your development workflow to ensure your extension adheres to WordPress / WooCommerce quality and security standards. The QIT allows the ability to test your extensions against new releases of PHP, WooCommerce, and WordPress, as well as other active extensions, at the same time. The following tests are available today:

- [End-to-End](https://www.google.com/url?q=https://href.li/?https://woocommerce.github.io/qit-documentation/%23/test-types/e2e&sa=D&source=editors&ust=1692724061410720&usg=AOvVaw2-K7A2Jp9eEE3I7yg5GtLw)
- [Activation](https://www.google.com/url?q=https://href.li/?https://woocommerce.github.io/qit-documentation/%23/test-types/activation&sa=D&source=editors&ust=1692724061410980&usg=AOvVaw3EGJl6KSaQL1ygcvoDFFvR)
- [Security](https://www.google.com/url?q=https://href.li/?https://woocommerce.github.io/qit-documentation/%23/test-types/security&sa=D&source=editors&ust=1692724061411228&usg=AOvVaw3t5gYK8Md1UQWZORTmKSSx)
- [PHPStan](https://www.google.com/url?q=https://href.li/?https://woocommerce.github.io/qit-documentation/%23/test-types/phpstan&sa=D&source=editors&ust=1692724061411473&usg=AOvVaw0cYoAE7ScAXqMU7aw5gAOB)
- [API](https://www.google.com/url?q=https://href.li/?https://woocommerce.github.io/qit-documentation/%23/test-types/api&sa=D&source=editors&ust=1692724061411713&usg=AOvVaw0dXv3dyfNaAwe6wiwqApHn)

## [Test Your Code with WP_DEBUG](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-19&sa=D&source=editors&ust=1692724061411947&usg=AOvVaw3UsBUZeYvFu9v4itS839zy)

Always develop with [WP_DEBUG](https://www.google.com/url?q=http://codex.wordpress.org/Debugging_in_WordPress&sa=D&source=editors&ust=1692724061412254&usg=AOvVaw1412x2vFGfPDqCXxohD-JF) mode on, so you can see all PHP warnings sent to the screen. This will flag things like making sure a variable is set before checking the value.

## [Separate Business Logic & Presentation Logic](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-20&sa=D&source=editors&ust=1692724061412504&usg=AOvVaw1-plFhPYmTkkU99E9IO3VN)

It’s a good practice to separate business logic (i.e., how the plugin works) from [presentation logic](https://www.google.com/url?q=http://en.wikipedia.org/wiki/Presentation_logic&sa=D&source=editors&ust=1692724061412782&usg=AOvVaw3graTlf6ciHm0E3N25NOMQ) (i.e., how it looks). Two separate pieces of logic are more easily maintained and swapped if necessary. An example is to have two different classes — one for displaying the end results, and one for the admin settings page.

## [Use Transients to Store Offsite Information](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-21&sa=D&source=editors&ust=1692724061413039&usg=AOvVaw0_S-ooFW3W6n-k4yV3Gbmo)

If you provide a service via an API, it’s best to store that information so future queries can be done faster and the load on your service is lessened. [WordPress transients](https://www.google.com/url?q=http://codex.wordpress.org/Transients_API&sa=D&source=editors&ust=1692724061413333&usg=AOvVaw2SqfyKOl4wa52wmN_B0iJw) can be used to store data for a certain amount of time.

## [Logging Data](https://www.google.com/url?q=https://woocommerce.com/document/create-a-plugin/%23section-22&sa=D&source=editors&ust=1692724061413569&usg=AOvVaw1Rz8wUNYXdGr4LnOCiOpQM)

You may want to log data that can be useful for debugging purposes. This is great with two conditions:

- Allow any logging as an ‘opt in’.
- Use the [WC_Logger](https://www.google.com/url?q=https://woocommerce.com/wc-apidocs/class-WC_Logger.html&sa=D&source=editors&ust=1692724061414103&usg=AOvVaw1Xl7lewASbQMGaV8Frgq-U) class. A user can then view logs on their system status page.

If adding logging to your extension, here’s a snippet for presenting a link to the logs, in a way the extension user can easily make use of.

```
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