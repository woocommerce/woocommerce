/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { getQueryArg, addQueryArgs } from '@wordpress/url';
import { useEffect, useState } from '@wordpress/element';

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

// Helper function to validate tab name and return the tab
const getTabByName = ( tabName, availableTabs ) => {
	return availableTabs.find( ( tab ) => tab.name === tabName );
};

// Get tab from URL or default to first tab
const getActiveTab = () => {
	const tabFromUrl = getQueryArg( window.location.search, 'tab' );
	const tab = getTabByName( tabFromUrl, tabs );
	return tab || tabs[ 0 ];
};

export function App() {
	const [ activeTab, setActiveTab ] = useState( getActiveTab() );

	// Handle tab selection
	const handleTabSelect = ( tabName ) => {
		const tab = getTabByName( tabName, tabs );
		if ( tab.name === activeTab.name ) {
			return;
		}

		setActiveTab( tab );
		// Update URL with new tab
		const newUrl = addQueryArgs( window.location.href, { tab: tab.name } );
		window.history.pushState( { tab: tab.name }, '', newUrl );
	};

	// Handle browser back/forward navigation
	useEffect( () => {
		const handlePopState = () => {
			setActiveTab( getActiveTab() );
		};

		window.addEventListener( 'popstate', handlePopState );

		return () => {
			window.removeEventListener( 'popstate', handlePopState );
		};
	}, [] );

	const tabsWithActiveClass = tabs.map( ( tab ) => ( {
		...tab,
		className: tab.name === activeTab.name ? 'is-active' : '',
	} ) );

	return (
		<div className="wrap">
			<h1>WooCommerce Admin Test Helper</h1>
			<TabPanel
				className="woocommerce-admin-test-helper__main-tab-panel"
				tabs={ tabsWithActiveClass }
				// Tab panel manages its own state, but doesn't apply the active class to the correct tab when navigating back so we don't use it.
				activeClass=""
				initialTabName={ activeTab.name }
				onSelect={ handleTabSelect }
			>
				{ () => (
					<>
						{ activeTab.content }
						{ applyFilters(
							`woocommerce_admin_test_helper_tab_${ activeTab.name }`,
							[]
						) }
					</>
				) }
			</TabPanel>
		</div>
	);
}
