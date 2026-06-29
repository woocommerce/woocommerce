/**
 * External dependencies
 */
import { act, fireEvent, render, screen, within } from '@testing-library/react';
import { TaskType } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ShippingRecommendation as _ShippingRecommendation } from '../shipping-recommendation';
import { ShippingRecommendationProps, TaskProps } from '../types';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '~/utils/features', () => ( {
	isFeatureEnabled: jest.fn(),
} ) );

const taskProps: TaskProps = {
	onComplete: () => {},
	query: {},
	task: {
		id: 'shipping-recommendation',
	} as TaskType,
};

const defaultProps: ShippingRecommendationProps = {
	activePlugins: [],
	isJetpackConnected: false,
	isResolving: false,
};

const ShippingRecommendation = (
	props: Partial< ShippingRecommendationProps > = {}
) => {
	return (
		<_ShippingRecommendation
			{ ...taskProps }
			{ ...defaultProps }
			{ ...props }
		/>
	);
};

const mockWindowLocation = () => {
	const originalLocation = window.location;
	const mockLocation = {
		href: 'test',
	} as Location;

	Object.defineProperty( window, 'location', {
		configurable: true,
		value: mockLocation,
	} );

	return {
		mockLocation,
		restore: () => {
			Object.defineProperty( window, 'location', {
				configurable: true,
				value: originalLocation,
			} );
		},
	};
};

const startWooShippingSetup = () => {
	fireEvent.click( screen.getByRole( 'button', { name: 'Set up' } ) );
};

describe( 'ShippingRecommendation', () => {
	afterEach( () => {
		jest.clearAllMocks();
		jest.useRealTimers();
	} );

	test( 'starts with the shipping providers hub', () => {
		render( <ShippingRecommendation /> );

		expect(
			screen.getByRole( 'heading', { name: 'Shipping providers' } )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Store setup' ) ).not.toBeInTheDocument();
		expect( screen.getByText( 'Woo Shipping' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Shippo' ) ).toBeInTheDocument();
		expect(
			screen.getAllByRole( 'button', { name: 'Install' } )
		).toHaveLength( 3 );
	} );

	test( 'uses a scoped shipping task page class while mounted', () => {
		document.documentElement.classList.add( 'wp-toolbar' );

		const { unmount } = render( <ShippingRecommendation /> );

		expect( document.body ).toHaveClass(
			'woocommerce-shipping-native-task-page'
		);
		expect( document.body ).not.toHaveClass(
			'woocommerce-shipping-native-full-page'
		);
		expect( document.body ).not.toHaveClass( 'is-wp-toolbar-disabled' );
		expect( document.documentElement ).toHaveClass( 'wp-toolbar' );

		unmount();

		expect( document.body ).not.toHaveClass(
			'woocommerce-shipping-native-task-page'
		);

		document.documentElement.classList.remove( 'wp-toolbar' );
	} );

	test( 'opens the inline setup method choice from Woo Shipping', () => {
		render( <ShippingRecommendation /> );

		startWooShippingSetup();

		expect(
			screen.getByRole( 'heading', {
				name: 'How should Woo set up shipping?',
			} )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Store setup' ) ).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'radio', {
				name: /Start with Woo recommendations/,
			} )
		).toBeChecked();
		expect( screen.getByText( 'Set up manually' ) ).toBeInTheDocument();
	} );

	test( 'shows the prototype breadcrumb in the shipping setup header', () => {
		const { container } = render( <ShippingRecommendation /> );

		startWooShippingSetup();

		expect(
			screen.getByRole( 'button', { name: 'Shipping' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Woo Shipping' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Not set up' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				'Manage zones, rates, labels, packages, and your Woo Shipping connection.'
			)
		).toBeInTheDocument();
		expect(
			container.querySelector( '.shipping-native-prototype-header' )
		).toBeInTheDocument();
	} );

	test( 'moves from setup method to selected regions and review rates', () => {
		render( <ShippingRecommendation /> );

		startWooShippingSetup();
		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );

		expect(
			screen.getByRole( 'heading', {
				name: 'Where you ship',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Countries and regions' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Canada (13)' } )
		).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );

		expect(
			screen.getByRole( 'heading', { name: 'Review rates' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Starting rates based on stores like yours' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Contiguous US' ) ).toBeInTheDocument();
		expect(
			screen.getAllByText( 'Standard shipping' ).length
		).toBeGreaterThan( 0 );
	} );

	test( 'opens the delivery options side panel from review rates', () => {
		render( <ShippingRecommendation /> );

		startWooShippingSetup();
		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );
		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );

		const contiguousZone = screen
			.getByText( 'Contiguous US' )
			.closest( '.shipping-setup-rates-zone' );
		expect( contiguousZone ).not.toBeNull();
		fireEvent.click(
			within( contiguousZone as HTMLElement ).getByRole( 'button', {
				name: 'Edit delivery options',
			} )
		);

		expect(
			screen.getByRole( 'dialog', {
				name: 'Set up delivery options: Contiguous US',
			} )
		).toBeInTheDocument();
		expect(
			screen.getAllByText( 'Standard shipping' ).length
		).toBeGreaterThan( 0 );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save delivery options' } )
		);

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	test( 'simulates connection success and shows the zones and rates hub preview', () => {
		jest.useFakeTimers();
		render( <ShippingRecommendation /> );

		startWooShippingSetup();
		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );
		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );
		fireEvent.click( screen.getByRole( 'button', { name: 'Continue' } ) );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Connect to WordPress.com',
			} )
		);

		expect( screen.getByText( 'Connecting' ) ).toBeInTheDocument();

		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );

		expect(
			screen.getByRole( 'heading', {
				name: 'You’re ready to use shipping!',
			} )
		).toBeInTheDocument();

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Go to view zones and rates' } )
		);

		expect(
			screen.getByRole( 'heading', { name: '5 zones' } )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Store setup' ) ).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: 'Zones & rates',
			} )
		).toBeInTheDocument();
	} );

	test( 'should trigger event tasklist_shipping_recommendation_visit_marketplace_click when clicking the WooCommerce Marketplace link', () => {
		const { restore } = mockWindowLocation();

		render( <ShippingRecommendation /> );

		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'tasklist_shipping_recommendation_visit_marketplace_click',
			{}
		);

		restore();
	} );

	test( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', () => {
		const { isFeatureEnabled } = jest.requireMock( '~/utils/features' );
		( isFeatureEnabled as jest.Mock ).mockReturnValue( true );
		const { mockLocation, restore } = mockWindowLocation();

		render( <ShippingRecommendation /> );

		fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );

		expect( mockLocation.href ).toContain(
			'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=shipping'
		);

		restore();
	} );
} );
