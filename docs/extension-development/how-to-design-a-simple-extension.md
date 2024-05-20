---
post_title: How to design a simple extension
menu_title: Design a simple extension
tags: how-to
---

## Introduction

Building a WooCommerce extension that provides a first-class experience for merchants and shoppers requires a hybrid development approach combining PHP and modern JavaScript. The PHP handles the lifecycle and server-side operations of your extension, while the modern JavaScript lets you shape the appearance and behavior of its user interface.

## The main plugin file

Your extension's main PHP file is a bootstrapping file. It contains important metadata about your extension that WordPress and WooCommerce use for a number of ecosystem integration processes, and it serves as the primary entry point for your extension's functionality. While there is not a particular rule enforced around naming this file, using a hyphenated version of the plugin name is a common best practice. (i.e. my-extension.php)

## Declaring extension metadata

Your extension's main plugin file should have a header comment that includes a number of important pieces of metadata about your extension. WordPress has a list of header requirements to which all plugins must adhere, but there are additional considerations for WooCommerce extensions:

- The `Author` and `Developer` fields are required and should be set to  
  either your name or your company name.

- The `Developer URI` field should be your official webpage URL.

- The `Plugin URI` field should contain the URL of the extension's product page in the WooCommerce Marketplace or the extension's official landing page on your website.

- For extensions listed in the WooCommerce Marketplace, to help facilitate the update process, add a `Woo` field and an appropriate value. WooCommerce Marketplace vendors can find this snippet by logging in to the Vendors Dashboard and navigating to `Extensions > All Extensions`. Then, select the product and click Edit product page. This snippet will be in the upper-right-hand corner of the screen.

Below is an example of what the header content might look like for an extension listed in the WooCommerce Marketplace.

```php
/**
 * Plugin Name: My Great WooCommerce Extension
 * Plugin URI: https://woocommerce.com/products/woocommerce-extension/
 * Description: Your extension's description text.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: http://yourdomain.com/
 * Developer: Your Name
 * Developer URI: http://yourdomain.com/
 * Text Domain: my-extension
 * Domain Path: /languages
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
```

## Preventing data leaks

As a best practice, your extension's PHP files should contain a conditional statement at the top that checks for WordPress' ABSPATH constant. If this constant is not defined, the script should exit.

`defined( 'ABSPATH' ) || exit;`

This check prevents your PHP files from being executed via direct browser access and instead only allows them to be executed from within the WordPress application environment.

## Managing extension lifecycle

Because your main PHP file is the primary point of coupling between your extension and WordPress, you should use it as a hub for managing your extension's lifecycle. At a very basic level, this means handling:

- Activation
- Execution
- Deactivation

Starting with these three broad lifecycle areas, you can begin to break your extension's functionality down further to help maintain a good separation of concerns.

## Handling activation and deactivation

A common pattern in WooCommerce extensions is to create dedicated functions in your main PHP file to serve as activation and deactivation hooks. You then register these hooks with WordPress using the applicable registration function. This tells WordPress to call the function when the plugin is activated or deactivated. Consider the following examples:

```php
function my_extension_activate() {
    // Your activation logic goes here.
}
register_activation_hook( __FILE__, 'my_extension_activate' );
```

```php
function my_extension_deactivate() {
    // Your deactivation logic goes here.
}
register_deactivation_hook( __FILE__, 'my_extension_deactivate' );
```

## Maintaining a separation of concerns

There are numerous ways to organize the code in your extension. You can find a good overview of best practices in the WordPress Plugin Developer Handbook. Regardless of the approach you use for organizing your code, the nature of WordPress' shared application space makes it imperative that you build with an eye toward interoperability. There are a few common principles that will help you optimize your extension and ensure it is a good neighbor to others:

- Use namespacing and prefixing to avoid conflicts with other extensions.
- Use classes to encapsulate your extension's functionality.
- Check for existing declarations, assignments, and implementations.

## The core extension class

As mentioned above, encapsulating different parts of your extension's functionality using classes is an important measure that not only helps with interoperability, but which also makes your code easier to maintain and debug. Your extension may have many different classes, each shouldering some piece of functionality. At a minimum, your extension should define a central class which can handle the setup, initialization and management of a single instance of itself.

## Implementing a singleton pattern

Unless you have a specific reason to create multiple instances of your main class when your extension runs, you should ensure that only one instance exists in the global scope at any time. A common way of doing this is to use a Singleton pattern. There are several ways to go about setting up a singleton in a PHP class. Below is a basic example of a singleton that also implements some of the best practices mentioned above about namespacing and pre-declaration checks:

```php
if ( ! class_exists( 'My_Extension' ) ) :
    /**
     * My Extension core class
     */
    class My_Extension {
        /**
         * The single instance of the class.
         */
        protected static $_instance = null;

        /**
         * Constructor.
         */
        protected function __construct() {
            // Instantiation logic will go here.
        }

        /**
         * Main Extension Instance.
         * Ensures only one instance of the extension is loaded or can be loaded.
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * Cloning is forbidden.
         */
        public function __clone() {
            // Override this PHP function to prevent unwanted copies of your instance.
            //   Implement your own error or use `wc_doing_it_wrong()`
        }

        /**
         * Unserializing instances of this class is forbidden.
         */
        public function __wakeup() {
            // Override this PHP function to prevent unwanted copies of your instance.
            //   Implement your own error or use `wc_doing_it_wrong()`
        }
    }
endif;
```

Notice that the example class above is designed to be instantiated by calling the static class method `instance()`, which will either return an existing instance of the class or create one and return it. In order to fully protect against unwanted instantiation, it's also necessary to override the built-in magic methods `__clone()` and `__wakeup()`. You can implement your own error logging here or use something like `_doing_it_wrong()` which handles error logging for you. You can also use WooCommerce's wrapper function `wc_doing_it_wrong()` here. Just be sure your code checks that the function exists first.

## Constructor

The example above includes an empty constructor for demonstration. In a real-world WooCommerce extension, however, this constructor should handle a few important tasks:

- Check for an active installation of WooCommerce & other sibling dependencies.

- Call a setup method that loads other files that your class depends on.
- Call an initialization method that gets your class and its dependencies ready to go.

If we build upon our example above, it might look something like this:

```php
protected function __construct() {
    $this->includes();
    $this->init();
    // You might also include post-setup steps such as showing activation notices here.
}
```

## Loading dependencies

The includes() function above is where you'll load other class dependencies, typically via an include or require constructs. A common way of managing and loading external dependencies is to use Composer's autoload feature, but you can also load specific files individually. You can read more about how to autoload external dependencies in the Composer documentation. A basic example of a setup method that uses both Composer and internal inclusion is below.

```php
public function includes() {
    $loader = include_once dirname( __FILE__ ) . '/' . 'vendor/autoload.php';

    if ( ! $loader ) {
        throw new Exception( 'vendor/autoload.php missing please run `composer install`' );
    }

    require_once dirname( __FILE__ ) . '/' . 'includes/my-extension-functions.php';
}
```

## Initialization

The `init()` function above is where you should handle any setup for the classes you loaded in the includes() method. This step is where you'll often perform any initial registration with relevant actions or filters. It's also where you can register and enqueue your extension's JavaScripts and stylesheets.

Here's an example of what your initialization method might look like:

```php
private function init() {
    // Set up cache management.
    new My_Extension_Cache();

    // Initialize REST API.
    new My_Extension_REST_API();

    // Set up email management.
    new My_Extension_Email_Manager();

    // Register with some-action hook
    add_action( 'some-action', 'my-extension-function' );
}
```

There are many different ways that your core class' initialization method might look, depending on the way that you choose to architect your extension. The important concept here is that this function serves as a central point for handling any initial registration and setup that your extension requires in order to respond to web requests going forward.

## Delaying initialization

The WordPress activation hook we set up above with register_activation_hook() may seem like a great place to instantiate our extension's main class, and in some cases it will work. By virtue of being a plugin for a plugin, however, WooCommerce extensions typically require WooCommerce to be loaded in order to function properly, so it's often best to delay instantiation and initialization until after WordPress has loaded other plugins.

To do that, instead of hooking your instantiation to your extension's activation hook, use the plugins_loaded action in WordPress to instantiate your extension's core class and add its singleton to the $GLOBALS array.

```php
function my_extension_initialize() {
    // This is also a great place to check for the existence of the WooCommerce class
    if ( ! class_exists( 'WooCommerce' ) ) {
    // You can handle this situation in a variety of ways,
    //   but adding a WordPress admin notice is often a good tactic.
        return;
    }

    $GLOBALS['my_extension'] = My_Extension::instance();
}
add_action( 'plugins_loaded', 'my_extension_initialize', 10 );
```

In the example above, WordPress will wait until after all plugins have been loaded before trying to instantiate your core class. The third argument in add_action() represents the priority of the function, which ultimately determines the order of execution for functions that hook into the plugins_loaded action. Using a value of 10 here ensures that other WooCommerce-related functionality will run before our extension is instantiated.

## Handling execution

Once your extension is active and initialized, the possibilities are wide open. This is where the proverbial magic happens in an extension, and it's largely up to you to define. While implementing specific functionality is outside the scope of this guide, there are some best practices to keep in mind as you think about how to build out your extension's functionality.

- Keep an event-driven mindset. Merchants and shoppers who use your extension will be interacting with WooCommerce using web requests, so it can be helpful to anchor your extension to some of the critical flows that users follow in WooCommerce.

- Keep business logic and presentation logic separate. This could be as simple as maintaining separate classes for handling back-end processing and front-end rendering.

- Where possible, break functionality into smaller parts and delegate responsibility to dedicated classes instead of building bloated classes and lengthy functions.

You can find detailed documentation of classes and hooks in the WooCommerce Core Code Reference and additional documentation of the REST API endpoints in the WooCommerce REST API Documentation.

## Handling deactivation

The WordPress deactivation hook we set up earlier in our main PHP file with register_deactivation_hook() is a great place to aggregate functionality for any cleanup that you need to handle when a merchant deactivates your extension. In addition to any WordPress-related deactivation tasks your extension needs to do, you should also account for WooCommerce-related cleanup, including:

- Removing Scheduled Actions
- Removing Notes in the Admin Inbox
- Removing Admin Tasks

## Uninstallation

While it's certainly possible to completely reverse everything your extension has created when a merchant deactivates it, it's not advisable nor practical in most cases. Instead, it's best to reserve that behavior for uninstallation.

For handling uninstallation, it's best to follow the guidelines in the WordPress Plugin Handbook.

## Putting it all together

Below is an example of what a main plugin file might look like for a very simple extension:

```php
/**
 * Plugin Name: My Great WooCommerce Extension
 * Plugin URI: https://woocommerce.com/products/woocommerce-extension/
 * Description: Your extension's description text.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: http://yourdomain.com/
 * Developer: Your Name
 * Developer URI: http://yourdomain.com/
 * Text Domain: my-extension
 * Domain Path: /languages
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activation and deactivation hooks for WordPress
 */
function myPrefix_extension_activate() {
    // Your activation logic goes here.
}
register_activation_hook( __FILE__, 'myPrefix_extension_activate' );

function myPrefix_extension_deactivate() {
    // Your deactivation logic goes here.

    // Don't forget to:
    // Remove Scheduled Actions
    // Remove Notes in the Admin Inbox
    // Remove Admin Tasks
}
register_deactivation_hook( __FILE__, 'myPrefix_extension_deactivate' );


if ( ! class_exists( 'My_Extension' ) ) :
    /**
     * My Extension core class
     */
    class My_Extension {

        /**
         * The single instance of the class.
         */
        protected static $_instance = null;

        /**
         * Constructor.
         */
        protected function __construct() {
            $this->includes();
            $this->init();
        }

        /**
         * Main Extension Instance.
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Cloning is forbidden.
         */
        public function __clone() {
            // Override this PHP function to prevent unwanted copies of your instance.
            //   Implement your own error or use `wc_doing_it_wrong()`
        }

        /**
         * Unserializing instances of this class is forbidden.
         */
        public function __wakeup() {
            // Override this PHP function to prevent unwanted copies of your instance.
            //   Implement your own error or use `wc_doing_it_wrong()`
        }

        /**
        * Function for loading dependencies.
        */
        private function includes() {
            $loader = include_once dirname( __FILE__ ) . '/' . 'vendor/autoload.php';

            if ( ! $loader ) {
                throw new Exception( 'vendor/autoload.php missing please run `composer install`' );
            }

            require_once dirname( __FILE__ ) . '/' . 'includes/my-extension-functions.php';
        }

        /**
         * Function for getting everything set up and ready to run.
         */
        private function init() {

            // Examples include:

            // Set up cache management.
            // new My_Extension_Cache();

            // Initialize REST API.
            // new My_Extension_REST_API();

            // Set up email management.
            // new My_Extension_Email_Manager();

            // Register with some-action hook
            // add_action('some-action', 'my-extension-function');
        }
    }
endif;

/**
 * Function for delaying initialization of the extension until after WooComerce is loaded.
 */
function my_extension_initialize() {

    // This is also a great place to check for the existence of the WooCommerce class
    if ( ! class_exists( 'WooCommerce' ) ) {
    // You can handle this situation in a variety of ways,
    //   but adding a WordPress admin notice is often a good tactic.
        return;
    }

    $GLOBALS['my_extension'] = My_Extension::instance();
}
```
