/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { Icon } from '@wordpress/components';
import { EllipsisMenu, MenuItem, MenuTitle } from '@woocommerce/components';

export default withState( {
	showCustomers: true,
	showOrders: true,
} )( ( { setState, showCustomers, showOrders } ) => (
	<EllipsisMenu
		label="Choose which analytics to display"
		renderContent={ ( { onToggle } ) => (
			<Fragment>
				<MenuTitle>Display Stats</MenuTitle>
				<MenuItem
					isCheckbox
					isClickable
					checked={ showCustomers }
					onInvoke={ () =>
						setState( { showCustomers: ! showCustomers } )
					}
				>
					Show Customers
				</MenuItem>
				<MenuItem
					isCheckbox
					isClickable
					checked={ showOrders }
					onInvoke={ () => setState( { showOrders: ! showOrders } ) }
				>
					Show Orders
				</MenuItem>
				<MenuItem isClickable onInvoke={ onToggle }>
					<Icon icon="no-alt" />
					Close Menu
				</MenuItem>
			</Fragment>
		) }
	/>
) );
