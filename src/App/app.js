/**
 * External dependencies.
 */
import { TabPanel } from '@wordpress/components';
import { applyFilters, addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
// TODO replace this with the actual controls
// import { Options } from '../Options';
const Options = () => <h2>Options</h2>;
import { AdminNotes } from '../admin-notes';
import { Tools } from '../tools';

const tabs = applyFilters(
	'woocommerce_admin_test_helper_tabs',
	[
		{
			name: 'options',
			title: 'Options',
			content: <Options/>,
		},
		{
			name: 'admin-notes',
			title: 'Admin notes',
			content: <AdminNotes/>,
		},
		{
			name: 'tools',
			title: 'Tools',
			content: <Tools/>,
		},
	]
);

export function App() {
	return (
		<div className="wrap">
			<h1>WooCommerce Admin Test Helper</h1>
			<TabPanel
				className="woocommerce-admin-test-helper__main-tab-panel"
				activeClass="active-tab"
				tabs={ tabs }
				initialTabName={ tabs[0].name }
			>
				{ ( tab ) => (
					<>
						{ tab.content }
						{ applyFilters(
							`woocommerce_admin_test_helper_tab_${tab.name}`,
							[]
						) }
					</>
				) }
			</TabPanel>
		</div>
	);
}
