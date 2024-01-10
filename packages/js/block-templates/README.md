# @woocommerce/block-templates

A collection of utility functions for use with WooCommerce admin block templates.

## API

### registerWooBlockType

Registers a WooCommerce block type.

#### Usage

```js
import { registerWooBlockType } from '@woocommerce/block-templates';

import metadata from './block.json';
import { Edit } from './edit';

registerWooBlockType( {
	name: metadata.name,
	metadata: metadata,
	settings: {
		edit: Edit,
	},
} );
```

#### Parameters

- _blockMetadata_ `Object`: Block metadata.

#### Returns

- `WPBlockType | undefined`: The block type if it was registered successfully, otherwise `undefined`.

### useLayoutTemplate

This hook is used to retrieve a layout template from the server.

#### Usage

```js
import { useLayoutTemplate } from '@woocommerce/block-templates';

export function Example() {
	const { layoutTemplate, isResolving } =
		useLayoutTemplate( 'my-layout-template' );

	return (
		<div>
			{ isResolving && <p>Loading layout template...</p> }
			{ layoutTemplate && (
				<p>{ JSON.stringify( layoutTemplate, null, 4 ) }</p>
			) }
			{ ! layoutTemplate && ! isResolving && (
				<p>'Layout template does not exist!'</p>
			) }
		</div>
	);
}
```

#### Parameters

- _layoutTemplateId_ `string`: The id of the layout template to retrieve.

#### Returns

- `Object`
    - _layoutTemplate_ `Object | undefined`: The layout template if it was found, otherwise `null`.
    - _isResolving_ `boolean`: Whether or not the layout template is resolving.

### useWooBlockProps

This hook is used to lightly mark an element as a WooCommerce block template block. The block's attributes must be passed to this hook and the return result passed to the outermost element of the block in order for the block to properly function in WooCommerce block template contexts.

If you define a ref for the element, it is important to pass the ref to this hook, which the hook in turn will pass to the component through the props it returns. Optionally, you can also pass any other props through this hook, and they will be merged and returned.

#### Usage

```js
import { useWooBlockProps } from '@woocommerce/block-templates';

export function Edit( { attributes } ) {
	const { blockProps } = useWooBlockProps( attributes, {
		className: 'my-block',
	} );

	return <div { ...blockProps }>Block content</div>;
}
```

#### Parameters

- _attributes_ `Object`: Block attributes.
- _props_ `Object`: Optional. Props to pass to the element.

#### Returns

- `Object`: Props to pass to the element to mark as a WooCommerce block.
