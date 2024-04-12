# SectionActions

`<SectionActions />` is a React component designed to be used within blocks in WooCommerce Product Editor,
providing a slot for specific actions related to the section in which it is included.

## Example

```jsx
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function CustomProductBlockEdit() {
  return (
    <>
      <SectionActions>
        <Button
          onClick={ handleProductAction }
          variant="secondary"
        >
          { __( 'Product action!', 'woocommerce' ) }
        </Button>
      </SectionActions>

      <OtherBlockComponents { ...other} />
    </>
  );
}
```
