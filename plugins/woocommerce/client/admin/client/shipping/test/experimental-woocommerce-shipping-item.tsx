/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
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

	it( 'should record shipping_partner_click when clicking Install button', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				{ ...defaultProps }
				tracking={ {
					context: 'settings',
					country: 'US',
					plugins: 'woocommerce-shipping',
				} }
			/>
		);

		screen.queryByRole( 'button', { name: 'Install' } )?.click();
		expect( recordEvent ).toHaveBeenCalledWith( 'shipping_partner_click', {
			context: 'settings',
			country: 'US',
			plugins: 'woocommerce-shipping',
			selected_plugin: 'woocommerce-shipping',
		} );
	} );

	it( 'should record shipping_partner_click when clicking Activate button', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				{ ...defaultProps }
				tracking={ {
					context: 'settings',
					country: 'US',
					plugins: 'woocommerce-shipping',
				} }
			/>
		);

		screen.queryByRole( 'button', { name: 'Activate' } )?.click();
		expect( recordEvent ).toHaveBeenCalledWith( 'shipping_partner_click', {
			context: 'settings',
			country: 'US',
			plugins: 'woocommerce-shipping',
			selected_plugin: 'woocommerce-shipping',
		} );
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

	it( 'should record shipping_partner_install with success on successful install', async () => {
		const tracking = {
			context: 'settings' as const,
			country: 'US',
			plugins: 'woocommerce-shipping',
		};
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				{ ...defaultProps }
				onInstallClick={ jest.fn( () => Promise.resolve() ) }
				tracking={ tracking }
			/>
		);

		screen.queryByRole( 'button', { name: 'Install' } )?.click();

		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_install',
				{
					context: 'settings',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: true,
				}
			);
		} );
	} );

	it( 'should record shipping_partner_install with failure on failed install', async () => {
		const tracking = {
			context: 'settings' as const,
			country: 'US',
			plugins: 'woocommerce-shipping',
		};
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				{ ...defaultProps }
				onInstallClick={ jest.fn( () => Promise.reject() ) }
				tracking={ tracking }
			/>
		);

		screen.queryByRole( 'button', { name: 'Install' } )?.click();

		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_install',
				{
					context: 'settings',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: false,
				}
			);
		} );
	} );

	it( 'should record shipping_partner_activate with success on successful activation', async () => {
		const tracking = {
			context: 'settings' as const,
			country: 'US',
			plugins: 'woocommerce-shipping',
		};
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				{ ...defaultProps }
				onActivateClick={ jest.fn( () => Promise.resolve() ) }
				tracking={ tracking }
			/>
		);

		screen.queryByRole( 'button', { name: 'Activate' } )?.click();

		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_activate',
				{
					context: 'settings',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: true,
				}
			);
		} );
	} );

	it( 'should record shipping_partner_activate with failure on failed activation', async () => {
		const tracking = {
			context: 'settings' as const,
			country: 'US',
			plugins: 'woocommerce-shipping',
		};
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				{ ...defaultProps }
				onActivateClick={ jest.fn( () => Promise.reject() ) }
				tracking={ tracking }
			/>
		);

		screen.queryByRole( 'button', { name: 'Activate' } )?.click();

		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith(
				'shipping_partner_activate',
				{
					context: 'settings',
					country: 'US',
					plugins: 'woocommerce-shipping',
					selected_plugin: 'woocommerce-shipping',
					success: false,
				}
			);
		} );
	} );
} );
