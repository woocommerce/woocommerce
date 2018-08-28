Search
======

A search box which autocompletes results while typing, allowing for the user to select an existing object (product, order, customer, etc). Currently only products are supported.

## Usage

```jsx
import { Search } from '@woocommerce/components';

class MySearchBox extends Component {
	updateLocalValue( results ) {
		// Do whatever with results.
	}
	render() {
		return (
			<Search type="products" onChange={ this.updateLocalValue } />
		);
	}
}
```

- `onChange`: Function called when selected results change, passed result list.
- `type` (required): Which object type to search, can be one of `customers`, `orders`, or `products`.
