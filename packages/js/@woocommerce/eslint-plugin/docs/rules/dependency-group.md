# Enforce dependencies docblocks formatting (dependency-group)

Ensures that all top-level package imports adhere to dependencies grouping conventions.

Specifically, this ensures that:

- An import is preceded by "External dependencies" or "Internal dependencies" as appropriate by the import source.

## Rule details

Examples of **incorrect** code for this rule:

```js
import { get } from 'lodash';
import { Component } from '@wordpress/element';
import edit from './edit';
```

Examples of **correct** code for this rule:

```js
/*
 * External dependencies
 */
import { get } from 'lodash';
import { Component } from '@wordpress/element';

/*
 * Internal dependencies
 */
import edit from './edit';
```
