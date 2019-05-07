```jsx
import { EllipsisMenu, MenuItem, MenuTitle, Button } from '@woocommerce/components';

const MyEllipsisMenu = withState( {
	showCustomers: true,
	showOrders: true,
} )( ( { setState, showCustomers, showOrders } ) => (
	<EllipsisMenu label="Choose which analytics to display"
		renderContent={ ( { onToggle } )=> {
			return (
				<div>
					<MenuTitle>Display Stats</MenuTitle>
					<MenuItem onInvoke={ () => setState( { showCustomers: ! showCustomers } )  }>
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
					<MenuItem onInvoke={ onToggle }>
						<Button
							label="Close menu"
							onClick={ onToggle }
						>
						Close Menu
						</Button>
					</MenuItem>
				</div>
			);
		} }
	/>
) );
```
