# WooCommerce Navigation

The WooCommerce Navigation feature is a navigational project designed to create a more intuitive and functional WooCommerce specific navigation.

This API will allow you to add in your own items to the navigation and register pages with the new navigation screens.

### Getting started

This feature is hidden behind a feature flag and can be turned on or off by visiting WooCommerce -> Settngs -> Advanced -> Features and checking the box next to the `Navigation` option.  It can also by controlled programmatically by setting the option `woocommerce_navigation_enable` to `yes` or `no`. 

The fastest way to get started is by creating an example plugin from WooCommerce Admin.  Inside your `woocommerce-admin` directory, enter the following command:

`npm run example -- --ext=add-navigation-items`

This will create a new plugin that covers various features of the navigation and helps to register some intial items and categories within the new navigation menu.  After running the command above, you can make edits directly to the files at `docs/examples/extensions/add-navigation-items` and they will be built and copied to your `wp-content/add-navigation-items` folder on save.

If you need to enable the WP Toolbar for debugging purposes in the new navigation, you can add the following filter to do so:

`add_filter( 'woocommerce_navigation_wp_toolbar_disabled', '__return_false' );`

### Adding a menu category

Categories in the new navigation are menu items that house child menu items.

Clicking on a category will not navigate to a new page, but instead open the child menu.  Note that categories without menu items will not be shown in the menu.

* `id` - (string) The unique ID of the menu item. Required.
* `title` - (string) Title of the menu item. Required.
* `parent` - (string) Parent menu item ID.
* `capability` - (string) Capability to view this menu item.

```php
\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_category(
    array(
        'id'     => 'example-category',
        'title'  => 'Example Category',
    )
);
```

Categories can also contain more categories by specifying the `parent` property for the child category.  There is no limit on the level of nesting.

```php
\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_category(
    array(
        'id'     => 'example-nested-category',
        'parent' => 'example-category',
        'title'  => 'Example Nested Category',
    )
);
```

### Adding a menu item

Adding an item, much like a category, can be added directly to the menu or to an existing category.  Typically this will create a link to the specified URL or callback unless overridden by JavaScript using the slot/fill approach described below.

* `id` - (string) The unique ID of the menu item. Required.
* `title` - (string) Title of the menu item. Required.
* `parent` - (string) Parent menu item ID.
* `capability` - (string) Capability to view this menu item.
* `url` - (string) URL or callback to be used. Required.
* `migrate` - (bool) Whether or not to hide the item in the wp admin menu.
* `matchExpression` - (string) An optional regex string to compare against the current location and mark the item active.

```php
\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
    array(
        'id'         => 'example-plugin',
        'title'      => 'Example Plugin',
        'capability' => 'view_woocommerce_reports',
        'url'        => 'https://www.google.com',
    )
);
```

### Registering plugin screens

In order to show the new navigation in place of the traditional WordPress menu on a given page, the screen ID must be registered to identify a page as supporting the new WooCommerce navigation.

When adding items, the navigation will automatically add support for the screen via the URL or callback provided with an item.  However, custom post types and taxonomies need to be registered with the navigation to work on the custom post type page.


```php
\Automattic\WooCommerce\Admin\Features\Navigation\Screen::register_post_type( 'my-custom-post-type' );
\Automattic\WooCommerce\Admin\Features\Navigation\Screen::register_taxonomy( 'my-custom-taxonomy' );
```

You can also manually add a screen without registering an item.

```php
\Automattic\WooCommerce\Admin\Features\Navigation\Screen::add_screen( 'my-plugin-page' );
```

### Slot/fill items

Using slot fill we can update items on the front-end of the site using JavaScript.  This is useful for modern JavaScript routing, more intricate interactions with menu items, or updating URLs and hyperlink text without reloading the page.

In order to use slot fill, you can import the `WooNavigationItem` component from `@woocommerce/navigation` and match the `item` prop with the ID of the item you'd like to modify the behavior of.


```js
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { registerPlugin } from "@wordpress/plugins";
import { useHistory } from "react-router-dom";
import { WooNavigationItem } from "@woocommerce/navigation";

const MyPlugin = () => {
    const history = useHistory();

    const handleClick = () => {
        history.push( '/my-plugin-path' );
    }

    return (
        <WooNavigationItem item="example-plugin">
            <Button onClick={ handleClick }>
                { __( 'My Link', 'plugin-domain' ) }
            </Button>
        </WooNavigationItem>
    );
};

registerPlugin('my-plugin', { render: MyPlugin });
```
