jest.mock( '../utils', () => ( {
	...jest.requireActual( '../utils' ),
	getReviews: jest
		.fn()
		.mockReturnValue( Promise.resolve( { reviews: [], totalReviews: 0 } ) ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest
		.fn()
		.mockImplementation( ( setting, defaultValue ) => defaultValue ),
} ) );

/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FrontendContainerBlock from '../frontend-container-block';
import { getReviews } from '../utils';

describe( 'FrontendContainerBlock', () => {
	afterEach( () => {
		( getReviews as jest.Mock ).mockReset();
		( getReviews as jest.Mock ).mockReturnValue(
			Promise.resolve( { reviews: [], totalReviews: 0 } )
		);
	} );

	it( 'renders nothing when categoryIds is an empty string (no category selected)', async () => {
		const { container } = render(
			<FrontendContainerBlock
				attributes={ {
					categoryIds: '',
					orderby: 'most-recent',
					reviewsOnPageLoad: 10,
					reviewsOnLoadMore: 10,
					showLoadMore: true,
					showOrderby: true,
				} }
			/>
		);
		await act( async () => {
			expect( container ).toBeEmptyDOMElement();
		} );
		expect( getReviews ).not.toHaveBeenCalled();
	} );

	it( 'renders reviews when categoryIds contains category IDs', async () => {
		( getReviews as jest.Mock ).mockResolvedValue( {
			reviews: [],
			totalReviews: 0,
		} );

		render(
			<FrontendContainerBlock
				attributes={ {
					categoryIds: '1',
					orderby: 'most-recent',
					reviewsOnPageLoad: 10,
					reviewsOnLoadMore: 10,
					showLoadMore: true,
					showOrderby: true,
				} }
			/>
		);

		await act( async () => {
			await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		} );

		expect( getReviews ).toHaveBeenCalled();
	} );
} );
