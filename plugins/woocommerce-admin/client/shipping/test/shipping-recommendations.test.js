/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ShippingRecommendations from '../shipping-recommendations';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );
jest.mock( '../../settings-recommendations/dismissable-list', () => ( {
	DismissableList: ( { children } ) => children,
	DismissableListHeading: ( { children } ) => children,
} ) );
jest.mock( '../../lib/notices', () => ( {
	createNoticesFromResponse: () => null,
} ) );

describe( 'ShippingRecommendations', () => {
	beforeEach( () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getActivePlugins: () => [],
				isJetpackConnected: () => false,
			} ) )
		);
		useDispatch.mockReturnValue( {
			installAndActivatePlugins: () => Promise.resolve(),
			createSuccessNotice: () => null,
		} );
	} );

	it( 'should not render when WCS is already installed', () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getActivePlugins: () => [ 'woocommerce-services' ],
			} ) )
		);
		render( <ShippingRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when the WooCommerce Shipping plugin is active', () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getActivePlugins: () => [ 'woocommerce-shipping' ],
			} ) )
		);
		render( <ShippingRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
	} );

	it( 'should not render when the WooCommerce Tax plugin is active', () => {
		useSelect.mockImplementation( ( fn ) =>
			fn( () => ( {
				getActivePlugins: () => [ 'woocommerce-shipping' ],
			} ) )
		);
		render( <ShippingRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).not.toBeInTheDocument();
	} );

	it( 'should render WCS when not installed', () => {
		render( <ShippingRecommendations /> );

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();
	} );

	it( 'allows to install WCS', async () => {
		const installAndActivatePluginsMock = jest
			.fn()
			.mockResolvedValue( undefined );
		const successNoticeMock = jest.fn();
		useDispatch.mockReturnValue( {
			installAndActivatePlugins: installAndActivatePluginsMock,
			isJetpackConnected: () => false,
			createSuccessNotice: successNoticeMock,
		} );
		render( <ShippingRecommendations /> );

		expect( installAndActivatePluginsMock ).not.toHaveBeenCalled();
		expect( successNoticeMock ).not.toHaveBeenCalled();

		userEvent.click( screen.getByText( 'Get started' ) );

		expect( installAndActivatePluginsMock ).toHaveBeenCalled();
		await waitFor( () => {
			expect( successNoticeMock ).toHaveBeenCalledWith(
				'🎉 WooCommerce Shipping is installed!',
				expect.anything()
			);
		} );
	} );
} );
