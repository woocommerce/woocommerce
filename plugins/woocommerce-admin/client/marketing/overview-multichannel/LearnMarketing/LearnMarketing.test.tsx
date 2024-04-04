/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { useBlogPosts } from './useBlogPosts';
import { LearnMarketing } from './LearnMarketing';

jest.mock( './useBlogPosts', () => ( {
	useBlogPosts: jest.fn(),
} ) );

describe( 'LearnMarketing component', () => {
	it( 'should render placeholders when loading is in progress', async () => {
		( useBlogPosts as jest.Mock ).mockReturnValue( {
			isLoading: true,
			error: undefined,
			posts: [],
		} );
		render( <LearnMarketing /> );

		// Click on expand button to expand the card.
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Expand' } )
		);

		// should render three elements with the "progressbar" role:
		// two from the PlaceholderPostTile, and one in the card footer.
		expect( screen.getAllByRole( 'progressbar' ) ).toHaveLength( 3 );
	} );

	it( 'should render an error message when there is an error', async () => {
		( useBlogPosts as jest.Mock ).mockReturnValue( {
			isLoading: false,
			error: new Error(),
			posts: [],
		} );
		render( <LearnMarketing /> );

		// Click on expand button to expand the card.
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Expand' } )
		);

		expect(
			screen.getByText( "Oops, our posts aren't loading right now" )
		).toBeInTheDocument();
	} );

	it( 'should render "No posts yet" when loading is done and there are no posts', async () => {
		( useBlogPosts as jest.Mock ).mockReturnValue( {
			isLoading: false,
			error: undefined,
			posts: [],
		} );
		render( <LearnMarketing /> );

		// Click on expand button to expand the card.
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Expand' } )
		);

		expect( screen.getByText( 'No posts yet' ) ).toBeInTheDocument();
	} );

	it( 'should render two posts in one page with pagination when loading is done and there are posts', async () => {
		( useBlogPosts as jest.Mock ).mockReturnValue( {
			isLoading: false,
			error: undefined,
			posts: [
				{
					title: 'Grow Your Store with an Omnichannel Presence',
					date: '2022-09-21T19:46:40',
					link: 'woocommerce.com/posts/grow-store-omnichannel-ecommerce/',
					author_name: 'Kathryn Marr',
					author_avatar:
						'https://secure.gravatar.com/avatar/431b87d722d366103cc5d9b26c66c665?s=96&d=mm&r=g',
					image: 'woocommerce.com/wp-content/uploads/2022/09/blog-fb-Omnichannel@2x.jpg?resize=650,340&crop=1',
				},
				{
					title: 'What is Affiliate Marketing and How to Use it to Make More Money Online',
					date: '2022-08-30T22:03:54',
					link: 'woocommerce.com/posts/what-is-affliate-marketing/',
					author_name: 'Kathryn Marr',
					author_avatar:
						'https://secure.gravatar.com/avatar/431b87d722d366103cc5d9b26c66c665?s=96&d=mm&r=g',
					image: 'woocommerce.com/wp-content/uploads/2022/08/blog-fb-Affiliate@2x.jpg?resize=650,340&crop=1',
				},
				{
					title: 'Ten Customer Retention Strategies to Boost Revenue for eCommerce Stores',
					date: '2022-06-22T17:58:12',
					link: 'woocommerce.com/posts/10-ecommerce-customer-retention-strategies-boost-revenue/',
					author_name: 'Craig Cohen',
					author_avatar:
						'https://secure.gravatar.com/avatar/66c306ae543fb47f594ef9b9cdb88d93?s=96&d=mm&r=g',
				},
				{
					title: 'TikTok Marketing: A Guide for WooCommerce Stores',
					date: '2022-05-25T14:03:00',
					link: 'woocommerce.com/posts/tiktok-marketing-a-guide-for-woocommerce-stores/',
					author_name: 'Elina Vilk',
					author_avatar:
						'https://secure.gravatar.com/avatar/fc7aedb0e531795eeffa3654ce203a8e?s=96&d=mm&r=g',
					image: 'woocommerce.com/wp-content/uploads/2022/05/Facebook-Post-1200x630@2x3.jpg?resize=650,340&crop=1',
				},
			],
		} );
		render( <LearnMarketing /> );

		// Click on expand button to expand the card.
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Expand' } )
		);

		// Assert that the first and second post title are in the page.
		expect(
			screen.getByText( 'Grow Your Store with an Omnichannel Presence' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'What is Affiliate Marketing and How to Use it to Make More Money Online'
			)
		).toBeInTheDocument();

		// Click on the next page button in card footer.
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Next Page' } )
		);

		// Assert that the third and fourth post title are in the page.
		expect(
			screen.getByText(
				'Ten Customer Retention Strategies to Boost Revenue for eCommerce Stores'
			)
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'TikTok Marketing: A Guide for WooCommerce Stores'
			)
		).toBeInTheDocument();
	} );
} );
