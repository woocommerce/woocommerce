# Currency

A collection of utilities to display and work with currency values.

## Installation

Install the module

```bash
pnpm install @woocommerce/currency --save
```

_This package assumes that your code will run in an **ES2015+** environment. If you're using an environment that has limited or no support for ES2015+ such as lower versions of IE then using [core-js](https://github.com/zloirock/core-js) or [@babel/polyfill](https://babeljs.io/docs/en/next/babel-polyfill) will add support for these methods. Learn more about it in [Babel docs](https://babeljs.io/docs/en/next/caveats)._

## Usage

```JS
import CurrencyFactory from '@woocommerce/currency';

const storeCurrency = CurrencyFactory(); // pass store settings into constructor.

// Formats money with a given currency symbol. Uses site's currency settings for formatting,
// from the settings api. Defaults to symbol=`$`, precision=2, decimalSeparator=`.`, thousandSeparator=`,`
const total = storeCurrency.formatAmount( 20.923 ); // '$20.92'

// Get the rounded decimal value of a number at the precision used for the current currency,
// from the settings api. Defaults to 2.
const total = storeCurrency.formatDecimal( '6.2892' ); // 6.29

// Get the string representation of a floating point number to the precision used by the current
// currency. This is different from `formatAmount` by not returning the currency symbol.
const total = storeCurrency.formatDecimalString( 1088.478 ); // '1088.48'
```
