import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

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
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		withSelect: () => ( Component: React.ComponentType ) => Component,
		withDispatch: () => ( Component: React.ComponentType ) => ( props ) => (
			<Component { ...props } createNotice={ vi.fn() } />
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/utils/features', () => {
	const mock = {
		isFeatureEnabled: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'Shipping', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );
	const props = {
		createNotice: vi.fn(),
		settings: {},
		shippingPartners: [],
		activePlugins: [],
		isJetpackConnected: false,
		countryCode: 'US',
		countryName: 'United States',
		isUpdateSettingsRequesting: false,
		onComplete: vi.fn(),
		task: {
			id: 'shipping',
		} as TaskType,
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
		render( <Shipping createNotice={ vi.fn() } { ...props } /> );
		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );
		expect( recordEvent ).toHaveBeenCalledWith(
			'tasklist_shipping_visit_marketplace_click',
			{}
		);
	} );
	it( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', async () => {
		const { isFeatureEnabled } = await import( '~/utils/features' );
		( isFeatureEnabled as Mock ).mockReturnValue( true );
		const mockLocation = {
			href: 'test',
		} as Location;
		mockLocation.href = 'test';
		Object.defineProperty( global.window, 'location', {
			value: mockLocation,
		} );
		render( <Shipping createNotice={ vi.fn() } { ...props } /> );
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
	describe( 'shipping partner impression tracking', () => {
		it( 'should fire shipping_partner_impression when entering label_printing step with partners', () => {
			const shippingPartners = [
				{
					id: 'woocommerce-shipping',
					name: 'WooCommerce Shipping',
					slug: 'woocommerce-shipping',
				},
				{
					id: 'shipstation',
					name: 'ShipStation',
					slug: 'woocommerce-shipstation-integration',
				},
			];
			const component = new Shipping( {
				...props,
				shippingPartners,
			} );

			// Simulate componentDidMount
			component.setState = vi.fn();
			component.state = {
				...component.state,
				step: 'store_location',
			};

			// Simulate stepping to label_printing
			component.componentDidUpdate(
				{
					...props,
					shippingPartners,
				},
				{
					step: 'rates',
				}
			);

			// Should not fire yet because step is store_location, not label_printing
			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_impression',
				expect.anything()
			);

			// Now simulate the step being label_printing
			component.state = {
				...component.state,
				step: 'label_printing',
			};
			component.componentDidUpdate(
				{
					...props,
					shippingPartners,
				},
				{
					step: 'rates',
				}
			);
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_impression',
				{
					context: 'tasklist',
					country: 'US',
					plugins:
						'woocommerce-shipping,woocommerce-shipstation-integration',
				}
			);
		} );
		it( 'should not fire shipping_partner_impression when there are no shipping partners', () => {
			( recordEvent as Mock ).mockClear();
			const component = new Shipping( {
				...props,
				shippingPartners: [],
			} );
			component.setState = vi.fn();
			component.state = {
				...component.state,
				step: 'label_printing',
			};
			component.componentDidUpdate(
				{
					...props,
					shippingPartners: [],
				},
				{
					step: 'rates',
				}
			);
			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_impression',
				expect.anything()
			);
		} );
		it( 'should only fire shipping_partner_impression once', () => {
			( recordEvent as Mock ).mockClear();
			const shippingPartners = [
				{
					id: 'woocommerce-shipping',
					name: 'WooCommerce Shipping',
					slug: 'woocommerce-shipping',
				},
			];
			const component = new Shipping( {
				...props,
				shippingPartners,
			} );
			component.setState = vi.fn();
			component.state = {
				...component.state,
				step: 'label_printing',
			};

			// First transition into label_printing
			component.componentDidUpdate(
				{
					...props,
					shippingPartners,
				},
				{
					step: 'rates',
				}
			);

			// Second transition (e.g., re-entering)
			component.componentDidUpdate(
				{
					...props,
					shippingPartners,
				},
				{
					step: 'rates',
				}
			);
			const impressionCalls = ( recordEvent as Mock ).mock.calls.filter(
				( call ) => call[ 0 ] === 'shipping_partner_impression'
			);
			expect( impressionCalls ).toHaveLength( 1 );
		} );
	} );
	describe( 'recordInstallAndActivateEvents', () => {
		const shippingPartners = [
			{
				id: 'woocommerce-shipping',
				name: 'WooCommerce Shipping',
				slug: 'woocommerce-shipping',
			},
		];
		it( 'should fire both install and activate success events on success', () => {
			( recordEvent as Mock ).mockClear();
			const component = new Shipping( {
				...props,
				shippingPartners,
				installedPlugins: [],
			} );
			component.recordInstallAndActivateEvents(
				'woocommerce-shipping',
				true
			);
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_install',
				{
					context: 'tasklist',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: true,
				}
			);
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_activate',
				{
					context: 'tasklist',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: true,
				}
			);
		} );
		it( 'should fire install failure only when plugin was not installed', () => {
			( recordEvent as Mock ).mockClear();
			const component = new Shipping( {
				...props,
				shippingPartners,
				installedPlugins: [],
			} );
			component.recordInstallAndActivateEvents(
				'woocommerce-shipping',
				false
			);
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_install',
				{
					context: 'tasklist',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: false,
				}
			);
			expect( recordEvent ).not.toHaveBeenCalledWith(
				'shipping_partner_activate',
				expect.anything()
			);
		} );
		it( 'should fire install success and activate failure when plugin was installed but activation failed', () => {
			( recordEvent as Mock ).mockClear();
			const component = new Shipping( {
				...props,
				shippingPartners,
				installedPlugins: [ 'woocommerce-shipping' ],
			} );
			component.recordInstallAndActivateEvents(
				'woocommerce-shipping',
				false
			);
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_install',
				{
					context: 'tasklist',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: true,
				}
			);
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_activate',
				{
					context: 'tasklist',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: false,
				}
			);
		} );
	} );
	describe( 'getShippingPartnerTrackingProps', () => {
		it( 'should return correct tracking props', () => {
			const shippingPartners = [
				{
					id: 'woocommerce-shipping',
					name: 'WooCommerce Shipping',
					slug: 'woocommerce-shipping',
				},
				{
					id: 'shipstation',
					name: 'ShipStation',
					slug: 'woocommerce-shipstation-integration',
				},
			];
			const component = new Shipping( {
				...props,
				shippingPartners,
			} );
			const trackingProps = component.getShippingPartnerTrackingProps();
			expect( trackingProps ).toEqual( {
				context: 'tasklist',
				country: 'US',
				plugins:
					'woocommerce-shipping,woocommerce-shipstation-integration',
			} );
		} );
		it( 'should filter out partners without slugs', () => {
			const shippingPartners = [
				{
					id: 'woocommerce-shipping',
					name: 'WooCommerce Shipping',
					slug: 'woocommerce-shipping',
				},
				{
					id: 'envia',
					name: 'Envia',
					slug: '',
				},
			];
			const component = new Shipping( {
				...props,
				shippingPartners,
			} );
			const trackingProps = component.getShippingPartnerTrackingProps();
			expect( trackingProps ).toEqual( {
				context: 'tasklist',
				country: 'US',
				plugins: 'woocommerce-shipping',
			} );
		} );
	} );
} );
