---
post_title: Managing custom attributes in WooCommerce menus and taxonomy archives
menu_title: Custom attributes in menus
tags: how-to
---

Attributes that can be used for the layered nav are a custom taxonomy, which means you can display them in menus, or display products by attributes. This requires some work on your part, and archives must be enabled.

## Register the taxonomy for menus

When registering taxonomies for your custom attributes, WooCommerce calls the following hook:

```php
$show_in_nav_menus = apply_filters('woocommerce_attribute_show_in_nav_menus', false, $name);
```

So, for example, if your attribute slug was `size` you would do the following to register it for menus:

```php
add_filter('woocommerce_attribute_show_in_nav_menus', 'wc_reg_for_menus', 1, 2);

function wc_reg_for_menus( $register, $name = '' ) {
if ( $name == 'pa_size' ) $register = true;
return $register;
}
```

Custom attribute slugs are prefixed with `pa_`, so an attribute called `size` would be `pa_size`

Now use your attribute in  **Appearance > Menus**. You will notice, however, that it has default blog styling when you click on a link to your taxonomy term.

## Create a template

You need to theme your attribute to make it display products as you want. To do this:

1.  Copy `woocommerce/templates/taxonomy-product_cat.php` into your theme folder
2.  Rename the template to reflect your attribute - in our example we'd use `taxonomy-pa_size.php`

You should now see this template when viewing taxonomy terms for your custom attribute.
