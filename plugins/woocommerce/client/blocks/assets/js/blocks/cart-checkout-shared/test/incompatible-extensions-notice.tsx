/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import {
	DISMISSED_INCOMPATIBLE_EXTENSIONS_FRONTEND_STORAGE_KEY,
	DISMISSED_INCOMPATIBLE_EXTENSIONS_STORAGE_KEY,
} from '@woocommerce/editor-components/incompatible-extension-notice/storage';
import { IncompatibleExtensionsFrontendNotice } from '../incompatible-extensions-notice';

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn(),
	CURRENT_USER_IS_ADMIN: true,
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

// Both keys are imported so a rename on either is caught here.
const FRONTEND_KEY = DISMISSED_INCOMPATIBLE_EXTENSIONS_FRONTEND_STORAGE_KEY;
const EDITOR_KEY = DISMISSED_INCOMPATIBLE_EXTENSIONS_STORAGE_KEY;

const setIncompatibleExtensions = (
	extensions: Array< { id: string; title: string } >
) => mockGetSetting.mockReturnValue( extensions );

describe( 'IncompatibleExtensionsFrontendNotice', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.localStorage.clear();
		setIncompatibleExtensions( [] );
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
			window.localStorage.setItem(
				FRONTEND_KEY,
				JSON.stringify( [ 'old-plugin' ] )
			);
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
			expect(
				JSON.parse(
					window.localStorage.getItem( FRONTEND_KEY ) || '[]'
				)
			).toEqual( [ 'test-plugin' ] );
		} );

		it( 'does not write the editor notice key (no cross-surface collision)', () => {
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );

			expect( window.localStorage.getItem( EDITOR_KEY ) ).toBeNull();
		} );

		it( 'stays dismissed when an incompatible extension is deactivated', () => {
			window.localStorage.setItem(
				FRONTEND_KEY,
				JSON.stringify( [ 'plugin-one', 'plugin-two' ] )
			);
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
			window.localStorage.setItem(
				FRONTEND_KEY,
				JSON.stringify( [ 'plugin-one', 'plugin-two' ] )
			);
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
			window.localStorage.setItem(
				FRONTEND_KEY,
				JSON.stringify( [ 'plugin-one', 'plugin-two' ] )
			);

			// Deactivate plugin-two. The banner stays hidden for plugin-one,
			// and plugin-two's lapsed acknowledgement is dropped.
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
			] );
			const { container, unmount } = render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);
			expect( container ).toBeEmptyDOMElement();
			expect(
				JSON.parse(
					window.localStorage.getItem( FRONTEND_KEY ) || '[]'
				)
			).toEqual( [ 'plugin-one' ] );
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
			window.localStorage.setItem(
				FRONTEND_KEY,
				JSON.stringify( [ 'plugin-one', 'plugin-two' ] )
			);
			// plugin-two is gone and a brand-new plugin-three has arrived.
			setIncompatibleExtensions( [
				{ id: 'plugin-one', title: 'Plugin One' },
				{ id: 'plugin-three', title: 'Plugin Three' },
			] );

			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
			expect(
				JSON.parse(
					window.localStorage.getItem( FRONTEND_KEY ) || '[]'
				)
			).toEqual( [ 'plugin-one' ] );
		} );

		it.each( [
			[ 'an object', { 'plugin-one': true } ],
			[ 'a bare string', 'plugin-one' ],
			[ 'a number', 7 ],
			[ 'null', null ],
			[ 'an array of junk', [ 1, null, { a: 1 } ] ],
		] )( 'survives a stored value that is %s', ( _label, value ) => {
			window.localStorage.setItem(
				FRONTEND_KEY,
				JSON.stringify( value )
			);
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
			window.localStorage.setItem(
				FRONTEND_KEY,
				JSON.stringify( [ 'test-plugin' ] )
			);
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
			window.localStorage.setItem(
				FRONTEND_KEY,
				JSON.stringify( [ 'test-plugin' ] )
			);
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			const { container } = render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/cart" />
			);

			expect( container ).toBeEmptyDOMElement();
		} );
	} );

	// Before this key rename the storefront banner shared the editor's key, so a
	// merchant's dismissal lives under EDITOR_KEY on every site that ran 10.7.0
	// or later. Without a migration they would all see the banner one more time
	// after upgrading — the exact symptom this component is meant to stop.
	describe( 'migration from the pre-rename storage key', () => {
		const renderCheckout = () =>
			render(
				<IncompatibleExtensionsFrontendNotice block="woocommerce/checkout" />
			);

		it( 'stays dismissed for a merchant who dismissed before the rename', () => {
			window.localStorage.setItem(
				EDITOR_KEY,
				JSON.stringify( [ 'test-plugin' ] )
			);
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
			window.localStorage.setItem(
				EDITOR_KEY,
				JSON.stringify( [
					'test-plugin',
					{ 'woocommerce/checkout': [ 'test-plugin', 'gateway-a' ] },
				] )
			);
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			const { container } = renderCheckout();

			expect( container ).toBeEmptyDOMElement();
			expect(
				JSON.parse(
					window.localStorage.getItem( FRONTEND_KEY ) || '[]'
				)
			).toEqual( [ 'test-plugin' ] );
		} );

		it( 'still shows the banner for an extension the merchant never acknowledged', () => {
			window.localStorage.setItem(
				EDITOR_KEY,
				JSON.stringify( [ 'old-plugin' ] )
			);
			setIncompatibleExtensions( [
				{ id: 'new-plugin', title: 'New Plugin' },
			] );

			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		it( 'ignores the legacy value once the storefront key exists', () => {
			window.localStorage.setItem( FRONTEND_KEY, JSON.stringify( [] ) );
			window.localStorage.setItem(
				EDITOR_KEY,
				JSON.stringify( [ 'test-plugin' ] )
			);
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			renderCheckout();

			expect( screen.getByTestId( 'notice-banner' ) ).toBeInTheDocument();
		} );

		it( 'migrates nothing from a value only the editor ever wrote', () => {
			window.localStorage.setItem(
				EDITOR_KEY,
				JSON.stringify( [
					{ 'woocommerce/checkout': [ 'test-plugin' ] },
				] )
			);
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
				window.localStorage.setItem( EDITOR_KEY, stored );
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
			window.localStorage.setItem( EDITOR_KEY, legacy );
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
				{ id: 'new-plugin', title: 'New Plugin' },
			] );

			renderCheckout();
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );

			expect( window.localStorage.getItem( EDITOR_KEY ) ).toBe( legacy );
		} );

		it( 'adds newly acknowledged slugs to the migrated ones', () => {
			window.localStorage.setItem(
				EDITOR_KEY,
				JSON.stringify( [ 'test-plugin' ] )
			);
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
				{ id: 'new-plugin', title: 'New Plugin' },
			] );

			renderCheckout();
			fireEvent.click( screen.getByTestId( 'dismiss-button' ) );

			expect(
				JSON.parse(
					window.localStorage.getItem( FRONTEND_KEY ) || '[]'
				)
			).toEqual( [ 'test-plugin', 'new-plugin' ] );
		} );

		it( 'reads the legacy value once per mount, not once per render', () => {
			const getItem = jest.spyOn( Storage.prototype, 'getItem' );
			window.localStorage.setItem(
				EDITOR_KEY,
				JSON.stringify( [ 'test-plugin' ] )
			);
			setIncompatibleExtensions( [
				{ id: 'test-plugin', title: 'Test Plugin' },
			] );

			const legacyReads = () =>
				getItem.mock.calls.filter( ( [ key ] ) => key === EDITOR_KEY )
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
} );
