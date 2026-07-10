---
post_title: 'Settings APIs and admin pages'
sidebar_label: Overview
sidebar_position: 0
---

# Settings APIs and admin pages

Managing your settings, admin pages, custom attributes, and more is possible through the Settings APIs. The docs in this section cover what the Settings APIs and admin pages system offers in general. This guide goes further to map all of the various approaches you have available, some of the scenarios they cover, and the trade-offs of each approach.

There are also code examples for your reference before you commit to an implementation path. However, reading through the approach you're interested in will give you greater scope to understand and apply the principles of each.

## Approaches at a glance

The Settings API and admin pages section has seven approaches, outlined in the table. The three core approaches (the Settings API, `WC_Integration`, and admin page registration) will cover most of your bases, while the remaining four are specific for navigation, email, and taxonomy visibility.

| Approach | Use when | Requires |
| :---- | :---- | :---- |
| [Settings API](/docs/extensions/settings-and-config/settings-api/) | Adding settings fields to an existing WooCommerce page, such as a payment gateway or shipping method | PHP |
| [`WC_Integration`](/docs/extensions/settings-and-config/implementing-settings/) | Your extension connects to a third-party service and needs a page under the Integrations tab | PHP |
| [Add a section to a settings tab](/docs/extensions/settings-and-config/adding-a-section-to-a-settings-tab/) | Your settings belong under an existing WooCommerce tab rather than a new page | PHP |
| [Admin page registration](/docs/extensions/settings-and-config/working-with-woocommerce-admin-pages/) | Your extension needs a standalone page outside WooCommerce's settings structure | PHP or JavaScript |
| [Store management links](/docs/extensions/settings-and-config/how-to-add-your-own-store-management-links/) | Surfacing quick-access links on the WooCommerce home screen | JavaScript |

## Settings API

`WC_Settings_API` is the class that all WooCommerce settings build on, with payment gateways and shipping methods directly extending it. It's going to be the right tool when your extension adds settings fields to an existing WooCommerce context instead of creating a standalone settings page.

There are three core responsibilities of this class:

- Define fields in `init_form_fields()`.
- Render them through `admin_options()`.
- Hook `process_admin_options()` to the update hook that matches your context.

For `process_admin_options()`, those contexts could be `woocommerce_update_options_payment_gateways` for a payment gateway, or `woocommerce_update_options_shipping_methods` for a shipping method.

The API lets you control field placements without presupposing a location. However, this means it doesn't create a settings page for you, so you'll need to register that separately if needed. For extensions that fit within payment or shipping contexts, you are extending the same class that WooCommerce's own gateways use.

```php
class My_Extension_Settings extends WC_Settings_API {
	public function __construct() {
		$this->id = 'my_extension';
		$this->init_form_fields();
		$this->init_settings();
		add_action(
			'woocommerce_update_options_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable', 'my-extension' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		);
	}
}
```

## WC_Integration

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

## Adding a section to a settings tab

Rather than creating a whole new page, you can add a section beneath an existing WooCommerce settings tab using two filters:

- `woocommerce_get_sections_{tab}` registers the section.
- `woocommerce_get_settings_{tab}` supplies its fields.

The `{tab}` portion of each filter corresponds to the tab you want to extend. For instance, `products` targets the **Products** tab, while `accounts` targets **Account and Privacy**.

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

## Custom attributes in menus

Product attributes in WooCommerce register as custom taxonomies, but are excluded from WordPress navigation menus by default. You opt a specific attribute into menu visibility using the `woocommerce_attribute_show_in_nav_menus` filter. Attribute slugs carry a `pa_` prefix, so an attribute named size becomes `pa_size`.

Once the attribute appears in **Appearance > Menus**, taxonomy archive pages for that attribute use default blog styling. To display products on those archive pages, you copy `woocommerce/templates/taxonomy-product_cat.php` into your theme and rename the file to match the attribute slug (for example, `taxonomy-pa_size.php`).

The implementation is a filter and a template file, but together they enable attribute-based product browsing through typical WordPress navigation.

```php
add_filter(
	'woocommerce_attribute_show_in_nav_menus',
	function( $register, $name ) {
		if ( 'pa_size' === $name ) {
			$register = true;
		}
		return $register;
	},
	1,
	2
);
```

## Email editor integration

The WooCommerce Email Editor lets merchants edit transactional emails using the WordPress Site Editor. Third-party extensions must explicitly opt their emails into Site Editor support as it's not used automatically, even once the editor is enabled.

:::note

The WooCommerce Email Editor is in alpha. To enable it, go to **WooCommerce > Settings > Advanced > Features** and turn on **Block Email Editor (alpha)**.

:::

The integration involves five steps:

1. Extend `WC_Email` and implement the required methods.
2. Register your email class with the `woocommerce_email_classes` filter.
3. Opt in to Site Editor support through the `woocommerce_transactional_emails_for_block_editor` filter.
4. Create a Block template in your plugin's `templates/emails/block/` directory.
5. Set up triggers by hooking into the appropriate WordPress or WooCommerce actions.

The opt-in step is what determines whether your email uses the Site Editor. Without it, your email appears in the email list but the Site Editor does not apply to it. Block templates use personalization tags: for example, `<!--[woocommerce/customer-first-name]-->` to insert dynamic content at send time.

:::tip

When developing and testing, delete the transient `wc_email_editor_initial_templates_generated` to force template regeneration.

:::

```php
// Opt in to block editor support.
add_filter(
	'woocommerce_transactional_emails_for_block_editor',
	function( $emails ) {
		$emails[] = 'your_plugin_custom_email';
		return $emails;
	}
);

// Register the email class.
add_filter(
	'woocommerce_email_classes',
	function( $email_classes ) {
		$email_classes['YourPlugin_Custom_Email'] = new YourPlugin_Custom_Email();
		return $email_classes;
	}
);
```

## Next steps

With the trade-offs and code examples for each approach as a reference, you can work through the full documentation for the one your extension needs.

1. [Settings API](/docs/extensions/settings-and-config/settings-api/). Core reference for the `WC_Settings_API` class.
2. [Implementing settings with `WC_Integration`](/docs/extensions/settings-and-config/implementing-settings/). How to use the `WC_Integration` class for third-party service connections.
3. [Adding a section to a settings tab](/docs/extensions/settings-and-config/adding-a-section-to-a-settings-tab/). Adding a section beneath an existing WooCommerce tab.
4. [Working with WooCommerce admin pages](/docs/extensions/settings-and-config/working-with-woocommerce-admin-pages/). Registering PHP and React admin pages.
5. [Adding store management links](/docs/extensions/settings-and-config/how-to-add-your-own-store-management-links/). Navigation shortcuts on the WooCommerce home screen.
6. [Custom attributes in menus](/docs/features/products/using-custom-attributes-in-menus/). Exposing attribute taxonomies in WordPress navigation menus.
7. [Email editor integration](/docs/extensions/settings-and-config/email-editor-integration/). Registering custom emails with the block Email Editor (alpha).
