# Translations in JS/TS files

In comparison to PHP files, translations in JS/TS files require a few additional steps. To use translation functions in JS/TS, the dependency `@wordpress/i18n` needs to be included at the top of the corresponding file:

```ts
const { sprintf, _n } = window.wp.i18n;
```

Once that dependency had been included, the translation function can than be used.

## Usage of localization functions

### `__()`

The function `__()` retrieves the translation of `$text`.

```ts
// Schema
const translation = __( string text, string domain = 'default' );

// Example
const { __ } = window.wp.i18n;

const translation = __( 'Place Order', 'woo-gutenberg-products-block' );
```

See also <https://developer.wordpress.org/reference/functions/__/>.

### `_n()`

The function `_n()` translates and retrieves the singular or plural form based on the supplied number.

```ts
// Schema
const translation = _n( string single, string plural, int number, string domain = 'default' );

// Example
const { sprintf, _n } = window.wp.i18n;

const translation = sprintf(
    /* translators: %s number of products in cart. */
    _n(
        '%d product',
        '%d products',
        Math.abs( category->count ),
        'woo-gutenberg-products-block'
    ),
    Math.abs( category->count )
);
```

See also <https://developer.wordpress.org/reference/functions/_n/>.

### `_x()`

The function `_x()` retrieves a translated string with gettext context.

```ts
// Schema
const translation = _x( string text, string context, string domain = 'default' );

// Example
const { _x } = window.wp.i18n;

const translation = _x( 'Draft', 'Order status', 'woo-gutenberg-products-block' );
```

See also <https://developer.wordpress.org/reference/functions/_x/>.

### `_nx()`

The function `_nx()` translates and retrieves the singular or plural form based on the supplied number, with gettext context.

```ts
// Schema
const translation = _nx( string single, string plural, int number, string context, string domain = 'default' );

// Example
const { sprintf, _nx } = window.wp.i18n;

const translation = sprintf(
    /* translators: %s number of products in cart. */
    _nx(
        '%d product',
        '%d products',
        Math.abs( category->count ),
        'Number of products in the cart',
        'woo-gutenberg-products-block'
    ),
    Math.abs( category->count )
);
```

See also <https://developer.wordpress.org/reference/functions/_nx/>.

### Template literals and variables

Template literals cannot be used in JS/TS translations. To use variables in JS/TS translations, the function `sprintf()` needs to be used, as variables cannot be used directly. Various examples on how to use this function, can be seen in the previous examples.

See also <https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/#sprintf>.
