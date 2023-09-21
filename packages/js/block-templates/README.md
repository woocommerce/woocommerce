# @woocommerce/block-templates

A collection of utility functions for use with WooCommerce admin block templates.

## API

### registerWooBlockType

Registers a WooCommerce block type.

_Usage_

```js
import { registerWooBlockType } from '@woocommerce/block-templates';

import metadata from './block.json';
import { Edit } from './edit';

registerWooBlockType( {
  name: metadata.name,
  metadata: metadata,
  settings: {
    edit: Edit,
  }
} );
```

_Parameters_

- _blockMetadata_ `Object`: Block metadata.

_Returns_

- `WPBlockType | undefined`: The block type if it was registered successfully, otherwise `undefined`.

### useWooBlockProps

This hook is used to lightly mark an element as a WooCommerce block template block. The block's attributes must be passed to this hook and the return result passed to the outermost element of the block in order for the block to properly function in WooCommerce block template contexts.

If you define a ref for the element, it is important to pass the ref to this hook, which the hook in turn will pass to the component through the props it returns. Optionally, you can also pass any other props through this hook, and they will be merged and returned.

_Usage_

```js
import { useWooBlockProps } from '@woocommerce/block-templates';

export function Edit( { attributes } ) {
  const { blockProps } = useWooBlockProps( 
    attributes,
    {
      className: 'my-block',
    }
  );

  return (
    <div { ...blockProps }>
      Block content
    </div>
  );
}
```

_Parameters_
- _attributes_: `Object`: Block attributes.
- _props_: `Object`: Optional. Props to pass to the element.

_Returns_

- `Object`: Props to pass to the element to mark as a WooCommerce block.
