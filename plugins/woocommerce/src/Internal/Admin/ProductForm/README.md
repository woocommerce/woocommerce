# Product Form

This folder contains helper classes to specifically extend the WooCommerce Admin product form.
This will primarily be done through the `Form` class, under the `Automattic\WooCommerce\Internal\Admin\ProductForm` namespace.

## Ex - Adding a new field:

```php
function add_product_form_field() {
    if (
        ! method_exists( '\Automattic\WooCommerce\Internal\Admin\ProductForm\Form', 'add_field' )
    ) {
        return;
    }

    \Automattic\WooCommerce\Internal\Admin\ProductForm\FormFactory::add_field(
        'test_new_field',
        'woocommerce-plugin-name',
        array(
            'type'       => 'text',
            'location'   => 'plugin-details',
        )
    );
}
add_filter( 'admin_init', 'add_product_form_field' );
```
