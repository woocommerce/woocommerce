# Number

A collection of utilities to properly localize numerical values in WooCommerce

## Installation

Install the module

```bash
pnpm install @woocommerce/number --save
```

_This package assumes that your code will run in an **ES2015+** environment. If you're using an environment that has limited or no support for ES2015+ such as lower versions of IE then using [core-js](https://github.com/zloirock/core-js) or [@babel/polyfill](https://babeljs.io/docs/en/next/babel-polyfill) will add support for these methods. Learn more about it in [Babel docs](https://babeljs.io/docs/en/next/caveats)._

## Usage

```JS
import { numberFormat, formatValue, calculateDelta } from '@woocommerce/number';

// It's best to retrieve the site currency settings and compose them with the format functions.
import { partial } from 'lodash';
// Retrieve this from the API or a global settings object.
const siteNumberOptions = {
  precision: 2,
  decimalSeparator: '.',
  thousandSeparator: ',',
};
// Compose.
const formatStoreNumber = partial( numberFormat, siteNumberOptions );
const formatStoreValue = partial( formatValue, siteNumberOptions );

// Formats a number using site's current locale.
const localizedNumber = formatStoreNumber( 1337 ); // '1,377'

// formatValue's second argument is a type: average, or number
// The third argument is the number/value to format
// (The first argument is the config object we composed with earlier)
const formattedAverage = formatStoreValue( 'average', '10.5' ); // 11 just uses Math.round
const formattedNumber = formatStoreValue( 'number', '1337' ); // 1,337 calls formatNumber ( see above )

// Get a rounded percent change/delta between two numbers
const delta = calculateDelta( 10, 8 ); // '25'
```
