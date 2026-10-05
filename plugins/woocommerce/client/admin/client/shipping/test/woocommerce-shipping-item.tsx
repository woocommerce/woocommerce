import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import WooCommerceShippingItem from '../woocommerce-shipping-item';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useDispatch: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/tracks', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/tracks' ) ),
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/admin-layout', async () => {
	const mockContext = {
		layoutPath: [ 'root' ],
		layoutString: 'root',
		extendLayout: () => {},
		isDescendantOf: () => false,
	};
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...( await vi.importActual( '@woocommerce/admin-layout' ) ),
		useLayoutContext: vi.fn().mockReturnValue( mockContext ),
		useExtendLayout: vi.fn().mockReturnValue( mockContext ),
	} );
} );
describe( 'WooCommerceShippingItem', () => {
	const defaultProps = {
		isPluginActive: false,
		pluginsBeingSetup: [] as string[],
		onInstallClick: vi.fn( () => Promise.resolve() ),
		onActivateClick: vi.fn( () => Promise.resolve() ),
	};
	beforeEach( () => {
		( useDispatch as Mock ).mockReturnValue( {
			createSuccessNotice: vi.fn(),
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
			screen.queryByRole( 'button', {
				name: 'Install',
			} )
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
			screen.queryByRole( 'button', {
				name: 'Activate',
			} )
		).toBeInTheDocument();
	} );
	it( 'should render an "Active" pill instead of a CTA button when WC Shipping is active', () => {
		render(
			<WooCommerceShippingItem
				{ ...defaultProps }
				isPluginInstalled={ true }
				isPluginActive={ true }
			/>
		);
		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Active' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', {
				name: 'Install',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', {
				name: 'Activate',
			} )
		).not.toBeInTheDocument();
	} );
	it( 'should call onInstallClick when clicking Install button', () => {
		const onInstallClick = vi.fn( () => Promise.resolve() );
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				isPluginActive={ false }
				pluginsBeingSetup={ [] }
				onInstallClick={ onInstallClick }
				onActivateClick={ vi.fn( () => Promise.resolve() ) }
			/>
		);
		screen
			.queryByRole( 'button', {
				name: 'Install',
			} )
			?.click();
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
		screen
			.queryByRole( 'button', {
				name: 'Install',
			} )
			?.click();
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
		screen
			.queryByRole( 'button', {
				name: 'Activate',
			} )
			?.click();
		expect( recordEvent ).toHaveBeenCalledWith( 'shipping_partner_click', {
			context: 'settings',
			country: 'US',
			plugins: 'woocommerce-shipping',
			selected_plugin: 'woocommerce-shipping',
		} );
	} );
	it( 'should record settings_shipping_recommendation_setup_click with action=install when clicking Install button', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				{ ...defaultProps }
			/>
		);
		screen
			.queryByRole( 'button', {
				name: 'Install',
			} )
			?.click();
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_shipping_recommendation_setup_click',
			{
				plugin: 'woocommerce-shipping',
				action: 'install',
			}
		);
	} );
	it( 'should record settings_shipping_recommendation_setup_click with action=activate when clicking Activate button', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				{ ...defaultProps }
			/>
		);
		screen
			.queryByRole( 'button', {
				name: 'Activate',
			} )
			?.click();
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_shipping_recommendation_setup_click',
			{
				plugin: 'woocommerce-shipping',
				action: 'activate',
			}
		);
	} );
	it( 'should call onActivateClick when clicking Activate button', () => {
		const onActivateClick = vi.fn( () => Promise.resolve() );
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				isPluginActive={ false }
				pluginsBeingSetup={ [] }
				onInstallClick={ vi.fn( () => Promise.resolve() ) }
				onActivateClick={ onActivateClick }
			/>
		);
		screen
			.queryByRole( 'button', {
				name: 'Activate',
			} )
			?.click();
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
				onInstallClick={ vi.fn( () => Promise.resolve() ) }
				tracking={ tracking }
			/>
		);
		screen
			.queryByRole( 'button', {
				name: 'Install',
			} )
			?.click();
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
				onInstallClick={ vi.fn( () => Promise.reject() ) }
				tracking={ tracking }
			/>
		);
		screen
			.queryByRole( 'button', {
				name: 'Install',
			} )
			?.click();
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
				onActivateClick={ vi.fn( () => Promise.resolve() ) }
				tracking={ tracking }
			/>
		);
		screen
			.queryByRole( 'button', {
				name: 'Activate',
			} )
			?.click();
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
				onActivateClick={ vi.fn( () => Promise.reject() ) }
				tracking={ tracking }
			/>
		);
		screen
			.queryByRole( 'button', {
				name: 'Activate',
			} )
			?.click();
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
