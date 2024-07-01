# 

The `WooProductMoreMenuItem` slot allows developers to add custom items to the "More" menu
in the WooCommerce Product Editor header.

![Product editor - More Menu](image-product-editor-more-menu-1)

This slot enables the injection of additional menu items, which can be ordered dynamically:

## Example

```jsx
export { __experimentalWooProductMoreMenuItem as WooProductMoreMenuItem } from '@woocommerce/product-editor';

const MyCustomMenuItem = () => (
    <WooProductMoreMenuItem order={ 3 }>
        <button onClick={() => console.log( 'Custom Action' ) }>
            Custom Action
        </button>
    </WooProductMoreMenuItem>
);
```

## Usage

### Fill Component

To add content to the WooProductMoreMenuItem slot, use the Fill component.

```jsx
export { __experimentalWooProductMoreMenuItem as WooProductMoreMenuItem } from '@woocommerce/product-editor';

const CustomMenuItem = () => (
    <WooProductMoreMenuItem order={ 2 }>
        <div>My Custom Menu Item</div>
    </WooProductMoreMenuItem>
);
```

### Slot Component

The Slot component is used to render all fills in the specified slot.

```jsx
import { MoreMenuDropdown } from '@wordpress/interface';
export { __experimentalWooProductMoreMenuItem as WooProductMoreMenuItem } from '@woocommerce/product-editor';

export const MoreMenu = () => {
    return (
        <MoreMenuDropdown
            toggleProps={ { onClick: () => console.log( 'Menu opened' ) } }
            popoverProps={ { className: 'woocommerce-product-header__more-menu' } }
        >
            { ( { onClose } ) => (
                <WooProductMoreMenuItem.Slot fillProps={ { onClose } } />
            ) }
        </MoreMenuDropdown>
    );
};
```

## API

### Properties

#### WooProductMoreMenuItem

* **children** `React.ReactNode`: The content to be rendered inside the fill.
* **order** `number`: The order in which this fill should appear relative to other fills in the same slot. Default is 1.

#### WooProductMoreMenuItem.Slot

* **fillProps** `object`: Additional props to pass to the fill component.
