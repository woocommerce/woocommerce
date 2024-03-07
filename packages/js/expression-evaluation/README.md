# @woocommerce/expression-evaluation

Evaluation of JavaScript-like expressions in an optional context.

Examples of simple expressions:

```js
1 + 2
```

```js
foo === 'bar'
```

```js
foo ? 'bar' : 'baz'
```

Examples of complex expressions:

```js
foo.bar.baz === 'qux'
```

```js
foo.bar
  && ( foo.bar.baz === 'qux' || foo.baz === 'quux' )
```

```js
foo.bar
	&& ( foo.baz === "qux" || foo.baz === "quux" )
	&& ( foo.quux > 1 && foo.quux <= 5 )
```

```js
foo.bar
	  && ( foo.baz === "qux" || foo.baz === "quux" )
	  && ( foo.quux > 1 && foo.quux <= 5 )
	? "boo"
	: "baa"
```

```js
foo
  + 5
  /* This is a comment */
  * ( bar ? baz : qux )
```

## API

### evaluate

Evaluates an expression in an optional context.

#### Usage

```js
import { evaluate } from '@woocommerce/expression-evaluation';

const result = evaluate( '1 + foo', { foo: 2 } );

console.log( result ); // 3
```

#### Parameters

- _expression_ `string`: The expression to evaluate.
- _context_ `Object`: Optional. The context to evaluate the expression in. Variables in the expression will be looked up in this object.

#### Returns

- `any`: The result of the expression evaluation.

## Expression syntax

### Grammar and types

The expression syntax is based on JavaScript. The formal grammar is defined in [parser.ts](./src/parser.ts).

An expression consists of a single statement.

Features like `if` statements, `for` loops, function calls, and variable assignments, are not supported.

The following types are supported:

- `null`
- Boolean: `true` and `false`
- Number: An integer or floating point number.
- String: A sequence of characters that represent text.

### Literals

Values in an expression can be written as literals.

#### null

```js
null
```

#### Boolean

```js
true
false
```

#### Number

```js
1
5.23
-9
```

#### String

String literals can be written with single or double quotes. This can be helpful if the string contains a single or double quote.

```js
'foo'
"foo"
'foo "bar"'
"foo 'bar'"
```

Quotes can be escaped with a backslash.

```js
'foo \'bar\''
"foo \"bar\""
```

### Context variables

Variables can be used in an expression. The value of a variable is looked up in the context.

```js
const result = evaluate( 'foo', { foo: 1 } );

console.log( result ); // 1
```

Nested properties can be accessed with the dot operator.

```js
const result = evaluate( 'foo.bar', { foo: { bar: 1 } } );

console.log( result ); // 1
```

### Operators

The following operators are supported.

#### Comparison operators

##### Equal (`==`)

Returns `true` if the operands are equal.

```js
1 == 1
```

##### Not equal (`!=`)

Returns `true` if the operands are not equal.

```js
1 != 2
```

##### Strict equal (`===`)

Returns `true` if the operands are equal and of the same type.

```js
1 === 1
```

##### Strict not equal (`!==`)

Returns `true` if the operands are not equal and/or not of the same type.

```js
1 !== "1"
```

##### Greater than (`>`)

Returns `true` if the left operand is greater than the right operand.

```js
2 > 1
```

##### Greater than or equal (`>=`)

Returns `true` if the left operand is greater than or equal to the right operand.

```js
2 >= 2
```

##### Less than (`<`)

Returns `true` if the left operand is less than the right operand.

```js
1 < 2
```

##### Less than or equal (`<=`)

Returns `true` if the left operand is less than or equal to the right operand.

```js
2 <= 2
```

#### Arithmetic operators

##### Addition (`+`)

Returns the sum of two operands.

```js
1 + 2
```

##### Subtraction (`-`)

Returns the difference of two operands.

```js
2 - 1
```

##### Multiplication (`*`)

Returns the product of two operands.

```js
2 * 3
```

##### Division (`/`)

Returns the quotient of two operands.

```js
6 / 2
```

##### Modulus (`%`)

Returns the remainder of two operands.

```js
5 % 2
```

##### Negation (`-`)

Returns the negation of an operand.

```js
-1
```

#### Logical operators

##### Logical AND (`&&`)

Returns `true` if both operands are `true`.

```js
true && true
```

##### Logical OR (`||`)

Returns `true` if either operand is `true`.

```js
true || false
```

##### Logical NOT (`!`)

Returns `true` if the operand is `false`.

```js
!false
```

#### Conditional (ternary) operator

Returns the first value if the condition is `true`, otherwise it returns the second value.

```js
true ? 1 : 2
```

### Comments

Comments can be used to document an expression. Comments are treated as whitespace and are ignored by the parser.

```js
/* This is a comment */
```
