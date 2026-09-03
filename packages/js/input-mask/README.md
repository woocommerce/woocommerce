# @woocommerce/input-mask

A small input mask engine. It formats text as the user types and reports the unmasked value. It has no dependencies.

The engine never drops a typed character. When the typed text does not fit the mask, the input shows the text as typed until the user fixes it.

## Installation

```bash
pnpm install @woocommerce/input-mask --save
```

## Mask syntax

The tokens are the default definitions of [imask](https://imask.js.org/), the most used mask library.

| Character | Accepts |
| --- | --- |
| `0` | a digit |
| `a` | a letter |
| `*` | any character |
| `\` | escapes the next character, so `\0` is a literal `0` |
| anything else | a literal, shown but not stored |

imask's `[]`, `{}` and backtick modifiers are not supported. Those characters are plain literals.

A literal appears once the user types a character after it, or when the mask is complete. The user can also type a literal, and the engine consumes it in place.

## Usage

### Bind to an input

```js
import { bind } from '@woocommerce/input-mask';

const bound = bind( input, {
	mask: '000.000.000-00',
	onChange: ( unmasked ) => save( unmasked ),
} );

bound.setValue( '12345678901' ); // Input shows 123.456.789-01.
bound.destroy();
```

`bind` formats the value the input already has. `setValue` accepts raw or formatted text.

### Format without an input

```js
import { format } from '@woocommerce/input-mask';

format( '12345678901', '000.000.000-00' );
// { display: '123.456.789-01', unmasked: '12345678901', fits: true, map: [ 0, 1, 2, -1, ... ] }
```

`fits` is false when the text does not fit the mask. `display` and `unmasked` are then the text as typed.

`map` gives the typed index of each display character, or `-1` for a literal the mask inserted.

### Remove escapes

```js
import { unescapeMask } from '@woocommerce/input-mask';

unescapeMask( '\\00-00' ); // '00-00', for a format hint.
```

## Editing rules

- Backspace over an inserted literal also removes the typed character before it. Delete removes the one after it.
- Pasted or autofilled text can be raw or formatted. Literals in the right place are consumed, not stored.
- When the text stops fitting the mask, the input shows it as typed. The mask comes back once the user removes the bad character.
- Text typed during IME composition is formatted when the composition ends.
