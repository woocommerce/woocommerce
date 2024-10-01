/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { default as Tools } from '../tools';
import { default as Options } from '../options';
import { default as Experiments } from '../experiments';
import { default as Features } from '../features';
import { default as RestAPIFilters } from '../rest-api-filters';
import RemoteInboxNotifications from '../remote-inbox-notifications';
import RemoteLogging from '../remote-logging';
import Payments from '../payments';

const tabs = applyFilters( 'woocommerce_admin_test_helper_tabs', [
	{
		name: 'options',
		title: 'Options',
		content: <Options />,
	},
	{
		name: 'tools',
		title: 'Tools',
		content: <Tools />,
	},
	{
		name: 'experiments',
		title: 'Experiments',
		content: <Experiments />,
	},
	{
		name: 'features',
		title: 'Features',
		content: <Features />,
	},
	{
		name: 'rest-api-filters',
		title: 'REST API FIlters',
		content: <RestAPIFilters />,
	},
	{
		name: 'remote-inbox-notifications',
		title: 'Remote Inbox Notifications',
		content: <RemoteInboxNotifications />,
	},
	{
		name: 'remote-logging',
		title: 'Remote Logging',
		content: <RemoteLogging />,
	},
	{
		name: 'woocommerce-payments',
		title: 'WCPay',
		content: <Payments />,
	},
] );

export function App() {
	return (
		<div className="wrap">
			<h1>WooCommerce Admin Test Helper</h1>
			<TabPanel
				className="woocommerce-admin-test-helper__main-tab-panel"
				activeClass="active-tab"
				tabs={ tabs }
				initialTabName={ tabs[ 0 ].name }
			>
				{ ( tab ) => (
					<>
						{ tab.content }
						{ applyFilters(
							`woocommerce_admin_test_helper_tab_${ tab.name }`,
							[]
						) }
					</>
				) }
			</TabPanel>
		</div>
	);
}
