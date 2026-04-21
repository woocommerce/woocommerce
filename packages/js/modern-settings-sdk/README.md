# @woocommerce/modern-settings-sdk

Public API for the WooCommerce Modernised Settings experience.

This package exposes the React primitives used by the modernised WooCommerce
settings screen — the `DataForm`-based page wrapper, the base field
transformer, a preload hook, and the public TypeScript contract — so that
external plugins (for example, WooPayments) can render the same settings UI
from their own settings tabs without reaching into the admin client.

For the PHP-side contract used to register settings schemas and hook into
the modernised settings pipeline, see
[`plugins/woocommerce/src/Internal/Admin/Settings/README.md`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/Admin/Settings/README.md).

## What's exported

- `ReactSettingsPage` — the `DataForm`-based page component that renders a
  preloaded settings response.
- `useReactSettings` — a hook that reads preloaded settings from
  `window.wcSettings.admin` for a given data path.
- `baseFieldTransformer`, `registerFieldTypeTransformer`,
  `getFieldTypeTransformer`, `parseOptions`, `createChildrenWithRows`,
  `reorderGroupFields`, `hideEmptyLabel` — helpers for turning a settings
  field definition into a DataForm field definition.
- Public TypeScript types: `ReactSettingsField`, `ReactSettingsFieldOptions`,
  `ReactSettingsOptionItem`, `ReactSettingsGroup`, `ReactSettingsResponse`,
  `RowConfiguration`, `RowConfigurations`, `FieldTransformer`.

## Basic usage

```tsx
import {
	ReactSettingsPage,
	baseFieldTransformer,
	useReactSettings,
} from '@woocommerce/modern-settings-sdk';

const MyPluginSettings = () => {
	const { data, isLoading, error } = useReactSettings( {
		dataPath: [ 'settings', 'my_plugin', 'default' ],
	} );

	return (
		<ReactSettingsPage
			data={ data }
			error={ error }
			isLoading={ isLoading }
			fieldTransformer={ baseFieldTransformer }
		/>
	);
};
```
