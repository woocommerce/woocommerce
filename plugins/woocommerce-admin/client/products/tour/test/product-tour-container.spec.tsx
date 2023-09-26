/**
 * External dependencies
 */
import userEvent from '@testing-library/user-event';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ProductTourContainer } from '../';
import { useProductTour } from '../use-product-tour';

const dismissModal = jest.fn();
const endTour = jest.fn();
const startTour = jest.fn();

const defaultValues = {
	dismissModal,
	endTour,
	isModalHidden: false,
	isTouring: false,
	startTour,
};

jest.mock( '../use-product-tour', () => {
	return {
		useProductTour: jest.fn(),
	};
} );

const mockedUseProductTour = useProductTour as jest.Mock;

describe( 'ProductTourContainer', () => {
	it( 'should render the modal initially if not already hidden', () => {
		mockedUseProductTour.mockImplementation( () => defaultValues );
		const { getByText } = render( <ProductTourContainer /> );
		expect(
			getByText( 'Meet the product editing form' )
		).toBeInTheDocument();
	} );

	it( 'should not render the modal when the tour has already started', () => {
		mockedUseProductTour.mockImplementation( () => ( {
			...defaultValues,
			isTouring: true,
		} ) );
		const { queryByText } = render( <ProductTourContainer /> );
		expect(
			queryByText( 'Meet the product editing form' )
		).not.toBeInTheDocument();
		expect(
			queryByText( 'Tell a story about your product' )
		).not.toBeInTheDocument();
	} );

	it( 'should call startTour after clicking the button to begin the tour', () => {
		mockedUseProductTour.mockImplementation( () => defaultValues );
		const { getByText } = render( <ProductTourContainer /> );
		userEvent.click( getByText( 'Show me around (10s)' ) );
		expect( startTour ).toBeCalled();
	} );

	it( 'should call dismissModal after closing the modal', () => {
		mockedUseProductTour.mockImplementation( () => defaultValues );
		const { getByText } = render( <ProductTourContainer /> );
		userEvent.click( getByText( "I'll explore on my own" ) );
		expect( dismissModal ).toBeCalled();
	} );

	it( 'should call endTour after closing the tour', () => {
		mockedUseProductTour.mockImplementation( () => ( {
			...defaultValues,
			isTouring: true,
		} ) );
		const { getByLabelText } = render( <ProductTourContainer /> );
		userEvent.click( getByLabelText( 'Close Tour' ) );
		expect( endTour ).toBeCalled();
	} );

	it( 'should not show tour or modal once tour is complete', () => {
		mockedUseProductTour.mockImplementation( () => ( {
			...defaultValues,
			isTouring: false,
			isModalHidden: true,
		} ) );
		const { queryByText } = render( <ProductTourContainer /> );
		expect(
			queryByText( 'Meet the product editing form' )
		).not.toBeInTheDocument();
		expect(
			queryByText( 'Tell a story about your product' )
		).not.toBeInTheDocument();
	} );
} );
