# Experimental

This is a private package not meant for use by third parties.

A collection of component imports and exports that are aliases for components transitioning from experimental to non-experimental. This package prevents the component from being undefined when the `@wordpress/components` library version is unclear.

It also contains several in-development components that are slated for inclusion in later releases of `@woocommerce/components`.

## Installation

Install the module

```bash
pnpm install @woocommerce/experimental --save
```

## Usage

Simply import the component name with the `__experimental` prefix. If found, the non-experimental version will be imported and if not, this will fallback to the experimental version.

```jsx
import { Text } from '@woocommerce/experimental';

render() {
	return (
		<Text>
			…
		</Text>
	);
}
```
