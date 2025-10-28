/* eslint-disable @woocommerce/dependency-group -- because we import mocks first, we deactivate this rule to avoid ESLint errors */
import '../../test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
import { useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { BackButtonContent } from '../back-button-content';
import { storeName } from '../../../store';

jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, label, onClick } ) => (
		<button aria-label={ label } onClick={ onClick }>
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
