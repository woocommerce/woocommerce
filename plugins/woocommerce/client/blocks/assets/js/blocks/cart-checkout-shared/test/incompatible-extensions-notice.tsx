/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import {
	getEditorStorageKey,
	getFrontendStorageKey,
	UNSCOPED_STORAGE_KEY,
} from '@woocommerce/editor-components/incompatible-extension-notice/storage';
import { IncompatibleExtensionsFrontendNotice } from '../incompatible-extensions-notice';

// Two sites of one subdirectory multisite. Same origin, so they share the
// browser's localStorage; different home URL, so they must not share a key.
const SITE_A = 'https://example.com/';
const SITE_B = 'https://example.com/site-b/';

let mockHomeUrl = SITE_A;
let mockIsMultisite = false;

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn(),
	CURRENT_USER_IS_ADMIN: true,
	// Getters, not values: the site under test changes between renders.
	get HOME_URL() {
		return mockHomeUrl;
	},
	get IS_MULTISITE() {
		return mockIsMultisite;
	},
} ) );

// Use the real localStorage-backed hook (via its source module) without pulling
// in the heavy `@woocommerce/base-hooks` barrel, so dismissal is exercised
// against real localStorage rather than a mock.
jest.mock( '@woocommerce/base-hooks', () => ( {
	useLocalStorageState: jest.requireActual(
		'../../../base/hooks/use-local-storage-state'
	).useLocalStorageState,
} ) );

jest.mock( '@woocommerce/base-components/notice-banner', () => ( {
	__esModule: true,
	default: ( {
		children,
		onRemove,
		status,
	}: {
		children: React.ReactNode;
		onRemove: () => void;
		status: string;
	} ) => (
		<div data-testid="notice-banner" data-status={ status }>
			{ children }
			<button onClick={ onRemove } data-testid="dismiss-button">
				Dismiss
			</button>
		</div>
	),
} ) );

const mockGetSetting = getSetting as jest.MockedFunction< typeof getSetting >;

// Functions, not constants: the storefront key carries the site, which tests
// change. Both come from the module, so a rename on either is caught here.
const frontendKey = () => getFrontendStorageKey();
const legacyKey = UNSCOPED_STORAGE_KEY;

const storedSlugs = ( key = frontendKey() ) =>
	JSON.parse( window.localStorage.getItem( key ) || '[]' );

const seedFrontend = ( value: unknown, key = frontendKey() ) =>
	window.localStorage.setItem( key, JSON.stringify( value ) );

const seedLegacy = ( value: unknown ) =>
	window.localStorage.setItem( legacyKey, JSON.stringify( value ) );

const setIncompatibleExtensions = (
	extensions: Array< { id: string; title: string } >
) => mockGetSetting.mockReturnValue( extensions );

describe( 'IncompatibleExtensionsFrontendNotice', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.localStorage.clear();
		setIncompatibleExtensions( [] );
		mockHomeUrl = SITE_A;
		mockIsMultisite = false;
	} );

	describe( 'rendering', () => {
		it( 'does not render when there are no incompatible extensions', () => {
			const { container } = render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			expect( container ).toBeEmptyDOMElement();
		} );

		it( 'renders the extension name for checkout', () => {
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			expect( screen.getByTestId( 'notice-banner' ) ).toHaveAttribute(
				'data-status',
				'warning'
			);
			expect(
				screen.getByText(
					/Test Plugin may not be compatible with the Checkout block/
				)
			).toBeInTheDocument();
		} );

		it( 'renders the extension name for cart', () => {
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/cart" />
			);

			expect(
				screen.getByText(
					/Test Plugin may not be compatible with the Cart block/
				)
			).toBeInTheDocument();
		} );

		it( 'renders a list when there are multiple incompatible extensions', () => {
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
				{ id: 'plugin-two', title: 'Plugin Two' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			expect( screen.getByRole( 'list' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Plugin One' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Plugin Two' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'dismissal behavior', () => {
		it( 'hides the banner and records the acknowledged extension on dismiss', () => {
			// Seed a previously acknowledged extension that is no longer
			// incompatible: its acknowledgement has lapsed, so the stored value
			// ends up as exactly what the merchant just accepted.
			seedFrontend( [ 'old-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );

			expect(
				screen.queryByTestId( 'notice-banner' )
			).not.toBeInTheDocument();
			expect( storedSlugs() ).toEqual( [ 'test-plugin' ] );
		} );

		it( 'does not write the editor notice key (no cross-surface collision)', () => {
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );

			expect( window.localStorage.getItem( legacyKey ) ).toBeNull();
			expect(
				window.localStorage.getItem( getEditorStorageKey() )
			).toBeNull();
		} );

		it( 'stays dismissed when an incompatible extension is deactivated', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );
			// Only one of the two acknowledged extensions is still active.
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
			] );

			const { container } = render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			expect( container ).toBeEmptyDOMElement();
		} );

		it( 'stays dismissed while every acknowledged extension is still active', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
				{ id: 'plugin-two', title: 'Plugin Two' },
			] );

			const { container } = render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			expect( container ).toBeEmptyDOMElement();
		} );

		// An acknowledgement lasts only while the extension stays incompatible.
		it( 'warns again when an acknowledged extension is deactivated and reactivated', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );

			// Deactivate plugin-two. The banner stays hidden for plugin-one,
			// and plugin-two's lapsed acknowledgement is dropped.
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
			] );
			const { container, unmount } = render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			expect( container ).toBeEmptyDOMElement();
			expect( storedSlugs() ).toEqual( [ 'plugin-one' ] );
			unmount();

			// Reactivate it: a fresh incompatibility, so the banner returns.
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
				{ id: 'plugin-two', title: 'Plugin Two' },
			] );
			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		// Pruning must only ever remove slugs, never add, or it would silently
		// accept the extension the merchant is being warned about.
		it( 'does not acknowledge a new extension while the banner is on screen', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );
			// plugin-two is gone and a brand-new plugin-three has arrived.
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
				{ id: 'plugin-three', title: 'Plugin Three' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
			expect( storedSlugs() ).toEqual( [ 'plugin-one' ] );
		} );

		it.each( [
			[ 'an object', { 'plugin-one': true } ],
			[ 'a bare string', 'plugin-one' ],
			[ 'a number', 7 ],
			[ 'null', null ],
			[ 'an array of junk', [ 1, null, { a: 1 } ] ],
		] )( 'survives a stored value that is %s', ( _label, value ) => {
			seedFrontend( value );
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			// Nothing readable was acknowledged, so the banner is still owed.
			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		it( 'renders again when a new, never-acknowledged extension appears', () => {
			seedFrontend( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
				{ id: 'new-plugin', title: 'New Plugin' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		it( 'shares dismissal across cart and checkout', () => {
			seedFrontend( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			const { container } = render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/cart" />
			);

			expect( container ).toBeEmptyDOMElement();
		} );
	} );

	// Before this split the storefront banner shared the editor's key, and
	// neither key named a site, so a merchant's dismissal lives under the
	// unscoped key on every site that ran 10.7.0 or later. Without a migration
	// they would all see the banner one more time after upgrading — the exact
	// symptom this component is meant to stop.
	describe( 'migration from the unscoped storage key', () => {
		const renderCheckout = () =>
			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

		it( 'stays dismissed for a merchant who dismissed before the rename', () => {
			seedLegacy( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			const { container } = renderCheckout();

			expect( container ).toBeEmptyDOMElement();
		} );

		// The editor writes `{ [block]: slugs }` objects into the same key and
		// preserves whatever the storefront left there, so a real site can hold
		// both shapes at once.
		it( 'migrates the storefront slugs out of a value the editor also wrote', () => {
			seedLegacy( [
				'test-plugin',
				{ 'woocommerce/checkout': [ 'test-plugin', 'gateway-a' ] },
			] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			const { container } = renderCheckout();

			expect( container ).toBeEmptyDOMElement();
			expect( storedSlugs() ).toEqual( [ 'test-plugin' ] );
		} );

		it( 'still shows the banner for an extension the merchant never acknowledged', () => {
			seedLegacy( [ 'old-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'new-plugin', title: 'New Plugin' },
			] );

			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		it( 'ignores the legacy value once the storefront key exists', () => {
			seedFrontend( [] );
			seedLegacy( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		it( 'migrates nothing from a value only the editor ever wrote', () => {
			seedLegacy( [ { 'woocommerce/checkout': [ 'test-plugin' ] } ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		it.each( [
			[ 'unparseable', 'not json at all' ],
			[
				'an object',
				JSON.stringify( { 'woocommerce/checkout': [ 'a' ] } ),
			],
			[ 'a bare string', JSON.stringify( 'test-plugin' ) ],
			[ 'null', JSON.stringify( null ) ],
		] )(
			'falls back to showing the banner when the legacy value is %s',
			( _label, stored ) => {
				window.localStorage.setItem( legacyKey, stored );
				setIncompatibleExtensions( [
					{ id: 'test-plugin', title: 'Test Plugin' },
				] );

				renderCheckout();

				expect(
					screen.getByTestId( 'notice-banner' )
				).toBeInTheDocument();
			}
		);

		// Leaving the old value intact keeps a revert of this change harmless and
		// keeps the editor notice's own dismissals working.
		it( 'never writes to the legacy key', () => {
			const legacy = JSON.stringify( [
				'test-plugin',
				{ 'woocommerce/checkout': [ 'test-plugin' ] },
			] );
			window.localStorage.setItem( legacyKey, legacy );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
				{ id: 'new-plugin', title: 'New Plugin' },
			] );

			renderCheckout();
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );

			expect( window.localStorage.getItem( legacyKey ) ).toBe( legacy );
		} );

		it( 'adds newly acknowledged slugs to the migrated ones', () => {
			seedLegacy( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
				{ id: 'new-plugin', title: 'New Plugin' },
			] );

			renderCheckout();
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );

			expect( storedSlugs() ).toEqual( [ 'test-plugin', 'new-plugin' ] );
		} );

		it( 'reads the legacy value once per mount, not once per render', () => {
			const getItem = jest.spyOn( Storage.prototype, 'getItem' );
			seedLegacy( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			const legacyReads = () =>
				getItem.mock.calls.filter( ( [ key ] ) => key === legacyKey )
					.length;

			const { rerender } = renderCheckout();
			expect( legacyReads() ).toBe( 1 );

			rerender(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			expect( legacyReads() ).toBe( 1 );

			getItem.mockRestore();
		} );
	} );

	// localStorage is keyed by origin, not by path, so on a subdirectory
	// multisite every site sees the same storage. Without the site in the key, a
	// dismissal on one of them hides another one's live warning.
	describe( 'site scoping', () => {
		const renderCheckout = () =>
			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

		it( 'does not carry a dismissal to another site on the same origin', () => {
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );
			const { unmount } = renderCheckout();
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );
			unmount();

			// Same browser, same origin, different site of the network.
			mockHomeUrl = SITE_B;
			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		// The reviewed revision matched a subset, so site B's single
		// incompatibility counted as covered by site A's larger acknowledgement.
		it( 'does not let a larger acknowledgement elsewhere cover this site', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );

			mockHomeUrl = SITE_B;
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
			] );
			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		it( "keeps each site's acknowledgements in its own key", () => {
			const siteAKey = frontendKey();
			seedFrontend( [ 'plugin-one' ], siteAKey );

			mockHomeUrl = SITE_B;
			setIncompatibleExtensions( [
				{ id: 'plugin-two', title: 'Plugin Two' },
			] );
			renderCheckout();
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );

			expect( frontendKey() ).not.toBe( siteAKey );
			expect( storedSlugs( siteAKey ) ).toEqual( [ 'plugin-one' ] );
			expect( storedSlugs() ).toEqual( [ 'plugin-two' ] );
		} );

		// The unscoped value names no site and every site on the origin sees it,
		// so on a multisite there is no telling whose dismissal it is. Warning
		// once more beats inheriting a dismissal made on a different site.
		it( 'does not migrate the unscoped value on a multisite', () => {
			mockIsMultisite = true;
			seedLegacy( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		// `useLocalStorageState` hands back the initial value both when the key
		// is missing and when it holds something it cannot parse. Only the first
		// may reach the migration: seeding a corrupt value from the unscoped key
		// would revive a dismissal the merchant has since replaced and hide a
		// warning that is currently owed.
		it( 'does not migrate over a scoped value it cannot parse', () => {
			window.localStorage.setItem( frontendKey(), '{not valid json' );
			seedLegacy( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
			expect( console ).toHaveErrored();
		} );

		it( 'does not read the unscoped key at all when the scoped one is corrupt', () => {
			const getItem = jest.spyOn( Storage.prototype, 'getItem' );
			window.localStorage.setItem( frontendKey(), '{not valid json' );
			seedLegacy( [ 'test-plugin' ] );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			renderCheckout();

			expect(
				getItem.mock.calls.filter( ( [ key ] ) => key === legacyKey )
			).toHaveLength( 0 );
			expect( console ).toHaveErrored();
			getItem.mockRestore();
		} );
	} );
} );
