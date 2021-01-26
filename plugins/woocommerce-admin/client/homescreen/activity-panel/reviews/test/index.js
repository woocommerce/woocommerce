/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ReviewsPanel } from '../';

const REVIEW = {
	id: 10,
	date_created: '2020-11-20T18:24:41',
	date_created_gmt: '2020-11-20T18:24:41',
	product_id: 45,
	status: 'hold',
	reviewer: 'Reviewer',
	reviewer_email: 'test@test.ca',
	review: '<p>It is an average hat</p>\n',
	rating: 3,
	verified: false,
	_embedded: {
		up: [
			{
				id: 45,
				name: 'Cap',
				slug: 'cap',
				permalink: 'https://one.wordpress.test/product/cap/',
				description:
					'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.',
				short_description: 'This is a simple product.',
				images: [
					{
						id: 74,
						date_created: '2020-11-20T17:28:47',
						date_created_gmt: '2020-11-20T17:28:47',
						date_modified: '2020-11-20T17:28:47',
						date_modified_gmt: '2020-11-20T17:28:47',
						src:
							'https://one.wordpress.test/wp-content/uploads/2020/11/cap-2-1.jpg',
						name: 'cap-2-1.jpg',
						alt: '',
					},
				],
			},
		],
	},
};

jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	Link: ( { children } ) => {
		return <>{ children }</>;
	},
} ) );

jest.mock( '../checkmark-circle-icon', () =>
	jest.fn().mockImplementation( () => '[checkmark-circle-icon]' )
);

describe( 'ReviewsPanel', () => {
	it( 'should render an empty review card', () => {
		render(
			<ReviewsPanel
				hasUnapprovedReviews={ false }
				isError={ false }
				isRequesting={ false }
				reviews={ [] }
			/>
		);
		expect( screen.queryByRole( 'section' ) ).toBeNull();
	} );

	it( 'should render a review card with title <name> reviewed <product name>', () => {
		render(
			<ReviewsPanel
				hasUnapprovedReviews={ true }
				isError={ false }
				isRequesting={ false }
				reviews={ [ REVIEW ] }
			/>
		);
		expect( screen.getByText( 'Reviewer reviewed Cap' ) ).not.toBeNull();
	} );

	it( 'should render checkmark circle icon in the review title, if review is verfied owner', () => {
		render(
			<ReviewsPanel
				hasUnapprovedReviews={ true }
				isError={ false }
				isRequesting={ false }
				reviews={ [ { ...REVIEW, verified: true } ] }
			/>
		);
		const header = screen.getByRole( 'heading', { level: 3 } );
		expect( header.innerHTML ).toMatch( /\[checkmark-circle-icon\]/ );
	} );

	describe( 'review actions', () => {
		it( 'should render a review card with approve, mark as spam, and delete buttons', () => {
			render(
				<ReviewsPanel
					hasUnapprovedReviews={ true }
					isError={ false }
					isRequesting={ false }
					reviews={ [ REVIEW ] }
				/>
			);
			expect( screen.queryByText( 'Approve' ) ).toBeInTheDocument();
			expect( screen.queryByText( 'Mark as spam' ) ).toBeInTheDocument();
			expect( screen.queryByText( 'Delete' ) ).toBeInTheDocument();
		} );

		it( 'should trigger updateReview with status approved when Approve is clicked', () => {
			const clickHandler = jest.fn( () => {
				return Promise.resolve();
			} );
			render(
				<ReviewsPanel
					hasUnapprovedReviews={ true }
					isError={ false }
					isRequesting={ false }
					reviews={ [ REVIEW ] }
					updateReview={ clickHandler }
				/>
			);
			fireEvent.click( screen.getByText( 'Approve' ) );
			expect( clickHandler ).toHaveBeenCalledWith( REVIEW.id, {
				status: 'approved',
			} );
		} );

		it( 'should trigger updateReview with status spam when Mark as spam is clicked', () => {
			const clickHandler = jest.fn( () => {
				return Promise.resolve();
			} );
			render(
				<ReviewsPanel
					hasUnapprovedReviews={ true }
					isError={ false }
					isRequesting={ false }
					reviews={ [ REVIEW ] }
					updateReview={ clickHandler }
				/>
			);
			fireEvent.click( screen.getByText( 'Mark as spam' ) );
			expect( clickHandler ).toHaveBeenCalledWith( REVIEW.id, {
				status: 'spam',
			} );
		} );

		it( 'should trigger deleteReview with review id when delete is clicked', () => {
			const clickHandler = jest.fn( () => {
				return Promise.resolve();
			} );
			render(
				<ReviewsPanel
					hasUnapprovedReviews={ true }
					isError={ false }
					isRequesting={ false }
					reviews={ [ REVIEW ] }
					deleteReview={ clickHandler }
				/>
			);
			fireEvent.click( screen.getByText( 'Delete' ) );
			expect( clickHandler ).toHaveBeenCalledWith( REVIEW.id );
		} );
	} );
} );
