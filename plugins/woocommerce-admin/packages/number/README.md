# Number

A collection of utilities to propery localize numerical values in WooCommerce

## Installation

Install the module

```bash
npm install @woocommerce/number --save
```

_This package assumes that your code will run in an **ES2015+** environment. If you're using an environment that has limited or no support for ES2015+ such as lower versions of IE then using [core-js](https://github.com/zloirock/core-js) or [@babel/polyfill](https://babeljs.io/docs/en/next/babel-polyfill) will add support for these methods. Learn more about it in [Babel docs](https://babeljs.io/docs/en/next/caveats)._

## Usage

```JS
import { formatNumber, formatValue, calculateDelta } from '@woocommerce/number';

// Formats a number using site's current locale.
// Defaults to en-US localization
const localizedNumber = formatNumber( 1337 ); // '1,377'

// formatValue's first argument is a type: average, or number
// The second argument is the number/value to format
const formattedAverage = formatValue( 'average', '10.5' ); // 11 just uses Math.round
const formattedNumber = formatValue( 'number', '1337' ); // 1,337 calls formatNumber ( see above )

// Get a rounded percent change/delta between two numbers
const delta = calculateDelta( 10, 8 ); // '25'
```
