# Add a message above the login / register form

> This is a **Developer level** doc. If you are unfamiliar with code and resolving potential conflicts, select a [WooExpert or Developer](https://woocommerce.com/customizations/) for assistance. We are unable to provide support for customizations under our [Support Policy](http://www.woocommerce.com/support-policy/).

This code will add a custom message above the login/register form on the user’s my-account page.

```php
if ( ! function_exists( 'YOUR_PREFIX_login_message' ) ) {
    /**
     * Add a message above the login / register form on my-account page
     */
    function YOUR_PREFIX_login_message() {
        if ( get_option( 'woocommerce_enable_myaccount_registration' ) == 'yes' ) {
            ?>
            <div class="woocommerce-info">
            <p><?php _e( 'Returning customers login. New users register for next time so you can:', 'YOUR-TEXTDOMAIN' ); ?></p>
            <ul>
                <li><?php _e( 'View your order history', 'YOUR-TEXTDOMAIN' ); ?></li>
                <li><?php _e( 'Check on your orders', 'YOUR-TEXTDOMAIN' ); ?></li>
                <li><?php _e( 'Edit your addresses', 'YOUR-TEXTDOMAIN' ); ?></li>
                <li><?php _e( 'Change your password', 'YOUR-TEXTDOMAIN' ); ?></li>
            </ul>
            </div>
            <?php
        }
    }
    add_action( 'woocommerce_before_customer_login_form', 'YOUR_PREFIX_login_message' );
}
```

Please note that for this code to work, the following options must be checked in the WooCommerce “Accounts & Privacy” settings:

- Allow customers to create an account during checkout.
- Allow customers to create an account on the "My Account" page.
