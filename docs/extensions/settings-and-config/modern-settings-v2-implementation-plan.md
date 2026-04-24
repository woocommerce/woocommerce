# Modern Settings V2 Implementation Plan

## Goal

Create a modern WooCommerce settings path that feels like a natural progression of `WC_Settings_Page`: PHP owns page registration, schema, permissions, persistence contracts, and script dependencies; React owns rendering, interaction, client-side validation hints, and richer controls.

The first PR should provide a clear adoption path for extensions without requiring them to rewrite their whole settings UI. The feature must remain inert unless the `modern-settings` feature flag is explicitly enabled.

## Principles

-   Keep `WC_Settings_Page` as the migration boundary.
-   Use one canonical settings schema that React consumes.
-   Treat legacy settings arrays as one schema source, not as the long-term renderer contract.
-   Make field-level custom components the primary extension mechanism.
-   Scope JavaScript registrations to a page/section to avoid collisions.
-   Make save behavior explicit through adapters.
-   Keep DataForm or other renderer internals out of the public extension API.
-   Provide diagnostics for integration mistakes from the first version.

## First PR Scope

The first PR should be a vertical slice:

-   PHP modern settings page contract.
-   Canonical schema builder from `WC_Settings_Page` arrays.
-   Field-level `component` metadata passthrough.
-   Scoped JavaScript component registry.
-   Native field rendering for common field types.
-   `form_post` and `none` save adapters.
-   PHP script dependency contract so extension scripts load before mount.
-   Developer diagnostics for missing payload, unsupported fields, and missing components.
-   A richer sample plugin showing native fields, a custom component, and form-post saving.
-   Documentation organized by migration path.

Do not include REST-managed saves, async option loading, or full React-managed settings pages in the first PR.

## PHP Architecture

Add a modern settings page adapter under `plugins/woocommerce/src/Internal/Admin/Settings/`.

The adapter should expose:

```php
interface ModernSettingsPageInterface {
	public function get_page_id(): string;

	public function get_schema( string $section ): array;

	public function get_script_handles( string $section ): array;

	public function get_save_adapter( string $section ): string;
}
```

`WC_Settings_Page` should get an opt-in method:

```php
public function get_modern_settings_page(): ?ModernSettingsPageInterface;
```

The default returns `null`, so existing pages keep legacy behavior. Pages that opt in still only render modern settings when `modern-settings` is enabled.

## Canonical Schema

The canonical schema should be independent of DataForm and should look like:

```php
array(
	'id'      => 'general',
	'title'   => 'General',
	'section' => 'default',
	'groups'  => array(
		'default' => array(
			'id'          => 'default',
			'title'       => '',
			'description' => '',
			'order'       => 0,
			'fields'      => array(
				array(
					'id'        => 'my_plugin_field',
					'label'     => 'Payment methods',
					'type'      => 'array',
					'component' => 'my-plugin/payment-method-picker',
					'value'     => array( 'card' ),
					'options'   => array(),
					'save'      => array(
						'adapter' => 'form_post',
						'name'    => 'my_plugin_field',
					),
				),
			),
		),
	),
)
```

Legacy `WC_Settings_Page` arrays should convert into this schema. Fields before the first `title` marker must fall into a default group automatically.

## JavaScript Architecture

Expose a scoped extension API from `@woocommerce/modern-settings-sdk`:

```ts
registerSettingsExtension( {
	scope: {
		page: 'woopayments',
		section: 'payments',
	},
	components: {
		'wcpay/payment-method-picker': PaymentMethodPicker,
	},
} );
```

Renderer resolution order:

1. `field.component`.
2. Scoped field override.
3. Scoped type renderer.
4. Native SDK renderer.
5. Unsupported-field diagnostic.

Custom components receive stable props:

```ts
type SettingsFieldComponentProps = {
	field: SettingsField;
	value: unknown;
	onChange: ( value: unknown ) => void;
	context: {
		page: string;
		section?: string;
	};
};
```

## Save Adapters

The first PR should implement:

-   `form_post`: serializes hidden inputs for the existing WooCommerce settings form.
-   `none`: display-only field, no hidden input.

The schema should be designed so future PRs can add `option`, `rest`, and `custom` adapters.

## Diagnostics

In development mode, warn when:

-   The expected payload is missing.
-   A field declares a component that is not registered.
-   A field type is unsupported.
-   A field has an unknown save adapter.
-   A page declares script handles that were not loaded before mount.

## Testing

Add focused unit tests for:

-   Schema conversion from legacy settings arrays.
-   Default group creation before the first title marker.
-   Field-level component metadata passthrough.
-   `form_post` serialization for scalar, checkbox, and array values.
-   Scoped component registry resolution.
-   Feature flag disabled path preserving legacy rendering.

Use browser testing against a local WordPress site to verify:

-   With `modern-settings` disabled, the settings page does not change.
-   With `modern-settings` enabled, the modern mount appears and native/custom fields render.
-   Plugin scripts registered by the PHP contract load before mount.
