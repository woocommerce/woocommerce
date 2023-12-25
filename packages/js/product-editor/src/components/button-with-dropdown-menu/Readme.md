# ButtonWithDropdownMenu Component

## Description

The `ButtonWithDropdownMenu` is a React component that renders a button with an associated dropdown menu. It provides flexibility in configuring the dropdown's content, appearance, and behavior.

## Usage

```jsx
import { ButtonWithDropdownMenu } from 'path_to_component';

<ButtonWithDropdownMenu
    text="Add to store"
    variant="secondary"
    controls={ [
        {
            title: 'First Menu Item Label',
            onClick: () => {},
        },
        {
            title: 'Second Menu Item Label',
            onClick: () => {},
        },
    ] }
    onButtonClick={() => console.log( 'Button clicked' ) }
/>
```