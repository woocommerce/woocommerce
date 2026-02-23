/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import WooCommerceShippingItem from '../experimental-woocommerce-shipping-item';
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
} ) );
jest.mock( '@woocommerce/tracks', () => ( {
	...jest.requireActual( '@woocommerce/tracks' ),
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/admin-layout', () => {
	const mockContext = {
		layoutPath: [ 'root' ],
		layoutString: 'root',
		extendLayout: () => {},
		isDescendantOf: () => false,
	};
	return {
		...jest.requireActual( '@woocommerce/admin-layout' ),
		useLayoutContext: jest.fn().mockReturnValue( mockContext ),
		useExtendLayout: jest.fn().mockReturnValue( mockContext ),
	};
} );

describe( 'WooCommerceShippingItem', () => {
	const defaultProps = {
		pluginsBeingSetup: [] as string[],
		onInstallClick: jest.fn( () => Promise.resolve() ),
		onActivateClick: jest.fn( () => Promise.resolve() ),
	};

	beforeEach( () => {
		( useDispatch as jest.Mock ).mockReturnValue( {
			createSuccessNotice: jest.fn(),
		} );
	} );

	it( 'should render WC Shipping item with CTA = "Install" when WC Shipping is not installed', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				{ ...defaultProps }
			/>
		);

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();

		expect(
			screen.queryByRole( 'button', { name: 'Install' } )
		).toBeInTheDocument();
	} );

	it( 'should render WC Shipping item with CTA = "Activate" when WC Shipping is installed', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				{ ...defaultProps }
			/>
		);

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();

		expect(
			screen.queryByRole( 'button', { name: 'Activate' } )
		).toBeInTheDocument();
	} );

	it( 'should call onInstallClick when clicking Install button', () => {
		const onInstallClick = jest.fn( () => Promise.resolve() );
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				pluginsBeingSetup={ [] }
				onInstallClick={ onInstallClick }
				onActivateClick={ jest.fn( () => Promise.resolve() ) }
			/>
		);

		screen.queryByRole( 'button', { name: 'Install' } )?.click();
		expect( onInstallClick ).toHaveBeenCalledWith( [
			'woocommerce-shipping',
		] );
	} );

	it( 'should record track when clicking Install button', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				{ ...defaultProps }
			/>
		);

		screen.queryByRole( 'button', { name: 'Install' } )?.click();
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_shipping_recommendation_setup_click',
			{
				plugin: 'woocommerce-shipping',
				action: 'install',
			}
		);
	} );

	it( 'should record track when clicking Activate button', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				{ ...defaultProps }
			/>
		);

		screen.queryByRole( 'button', { name: 'Activate' } )?.click();
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_shipping_recommendation_setup_click',
			{
				plugin: 'woocommerce-shipping',
				action: 'activate',
			}
		);
	} );

	it( 'should call onActivateClick when clicking Activate button', () => {
		const onActivateClick = jest.fn( () => Promise.resolve() );
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				pluginsBeingSetup={ [] }
				onInstallClick={ jest.fn( () => Promise.resolve() ) }
				onActivateClick={ onActivateClick }
			/>
		);

		screen.queryByRole( 'button', { name: 'Activate' } )?.click();
		expect( onActivateClick ).toHaveBeenCalledWith( [
			'woocommerce-shipping',
		] );
	} );
} );
