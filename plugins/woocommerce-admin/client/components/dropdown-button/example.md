```jsx
import { Dropdown } from '@wordpress/components';
import { DropdownButton } from '@woocommerce/components';

const MyDropdownButton = () => (
	<Dropdown
		renderToggle={ ( { isOpen, onToggle } ) => (
			<DropdownButton
				onClick={ onToggle }
				isOpen={ isOpen }
				labels={ [ 'All Products Sold' ] }
			/>
		) }
		renderContent={ () => (
			<p>Dropdown content here</p>
		) }
	/>
);
```
