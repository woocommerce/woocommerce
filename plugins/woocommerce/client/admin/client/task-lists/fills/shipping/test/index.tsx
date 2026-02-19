/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import { TaskType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Shipping, hasInstallableSlug } from '../index';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	withSelect: () => ( Component: React.ComponentType ) => Component,
	withDispatch: () => ( Component: React.ComponentType ) => Component,
} ) );

jest.mock( '~/utils/features', () => ( {
	isFeatureEnabled: jest.fn(),
} ) );

describe( 'Shipping', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	const props = {
		settings: {},
		shippingPartners: [],
		activePlugins: [],
		isJetpackConnected: false,
		countryCode: 'US',
		countryName: 'United States',
		isUpdateSettingsRequesting: false,
		onComplete: jest.fn(),
		task: { id: 'shipping' } as TaskType,
	};

	const usShippingPartner = {
		id: 'woocommerce-shipping',
		name: 'WooCommerce Shipping',
		slug: 'woocommerce-shipping',
	};

	const chileShippingPartner = {
		id: 'envia',
		name: 'Envia',
		slug: '',
	};

	it( 'should trigger event tasklist_shipping_visit_marketplace_click when clicking the WooCommerce Marketplace link', () => {
		render( <Shipping { ...props } /> );

		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'tasklist_shipping_visit_marketplace_click',
			{}
		);
	} );

	it( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', async () => {
		const { isFeatureEnabled } = jest.requireMock( '~/utils/features' );
		( isFeatureEnabled as jest.Mock ).mockReturnValue( true );

		const mockLocation = {
			href: 'test',
		} as Location;

		mockLocation.href = 'test';
		Object.defineProperty( global.window, 'location', {
			value: mockLocation,
		} );

		render( <Shipping { ...props } /> );

		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );

		expect( mockLocation.href ).toContain(
			'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=shipping'
		);
	} );

	it( 'treats US shipping partners with slugs as installable', () => {
		expect( hasInstallableSlug( usShippingPartner ) ).toBe( true );
	} );

	it( 'treats Chile shipping partners without slugs as non-installable', () => {
		expect( hasInstallableSlug( chileShippingPartner ) ).toBe( false );
	} );

	it( 'treats missing slugs as non-installable partners', () => {
		expect( hasInstallableSlug( {} ) ).toBe( false );
	} );
} );
