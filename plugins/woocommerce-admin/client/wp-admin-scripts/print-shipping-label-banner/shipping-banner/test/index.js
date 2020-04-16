/**
 * External dependencies
 */
import { shallow } from 'enzyme';
import { recordEvent } from 'lib/tracks';
import { ExternalLink, Button } from '@wordpress/components';
/**
 * Internal dependencies
 */

jest.mock( '../../wcs-api.js' );
import { acceptWcsTos, getWcsAssets } from '../../wcs-api.js';
import { setupErrorTypes } from '../../setup-notice';

acceptWcsTos.mockReturnValue( Promise.resolve() );
const wcsAssetsMock = {};
getWcsAssets.mockReturnValue( Promise.resolve( wcsAssetsMock ) );

import { ShippingBanner } from '../index.js';

jest.mock( 'lib/tracks' );
jest.mock( '@woocommerce/wc-admin-settings' );

describe( 'Tracking impression in shippingBanner', () => {
	const expectedTrackingData = {
		banner_name: 'wcadmin_install_wcs_prompt',
		jetpack_connected: true,
		jetpack_installed: true,
		wcs_installed: true,
	};
	const wcsPluginSlug = 'woocommerce-services';

	beforeEach( () => {
		shallow(
			<ShippingBanner
				isJetpackConnected={ true }
				activePlugins={ [ wcsPluginSlug, 'jetpack' ] }
				itemsCount={ 1 }
				activatePlugins={ jest.fn() }
				installPlugin={ jest.fn() }
				isRequesting={ false }
			/>
		);
	} );

	it( 'should record an event when user sees banner loaded', () => {
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'banner_impression',
			expectedTrackingData
		);
	} );
} );

describe( 'Tracking clicks in shippingBanner', () => {
	const isJetpackConnected = true;
	let shippingBannerWrapper;

	const getExpectedTrackingData = ( element ) => {
		return {
			banner_name: 'wcadmin_install_wcs_prompt',
			jetpack_connected: true,
			jetpack_installed: true,
			wcs_installed: true,
			element,
		};
	};
	const wcsPluginSlug = 'woocommerce-services';

	beforeEach( () => {
		shippingBannerWrapper = shallow(
			<ShippingBanner
				isJetpackConnected={ isJetpackConnected }
				activePlugins={ [ wcsPluginSlug, 'jetpack' ] }
				installPlugin={ jest.fn() }
				activatePlugins={ jest.fn() }
				isRequesting={ false }
				itemsCount={ 1 }
			/>
		);
	} );

	it( 'should record an event when user clicks "Create shipping label"', () => {
		const createShippingLabelButton = shippingBannerWrapper.find( Button );
		expect( createShippingLabelButton.length ).toBe( 1 );
		createShippingLabelButton.simulate( 'click' );
		expect( recordEvent ).toHaveBeenCalledWith(
			'banner_element_clicked',
			getExpectedTrackingData( 'shipping_banner_create_label' )
		);
	} );

	it( 'should record an event when user clicks "WooCommerce Service"', () => {
		const links = shippingBannerWrapper.find( ExternalLink );
		expect( links.length ).toBe( 1 );
		const wcsLink = links.first();
		wcsLink.simulate( 'click' );
		expect( recordEvent ).toHaveBeenCalledWith(
			'banner_impression',
			getExpectedTrackingData()
		);
	} );

	it( 'should record an event when user clicks "x" to dismiss the banner', () => {
		const noticeDimissButton = shippingBannerWrapper.find(
			'.notice-dismiss'
		);
		expect( noticeDimissButton.length ).toBe( 1 );
		noticeDimissButton.simulate( 'click' );
		expect( recordEvent ).toHaveBeenCalledWith(
			'banner_element_clicked',
			getExpectedTrackingData( 'shipping_banner_dimiss' )
		);
	} );
} );

describe( 'Create shipping label button', () => {
	let shippingBannerWrapper;
	const installPlugin = jest.fn().mockReturnValue( {
		status: 'success',
	} );
	const activatePlugins = jest.fn().mockReturnValue( {
		status: 'success',
	} );
	delete window.location; // jsdom won't allow to rewrite window.location unless deleted first
	window.location = {
		href: 'http://wcship.test/wp-admin/post.php?post=1000&action=edit',
	};

	beforeEach( () => {
		shippingBannerWrapper = shallow(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [] }
				installPlugin={ installPlugin }
				isRequesting={ false }
				itemsCount={ 1 }
			/>
		);
	} );

	it( 'should install WooCommerce Shipping when button is clicked', () => {
		const createShippingLabelButton = shippingBannerWrapper.find( Button );
		expect( createShippingLabelButton.length ).toBe( 1 );
		createShippingLabelButton.simulate( 'click' );
		expect( installPlugin ).toHaveBeenCalledWith( 'woocommerce-services' );
	} );

	it( 'should activate WooCommerce Shipping when installation finishes', () => {
		const createShippingLabelButton = shippingBannerWrapper.find( Button );
		expect( createShippingLabelButton.length ).toBe( 1 );
		createShippingLabelButton.simulate( 'click' );

		expect( activatePlugins ).toHaveBeenCalledWith( [
			'woocommerce-services',
		] );
	} );

	it( 'should perform a request to accept the TOS and get WCS assets to load', async () => {
		const loadWcsAssetsMock = jest.fn();
		shippingBannerWrapper.instance().loadWcsAssets = loadWcsAssetsMock;

		await shippingBannerWrapper.instance().acceptTosAndGetWCSAssets();

		expect( acceptWcsTos ).toHaveBeenCalled();

		expect( getWcsAssets ).toHaveBeenCalled();

		expect( loadWcsAssetsMock ).toHaveBeenCalledWith( wcsAssetsMock );
	} );

	it( 'should load WCS assets when a path is provided', () => {
		const scriptMock = {};
		const linkMock = {};
		const divMock = { dataset: { args: null } };
		const createElementMockReturn = {
			div: divMock,
			script: scriptMock,
			link: linkMock,
		};

		window.jQuery = jest.fn();
		window.jQuery.mockReturnValue( {
			sortable: jest.fn(),
			hide: jest.fn(),
		} );

		const createElementMock = jest.fn( ( tagName ) => {
			return createElementMockReturn[ tagName ];
		} );
		const createElement = document.createElement;
		document.createElement = createElementMock;

		const getElementsByTagNameMock = jest.fn();
		const headMock = {
			appendChild: jest.fn(),
		};
		getElementsByTagNameMock.mockReturnValueOnce( [ headMock ] );
		const getElementsByTagName = document.getElementsByTagName;
		document.getElementsByTagName = getElementsByTagNameMock;
		const getElementByIdMock = jest.fn();
		getElementByIdMock.mockReturnValue( {
			insertAdjacentHTML: jest.fn(),
		} );
		const getElementById = document.getElementById;
		document.getElementById = getElementByIdMock;

		const appendChildMock = jest.fn();
		const appendChild = document.body.appendChild;
		document.body.appendChild = appendChildMock;

		const openWcsModalMock = jest.fn();
		shippingBannerWrapper.instance().openWcsModal = openWcsModalMock;

		shippingBannerWrapper.instance().loadWcsAssets( {
			assets: {
				wc_connect_admin_script: '/path/to/wcs.js',
				wc_connect_admin_style: '/path/to/wcs.css',
			},
		} );

		expect( createElementMock ).toHaveBeenCalledWith( 'script' );
		expect( createElementMock ).toHaveNthReturnedWith( 1, scriptMock );
		expect( scriptMock.async ).toEqual( true );
		expect( scriptMock.src ).toEqual( '/path/to/wcs.js' );
		expect( appendChildMock ).toHaveBeenCalledWith( scriptMock );

		expect( getElementsByTagNameMock ).toHaveBeenCalledWith( 'head' );
		expect( getElementsByTagNameMock ).toHaveReturnedWith( [ headMock ] );
		expect( createElementMock ).toHaveBeenCalledWith( 'link' );
		expect( createElementMock ).toHaveNthReturnedWith( 2, linkMock );
		expect( linkMock.rel ).toEqual( 'stylesheet' );
		expect( linkMock.type ).toEqual( 'text/css' );
		expect( linkMock.href ).toEqual( '/path/to/wcs.css' );
		expect( linkMock.media ).toEqual( 'all' );

		document.createElement = createElement;
		document.getElementById = getElementById;
		document.body.appendChild = appendChild;
		document.getElementsByTagName = getElementsByTagName;
	} );

	it( 'should open WCS modal', () => {
		window.wcsGetAppStore = jest.fn();
		const getState = jest.fn();
		const dispatch = jest.fn();
		const subscribe = jest.fn();
		window.wcsGetAppStore.mockReturnValueOnce( {
			getState,
			dispatch,
			subscribe,
		} );
		getState.mockReturnValueOnce( {
			ui: {
				selectedSiteId: 'SITE_ID',
			},
		} );

		const getElementByIdMock = jest.fn();
		getElementByIdMock.mockReturnValue( {
			style: { display: null },
		} );
		const getElementById = document.getElementById;
		document.getElementById = getElementByIdMock;

		shippingBannerWrapper.instance().openWcsModal();
		expect( window.wcsGetAppStore ).toHaveBeenCalledWith(
			'wc-connect-create-shipping-label'
		);
		expect( getState ).toHaveBeenCalledTimes( 1 );
		expect( subscribe ).toHaveBeenCalledTimes( 1 );
		expect( getElementByIdMock ).toHaveBeenCalledWith(
			'woocommerce-admin-print-label'
		);

		document.getElementById = getElementById;
	} );
} );

describe( 'In the process of installing, activating, loading assets for WooCommerce Service', () => {
	let shippingBannerWrapper;

	beforeEach( () => {
		shippingBannerWrapper = shallow(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn() }
				activePlugins={ [ 'jetpack', 'woocommerce-services' ] }
				installPlugin={ jest.fn() }
				isRequesting={ true }
				itemsCount={ 1 }
			/>
		);
	} );

	it( 'should show a busy loading state on "Create shipping label"', () => {
		shippingBannerWrapper.setState( { isShippingLabelButtonBusy: true } );
		const createShippingLabelButton = shippingBannerWrapper.find( Button );
		expect( createShippingLabelButton.length ).toBe( 1 );
		expect( createShippingLabelButton.prop( 'disabled' ) ).toBe( true );
		expect( createShippingLabelButton.prop( 'isBusy' ) ).toBe( true );
	} );

	it( 'should disable the dismiss button ', () => {
		shippingBannerWrapper.setState( { isShippingLabelButtonBusy: true } );
		const dismissButton = shippingBannerWrapper.find( '.notice-dismiss' );
		expect( dismissButton.length ).toBe( 1 );
		expect( dismissButton.prop( 'disabled' ) ).toBe( true );
	} );
} );

describe( 'Setup error message', () => {
	let shippingBannerWrapper;
	const activatePlugins = jest.fn().mockReturnValue( {
		status: 'failed',
	} );
	const installPlugin = jest.fn().mockReturnValue( {
		status: 'failed',
	} );

	beforeEach( () => {
		shippingBannerWrapper = shallow(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [ 'jetpack' ] }
				installPlugin={ installPlugin }
				itemsCount={ 1 }
				isRequesting={ false }
			/>
		);
	} );

	it( 'should not show if there is no error', () => {
		expect( shippingBannerWrapper.instance().isSetupError() ).toBe( false );
	} );

	it( 'should show if there is installation error', async () => {
		await shippingBannerWrapper
			.instance()
			.installAndActivatePlugins( 'woocommerce-services' );

		expect( shippingBannerWrapper.instance().isSetupError() ).toBe( true );
		expect( shippingBannerWrapper.instance().state.setupErrorReason ).toBe(
			setupErrorTypes.INSTALL
		);
	} );

	it( 'should show if there is activation error', async () => {
		// Create a new wrapper with an installPlugin that passes.
		const wrapper = shallow(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ activatePlugins }
				activePlugins={ [ 'jetpack' ] }
				installPlugin={ jest.fn().mockReturnValue( {
					status: 'success',
				} ) }
				itemsCount={ 1 }
				isRequesting={ false }
			/>
		);

		await wrapper
			.instance()
			.installAndActivatePlugins( 'woocommerce-services' );

		expect( wrapper.instance().isSetupError() ).toBe( true );
		expect( wrapper.instance().state.setupErrorReason ).toBe(
			setupErrorTypes.ACTIVATE
		);
	} );
} );

describe( 'The message in the banner', () => {
	const createShippingBannerWrapper = ( { activePlugins } ) =>
		shallow(
			<ShippingBanner
				isJetpackConnected={ true }
				activatePlugins={ jest.fn() }
				activePlugins={ activePlugins }
				installPlugin={ jest.fn() }
				wcsPluginSlug={ 'woocommerce-services' }
				isRequesting={ true }
				itemsCount={ 1 }
			/>
		);

	const notActivatedMessage =
		'By clicking "Create shipping label", WooCommerce Shipping will be installed and you agree to its Terms of Service.';
	const activatedMessage =
		'You\'ve already installed WooCommerce Shipping. By clicking "Create shipping label", you agree to its Terms of Service.';

	it( 'should show install text "By clicking "Create shipping label"..." when first loaded.', () => {
		const shippingBannerWrapper = createShippingBannerWrapper( {
			activePlugins: [],
		} );
		const createShippingLabelText = shippingBannerWrapper.find( 'p' );
		expect( createShippingLabelText.text() ).toBe( notActivatedMessage );
	} );

	it( 'should continue to show the initial message "By clicking "Create shipping label"..." after WooCommerce Service is installed successfully.', () => {
		const shippingBannerWrapper = createShippingBannerWrapper( {
			activePlugins: [],
		} );
		// Mock installation and activation successful
		shippingBannerWrapper.setProps( {
			activePlugins: [ 'woocommerce-services' ],
		} );
		const createShippingLabelText = shippingBannerWrapper.find( 'p' );
		expect( createShippingLabelText.text() ).toBe( notActivatedMessage );
	} );

	it( 'should continue to show the initial message "By clicking "Create shipping label"..." after WooCommerce Service is installed and activated successfully.', () => {
		const shippingBannerWrapper = createShippingBannerWrapper( {
			activePlugins: [],
		} );
		// Mock installation and activation successful
		shippingBannerWrapper.setProps( {
			activePlugins: [ 'woocommerce-services' ],
		} );
		const createShippingLabelText = shippingBannerWrapper.find( 'p' );
		expect( createShippingLabelText.text() ).toBe( notActivatedMessage );
	} );

	it( 'should show install text "By clicking "You\'ve already installed WooCommerce Shipping."..." when WooCommerce Service is already installed.', () => {
		const shippingBannerWrapper = createShippingBannerWrapper( {
			activePlugins: [ 'woocommerce-services' ],
		} );
		const createShippingLabelText = shippingBannerWrapper.find( 'p' );
		expect( createShippingLabelText.text() ).toBe( activatedMessage );
	} );
} );
