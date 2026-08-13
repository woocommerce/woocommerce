import '../../test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
import { useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BackButtonContent } from '../back-button-content';
import { storeName } from '../../../store';

jest.mock( '@wordpress/components', () => ( {
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
} ) );

jest.mock( '@wordpress/icons', () => ( {
	Icon: () => <span>Icon</span>,
	arrowLeft: 'arrowLeft',
	chevronLeft: 'chevronLeft',
	chevronRight: 'chevronRight',
	wordpress: 'wordpress',
} ) );

jest.mock( '../../../private-apis', () => ( {
	BackButton: ( { children } ) => children( { length: 1 } ),
} ) );

const useSelectMock = useSelect as jest.Mock;
const applyFiltersMock = applyFilters as jest.Mock;

const mockUrls = {
	back: 'https://example.com/back',
	listings: 'https://example.com/listings',
	send: 'https://example.com/send',
};

// Renders the component inside the editor header's back button slot with the
// given width. jsdom has no layout, so getBoundingClientRect is stubbed.
const renderInSlot = ( slotWidth: number ) => {
	jest.spyOn(
		HTMLElement.prototype,
		'getBoundingClientRect'
	).mockReturnValue( { width: slotWidth } as DOMRect );

	return render(
		<div className="editor-header__back-button">
			<BackButtonContent />
		</div>
	);
};

describe( 'BackButtonContent', () => {
	beforeEach( () => {
		jest.clearAllMocks();

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
		jest.restoreAllMocks();
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
			getByRole( 'button', { name: 'Close editor' } )
		).toBeInTheDocument();
	} );

	it( 'should have click handler', () => {
		const { getByRole } = render( <BackButtonContent /> );
		const button = getByRole( 'button', { name: 'Close editor' } );

		// Verify button has onClick handler (we don't actually click to avoid navigation error)
		expect( button ).toBeInTheDocument();
		expect( button.onclick ).not.toBeNull();
	} );

	it( 'should render the fullscreen-style button in a wide slot (WordPress ≤ 7.0 header)', () => {
		const { container } = renderInSlot( 64 );
		expect(
			container.querySelector(
				'.woocommerce-email-editor__view-mode-toggle'
			)
		).toBeInTheDocument();
	} );

	it( 'should render the compact button in a narrow slot (WordPress 7.1+ header)', () => {
		const { container, getByRole } = renderInSlot( 32 );
		expect(
			container.querySelector(
				'.woocommerce-email-editor__view-mode-toggle'
			)
		).not.toBeInTheDocument();
		expect(
			getByRole( 'button', { name: 'Close editor' } )
		).toHaveAttribute( 'data-icon', 'chevronLeft' );
	} );

	it( 'should render the right chevron in a narrow slot in RTL', () => {
		( isRTL as jest.Mock ).mockReturnValueOnce( true );
		const { getByRole } = renderInSlot( 32 );
		expect(
			getByRole( 'button', { name: 'Close editor' } )
		).toHaveAttribute( 'data-icon', 'chevronRight' );
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
