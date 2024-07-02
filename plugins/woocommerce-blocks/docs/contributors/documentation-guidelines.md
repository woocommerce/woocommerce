# Documentation Guidelines <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Use active instead of passive voice](#use-active-instead-of-passive-voice)
    -   [Example](#example)
-   [Use the personal pronoun “you”](#use-the-personal-pronoun-you)
-   [Don’t use gendered pronouns](#dont-use-gendered-pronouns)
-   [Filename must resemble the title](#filename-must-resemble-the-title)
    -   [Example](#example-1)
-   [Use correct heading hierarchy](#use-correct-heading-hierarchy)
    -   [Example](#example-2)
-   [Use semantically correct markup](#use-semantically-correct-markup)
-   [Use correct spelling](#use-correct-spelling)
    -   [Example](#example-3)
-   [Use images not wider than 50% width](#use-images-not-wider-than-50-width)
-   [Use descriptive links](#use-descriptive-links)
    -   [Example](#example-4)
-   [Explain arguments](#explain-arguments)
    -   [Example](#example-5)
-   [Explicitly define language for code examples](#explicitly-define-language-for-code-examples)
    -   [Example](#example-6)
        -   [CSS code example](#css-code-example)
        -   [JS code example](#js-code-example)
-   [Import from global imports rather than alias](#import-from-global-imports-rather-than-alias)
-   [Use table of contents for top-level readme](#use-table-of-contents-for-top-level-readme)
-   [Use internal links](#use-internal-links)
-   [Sort releases descending](#sort-releases-descending)
    -   [Example](#example-7)
-   [Structure “How to” instructions](#structure-how-to-instructions)
-   [Link references](#link-references)

## Use active instead of passive voice

Active voice should be preferred over passive voice. Passive voice can be used, when it fits better.

### Example

-   **Active voice:** You want to render your own components in specific places in the Cart and Checkout.
-   **Passive voice:** Slots and Fills add the possibility to render your own HTML in pre-defined places in the Cart and Checkout.

## Use the personal pronoun “you”

When using active voice, the second person singular (you) must be used.

## Don’t use gendered pronouns

Gendered pronouns (she/her/hers and he/him/his) must not be used.

## Filename must resemble the title

The filename of the document must match the title.

### Example

-   **Title:** Slots and Fills
-   **Filename:** slots-and-fills.md

## Use correct heading hierarchy

Correct H1-H6 headings must be used. Each document can only have one H1 heading. An H3 heading can only be used within an H2 heading, an H4 heading can only be used within an H3 heading, etc. If possible, avoid using articles in headings.

### Example

-   **Incorrect:** The problem
-   **Correct:** Problem

## Use semantically correct markup

The markup used must be semantically correct, e.g. list markup must only be used to display a list.

## Use correct spelling

Classes and tokens from the codebase must be written exactly as they appear in the codebase. Proper nouns must be written correctly.

### Example

-   **RestApi:** The `p` and the `i` of `RestApi` are written in lowercase
-   **Composer:** The `C` of `Composer` is written in uppercase.
-   **ESLint:** The `E`, the `S` and the `L` of `ESLint` are written in uppercase.

## Use images not wider than 50% width

Embedded images should not exceed a width of 50%.

## Use descriptive links

When linking to another document, a descriptive link text must be used.

### Example

-   **Incorrect:** Check this document
-   **Correct:** Check the Slot Fill documentation

## Explain arguments

When listing or describing arguments, a table must be used to describe them. Refer to them as either arguments or props, depending on if they’re being used on a component or in a function/method. In the table, the description column should begin with a capital letter and end in a full stop. When listing an argument that is an array/object, list the argument name, type: array/object and a high-level description of what the purpose of the argument is. Then below, you detail the individual keys of the array/object.

### Example

| Argument | Type | Default value | Required | Description |
| -------- | ---- | ------------- | -------- | ----------- |
| ...      | ...  | ...           | ...      | ...         |

## Explicitly define language for code examples

When using code examples, the fence format and the language definition must be used.

### Example

#### CSS code example

````text
```css
/* This will apply to prices in the checkout block */
.wc-block-checkout .wc-block-components-formatted-money-amount {
	font-style: italic;
}
```
````

#### JS code example

````text
```js
const { registerExpressPaymentMethod } = window.wc.blocksRegistry;
```
````

## Import from global imports rather than alias

In code examples, use imports from the global window object rather than the aliases, since the aliases require build tools to work. Example:

```diff
-import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
+const { registerCheckoutFilters } = window.wc.blocksCheckout;
```

## Use table of contents for top-level readme

Every top-level README must have a table of contents. The table of contents can be generated automatically using the Visual Studio Code extension Markdown All in One.

## Use internal links

When listing features and options, e.g. ExperimentalOrderMeta, a table of contents with internal links must be used to allow jumping to the specific feature and option directly.

## Sort releases descending

Releases must be sorted starting with the most recent release.

### Example

-   5.7.1
-   5.7.0
-   5.6.0
-   5.5.0

## Structure “How to” instructions

When explaining functionality, the following structure should be used:

-   Problem
-   Solution
-   Usage
-   Things to consider
-   Putting it together

## Link references

When referencing other documentations, the corresponding document should be linked.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/documentation-guidelines.md)

<!-- /FEEDBACK -->
