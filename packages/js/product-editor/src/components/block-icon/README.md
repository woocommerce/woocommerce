# BlockIcon

This component uses the icon defined as a block attribute or metadata and renders it.

It looks first within the block's `attributes` and if there is no icon defined there, then looks at the block's `metadata`.

## Usage

### Icon configuration

1. As a block attribute

    In the block configuration file `./block.json`

    ```json
    "attributes": {
    	"icon": {
    		"type": "object"
    	}
    }
    ```

    In the server during the template configuration

    ```php
    array(
    	'woocommerce/product-section', // Block name
    	array(
    		// Block attributes
    		'icon'	=> array(
    			// It's possible to pass a valid html string
    			'src'	=> '<svg ... />',

    			// Or an absolute url
    			'src'	=> 'https://...',
    			'alt'	=> 'The alt name for the icon',

    			// Or a Dashicon icon-key
    			'src'	=> 'default-block',
    		),
    	),
    	array(
    		// Inner blocks
    	),
    ),
    ```

2. As part of the block's metadata

    See [the official blocks icon documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#icon).

### Rendering the Icon

```javascript
import { __experimentalBlockIcon as BlockIcon } from '@woocommerce/product-editor';

export function BlockEdit( { clientId } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<h2>
				<BlockIcon clientId={ clientId } />

				<span>{ title }</span>
			</h2>

			<InnerBlocks />
		</div>
	);
}
```
