/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { Icon } from '@wordpress/icons';
import CrossSmall from 'gridicons/dist/cross-small';
import { EllipsisMenu, MenuItem, MenuTitle } from '@woocommerce/components';

const ExampleEllipsisMenu = () => {
	const [ { showCustomers, showOrders }, setState ] = useState( {
		showCustomers: true,
		showOrders: true,
	} );
	return (
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
							setState( {
								showOrders,
								showCustomers: ! showCustomers,
							} )
						}
					>
						Show Customers
					</MenuItem>
					<MenuItem
						isCheckbox
						isClickable
						checked={ showOrders }
						onInvoke={ () =>
							setState( {
								showCustomers,
								showOrders: ! showOrders,
							} )
						}
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
	);
};

export const Basic = () => <ExampleEllipsisMenu />;

export default {
	title: 'WooCommerce Admin/components/EllipsisMenu',
	component: EllipsisMenu,
};
