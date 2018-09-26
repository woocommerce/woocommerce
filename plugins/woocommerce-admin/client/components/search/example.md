```jsx
import { Search } from '@woocommerce/components';

const MySearch = withState( {
	selected: [],
} )( ( { selected, setState } ) => (
	<Search
		type="products"
		placeholder="Search for a product"
		selected={ selected }
		onChange={ items => setState( { selected: items } ) }
	/>
) );
```
