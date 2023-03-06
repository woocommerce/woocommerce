/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { acceptWcsTos, getWcsAssets } from '../../wcs-api.js';
import { ShippingBanner } from '../index.js';

jest.mock( '../../wcs-api.js' );

acceptWcsTos.mockReturnValue( Promise.resolve() );

jest.mock( '@woocommerce/tracks' );

const wcsPluginSlug = 'woocommerce-services';

describe( 'Tracking impression in shippingBanner', () => {
	const expectedTrackingData = {
		banner_name: 'wcadmin_install_wcs_prompt',
		jetpack_connected: true,
		jetpack_installed: true,
		wcs_installed: true,
	};

	it( 'should record an event when user sees banner loaded', () => {
		render(
			<ShippingBanner
				isJetpackConnected={ true }
				activePlugins={ [ wcsPluginSlug, 'jetpack' ] }
				itemsCount={ 1 }
				activatePlugins={ jest.fn() }
				installPlugins={ jest.fn() }
				isRequesting={ false }
			/>
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'banner_impression',
			expectedTrackingData
		);
	} );
} );

describe( 'Tracking clicks in shippingBanner', () => {
	const getExpectedTrackingData = ( element, wcsInstalled = true ) => {
		return {
			banner_name: 'wcadmin_install_wcs_prompt',
			jetpack_connected: true,
			jetpack_installed: true,
			wcs_installed: wcsInstalled,
			element,
		};
	};

	it( 'should record an event when user clicks "Create shipping label"', async () => {
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activePlugins={ [ wcsPluginSlug, 'jetpack' ] }
				installPlugins={ jest.fn() }
				activatePlugins={ jest.fn() }
				isRequesting={ false }
				itemsCount={ 1 }
			/>
		);

		userEvent.click(
			getByRole( 'button', { name: 'Create shipping label' } )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'banner_element_clicked',
				getExpectedTrackingData( 'shipping_banner_create_label' )
			)
		);
	} );

	it( 'should record an event when user clicks "WooCommerce Shipping"', async () => {
		// Render the banner without WCS being active.
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activePlugins={ [ 'jetpack' ] }
				installPlugins={ jest.fn() }
				activatePlugins={ jest.fn() }
				isRequesting={ false }
				itemsCount={ 1 }
			/>
		);

		userEvent.click(
			getByRole( 'link', { name: /WooCommerce Shipping/ } )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'banner_element_clicked',
				getExpectedTrackingData(
					'shipping_banner_woocommerce_service_link',
					false
				)
			)
		);
	} );

	it( 'should record an event when user clicks "x" to dismiss the banner', async () => {
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activePlugins={ [ wcsPluginSlug, 'jetpack' ] }
				installPlugins={ jest.fn() }
				activatePlugins={ jest.fn() }
				isRequesting={ false }
				itemsCount={ 1 }
			/>
		);

		userEvent.click(
			getByRole( 'button', { name: 'Close Print Label Banner.' } )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'banner_element_clicked',
				getExpectedTrackingData( 'shipping_banner_dimiss' )
			)
		);
	} );
} );

describe( 'Create shipping label button', () => {
	const installPlugins = jest.fn().mockReturnValue( {
		success: true,
	} );
	const activatePlugins = jest.fn().mockReturnValue( {
		success: true,
	} );
	delete window.location; // jsdom won't allow to rewrite window.location unless deleted first
	window.location = {
		href: 'http://wcship.test/wp-admin/post.php?post=1000&action=edit',
	};

	it( 'should install WooCommerce Shipping when button is clicked', async () => {
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [] }
				installPlugins={ installPlugins }
				isRequesting={ false }
				itemsCount={ 1 }
			/>
		);
		userEvent.click(
			getByRole( 'button', {
				name: 'Create shipping label',
			} )
		);

		await waitFor( () =>
			expect( installPlugins ).toHaveBeenCalledWith( [
				'woocommerce-services',
			] )
		);
	} );

	it( 'should activate WooCommerce Shipping when installation finishes', async () => {
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [] }
				installPlugins={ installPlugins }
				isRequesting={ false }
				itemsCount={ 1 }
			/>
		);
		userEvent.click(
			getByRole( 'button', {
				name: 'Create shipping label',
			} )
		);

		await waitFor( () =>
			expect( activatePlugins ).toHaveBeenCalledWith( [
				'woocommerce-services',
			] )
		);
	} );

	it( 'should perform a request to accept the TOS and get WCS assets to load', async () => {
		getWcsAssets.mockReturnValueOnce( Promise.resolve( {} ) );

		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [ wcsPluginSlug ] }
				installPlugins={ installPlugins }
				isRequesting={ false }
				itemsCount={ 1 }
			/>
		);

		userEvent.click(
			getByRole( 'button', {
				name: 'Create shipping label',
			} )
		);

		await waitFor( () => expect( acceptWcsTos ).toHaveBeenCalled() );
		expect( getWcsAssets ).toHaveBeenCalled();
	} );

	it( 'should load WCS assets when a path is provided', async () => {
		const mockAssets = {
			wc_connect_admin_script: '/path/to/wcs.js',
			wc_connect_admin_style: '/path/to/wcs.css',
		};
		getWcsAssets.mockReturnValueOnce(
			Promise.resolve( {
				assets: mockAssets,
			} )
		);

		const { getByRole } = render(
			<Fragment>
				<div id="woocommerce-order-data" />
				<div id="woocommerce-order-actions" />
				<ShippingBanner
					isJetpackConnected={ true }
					activatePlugins={ activatePlugins }
					activePlugins={ [ wcsPluginSlug, 'jetpack' ] }
					installPlugins={ installPlugins }
					isRequesting={ false }
					itemsCount={ 1 }
				/>
			</Fragment>
		);

		userEvent.click(
			getByRole( 'button', {
				name: 'Create shipping label',
			} )
		);

		// Check that the metaboxes have been created.
		await waitFor( () =>
			expect(
				getByRole( 'heading', { level: 2, name: 'Shipping Label' } )
			).toBeInTheDocument()
		);

		expect(
			getByRole( 'heading', { level: 2, name: 'Shipment Tracking' } )
		).toBeInTheDocument();

		// Check that the script and style elements have been created.
		expect( document.getElementsByTagName( 'script' )[ 0 ].src ).toBe(
			'http://localhost' + mockAssets.wc_connect_admin_script
		);

		expect( document.getElementsByTagName( 'link' )[ 0 ].href ).toBe(
			'http://localhost' + mockAssets.wc_connect_admin_style
		);
	} );

	it( 'should open WCS modal', async () => {
		window.wcsGetAppStoreAsync = jest.fn();
		const getState = jest.fn();
		const dispatch = jest.fn();
		const subscribe = jest.fn();
		window.wcsGetAppStoreAsync.mockReturnValueOnce(
			Promise.resolve( {
				getState,
				dispatch,
				subscribe,
			} )
		);
		getState.mockReturnValueOnce( {
			ui: {
				selectedSiteId: 'SITE_ID',
			},
		} );

		getWcsAssets.mockReturnValueOnce(
			Promise.resolve( {
				assets: {
					// Easy to identify string in our hijacked setter function.
					wc_connect_admin_script: 'wc_connect_admin_script',
					// Empty string to avoid creating a script tag we also have to hijack.
					wc_connect_admin_style: '',
				},
			} )
		);

		// Force the script tag to trigger its onload().
		// Adapted from https://stackoverflow.com/a/49204336.
		// const scriptSrcProperty = window.HTMLScriptElement.prototype.src;
		Object.defineProperty( window.HTMLScriptElement.prototype, 'src', {
			set( src ) {
				if ( src === 'wc_connect_admin_script' ) {
					setTimeout( () => this.onload() );
				}
			},
		} );

		const { getByRole } = render(
			<Fragment>
				<div id="woocommerce-order-data" />
				<div id="woocommerce-order-actions" />
				<div id="woocommerce-admin-print-label" />
				<ShippingBanner
					isJetpackConnected={ true }
					activatePlugins={ activatePlugins }
					activePlugins={ [ wcsPluginSlug, 'jetpack' ] }
					installPlugins={ installPlugins }
					isRequesting={ false }
					itemsCount={ 1 }
				/>
			</Fragment>
		);

		// Initiate the loading of WCS assets on first click.
		userEvent.click(
			getByRole( 'button', {
				name: 'Create shipping label',
			} )
		);

		await waitFor( () =>
			expect( window.wcsGetAppStoreAsync ).toHaveBeenCalledWith(
				'wc-connect-create-shipping-label'
			)
		);
		await waitFor( () => expect( getState ).toHaveBeenCalledTimes( 1 ) );
		await waitFor( () => expect( subscribe ).toHaveBeenCalledTimes( 1 ) );

		expect(
			document.getElementById( 'woocommerce-admin-print-label' )
		).not.toBeVisible();
	} );
} );

describe( 'In the process of installing, activating, loading assets for WooCommerce Service', () => {
	it( 'should show a busy loading state on "Create shipping label" and should disable "Close Print Label Banner"', async () => {
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn() }
				activePlugins={ [ 'jetpack' ] }
				installPlugins={ jest.fn() }
				isRequesting={ true }
				itemsCount={ 1 }
			/>
		);

		expect(
			getByRole( 'button', { name: 'Create shipping label' } )
		).not.toHaveClass( 'is-busy' );

		expect(
			getByRole( 'button', { name: 'Close Print Label Banner.' } )
		).toBeEnabled();

		userEvent.click(
			getByRole( 'button', { name: 'Create shipping label' } )
		);

		await waitFor( () =>
			expect(
				getByRole( 'button', { name: 'Create shipping label' } )
			).toHaveClass( 'is-busy' )
		);

		expect(
			getByRole( 'button', { name: 'Close Print Label Banner.' } )
		).toBeDisabled();
	} );
} );

describe( 'Setup error message', () => {
	it( 'should not show if there is no error (no interaction)', () => {
		const { container } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn() }
				activePlugins={ [ 'jetpack' ] }
				installPlugins={ jest.fn() }
				itemsCount={ 1 }
				isRequesting={ false }
			/>
		);

		expect(
			container.getElementsByClassName(
				'wc-admin-shipping-banner-install-error'
			)
		).toHaveLength( 0 );
	} );

	it( 'should show if there is installation error', async () => {
		const { getByRole, getByText } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn() }
				activePlugins={ [ 'jetpack' ] }
				installPlugins={ jest.fn().mockReturnValue( {
					success: false,
				} ) }
				itemsCount={ 1 }
				isRequesting={ false }
			/>
		);

		userEvent.click(
			getByRole( 'button', { name: 'Create shipping label' } )
		);

		await waitFor( () =>
			expect(
				getByText(
					'Unable to install the plugin. Refresh the page and try again.'
				)
			).toBeInTheDocument()
		);
	} );

	it( 'should show if there is activation error', async () => {
		const { getByRole, getByText } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn().mockReturnValue( {
					success: false,
				} ) }
				activePlugins={ [ 'jetpack' ] }
				installPlugins={ jest.fn().mockReturnValue( {
					success: true,
				} ) }
				itemsCount={ 1 }
				isRequesting={ false }
			/>
		);

		userEvent.click(
			getByRole( 'button', { name: 'Create shipping label' } )
		);

		await waitFor( () =>
			expect(
				getByText(
					'Unable to activate the plugin. Refresh the page and try again.'
				)
			).toBeInTheDocument()
		);
	} );
} );

describe( 'The message in the banner', () => {
	const createShippingBannerWrapper = ( { activePlugins } ) =>
		render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn() }
				activePlugins={ activePlugins }
				installPlugins={ jest.fn() }
				wcsPluginSlug={ 'woocommerce-services' }
				isRequesting={ true }
				itemsCount={ 1 }
			/>
		);

	const notActivatedMessage =
		'By clicking "Create shipping label", WooCommerce Shipping(opens in a new tab) will be installed and you agree to its Terms of Service(opens in a new tab).';
	const activatedMessage =
		'You\'ve already installed WooCommerce Shipping. By clicking "Create shipping label", you agree to its Terms of Service(opens in a new tab).';

	it( 'should show install text "By clicking "Create shipping label"..." when first loaded.', () => {
		const { container } = createShippingBannerWrapper( {
			activePlugins: [],
		} );

		expect(
			container.querySelector( '.wc-admin-shipping-banner-blob p' )
				.textContent
		).toBe( notActivatedMessage );
	} );

	it( 'should continue to show the initial message "By clicking "Create shipping label"..." after WooCommerce Service is installed successfully.', () => {
		const { container, rerender } = createShippingBannerWrapper( {
			activePlugins: [],
		} );

		rerender(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn() }
				activePlugins={ [ 'woocommerce-services' ] }
				installPlugins={ jest.fn() }
				wcsPluginSlug={ 'woocommerce-services' }
				isRequesting={ true }
				itemsCount={ 1 }
			/>
		);

		expect(
			container.querySelector( '.wc-admin-shipping-banner-blob p' )
				.textContent
		).toBe( notActivatedMessage );
	} );

	it( 'should show install text "By clicking "You\'ve already installed WooCommerce Shipping."..." when WooCommerce Service is already installed.', () => {
		const { container } = createShippingBannerWrapper( {
			activePlugins: [ 'woocommerce-services' ],
		} );

		expect(
			container.querySelector( '.wc-admin-shipping-banner-blob p' )
				.textContent
		).toBe( activatedMessage );
	} );
} );
