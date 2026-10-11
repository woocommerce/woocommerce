/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import { Header } from '../index';

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting() {
		return 'Fake Site Title';
	},
} ) );

// Mock dependencies
jest.mock( '../../launch-your-store', () => ( {
	LaunchYourStoreStatus: () => <div data-testid="launch-your-store-status" />,
	useLaunchYourStore: () => ( {
		isLoading: false,
		launchYourStoreEnabled: true,
	} ),
} ) );

jest.mock( '~/order-attribution-install-banner', () => ( {
	OrderAttributionInstallBanner: () => (
		<div data-testid="order-attribution-install-banner" />
	),
	BANNER_TYPE_HEADER: 'header',
} ) );

jest.mock( '~/hooks/use-tasklists-state', () => ( {
	isTaskListActive: () => false,
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	isWCAdmin: () => true,
	getScreenFromPath: () => 'homescreen',
	getPath: () => '/analytics/overview',
} ) );

global.window.wcNavigation = {};

const encodedBreadcrumb = [
	[ 'admin.php?page=wc-settings', 'Settings' ],
	'Accounts &amp; Privacy',
];

describe( 'Header', () => {
	it( 'should render decoded breadcrumb name', () => {
		const { queryByText } = render(
			<Header sections={ encodedBreadcrumb } query={ {} } />
		);
		expect( queryByText( 'Accounts &amp; Privacy' ) ).toBe( null );
		expect( queryByText( 'Accounts & Privacy' ) ).not.toBe( null );
	} );

	it( 'correctly updates the document title to reflect the navigation state', () => {
		render( <Header sections={ encodedBreadcrumb } query={ {} } /> );

		expect( document.title ).toBe(
			'Accounts & Privacy ‹ Settings ‹ Fake Site Title — WooCommerce'
		);
	} );

	it( 'should render LaunchYourStoreStatus and OrderAttributionInstallBanner only once', () => {
		const { getAllByTestId } = render(
			<Header sections={ encodedBreadcrumb } query={ {} } />
		);

		// Verify that each component is rendered exactly once
		expect( getAllByTestId( 'launch-your-store-status' ) ).toHaveLength(
			1
		);
		expect(
			getAllByTestId( 'order-attribution-install-banner' )
		).toHaveLength( 1 );
	} );
} );
