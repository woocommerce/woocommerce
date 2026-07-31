/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { OrderAttributionInstallBanner } from '../order-attribution-install-banner';
import { useOrderAttributionInstallBanner } from '../use-order-attribution-install-banner';

jest.mock( '../use-order-attribution-install-banner', () => ( {
	useOrderAttributionInstallBanner: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUser: jest.fn(),
} ) );

const mockUseDispatch = useDispatch;
const mockUseUser = useUser;
const mockUseOrderAttributionInstallBanner = useOrderAttributionInstallBanner;

describe( 'OrderAttributionInstallBanner eligibility', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockUseDispatch.mockReturnValue( {
			installAndActivatePlugins: jest.fn(),
		} );
		mockUseOrderAttributionInstallBanner.mockReturnValue( {
			isDismissed: false,
			dismiss: jest.fn(),
			shouldShowBanner: false,
		} );
	} );

	const renderWithCapabilities = ( capabilities ) => {
		const currentUserCan = jest.fn( ( capability ) =>
			capabilities.includes( capability )
		);
		mockUseUser.mockReturnValue( { currentUserCan } );

		const result = render( <OrderAttributionInstallBanner /> );

		return { ...result, currentUserCan };
	};

	it( 'does not resolve promotion data for users without WooCommerce admin access', () => {
		const { container, currentUserCan } = renderWithCapabilities( [
			'install_plugins',
		] );

		expect( container ).toBeEmptyDOMElement();
		expect( currentUserCan ).toHaveBeenCalledWith(
			'edit_others_shop_orders'
		);
		expect( currentUserCan ).toHaveBeenCalledWith( 'manage_woocommerce' );
		expect( mockUseOrderAttributionInstallBanner ).not.toHaveBeenCalled();
	} );

	it( 'does not resolve promotion data for users who cannot install plugins', () => {
		const { container, currentUserCan } = renderWithCapabilities( [
			'manage_woocommerce',
		] );

		expect( container ).toBeEmptyDOMElement();
		expect( currentUserCan ).toHaveBeenCalledWith( 'install_plugins' );
		expect( mockUseOrderAttributionInstallBanner ).not.toHaveBeenCalled();
	} );

	it.each( [ 'manage_woocommerce', 'edit_others_shop_orders' ] )(
		'resolves promotion data for eligible users with %s',
		( woocommerceCapability ) => {
			renderWithCapabilities( [
				woocommerceCapability,
				'install_plugins',
			] );

			expect( mockUseOrderAttributionInstallBanner ).toHaveBeenCalledWith(
				{ isInstalling: false }
			);
		}
	);
} );
