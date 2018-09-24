```jsx
import { EllipsisMenu, MenuItem, MenuTitle } from '@woocommerce/components';

const MyEllipsisMenu = withState( {
	showCustomers: true,
	showOrders: true,
} )( ( { setState, showCustomers, showOrders } ) => (
	<EllipsisMenu label="Choose which analytics to display">
		<MenuTitle>Display Stats</MenuTitle>
		<MenuItem onInvoke={ () => setState( { showCustomers: ! showCustomers } ) }>
			<ToggleControl
				label="Show Customers"
				checked={ showCustomers }
				onChange={ () => setState( { showCustomers: ! showCustomers } ) }
			/>
		</MenuItem>
		<MenuItem onInvoke={ () => setState( { showOrders: ! showOrders } ) }>
			<ToggleControl
				label="Show Orders"
				checked={ showOrders }
				onChange={ () => setState( { showOrders: ! showOrders } ) }
			/>
		</MenuItem>
	</EllipsisMenu>
) );
```
