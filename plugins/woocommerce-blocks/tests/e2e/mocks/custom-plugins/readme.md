# Testing WordPress actions and filters

This documentation covers testing WordPress actions and filters when writing Playwright tests.

## Table of Contents

- [Description](#description)
- [Usage](#usage)

## Description

You can test an action or filter (change) by creating a custom WordPress plugin, installing it and when you are done, removing it.

The 3 functions responsible are located inside of `tests/e2e-pw/mocks/custom-plugins/utils.ts`:

- `createPluginFromPHPFile()`
- `installPluginFromPHPFile()`
- `uninstallPluginFromPHPFile()`

## Usage

### Example: Testing a custom Add to Cart text

1. Create the custom plugin file.

`update-product-button-text.php`.

```php
<?php
/**
 * Plugin Name: Custom Add to Cart Text
 * Description: Modifies the "Add to Cart" button text for WooCommerce products.
 */

function woocommerce_add_to_cart_button_text_archives() {
    return 'Buy Now';
}

add_filter( 'woocommerce_product_add_to_cart_text', 'woocommerce_add_to_cart_button_text_archives' );
```

2. Install the plugin when running the test.

```javascript
test( 'the filter `woocommerce_product_add_to_cart_text` is applied', async ( { frontendUtils } ) => {
    await installPluginFromPHPFile(
        `${ __dirname }/update-product-button-text.php`
    );
    await frontendUtils.goToShop();
    const blocks = await frontendUtils.getBlockByName( blockData.name );
    const buttonWithNewText = await blocks.getByText( 'Buy Now' ).count();

    const productsDisplayed = 16;
    expect( buttonWithNewText ).toEqual( productsDisplayed );
} );
```

3. Remove the plugin when done testing.

```javascript
test.afterAll( async () => {
    await uninstallPluginFromPHPFile(
        `${ __dirname }/update-product-button-text.php`
    );
} );
```

In the above example, the test checks whether the filter `woocommerce_product_add_to_cart_text` is applied correctly. It installs the "Custom Add to Cart Text" plugin, navigates to the shop page using `frontendUtils`, and verifies if the "Buy Now" button text appears as expected. Finally, it cleans the cart and uninstalls the plugin.

You can adapt this example to test other filters and actions by modifying the code accordingly.
