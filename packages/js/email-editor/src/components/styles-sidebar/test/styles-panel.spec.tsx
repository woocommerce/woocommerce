/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { StylesPanel } from '../styles-panel';
import { StylesSidebar } from '../styles-sidebar';
import { useCanEditEmailStyles } from '../hooks';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

// Importing the package store loads `@wordpress/core-data`, and the private
// APIs load `@wordpress/block-editor`; both drag in untransformable ESM under
// the test runner. Only the store's name and the panel gates are needed here.
jest.mock( '../../../store', () => ( {
	storeName: 'email-editor/editor',
} ) );

jest.mock( '../../../private-apis', () => ( {
	useHasStylesColorPanel: () => true,
	useHasStylesBackgroundPanel: () => true,
} ) );

// The screens pull in the block editor's private APIs, which need a running
// editor. This suite is about how the panel and the sidebar compose, so stand
// them in for markers we can assert on.
jest.mock( '../screens', () => ( {
	ScreenRoot: () => <div data-testid="screen-root" />,
	ScreenTypography: () => <div data-testid="screen-typography" />,
	ScreenTypographyElement: () => (
		<div data-testid="screen-typography-element" />
	),
	ScreenColors: () => <div data-testid="screen-colors" />,
	ScreenBackground: () => <div data-testid="screen-background" />,
	ScreenLayout: () => <div data-testid="screen-layout" />,
} ) );

jest.mock( '../navigator', () => {
	const Navigator = ( { children } ) => <div>{ children }</div>;
	Navigator.Screen = ( { path, children } ) =>
		path === '/' ? <div>{ children }</div> : null;
	Navigator.BackButton = () => null;
	return { Navigator };
} );

jest.mock( '@wordpress/editor', () => ( {
	PluginSidebar: ( { children, name } ) => (
		<div data-testid="plugin-sidebar" data-name={ name }>
			{ children }
		</div>
	),
	PluginSidebarMoreMenuItem: () => <div data-testid="more-menu-item" />,
} ) );

const mockedUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;

/**
 * Drive `canUserEditGlobalEmailStyles()` through the mocked `useSelect`.
 *
 * @param canEdit What the selector should report.
 */
function mockCanEdit( canEdit: boolean ) {
	mockedUseSelect.mockImplementation( ( mapSelect: ( s ) => unknown ) =>
		mapSelect( () => ( {
			canUserEditGlobalEmailStyles: () => ( { canEdit, postId: 42 } ),
		} ) )
	);
}

describe( 'StylesPanel', () => {
	beforeEach( () => {
		mockedUseSelect.mockReset();
	} );

	it( 'renders the styles screens without any editor chrome', () => {
		render( <StylesPanel /> );

		expect( screen.getByTestId( 'screen-root' ) ).toBeInTheDocument();
		// The point of the split: no PluginSidebar, so no complementary area
		// and no dependency on the editor's interface skeleton.
		expect(
			screen.queryByTestId( 'plugin-sidebar' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'more-menu-item' )
		).not.toBeInTheDocument();
	} );

	it( 'carries the class the panel stylesheet is scoped to', () => {
		const { container } = render( <StylesPanel /> );

		expect(
			container.querySelector( '.woocommerce-email-editor-styles-panel' )
		).not.toBeNull();
	} );

	it( 'renders regardless of edit permission, leaving the gate to the consumer', () => {
		mockCanEdit( false );

		render( <StylesPanel /> );

		expect( screen.getByTestId( 'screen-root' ) ).toBeInTheDocument();
		expect( mockedUseSelect ).not.toHaveBeenCalled();
	} );
} );

describe( 'StylesSidebar', () => {
	beforeEach( () => {
		mockedUseSelect.mockReset();
	} );

	it( 'registers a plugin sidebar containing the panel when the user may edit', () => {
		mockCanEdit( true );

		render( <StylesSidebar /> );

		const sidebar = screen.getByTestId( 'plugin-sidebar' );
		expect( sidebar ).toHaveAttribute(
			'data-name',
			'email-styles-sidebar'
		);
		expect( sidebar ).toContainElement(
			screen.getByTestId( 'screen-root' )
		);
		expect( screen.getByTestId( 'more-menu-item' ) ).toBeInTheDocument();
	} );

	it( 'renders nothing when the user may not edit global email styles', () => {
		mockCanEdit( false );

		render( <StylesSidebar /> );

		expect(
			screen.queryByTestId( 'plugin-sidebar' )
		).not.toBeInTheDocument();
		expect( screen.queryByTestId( 'screen-root' ) ).not.toBeInTheDocument();
	} );
} );

describe( 'useCanEditEmailStyles', () => {
	beforeEach( () => {
		mockedUseSelect.mockReset();
	} );

	it( 'reports what canUserEditGlobalEmailStyles resolves to', () => {
		mockCanEdit( true );
		let result: boolean | undefined;
		const Probe = () => {
			result = useCanEditEmailStyles();
			return null;
		};

		render( <Probe /> );

		expect( result ).toBe( true );
	} );
} );
