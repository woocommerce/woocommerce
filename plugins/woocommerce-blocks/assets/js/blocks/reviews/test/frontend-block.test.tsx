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
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import ReviewsFrontendBlock from '../frontend-block';
import { getReviews } from '../utils';

describe( 'ReviewsFrontendBlock', () => {
	const dummyReview = {
		date_created: '2021-08-04T15: 00: 00',
		date_created_gmt: '2021-08-04T15: 00: 00',
		formatted_date_created: 'August 4, 2021',
		product_name: 'Product Name',
		product_permalink: 'https://example.com/product/product-name/',
		review: 'This is a review.',
		reviewer: 'Reviewer',
		id: 1,
		product_id: 1,
		product_image: {
			alt: 'Product Name',
			thumbnail: 'https://example.com/product/product-name.jpg',
			name: 'product-name',
			sizes: '(max-width: 800px) 100vw, 800px',
			src: 'https://example.com/product/product-name.jpg',
			srcset: 'logo-1.jpg 800w, logo-1-300x300.jpg 300w, logo-1-150x150.jpg 150w, logo-1-768x767.jpg 768w, logo-1-324x324.jpg 324w, logo-1-416x415.jpg 416w, logo-1-100x100.jpg 100w',
		},
		reviewer_avatar_urls: { 48: '' },
		verified: true,
		rating: 1,
	};

	it( 'Does not render when there are no reviews', async () => {
		const { container } = render(
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore - withReviews HOC will need refactoring to TS to fix this.
			<ReviewsFrontendBlock
				attributes={ {} }
				sortSelectValue={ 'most-recent' }
				reviewsToDisplay={ 0 }
				orderby={ 'reviewer' }
				order={ 'asc' }
				onAppendReviews={ jest.fn() }
				onChangeOrderby={ jest.fn() }
			/>
		);
		await act( async () => {
			expect( container ).toBeEmptyDOMElement();
		} );
	} );

	it( 'Shows load more button when there are more reviews than displayed.', async () => {
		( getReviews as jest.Mock ).mockResolvedValue( {
			reviews: [ dummyReview, dummyReview, dummyReview ],
			totalReviews: 3,
		} );

		const { findByText } = render(
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore - we can't fix this until withReviews is converted to TS.
			<ReviewsFrontendBlock
				attributes={ { showLoadMore: 'true' } }
				sortSelectValue={ 'most-recent' }
				reviewsToDisplay={ 1 }
				orderby={ 'reviewer' }
				order={ 'asc' }
				onChangeOrderby={ jest.fn() }
			/>
		);

		const loadMoreButton = await findByText( 'Load more' );
		expect( loadMoreButton ).toBeInTheDocument();
	} );

	it( 'renders a order by select when showOrderby is passed as attribute and reviewRatingsEnabled is not set (defaults to true).', async () => {
		( getReviews as jest.Mock ).mockResolvedValue( {
			reviews: [ dummyReview, dummyReview, dummyReview ],
			totalReviews: 3,
		} );

		const { findByText } = render(
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore - we can't fix this until withReviews is converted to TS.
			<ReviewsFrontendBlock
				attributes={ { showLoadMore: true, showOrderby: true } }
				sortSelectValue={ 'most-recent' }
				reviewsToDisplay={ 1 }
				orderby={ 'reviewer' }
				order={ 'asc' }
				onChangeOrderby={ jest.fn() }
			/>
		);

		const orderBySelect = await findByText( 'Order by' );
		expect( orderBySelect ).toBeInTheDocument();
	} );

	it( 'when reviewRatingsEnabled is set to false the order by select is not shown.', async () => {
		( getReviews as jest.Mock ).mockResolvedValue( {
			reviews: [ dummyReview, dummyReview, dummyReview ],
			totalReviews: 3,
		} );

		( getSetting as jest.Mock ).mockReturnValue( false );

		const { findByText } = render(
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore - we can't fix this until withReviews is converted to TS.
			<ReviewsFrontendBlock
				attributes={ { showLoadMore: true, showOrderby: true } }
				sortSelectValue={ 'most-recent' }
				reviewsToDisplay={ 1 }
				orderby={ 'reviewer' }
				order={ 'asc' }
				onChangeOrderby={ jest.fn() }
			/>
		);

		expect( () => findByText( 'Order by' ) ).rejects.toThrow();
	} );
} );
