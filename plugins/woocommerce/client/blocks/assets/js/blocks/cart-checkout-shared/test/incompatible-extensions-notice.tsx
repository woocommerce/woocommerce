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

const SITE_A = 1;
const SITE_B = 2;

let mockIsAdmin = true;
let mockSiteId = SITE_A;
let mockIsMultisite = false;

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn(),
	// Getters, not values: the viewer and site change between renders.
	get CURRENT_USER_IS_ADMIN() {
		return mockIsAdmin;
	},
	get CURRENT_SITE_ID() {
		return mockSiteId;
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

// Both come from the module, so a rename on either is caught here.
const frontendKey = () => getFrontendStorageKey();
const legacyKey = UNSCOPED_STORAGE_KEY;

const storedSlugs = ( key = frontendKey() ) =>
	JSON.parse( window.localStorage.getItem( key ) || '[]' );

const seedFrontend = ( value: unknown ) =>
	window.localStorage.setItem( frontendKey(), JSON.stringify( value ) );

const seedLegacy = ( value: unknown ) =>
	window.localStorage.setItem( legacyKey, JSON.stringify( value ) );

const setIncompatibleExtensions = (
	extensions: Array< { id: string; title: string } >
) => mockGetSetting.mockImplementation( () => extensions );

// The setting is registered by the Cart and Checkout blocks, not by core data,
// so the payload can arrive without it — `getSetting` then hands back whatever
// fallback the caller passed.
const withoutIncompatibleExtensionsSetting = () =>
	mockGetSetting.mockImplementation( ( _name, fallback ) => fallback );

describe( 'IncompatibleExtensionsFrontendNotice', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.localStorage.clear();
		setIncompatibleExtensions( [] );
		mockIsAdmin = true;
		mockSiteId = SITE_A;
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

	// Sites on a subdirectory multisite share one localStorage origin, so the
	// storefront banner must use the current site's key rather than another
	// site's acknowledgement.
	describe( 'site scoping', () => {
		it( 'does not carry a dismissal to another site on the same origin', () => {
			mockIsMultisite = true;
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );
			const { unmount } = render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			fireEvent.click(
				screen.getByRole( 'button', { name: 'Dismiss' } )
			);
			unmount();

			mockSiteId = SITE_B;

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );
	} );

	// Before this split the storefront banner shared the editor's key, and
	// neither key named a site, so a merchant's dismissal lives under the
	// unscoped key on every site that ran 10.7.0 or later. Without a migration
	// they would all see the banner one more time after upgrading — the exact
	// symptom this component is meant to stop. The contract itself (site
	// scoping, the multisite refusal, corrupt-value handling) is pinned in the
	// storage module's own tests; these pin this surface's wiring to it.
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

		// `useLocalStorageState` hands back the initial value both when the key
		// is missing and when it holds something it cannot parse. Only the first
		// may reach the migration — pinned here because this surface once seeded
		// the legacy value straight in, so a corrupt scoped value revived it.
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
	} );

	// Pruning turns "this extension is no longer incompatible" into a write that
	// drops the acknowledgement. Everything that makes the incompatible list read
	// as empty therefore has to be told apart from the list genuinely being
	// empty, or a real acknowledgement is erased by a page that never had the
	// data to judge it.
	describe( 'when the list of incompatible extensions is not available', () => {
		const renderCheckout = () =>
			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

		it( 'leaves the acknowledgement alone when the setting is missing', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );
			withoutIncompatibleExtensionsSetting();

			const { container } = renderCheckout();

			expect( container ).toBeEmptyDOMElement();
			expect( storedSlugs() ).toEqual( [ 'plugin-one', 'plugin-two' ] );
		} );

		// The load that lost the setting must not cost the merchant the
		// dismissal they will need on the next, normal one.
		it( 'still hides the banner on the next load with the setting back', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );
			withoutIncompatibleExtensionsSetting();
			renderCheckout();

			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
				{ id: 'plugin-two', title: 'Plugin Two' },
			] );
			const { container } = renderCheckout();

			expect( container ).toBeEmptyDOMElement();
		} );

		// A shopper is never sent the list, so for them it is always missing.
		// The admin check has to hold on its own even if it were not: a viewer
		// who cannot see the banner must not rewrite what it is based on.
		it( 'leaves the acknowledgement alone for a shopper', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );
			mockIsAdmin = false;
			// Registered and empty, so only the admin check can hold the prune.
			setIncompatibleExtensions( [] );

			const { container } = renderCheckout();

			expect( container ).toBeEmptyDOMElement();
			expect( storedSlugs() ).toEqual( [ 'plugin-one', 'plugin-two' ] );
		} );

		it( 'renders nothing for a shopper even when extensions are incompatible', () => {
			mockIsAdmin = false;
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
			] );

			const { container } = renderCheckout();

			expect( container ).toBeEmptyDOMElement();
		} );

		// The banner is split so the storage hooks never mount for a shopper.
		// Gating only the migration callback would not do: the hook writes its
		// initial value on mount, so a shopper would leave an empty array under
		// this site's key and close the one-shot migration for good.
		it( 'leaves the key absent for a shopper, so an admin can still migrate', () => {
			seedLegacy( [ 'plugin-one' ] );
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
			] );

			mockIsAdmin = false;
			renderCheckout().unmount();

			expect( window.localStorage.getItem( frontendKey() ) ).toBeNull();

			mockIsAdmin = true;
			const { container } = renderCheckout();

			expect( container ).toBeEmptyDOMElement();
			expect( storedSlugs() ).toEqual( [ 'plugin-one' ] );
		} );

		// The control for the shopper cases above: with an admin and a list that
		// really is empty, the lapsed acknowledgement is dropped as it should be.
		it( 'does drop a lapsed acknowledgement for an admin', () => {
			seedFrontend( [ 'plugin-one', 'plugin-two' ] );
			setIncompatibleExtensions( [] );

			renderCheckout();

			expect( storedSlugs() ).toEqual( [] );
		} );
	} );
} );
