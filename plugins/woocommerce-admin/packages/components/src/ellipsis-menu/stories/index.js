/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { Icon } from '@wordpress/icons';
import CrossSmall from 'gridicons/dist/cross-small';
import { EllipsisMenu, MenuItem, MenuTitle } from '@woocommerce/components';

const ExampleEllipsisMenu = withState( {
	showCustomers: true,
	showOrders: true,
} )( ( { setState, showCustomers, showOrders } ) => (
	<EllipsisMenu
		label="Choose which analytics to display"
		renderContent={ ( { onToggle } ) => (
			<Fragment>
				<MenuTitle>Display stats</MenuTitle>
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
					<Icon icon={ <CrossSmall /> } />
					Close Menu
				</MenuItem>
			</Fragment>
		) }
	/>
) );

export const Basic = () => <ExampleEllipsisMenu />;

export default {
	title: 'WooCommerce Admin/components/EllipsisMenu',
	component: EllipsisMenu,
};
