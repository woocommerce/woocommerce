# Data

WooCommerce Admin data store and utilities.

WooCommerce registers the `wc-store-data` script in wp-admin only, so these stores are not available on the storefront or in blocks rendered there. For settings in blocks and storefront code, use `getSetting` from `wc.wcSettings` instead. See [`@woocommerce/settings` in the dependency extraction plugin README](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/dependency-extraction-webpack-plugin/README.md#woocommercesettings).

## Installation

Install the module

```bash
pnpm install @woocommerce/data --save
```

_This package assumes that your code will run in an **ES2015+** environment. If you're using an environment that has limited or no support for ES2015+ such as lower versions of IE then using [core-js](https://github.com/zloirock/core-js) or [@babel/polyfill](https://babeljs.io/docs/en/next/babel-polyfill) will add support for these methods. Learn more about it in [Babel docs](https://babeljs.io/docs/en/next/caveats)._

## Usage

```JS
import { settingsStore } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

function MySettings() {
	const settings = useSelect( select => {
		return select( settingsStore ).getSettings('general').general;
	} );
	return (
		<ul>
			{ Object.keys( settings ?? {} ).map( setting => (
				<li key={ setting }>{ setting }</li>
			) ) }
		</ul>
	);
}

// Rendered in the application:
//
//  <MySettings />
```
