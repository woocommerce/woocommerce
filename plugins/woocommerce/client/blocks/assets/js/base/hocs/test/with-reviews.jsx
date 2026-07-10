/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import withReviews from '../with-reviews';
import * as mockUtils from '../../../blocks/reviews/utils';
import * as mockBaseUtils from '../../utils/errors';

jest.mock( '../../../blocks/reviews/utils', () => ( {
	getSortArgs: () => ( {
		order: 'desc',
		orderby: 'date_gmt',
	} ),
	getReviews: jest.fn(),
} ) );

jest.mock( '../../utils/errors', () => ( {
	formatError: jest.fn(),
} ) );

const mockReviews = [
	{ reviewer: 'Alice', review: 'Lorem ipsum', rating: 2 },
	{ reviewer: 'Bob', review: 'Dolor sit amet', rating: 3 },
	{ reviewer: 'Carol', review: 'Consectetur adipiscing elit', rating: 5 },
];
const defaultArgs = {
	offset: 0,
	order: 'desc',
	orderby: 'date_gmt',
	per_page: 2,
	product_id: 1,
};

// Capture the props the HOC injects into the wrapped component.
let lastProps;
const CapturedComponent = jest.fn( ( props ) => {
	lastProps = props;
	return null;
} );
const TestComponent = withReviews( CapturedComponent );

// Run an interaction and flush the async state updates it triggers inside
// `act`, so React's async fetch-then-setState work does not leak past the test.
const settle = async ( fn ) => {
	await act( async () => {
		fn();
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	} );
};

describe( 'withReviews Component', () => {
	let renderResult;
	const renderComponent = ( props ) =>
		settle( () => {
			renderResult = render(
				<TestComponent
					attributes={ {} }
					order="desc"
					orderby="date_gmt"
					productId={ 1 }
					reviewsToDisplay={ 2 }
					{ ...props }
				/>
			);
		} );

	afterEach( () => {
		mockUtils.getReviews.mockReset();
		CapturedComponent.mockClear();
		lastProps = undefined;
	} );

	describe( 'lifecycle events', () => {
		beforeEach( async () => {
			mockUtils.getReviews
				.mockImplementationOnce( () =>
					Promise.resolve( {
						reviews: mockReviews.slice( 0, 2 ),
						totalReviews: mockReviews.length,
					} )
				)
				.mockImplementationOnce( () =>
					Promise.resolve( {
						reviews: mockReviews.slice( 2, 3 ),
						totalReviews: mockReviews.length,
					} )
				);
			await renderComponent();
		} );

		it( 'getReviews is called on mount with default args', () => {
			const { getReviews } = mockUtils;

			expect( getReviews ).toHaveBeenCalledWith( defaultArgs );
			expect( getReviews ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'getReviews is called on component update', async () => {
			const { getReviews } = mockUtils;
			await settle( () =>
				renderResult.rerender(
					<TestComponent
						order="desc"
						orderby="date_gmt"
						productId={ 1 }
						reviewsToDisplay={ 3 }
					/>
				)
			);

			expect( getReviews ).toHaveBeenNthCalledWith( 2, {
				...defaultArgs,
				offset: 2,
				per_page: 1,
			} );
			expect( getReviews ).toHaveBeenCalledTimes( 2 );
		} );
	} );

	describe( 'when the API returns product data', () => {
		beforeEach( async () => {
			mockUtils.getReviews.mockImplementation( () =>
				Promise.resolve( {
					reviews: mockReviews.slice( 0, 2 ),
					totalReviews: mockReviews.length,
				} )
			);
			await renderComponent();
		} );

		it( 'sets reviews based on API response', () => {
			expect( lastProps.error ).toBeNull();
			expect( lastProps.isLoading ).toBe( false );
			expect( lastProps.reviews ).toEqual( mockReviews.slice( 0, 2 ) );
			expect( lastProps.totalReviews ).toEqual( mockReviews.length );
		} );
	} );

	describe( 'when the API returns an error', () => {
		const error = { message: 'There was an error.' };
		const formattedError = { message: 'There was an error.', type: 'api' };

		beforeEach( async () => {
			mockUtils.getReviews.mockImplementation( () =>
				Promise.reject( error )
			);
			mockBaseUtils.formatError.mockImplementation(
				() => formattedError
			);
			await renderComponent();
		} );

		test( 'sets the error prop', () => {
			const { formatError } = mockBaseUtils;

			expect( formatError ).toHaveBeenCalledWith( error );
			expect( formatError ).toHaveBeenCalledTimes( 1 );
			expect( lastProps.error ).toEqual( formattedError );
			expect( lastProps.isLoading ).toBe( false );
			expect( lastProps.reviews ).toEqual( [] );
		} );
	} );
} );
