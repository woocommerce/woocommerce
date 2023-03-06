# AbbreviatedCard

Use `AbbreviatedCard` to display an abbreviated card element.

## Usage

```jsx
import { Icon, box } from '@wordpress/icons';

<AbbreviatedCard
	href="#"
	icon={ <Icon icon={ page } /> }
	onClick={ () => alert( 'Abbreviated card clicked' ) }
>
	Content
</AbbreviatedCard>;
```

### Props

| Name        | Type      | Default | Description                                                                      |
| ----------- | --------- | ------- | -------------------------------------------------------------------------------- |
| `children`  | ReactNode | `null`  | (required) The children inside the abbreviated card, rendered in the `component` |
| `className` | String    | `null`  | Additional CSS classes                                                           |
| `href`      | String    | `null`  | (required) The resource to link to                                               |
| `icon`      | Element   | `null`  | (required) The element used to represent the icon for this card                  |
| `onClick`   | Function  | `null`  | On click handler called when the component is clicked                            |
| `type`      | String    | `null`  | Type of link                                                                     |
