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
import { render, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FrontendContainerBlock from '../frontend-container-block';
import { getReviews } from '../utils';

const attributes = {
	orderby: 'most-recent',
	reviewsOnPageLoad: 10,
	reviewsOnLoadMore: 10,
	showLoadMore: true,
	showOrderby: true,
};

describe( 'FrontendContainerBlock', () => {
	afterEach( () => {
		( getReviews as jest.Mock ).mockReset();
		( getReviews as jest.Mock ).mockReturnValue(
			Promise.resolve( { reviews: [], totalReviews: 0 } )
		);
	} );

	it( 'renders nothing when Reviews by Category has no selected category', () => {
		const { container } = render(
			<FrontendContainerBlock
				attributes={ { ...attributes, categoryIds: '' } }
			/>
		);

		expect( container ).toBeEmptyDOMElement();
		expect( getReviews ).not.toHaveBeenCalled();
	} );

	it( 'renders reviews when category IDs are present', async () => {
		render(
			<FrontendContainerBlock
				attributes={ { ...attributes, categoryIds: '1' } }
			/>
		);

		await waitFor( () => expect( getReviews ).toHaveBeenCalled() );
	} );

	it( 'renders all reviews when filter attributes are absent', async () => {
		render( <FrontendContainerBlock attributes={ attributes } /> );

		await waitFor( () => expect( getReviews ).toHaveBeenCalled() );
	} );
} );
