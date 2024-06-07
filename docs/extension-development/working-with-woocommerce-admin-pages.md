---
post_title: Integrating admin pages into WooCommerce extensions
menu_title: Integrating admin pages
tags: how-to
---

## Introduction

There are a number of ways to manage admin-area pages for your WooCommerce extension. You can use existing PHP pages or create new React-powered pages. Regardless of the approach you choose, you'll need to register your page with the [`PageController`](https://woocommerce.github.io/code-reference/classes/Automattic-WooCommerce-Admin-PageController.html) in order to display the WooCommerce Admin header and activity panel on your page.

## Connecting a PHP-powered page to WooCommerce Admin

To register an existing PHP-powered admin page with the [`PageController`](https://woocommerce.github.io/code-reference/classes/Automattic-WooCommerce-Admin-PageController.html), use the [`wc_admin_connect_page()`](https://woocommerce.github.io/code-reference/namespaces/default.html#function_wc_admin_connect_page) function. For example:

```php
wc_admin_connect_page(
    array(
        'id'        => 'woocommerce-settings',
        'screen_id' => 'woocommerce_page_wc-settings-general',
        'title'     => array( 'Settings', 'General' ),
        'path'      => add_query_arg( 'page', 'wc-settings', 'admin.php' ),
    )
);
```

The [`wc_admin_connect_page()`](https://woocommerce.github.io/code-reference/namespaces/default.html#function_wc_admin_connect_page) function accepts an array of arguments, two of which are optional:

* `id` (**required**) - This identifies the page with the controller.
* `parent` (_optional_) - This value denotes the page as a child of a parent (using the parent's ID) and is used for generating breadcrumbs.
* `screen_id` (**required**) - This corresponds to [`PageController::get_current_screen_id()`](https://woocommerce.github.io/code-reference/classes/Automattic-WooCommerce-Admin-PageController.html#method_get_current_screen_id). It is used to determine the current page. (see note below)
* `title` (**required**) - This corresponds to the page's title and is used to build breadcrumbs. You can supply a string or an array of breadcrumb pieces here.
* `path` (_optional_) - This is the page's relative path. Used for linking breadcrumb pieces when this page is a parent.

In the example above, you can see how to use an array to construct breadcrumbs for your extension. WooCommerce will attach a link leading to the `path` value to the first piece in the title array. All subsequent pieces are rendered as text and not linked.

### A note about determining the screen ID

WooCommerce Admin uses its own version of [`get_current_screen()`](https://developer.wordpress.org/reference/functions/get_current_screen/) to allow for more precise identification of admin pages, which may have various tabs and subsections.

The format of this ID may vary depending on the structural elements present on the page. Some formats that the function will generate are:

* `{$current_screen->action}-{$current_screen->action}-tab-section`
* `{$current_screen->action}-{$current_screen->action}-tab`
* `{$current_screen->action}-{$current_screen->action}` if no tab is present
* `{$current_screen->action}` if no action or tab is present

If your extension adds new pages with tabs or subsections, be sure to use the `wc_admin_pages_with_tabs` and `wc_admin_page_tab_sections` filters to have WooCommerce generate accurate screen IDs for them.

You can also use the `wc_admin_current_screen_id` filter to make any changes necessary to the screen ID generation behavior.

## Registering a Rect-powered page

To register a React-powered page, use the [`wc_admin_register_page()`](https://woocommerce.github.io/code-reference/namespaces/default.html#function_wc_admin_register_page) function. It accepts an array of arguments:

* `id` (**required**) - This identifies the page with the controller.
* `parent` (_optional_) - This denotes the page as a child of `parent` (using the parent's ID) and is used for generating breadcrumbs.
* `title` (**required**) - This corresponds to the page's title and is used to build breadcrumbs. You can supply a String or an Array of breadcrumb pieces here.
* `path` (**required**) - This is the page's path (relative to `#wc-admin`). It is used for identifying this page and for linking breadcrumb pieces when this page is a parent.
* `capability` (_optional_) - User capability needed to access this page. The default value is `manage_options`.
* `icon` (_optional_) - Use this to apply a Dashicons helper class or base64-encoded SVG. Include the entire dashicon class name, ie `dashicons-*`. Note that this won't be included in WooCommerce Admin Navigation.
* `position` (_optional_) - Menu item position for parent pages. See: [`add_menu_page()`](https://developer.wordpress.org/reference/functions/add_menu_page/).
* `nav_args` (_optional_) - An array of parameters for registering items in WooCommerce Navigation. (see usage below)
    * `order` - Order number for presentation.
    * `parent` - Menu for item to fall under. For example: `woocommerce`, `woocommerce-settings` or `woocommerce-analytics`. Categories added by an extension are available as well.

Registering a React-powered page is similar to connecting a PHP page, but with some key differences. Registering pages will automatically create WordPress menu items for them, with the appropriate hierarchy based on the value of `parent`.

### Example: Adding a new WooCommerce Admin page

```php
if ( ! function_exists( 'YOUR_PREFIX_add_extension_register_page' ) ) {
  function YOUR_PREFIX_add_extension_register_page() {
    if ( ! function_exists( 'wc_admin_register_page' ) ) {
        return;
    }
 
    wc_admin_register_page( array(
        'id'       => 'my-example-page',
        'title'    => __( 'My Example Page', 'YOUR-TEXTDOMAIN' ),
        'parent'   => 'woocommerce',
        'path'     => '/example',
        'nav_args' => array(
            'order'  => 10,
            'parent' => 'woocommerce',
        ),
    ) );
  }
}
add_action( 'admin_menu', 'YOUR_PREFIX_add_extension_register_page' );
```

In the example above, we encapsulated our call to [`wc_admin_register_page()`](https://woocommerce.github.io/code-reference/namespaces/default.html#function_wc_admin_register_page) in a function that we have hooked to the [`admin_menu`](https://developer.wordpress.org/reference/hooks/admin_menu/) action. Once you have registered a page with the controller, you can supply a React component on the client side.

```js
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
 
const MyExamplePage = () => <h1>My Example Extension</h1>;
 
addFilter( 'woocommerce_admin_pages_list', 'my-namespace', ( pages ) => {
    pages.push( {
        container: MyExamplePage,
        path: '/example',
        breadcrumbs: [ __( 'My Example Page', 'YOUR-TEXTDOMAIN' ) ],
        navArgs: {
            id: 'my-example-page',
            parentPath: '/other-example',
        },
    } );
 
    return pages;
} );
```

Above, we're creating a simple [functional React component](https://reactjs.org/docs/components-and-props.html#function-and-class-components) for the sake of demonstration, but a real-world extension would likely have a more complex nesting of components.

When supplying a component to the list of WooCommerce Admin Pages, it's important to make sure that the value you specify for `navArgs.id` matches the `id` for the page you register with `PageController` in your call to [`wc_admin_register_page()`](https://woocommerce.github.io/code-reference/namespaces/default.html#function_wc_admin_register_page).

Pass the path to the parent page (the `path` value of the query arg for the parent page's url) as `navArgs.parentPath` to highlight that parent page's menu when this page is active.

## Further reading

You can learn more about how page registration works by checking out the [`PageController`](https://woocommerce.github.io/code-reference/classes/Automattic-WooCommerce-Admin-PageController.html) class in the WooCommerce Core Code Reference.

You can see real-world examples of the two page registration methods in WooCommerce Core by taking a look at:

* [How WooCommerce Admin registers existing core pages](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/react-admin/connect-existing-pages.php) - registering PHP-powered pages
* [How WooCommerce registers React-powered Analytics report pages](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/Admin/Analytics.php) - registering React-powered pages
