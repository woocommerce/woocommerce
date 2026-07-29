/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
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

// The storefront banner's own key, and the editor notice's key it must no
// longer collide with.
const FRONTEND_KEY =
	'wc-blocks_dismissed_incompatible_extensions_notices_frontend';
const EDITOR_KEY = 'wc-blocks_dismissed_incompatible_extensions_notices';

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
			// Seed a previously acknowledged extension that is not currently
			// present, to prove the union is stored rather than replaced.
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
			).toEqual( [ 'old-plugin', 'test-plugin' ] );
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

		it( 'stays dismissed when a previously acknowledged extension is reactivated', () => {
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
} );
