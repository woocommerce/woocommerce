/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import {
	acceptWcsTos,
	getWcsAssets,
	getWcsLabelPurchaseConfigs,
} from '../../wcs-api.js';
import { ShippingBanner } from '../index.js';

jest.mock( '../../wcs-api.js' );

acceptWcsTos.mockReturnValue( Promise.resolve() );

jest.mock( '@woocommerce/tracks' );

const wcsPluginSlug = 'woocommerce-shipping';
const wcstPluginSlug = 'woocommerce-services';

describe( 'Tracking impression in shippingBanner', () => {
	const expectedTrackingData = {
		banner_name: 'wcadmin_install_wcs_prompt',
		jetpack_connected: true,
		jetpack_installed: true,
		wcs_installed: false,
	};

	it( 'should record an event when user sees banner loaded', () => {
		render(
			<ShippingBanner
				isJetpackConnected={ true }
				activePlugins={ [ wcstPluginSlug, 'jetpack' ] }
				itemsCount={ 1 }
				activatePlugins={ jest.fn() }
				installPlugins={ jest.fn() }
				isRequesting={ false }
				isWcstCompatible={ true }
				orderId={ 1 }
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
		const actionButtonLabel = 'Create shipping label';
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activePlugins={ [ 'jetpack' ] }
				installPlugins={ jest
					.fn()
					.mockResolvedValue( { success: true } ) }
				activatePlugins={ jest
					.fn()
					.mockResolvedValue( { success: true } ) }
				isRequesting={ false }
				itemsCount={ 1 }
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel={ actionButtonLabel }
			/>
		);

		userEvent.click( getByRole( 'button', { name: actionButtonLabel } ) );

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'banner_element_clicked',
				getExpectedTrackingData( 'shipping_banner_create_label', false )
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
				orderId={ 1 }
				isWcstCompatible={ true }
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
				activePlugins={ [ wcstPluginSlug, 'jetpack' ] }
				installPlugins={ jest.fn() }
				activatePlugins={ jest.fn() }
				isRequesting={ false }
				itemsCount={ 1 }
				orderId={ 1 }
				isWcstCompatible={ true }
			/>
		);

		userEvent.click(
			getByRole( 'button', { name: 'Close Print Label Banner.' } )
		);

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'banner_element_clicked',
				getExpectedTrackingData( 'shipping_banner_dimiss', false )
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
		const actionButtonLabel = 'Create shipping label';

		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [] }
				installPlugins={ installPlugins }
				isRequesting={ false }
				itemsCount={ 1 }
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel={ actionButtonLabel }
			/>
		);
		userEvent.click(
			getByRole( 'button', {
				name: actionButtonLabel,
			} )
		);

		await waitFor( () =>
			expect( installPlugins ).toHaveBeenCalledWith( [
				'woocommerce-shipping',
			] )
		);
	} );

	it( 'should activate WooCommerce Shipping when installation finishes', async () => {
		const actionButtonLabel = 'Create shipping label';
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [] }
				installPlugins={ installPlugins }
				isRequesting={ false }
				itemsCount={ 1 }
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel={ actionButtonLabel }
			/>
		);
		userEvent.click(
			getByRole( 'button', {
				name: actionButtonLabel,
			} )
		);

		await waitFor( () =>
			expect( activatePlugins ).toHaveBeenCalledWith( [
				'woocommerce-shipping',
			] )
		);
	} );

	it( 'should perform a request to accept the TOS and get WCS assets to load', async () => {
		getWcsLabelPurchaseConfigs.mockReturnValueOnce( Promise.resolve( {} ) );
		getWcsAssets.mockReturnValueOnce( Promise.resolve( {} ) );
		const actionButtonLabel = 'Create shipping label';

		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [ wcstPluginSlug ] }
				installPlugins={ installPlugins }
				isRequesting={ false }
				itemsCount={ 1 }
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel={ actionButtonLabel }
			/>
		);

		userEvent.click(
			getByRole( 'button', {
				name: actionButtonLabel,
			} )
		);

		await waitFor( () => expect( acceptWcsTos ).toHaveBeenCalled() );
		expect( getWcsAssets ).toHaveBeenCalled();
	} );

	it( 'should load WCS assets when a path is provided', async () => {
		const actionButtonLabel = 'Create shipping label';
		getWcsLabelPurchaseConfigs.mockReturnValueOnce( Promise.resolve( {} ) );

		const mockAssets = {
			wcshipping_create_label_script: '/path/to/wcs.js',
			wcshipping_create_label_style: '/path/to/wcs.css',
			wcshipping_shipment_tracking_script:
				'/wcshipping_shipment_tracking_script',
			wcshipping_shipment_tracking_style:
				'/wcshipping_shipment_tracking_style',
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
					activePlugins={ [ wcstPluginSlug, 'jetpack' ] }
					installPlugins={ installPlugins }
					isRequesting={ false }
					itemsCount={ 1 }
					orderId={ 1 }
					isWcstCompatible={ true }
					actionButtonLabel={ actionButtonLabel }
				/>
			</Fragment>
		);

		userEvent.click(
			getByRole( 'button', {
				name: actionButtonLabel,
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
		const allScriptSrcs = Array.from(
			document.querySelectorAll( 'script' )
		).map( ( script ) => script.src );

		const allLinkHrefs = Array.from(
			document.querySelectorAll( 'link' )
		).map( ( link ) => link.href );

		expect( allScriptSrcs ).toContain(
			'http://localhost' + mockAssets.wcshipping_create_label_script
		);

		expect( allScriptSrcs ).toContain(
			'http://localhost' + mockAssets.wcshipping_shipment_tracking_script
		);

		expect( allLinkHrefs ).toContain(
			'http://localhost' + mockAssets.wcshipping_create_label_style
		);
		expect( allLinkHrefs ).toContain(
			'http://localhost' + mockAssets.wcshipping_shipment_tracking_style
		);
	} );

	it( 'should open WCS modal', async () => {
		const actionButtonLabel = 'Create shipping label';
		getWcsLabelPurchaseConfigs.mockReturnValueOnce( Promise.resolve( {} ) );
		getWcsAssets.mockReturnValueOnce(
			Promise.resolve( {
				assets: {
					// Easy to identify string in our hijacked setter function.
					wcshipping_create_label_script:
						'wcshipping_create_label_script',
					wcshipping_shipment_tracking_script:
						'wcshipping_create_label_script',
					// Empty string to avoid creating a script tag we also have to hijack.
					wcshipping_create_label_style: '',
					wcshipping_shipment_tracking_style: '',
				},
			} )
		);

		// Force the script tag to trigger its onload().
		// Adapted from https://stackoverflow.com/a/49204336.
		// const scriptSrcProperty = window.HTMLScriptElement.prototype.src;
		Object.defineProperty( window.HTMLScriptElement.prototype, 'src', {
			set( src ) {
				if (
					[
						'wcshipping_create_label_script',
						'wcshipping_shipment_tracking_script',
					].includes( src )
				) {
					setTimeout( () => {
						this.onload();
					}, 1 );
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
					orderId={ 1 }
					isWcstCompatible={ true }
					actionButtonLabel={ actionButtonLabel }
				/>
			</Fragment>
		);

		const openWcsModalSpy = jest.spyOn(
			ShippingBanner.prototype,
			'openWcsModal'
		);

		// Initiate the loading of WCS assets on first click.
		userEvent.click(
			getByRole( 'button', {
				name: actionButtonLabel,
			} )
		);

		await waitFor( () => {
			expect(
				document.getElementById( 'woocommerce-admin-print-label' )
			).not.toBeVisible();
		} );

		expect( openWcsModalSpy ).toHaveBeenCalledTimes( 1 );
	} );
} );

describe( 'In the process of installing, activating, loading assets for WooCommerce Service', () => {
	it( 'should show a busy loading state on "Create shipping label" and should disable "Close Print Label Banner"', async () => {
		const actionButtonLabel = 'Create shipping label';
		const { getByRole } = render(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn() }
				activePlugins={ [ 'jetpack' ] }
				installPlugins={ jest.fn() }
				isRequesting={ true }
				itemsCount={ 1 }
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel={ actionButtonLabel }
			/>
		);

		expect(
			getByRole( 'button', { name: actionButtonLabel } )
		).not.toHaveClass( 'is-busy' );

		expect(
			getByRole( 'button', { name: 'Close Print Label Banner.' } )
		).toBeEnabled();

		userEvent.click( getByRole( 'button', { name: actionButtonLabel } ) );

		await waitFor( () =>
			expect(
				getByRole( 'button', { name: actionButtonLabel } )
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
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel="Create shipping label"
			/>
		);

		expect(
			container.getElementsByClassName(
				'wc-admin-shipping-banner-install-error'
			)
		).toHaveLength( 0 );
	} );

	it( 'should show if there is installation error', async () => {
		const actionButtonLabel = 'Create shipping label';
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
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel={ actionButtonLabel }
			/>
		);

		userEvent.click( getByRole( 'button', { name: actionButtonLabel } ) );

		await waitFor( () =>
			expect(
				getByText(
					'Unable to install the plugin. Refresh the page and try again.'
				)
			).toBeInTheDocument()
		);
	} );

	it( 'should show if there is activation error', async () => {
		const actionButtonLabel = 'Create shipping label';

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
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel={ actionButtonLabel }
			/>
		);

		userEvent.click( getByRole( 'button', { name: actionButtonLabel } ) );

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
				isRequesting={ true }
				itemsCount={ 1 }
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel="Create shipping label"
			/>
		);

	const notActivatedMessage =
		'By clicking "Create shipping label", WooCommerce Shipping(opens in a new tab) will be installed and you agree to its Terms of Service(opens in a new tab).';

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
				activePlugins={ [ wcstPluginSlug ] }
				installPlugins={ jest.fn() }
				isRequesting={ true }
				itemsCount={ 1 }
				orderId={ 1 }
				isWcstCompatible={ true }
				actionButtonLabel="Create shipping label"
			/>
		);

		expect(
			container.querySelector( '.wc-admin-shipping-banner-blob p' )
				.textContent
		).toBe( notActivatedMessage );
	} );
} );

describe( 'If incompatible WCS&T is active', () => {
	const installPlugins = jest.fn().mockReturnValue( {
		success: true,
	} );
	const activatePlugins = jest.fn().mockReturnValue( {
		success: true,
	} );

	beforeEach( () => {
		acceptWcsTos.mockClear();
	} );

	it( 'should install and activate but show an error notice when an incompatible version of WCS&T is installed', async () => {
		const actionButtonLabel = 'Install WooCommerce Shipping';

		const { getByRole, getByText } = render(
			<Fragment>
				<div id="woocommerce-order-data" />
				<div id="woocommerce-order-actions" />
				<div id="woocommerce-admin-print-label" />
				<ShippingBanner
					isJetpackConnected={ true }
					activatePlugins={ activatePlugins }
					activePlugins={ [ wcstPluginSlug, 'jetpack' ] }
					installPlugins={ installPlugins }
					itemsCount={ 1 }
					isRequesting={ false }
					orderId={ 1 }
					isWcstCompatible={ false }
					actionButtonLabel={ actionButtonLabel }
				/>
			</Fragment>
		);

		userEvent.click( getByRole( 'button', { name: actionButtonLabel } ) );

		await waitFor( () => {
			expect( installPlugins ).toHaveBeenCalledWith( [ wcsPluginSlug ] );
		} );
		await waitFor( () => {
			expect( activatePlugins ).toHaveBeenCalledWith( [ wcsPluginSlug ] );
		} );

		await waitFor( () => {
			expect( acceptWcsTos ).not.toHaveBeenCalled();
		} );

		const notice = getByText( ( _, element ) => {
			const hasText = ( node ) =>
				node.textContent ===
				'Please update the WooCommerce Shipping & Tax plugin to the latest version to ensure compatibility with WooCommerce Shipping.';
			const nodeHasText = hasText( element );
			const childrenDontHaveText = Array.from( element.children ).every(
				( child ) => ! hasText( child )
			);
			return nodeHasText && childrenDontHaveText;
		} );

		await waitFor( () => expect( notice ).toBeInTheDocument() );

		// Assert that the "update" link is present
		const updateLink = getByText( /update/i );
		expect( updateLink ).toBeInTheDocument();
		expect( updateLink.tagName ).toBe( 'A' ); // Ensures it's a link
	} );
} );
