Tag
===

This component can be used to show an item styled as a "tag", optionally with an `X` + "remove". Generally this is used in a collection of selected items, see the Search component.

## Usage

```jsx
import { Tag } from '@woocommerce/components';

class MyComponent extends Component {
	render() {
		return (
			<Tag label="My Tag" id={ 1 } />
		);
	}
}
```

- `id` (required): The ID for this item, used in the remove function.
- `label` (required): The name for this item, displayed as the tag's text.
- `remove`: A function called when the remove X is clicked. If not used, no X icon will display.
- `removeLabel`: The label for removing this item (shown when hovering on X, or read to screen reader users). Defaults to "Remove tag".
- `screenReaderLabel`: A more descriptive label for screen reader users. Defaults to the `name` prop.
