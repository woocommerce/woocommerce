# Formatters <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

-   [Why are formatters useful?](#why-are-formatters-useful)
-   [How to use formatters](#how-to-use-formatters)
    -   [Real world example](#real-world-example)
-   [MoneyFormatter](#moneyformatter)
    -   [Arguments](#arguments)
    -   [Example use and returned value](#example-use-and-returned-value)
-   [CurrencyFormatter](#currencyformatter)
    -   [Arguments](#arguments-1)
    -   [Example use and returned value](#example-use-and-returned-value-1)
-   [HtmlFormatter](#htmlformatter)
    -   [Arguments](#arguments-2)
    -   [Example use and returned value](#example-use-and-returned-value-2)

`Formatters` are utility classes that allow you to format values to so that they are compatible with the StoreAPI. Default formatters handle values such as monetary amounts, currency information, or HTML.

It is recommended that you use these formatters when you are extending the StoreAPI.

## Why are formatters useful?

Using the formatter utilities when returning certain types of data will ensure that your custom data is consistent and compatible with other endpoints. They also take care of any store specific settings that may affect the formatting of the data, such as currency settings.

Store API includes formatters for:

-   [Money](#moneyformatter)
-   [Currency](#currencyformatter)
-   [HTML](#htmlformatter)

## How to use formatters

To get a formatter, you can use the `get_formatter` method of the `ExtendSchema` class. This method accepts a string, which is the name of the formatter you want to use.

```php
get_formatter('money'); // For the MoneyFormatter
get_formatter('html'); // For the HtmlFormatter
get_formatter('currency'); // CurrencyFormatter
```

This returns a `FormatterInterface` which has the `format` method. The `format` method signature is:

```php
format( $value, array $options = [] );
```

Only `MoneyFormatter`'s behavior can be controlled by the `$options` parameter. This parameter is optional.

### Real world example

Let's say we're going to be returning some extra price data via the API. We want to return the price in cents, and also the currency data for the store. We can use the `MoneyFormatter` and `CurrencyFormatter` to do this.

First we need to ensure we can access the formatter classes. We can do this by using the `use` keyword:

```php
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Utilities\ExtendSchema;

$extend = StoreApi::container()->get( ExtendSchema::class );

$my_custom_price = $extend->get_formatter( 'money' )->format( '10.00', [
  'rounding_mode' => PHP_ROUND_HALF_DOWN,
  'decimals'      => 2
] );

$price_response = $extend->get_formatter( 'currency' )->format( [
	'price'         => $my_custom_price,
] );
```

The above code would result in `$price_response` being set to:

```text
[
    'price' => '1000'
    'currency_code' => 'GBP'
    'currency_symbol' => '£'
    'currency_minor_unit' => 2
    'currency_decimal_separator' => '.'
    'currency_thousand_separator' => ','
    'currency_prefix' => '£'
    'currency_suffix' => ''
]
```

## MoneyFormatter

The [`MoneyFormatter`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/StoreApi/Formatters/MoneyFormatter.php) class can be used to format a monetary value using the store settings. The store settings may be overridden by passing options to this formatter's `format` method.

Values are returned in cents to avoid floating point rounding errors, so when using this formatter you'll most likely also be returning the currency data using the [`CurrencyFormatter`](#currencyformatter) alongside it. This will allow the consumer of the API to display the value in the intended format.

### Arguments

| Argument                    | Type     | Description                                                                                                                                                                                                                      |
| --------------------------- | -------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `$value`                    | `number` | The number you want to format into a monetary value                                                                                                                                                                              |
| `$options`                  | `array`  | Should contain two keys, `decimals` which should be an `integer`,                                                                                                                                                                |
| `$options['decimals']`      | `number` | Used to control how many decimal places should be displayed in the monetary value. Defaults to the store setting.                                                                                                                |
| `$options['rounding_mode']` | `number` | Used to determine how to round the monetary value. This should be one of the PHP rounding modes described in the [PHP round() documentation](https://www.php.net/manual/en/function.round.php). Defaults to `PHP_ROUND_HALF_UP`. |

### Example use and returned value

```php
get_formatter( 'money' )->format( 10.443, [
  'rounding_mode' => PHP_ROUND_HALF_DOWN,
  'decimals'      => 2
] );
```

returns `1044`

## CurrencyFormatter

This formatter takes an array of prices, and returns the same array but with currency data appended to it. The currency data added is:

| Key                           | Type     | Description                                                                                       |
| ----------------------------- | -------- | ------------------------------------------------------------------------------------------------- |
| `currency_code`               | `string` | The string representation of the currency, e.g. GPB or USD                                        |
| `currency_symbol`             | `string` | The symbol of the currency, e.g. &pound; or \$                                                    |
| `currency_minor_unit`         | `number` | How many decimal places will be shown in the currency                                             |
| `currency_decimal_separator`  | `string` | The string used to separate the whole value and the decimal value in the currency.                |
| `currency_thousand_separator` | `string` | The string used to separate thousands in the currency, for example: &pound;10,000 or &euro;10.000 |
| `currency_prefix`             | `string` | A string that should appear before the currency value.                                            |
| `currency_suffix`             | `string` | A string that should appear after the currency value.                                             |

This data can then be used by the client/consumer to format prices correctly according to store settings. Important: the array of prices passed to this formatted should already be in monetary format so you should use the [`MoneyFormatter`](#moneyformatter) first.

### Arguments

| Argument | Type       | Description                                                                  |
| -------- | ---------- | ---------------------------------------------------------------------------- |
| `$value` | `number[]` | An array of prices that you want to merge with the store's currency settings |

### Example use and returned value

```php
get_formatter( 'currency' )->format( [
  'price'         => 1800,
  'regular_price' => 1800,
  'sale_price'    => 1800,
] );
```

returns

```text
'price' => '1800'
'regular_price' => '1800'
'sale_price' => '1800'
'price_range' => null
'currency_code' => 'GBP'
'currency_symbol' => '£'
'currency_minor_unit' => 2
'currency_decimal_separator' => '.'
'currency_thousand_separator' => ','
'currency_prefix' => '£'
'currency_suffix' => ''
```

## HtmlFormatter

This formatter will take an HTML value, run it through: [`wptexturize`](https://developer.wordpress.org/reference/functions/wptexturize/),
[`convert_chars`](https://developer.wordpress.org/reference/functions/convert_chars/),
[`trim`](https://www.php.net/manual/en/function.trim.php), and [`wp_kses_post`](https://developer.wordpress.org/reference/functions/wp_kses_post/)
before returning it. The purpose of this formatter is to make HTML "safe" (in terms of correctly formatted characters).
`wp_kses_post` will ensure only HTML tags allowed in the context of a `post` are present in the string.

### Arguments

| Argument | Type     | Description                                     |
| -------- | -------- | ----------------------------------------------- |
| `$value` | `string` | The string you want to format into "safe" HTML. |

### Example use and returned value

```php
get_formatter( 'html' )->format(
  "<script>alert('bad script!')</script> This \"coffee\" is <strong>very strong</strong>."
);
```

returns:

```text
alert('bad script!') This &#8220;coffee&#8221; is <strong>very strong</strong>.
```

This formatter should be used when returning HTML from the StoreAPI regardless of whether the HTML is user generated or not. This will ensure the consumer/client can display the HTML safely and without encoding issues.

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/third-party-developers/extensibility/rest-api/extend-rest-api-formatters.md)

<!-- /FEEDBACK -->
