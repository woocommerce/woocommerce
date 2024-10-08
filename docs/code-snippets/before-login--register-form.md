---
post_title: Add a message above the login / register form
tags: code-snippet
---

This code will add a custom message above the login/register form on the user's my-account page.

Add this code to your child theme's `functions.php` file or via a plugin that allows custom functions to be added, such as the [Code snippets](https://wordpress.org/plugins/code-snippets/) plugin. Avoid adding custom code directly to your parent theme's `functions.php` file, as this will be wiped entirely when you update the theme.

```php
if ( ! function_exists( 'YOUR_PREFIX_login_message' ) ) {
    /**
     * Add a message above the login / register form on my-account page
     */
    function YOUR_PREFIX_login_message() {
        if ( get_option( 'woocommerce_enable_myaccount_registration' ) == 'yes' ) {
            ?&gt;
            &lt;div class="woocommerce-info"&gt;
            &lt;p&gt;&lt;?php _e( 'Returning customers login. New users register for next time so you can:', 'YOUR-TEXTDOMAIN' ); ?&gt;&lt;/p&gt;
            &lt;ul&gt;
                &lt;li&gt;&lt;?php _e( 'View your order history', 'YOUR-TEXTDOMAIN' ); ?&gt;&lt;/li&gt;
                &lt;li&gt;&lt;?php _e( 'Check on your orders', 'YOUR-TEXTDOMAIN' ); ?&gt;&lt;/li&gt;
                &lt;li&gt;&lt;?php _e( 'Edit your addresses', 'YOUR-TEXTDOMAIN' ); ?&gt;&lt;/li&gt;
                &lt;li&gt;&lt;?php _e( 'Change your password', 'YOUR-TEXTDOMAIN' ); ?&gt;&lt;/li&gt;
            &lt;/ul&gt;
            &lt;/div&gt;
            &lt;?php
        }
    }
    add_action( 'woocommerce_before_customer_login_form', 'YOUR_PREFIX_login_message' );
}
```

Please note that for this code to work, the following options must be checked in the WooCommerce "Accounts & Privacy" settings:

-   Allow customers to create an account during checkout.
-   Allow customers to create an account on the "My Account" page.
