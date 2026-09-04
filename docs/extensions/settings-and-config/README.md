---
post_title: 'Settings APIs and admin pages'
sidebar_label: Overview
sidebar_position: 0
---

# Settings APIs and admin pages

Managing your settings, admin pages, and more is possible through the Settings APIs. The docs in this section cover what the Settings APIs and admin pages system offers in general. This guide goes further to map all of the various approaches you have available, some of the scenarios they cover, and the trade-offs of each approach.

There are also code examples for your reference before you commit to an implementation path. However, reading through the approach you're interested in will give you greater scope to understand and apply the principles of each.

## Approaches at a glance

The Settings API and admin pages section has six approaches, outlined in the table. The four core concepts (Settings API, settings pages, settings tab sections, and admin pages) cover most settings and admin placement decisions, while the remaining approaches cover specialized integrations and navigation shortcuts.

| Approach | Use when | Requires |
| :---- | :---- | :---- |
| [Settings API](/docs/extensions/settings-and-config/settings-api/) | Defining settings for a configurable component, such as a payment gateway or shipping method | PHP |
| [Settings pages](/docs/extensions/settings-and-config/extend-wc-settings-page/) | Your extension needs a full tab under **WooCommerce > Settings** with one or more sections | PHP |
| [Settings tab sections](/docs/extensions/settings-and-config/adding-a-section-to-a-settings-tab/) | Your settings belong under an existing WooCommerce tab rather than a new page | PHP |
| [Admin pages](/docs/extensions/settings-and-config/working-with-woocommerce-admin-pages/) | Your extension needs a standalone page outside WooCommerce's settings structure | PHP or JavaScript |
| [Integration settings](/docs/extensions/settings-and-config/implementing-settings/) | Your extension connects to a third-party service and needs a page under the Integrations tab | PHP |
| [Store management links](/docs/extensions/settings-and-config/how-to-add-your-own-store-management-links/) | Surfacing quick-access links on the WooCommerce home screen | JavaScript |

## Settings API

`WC_Settings_API` provides field definition, rendering, loading, and saving for configurable WooCommerce components. Plugin developers usually inherit it through `WC_Payment_Gateway`, `WC_Shipping_Method`, or `WC_Integration` rather than extending it directly.

Use the [Payment Gateway API](/docs/features/payments/payment-gateway-api/), [Shipping Method API](/docs/features/shipping/shipping-method-api/), or [integration settings](/docs/extensions/settings-and-config/implementing-settings/) guide for a complete implementation.

## Settings pages

Use `WC_Settings_Page` when your extension needs a full tab under **WooCommerce > Settings**. A settings page class registers the tab, renders one or more sections, defines fields with WooCommerce's settings array format, and lets WooCommerce handle saving through the existing settings form.

The main trade-off is navigation weight. If your extension only needs a few settings that clearly belong under an existing WooCommerce settings tab, add a section to that tab instead of creating a new top-level tab. If the extension needs an interface outside WooCommerce settings entirely, use admin page registration.

```php
final class My_Plugin_Settings_Page extends WC_Settings_Page {
	public function __construct() {
		$this->id    = 'my_plugin';
		$this->label = __( 'My plugin', 'my-plugin' );

		parent::__construct();
	}

	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => __( 'My plugin settings', 'my-plugin' ),
				'type'  => 'title',
				'id'    => 'my_plugin_options',
			),
			array(
				'title'   => __( 'Enable feature', 'my-plugin' ),
				'id'      => 'my_plugin_enabled',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'my_plugin_options',
			),
		);
	}
}

add_filter(
	'woocommerce_get_settings_pages',
	function( $settings_pages ) {
		$settings_pages[] = new My_Plugin_Settings_Page();
		return $settings_pages;
	}
);
```

## Settings tab sections

Rather than creating a whole new page, you can add a section beneath an existing WooCommerce settings tab using two filters:

- `woocommerce_get_sections_{tab}` registers the section.
- `woocommerce_get_settings_{tab}` supplies its fields.

The `{tab}` portion of each filter corresponds to the settings page ID for the tab you want to extend. For instance, `products` targets the **Products** tab, while `account` targets **Accounts & Privacy**.

Placing settings under an existing tab keeps the admin area organized and means merchants find your extension's options in an understandable context. The limit is that linking to your section from documentation or onboarding flows requires a URL with both a tab and a section parameter rather than a page URL you control.

```php
add_filter(
	'woocommerce_get_sections_products',
	function( $sections ) {
		$sections['my_extension'] = __( 'My Extension', 'my-extension' );
		return $sections;
	}
);

add_filter(
	'woocommerce_get_settings_products',
	function( $settings, $current_section ) {
		if ( 'my_extension' === $current_section ) {
			return array(
				array(
					'title' => __( 'My Extension', 'my-extension' ),
					'type'  => 'title',
					'id'    => 'my_extension',
				),
				array(
					'title' => __( 'Enable feature', 'my-extension' ),
					'type'  => 'checkbox',
					'id'    => 'my_extension_enabled',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'my_extension',
				),
			);
		}

		return $settings;
	},
	10,
	2
);
```

## Admin page registration

When your extension needs a page outside WooCommerce's settings structure, you register it with the `PageController`. This attaches the WooCommerce Admin header and activity panel to the page and WooCommerce provides two registration paths:

- `wc_admin_connect_page()` for PHP-powered pages.
- `wc_admin_register_page()` for React-powered pages.

PHP registration connects an existing admin page to the WooCommerce Admin shell. This will suit extensions that already render an interface with PHP. React registration creates a menu entry and renders a component you supply, which gives you a component-driven interface but requires a JavaScript build step.

Both paths are for extensions with an interface that's too distinct to fit within WooCommerce's settings tabs. For example, this could be a reporting dashboard, a product import tool, or any page with its own layout.

```php
// PHP-powered page.
wc_admin_connect_page(
	array(
		'id'        => 'my-extension-page',
		'screen_id' => 'my-extension-page',
		'title'     => array( 'My Extension', 'Settings' ),
		'path'      => add_query_arg( 'page', 'my-extension', 'admin.php' ),
	)
);
```

```jsx
// React-powered page.
import { addFilter } from '@wordpress/hooks';

const MyPage = () => <h1>My Extension</h1>;

addFilter( 'woocommerce_admin_pages_list', 'my-extension', ( pages ) => {
	pages.push( {
		container: MyPage,
		path: '/my-extension',
		breadcrumbs: [ 'My Extension' ],
	} );

	return pages;
} );
```

## Integration settings

`WC_Integration` extends `WC_Settings_API` and creates a settings page under **WooCommerce > Settings > Integrations**. It also handles data saving and sanitization for you. You should use it when your extension connects to an external service and a dedicated page under the **Integrations** tab is the right home for its settings.

The main trade-off is placement: if your settings belong under an existing WooCommerce tab or on a standalone page, this is not the path. `WC_Integration` is great for fields such as API keys or toggle switches because you get saving and sanitization without writing that logic yourself.

```php
class My_Integration extends WC_Integration {
	public function __construct() {
		$this->id                 = 'my-integration';
		$this->method_title       = __( 'My Integration', 'my-integration' );
		$this->method_description = __( 'Connect to My Service.', 'my-integration' );
		$this->init_form_fields();
		$this->init_settings();
		add_action(
			'woocommerce_update_options_integration_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'api_key' => array(
				'title' => __( 'API Key', 'my-integration' ),
				'type'  => 'text',
			),
		);
	}
}

add_filter(
	'woocommerce_integrations',
	function( $integrations ) {
		$integrations[] = 'My_Integration';
		return $integrations;
	}
);
```

## Store management links

The WooCommerce home screen includes a store management dashboard that displays quick-access links and statistics for merchants. You can add your own link there using the `woocommerce_admin_homescreen_quicklinks` JavaScript filter.

However, two constraints apply:

- Links must point to pages within WooCommerce as external URLs are not supported.
- All extension-added links appear under a fixed **Extensions** category and custom categories are not available.

You enqueue your script using `admin_enqueue_scripts`, with a dependency on `wp-hooks` and a priority higher than 15 to ensure it runs before the section renders.

Note that this approach doesn't *configure* settings as its purpose is discoverability for merchants who use the WooCommerce home screen as a starting point for day-to-day tasks.

```js
import { megaphone } from '@wordpress/icons';
import { addFilter } from '@wordpress/hooks';

addFilter( 'woocommerce_admin_homescreen_quicklinks', 'my-extension', ( quickLinks ) => {
	return [
		...quickLinks,
		{
			title: 'My Extension',
			href: 'link/to/my-extension',
			icon: megaphone,
		},
	];
} );
```

## Next steps

With the trade-offs and code examples for each approach as a reference, you can work through the full documentation for the one your extension needs.

1. [Settings API](/docs/extensions/settings-and-config/settings-api/). Core reference for the `WC_Settings_API` class.
2. [Adding a settings page](/docs/extensions/settings-and-config/extend-wc-settings-page/). Creating a full tab under **WooCommerce > Settings**.
3. [Adding a section to a settings tab](/docs/extensions/settings-and-config/adding-a-section-to-a-settings-tab/). Adding a section beneath an existing WooCommerce tab.
4. [Working with WooCommerce admin pages](/docs/extensions/settings-and-config/working-with-woocommerce-admin-pages/). Registering PHP and React admin pages.
5. [Implementing settings with `WC_Integration`](/docs/extensions/settings-and-config/implementing-settings/). How to use the `WC_Integration` class for third-party service connections.
6. [Adding store management links](/docs/extensions/settings-and-config/how-to-add-your-own-store-management-links/). Navigation shortcuts on the WooCommerce home screen.
