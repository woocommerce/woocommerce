/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import CreateAccountBlock from '../form';
import { textContentMatcher } from '../../../../../../tests/utils/find-by-text';

jest.mock( '@woocommerce/settings', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest
		.fn()
		.mockImplementation( ( key: string, defaultValue: unknown ) => {
			if ( key === 'registrationGeneratePassword' ) {
				return true;
			}
			return defaultValue;
		} ),
} ) );

describe( 'CreateAccountFrontendBlock', () => {
	it( 'Renders password field if registrationGeneratePassword is false', async () => {
		( getSetting as jest.Mock ).mockImplementation(
			( key: string, defaultValue: unknown ) => {
				if ( key === 'registrationGeneratePassword' ) {
					return false;
				}
				return defaultValue;
			}
		);

		const { findByText } = render(
			<CreateAccountBlock
				attributes={ {
					customerEmail: 'test@test.com',
				} }
				isEditor={ false }
			/>
		);

		await act( async () => {
			expect(
				await findByText(
					textContentMatcher( 'Set a password for test@test.com' )
				)
			).toBeInTheDocument();
		} );
	} );

	it( 'Renders no password field if registrationGeneratePassword is true', async () => {
		( getSetting as jest.Mock ).mockImplementation(
			( key: string, defaultValue: unknown ) => {
				if ( key === 'registrationGeneratePassword' ) {
					return true;
				}
				return defaultValue;
			}
		);

		const { findByText } = render(
			<CreateAccountBlock
				attributes={ {
					customerEmail: 'test@test.com',
				} }
				isEditor={ false }
			/>
		);

		await act( async () => {
			expect(
				await findByText(
					textContentMatcher( 'Set a password for test@test.com' )
				)
			).not.toBeInTheDocument();
		} );
	} );

	/*
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

		const orderBySelect = await findByText( 'Set a password' );
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
	} );*/
} );
