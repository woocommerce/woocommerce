import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
} from 'vitest';
import '../../test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom/vitest';
import { useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BackButtonContent } from '../back-button-content';
import { storeName } from '../../../store';
vi.mock( '@wordpress/components', () => {
	const mock = {
		Button: ( { children, label, onClick, icon } ) => (
			<button aria-label={ label } onClick={ onClick } data-icon={ icon }>
				{ children }
			</button>
		),
		__unstableMotion: {
			div: ( { children, className } ) => (
				<div className={ className }>{ children }</div>
			),
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/icons', () => {
	const mock = {
		Icon: () => <span>Icon</span>,
		arrowLeft: 'arrowLeft',
		chevronLeft: 'chevronLeft',
		chevronRight: 'chevronRight',
		wordpress: 'wordpress',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../private-apis', () => {
	const mock = {
		BackButton: ( { children } ) =>
			children( {
				length: 1,
			} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const useSelectMock = useSelect as Mock;
const applyFiltersMock = applyFilters as Mock;
const mockUrls = {
	back: 'https://example.com/back',
	listings: 'https://example.com/listings',
	send: 'https://example.com/send',
};

// jsdom does not do layout, so we fake the slot width the way each WordPress
// version sizes that column.
type SizeSlot = ( slot: HTMLElement ) => number;
const fixedColumn: SizeSlot = () => 64;
const contentSizedColumn: SizeSlot = ( slot ) =>
	slot.querySelector( 'button' ) ? 74 : 0;
const renderInSlot = ( sizeSlot: SizeSlot ) => {
	vi.spyOn(
		HTMLElement.prototype,
		'getBoundingClientRect'
	).mockImplementation( function ( this: HTMLElement ) {
		const width = this.classList.contains( 'editor-header__back-button' )
			? sizeSlot( this )
			: 0;
		return {
			width,
		} as DOMRect;
	} );
	return render(
		<div className="editor-header__back-button">
			<BackButtonContent />
		</div>
	);
};
describe( 'BackButtonContent', () => {
	beforeEach( () => {
		vi.clearAllMocks();

		// Reset applyFilters to default behavior
		applyFiltersMock.mockImplementation(
			( _hook, defaultValue ) => defaultValue
		);
		useSelectMock.mockImplementation( ( selector ) =>
			selector( ( store ) => {
				if ( store === storeName ) {
					return {
						getUrls: () => mockUrls,
					};
				}
				return {};
			} )
		);
	} );
	afterEach( () => {
		vi.restoreAllMocks();
	} );
	it( 'should render the back button', () => {
		const { container } = render( <BackButtonContent /> );
		expect(
			container.querySelector(
				'.woocommerce-email-editor__view-mode-toggle'
			)
		).toBeInTheDocument();
	} );
	it( 'should render the button with correct label', () => {
		const { getByRole } = render( <BackButtonContent /> );
		expect(
			getByRole( 'button', {
				name: 'Close editor',
			} )
		).toBeInTheDocument();
	} );
	it( 'should have click handler', () => {
		const { getByRole } = render( <BackButtonContent /> );
		const button = getByRole( 'button', {
			name: 'Close editor',
		} );

		// Verify button has onClick handler (we don't actually click to avoid navigation error)
		expect( button ).toBeInTheDocument();
		expect( button.onclick ).not.toBeNull();
	} );
	it( 'should render the fullscreen-style button in a fixed 64px slot (WordPress ≤ 7.0 header)', () => {
		const { container } = renderInSlot( fixedColumn );
		expect(
			container.querySelector(
				'.woocommerce-email-editor__view-mode-toggle'
			)
		).toBeInTheDocument();
	} );
	it( 'should render the compact button in a content-sized slot (WordPress 7.1+ header)', () => {
		const { container, getByRole } = renderInSlot( contentSizedColumn );
		expect(
			container.querySelector(
				'.woocommerce-email-editor__view-mode-toggle'
			)
		).not.toBeInTheDocument();
		expect(
			getByRole( 'button', {
				name: 'Close editor',
			} )
		).toHaveAttribute( 'data-icon', 'chevronLeft' );
	} );
	it( 'should render the right chevron in a content-sized slot in RTL', () => {
		( isRTL as Mock ).mockReturnValueOnce( true );
		const { getByRole } = renderInSlot( contentSizedColumn );
		expect(
			getByRole( 'button', {
				name: 'Close editor',
			} )
		).toHaveAttribute( 'data-icon', 'chevronRight' );
	} );
	it( 'should fall back to the fullscreen-style button when there is no header slot', () => {
		const { container } = render( <BackButtonContent /> );
		expect(
			container.querySelector(
				'.woocommerce-email-editor__view-mode-toggle'
			)
		).toBeInTheDocument();
	} );
	it( 'should apply woocommerce_email_editor_close_content filter to render custom component', () => {
		// Mock the filter to return a custom component
		const CustomComponent = () => (
			<span data-testid="custom-back-button">Custom Back Button</span>
		);
		applyFiltersMock.mockImplementation( ( hook, defaultValue ) => {
			if ( hook === 'woocommerce_email_editor_close_content' ) {
				return CustomComponent;
			}
			return defaultValue;
		} );
		const { getByTestId, container } = render( <BackButtonContent /> );

		// Verify custom component is rendered
		expect( getByTestId( 'custom-back-button' ) ).toBeInTheDocument();
		expect( getByTestId( 'custom-back-button' ) ).toHaveTextContent(
			'Custom Back Button'
		);

		// Verify default component is NOT rendered
		expect(
			container.querySelector(
				'.woocommerce-email-editor__view-mode-toggle'
			)
		).not.toBeInTheDocument();
	} );
} );
