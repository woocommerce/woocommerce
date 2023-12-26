/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { KnowledgeBase } from '../index.js';

jest.mock( '@woocommerce/tracks' );

const mockPosts = [
	{
		title: 'WooCommerce Blog Post 1',
		date: '2020-05-28T15:00:00',
		link: 'https://woo.com/posts/woo-blog-post-1/',
		author_name: 'John Doe',
		author_avatar: 'https://avatar.domain/avatar1.png',
	},
	{
		title: 'WooCommerce Blog Post 2',
		date: '2020-04-29T12:00:00',
		link: 'https://woo.com/posts/woo-blog-post-2/',
		author_name: 'Jane Doe',
		author_avatar: 'https://avatar.domain/avatar2.png',
	},
	{
		title: 'WooCommerce Blog Post 3',
		date: '2020-03-29T12:00:00',
		link: 'https://woo.com/posts/woo-blog-post-3/',
		author_name: 'Jim Doe',
		author_avatar: 'https://avatar.domain/avatar3.png',
	},
];

describe( 'Posts and not loading', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = render(
			<KnowledgeBase
				posts={ mockPosts }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should not display the placeholder', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.querySelector( '.is-loading, .is-placeholder' )
		).toBeFalsy();
	} );

	it( 'should display default title and description', () => {
		const { getByText } = knowledgeBaseWrapper;

		expect( getByText( 'WooCommerce knowledge base' ) ).toBeInTheDocument();

		expect(
			getByText(
				'Learn the ins and outs of successful marketing from the experts at WooCommerce.'
			)
		).toBeInTheDocument();
	} );

	it( 'should display posts wrapper', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-knowledgebase-card__posts'
			)
		).toHaveLength( 1 );
	} );

	it( 'should display the slider', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName( 'woocommerce-marketing-slider' )
		).toHaveLength( 1 );
	} );

	it( 'should display correct number of posts', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-knowledgebase-card__page'
			)
		).toHaveLength( 1 );

		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-knowledgebase-card__post'
			)
		).toHaveLength( 2 );
	} );

	it( 'should not display the empty content component', () => {
		const { queryByText } = knowledgeBaseWrapper;

		expect( queryByText( 'No posts yet' ) ).toBeNull();
		expect( queryByText( /Read /i ) ).toBeNull();
		expect( queryByText( 'the WooCommerce blog' ) ).toBeNull();
		expect(
			queryByText( / for more tips on marketing your store/i )
		).toBeNull();
	} );

	it( 'should display the pagination', () => {
		const { getByLabelText } = knowledgeBaseWrapper;

		expect(
			getByLabelText( 'Previous Page', { selector: 'button' } )
		).toBeInTheDocument();
		expect(
			getByLabelText( 'Next Page', { selector: 'button' } )
		).toBeInTheDocument();
	} );
} );

describe( 'No posts and loading', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = render(
			<KnowledgeBase
				posts={ [] }
				isLoading={ true }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should display placeholder', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.querySelector(
				'.is-loading.woocommerce-marketing-knowledgebase-card__post'
			)
		).toBeTruthy();
	} );

	it( 'should not display slider', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-slider__slide'
			)
		).toHaveLength( 0 );
	} );

	it( 'should display posts wrapper', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-knowledgebase-card__posts'
			)
		).toHaveLength( 1 );
	} );

	it( 'should not display the empty content component', () => {
		const { queryByText } = knowledgeBaseWrapper;

		expect( queryByText( 'No posts yet' ) ).toBeNull();
		expect( queryByText( /Read /i ) ).toBeNull();
		expect( queryByText( 'the WooCommerce blog' ) ).toBeNull();
		expect(
			queryByText( / for more tips on marketing your store/i )
		).toBeNull();
	} );

	it( 'should not display the pagination', () => {
		const { queryByLabelText } = knowledgeBaseWrapper;

		expect(
			queryByLabelText( 'Previous Page', { selector: 'button' } )
		).toBeNull();
		expect(
			queryByLabelText( 'Next Page', { selector: 'button' } )
		).toBeNull();
	} );
} );

describe( 'Error and not loading', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = render(
			<KnowledgeBase
				posts={ [] }
				isLoading={ false }
				error={ {
					message: 'error',
				} }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should not display the placeholder', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.querySelector( '.is-loading, .is-placeholder' )
		).toBeFalsy();
	} );

	it( 'should not display posts wrapper', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-knowledgebase-card__posts'
			)
		).toHaveLength( 0 );
	} );

	it( 'should display the error component', () => {
		const { getByText } = knowledgeBaseWrapper;

		expect(
			getByText( "Oops, our posts aren't loading right now" )
		).toBeInTheDocument();
		expect( getByText( /Read /i ) ).toBeInTheDocument();
		expect( getByText( 'the WooCommerce blog' ) ).toBeInTheDocument();
		expect(
			getByText( / for more tips on marketing your store/i )
		).toBeInTheDocument();
	} );

	it( 'should not display the pagination', () => {
		const { queryByLabelText } = knowledgeBaseWrapper;

		expect(
			queryByLabelText( 'Previous Page', { selector: 'button' } )
		).toBeNull();
		expect(
			queryByLabelText( 'Next Page', { selector: 'button' } )
		).toBeNull();
	} );
} );

describe( 'No posts and not loading', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = render(
			<KnowledgeBase
				posts={ [] }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should not display the placeholder', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.querySelector( '.is-loading, .is-placeholder' )
		).toBeFalsy();
	} );

	it( 'should not display posts wrapper', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-knowledgebase-card__posts'
			)
		).toHaveLength( 0 );
	} );

	it( 'should display the empty content component', () => {
		const { getByText } = knowledgeBaseWrapper;

		expect( getByText( 'No posts yet' ) ).toBeInTheDocument();
		expect( getByText( /Read /i ) ).toBeInTheDocument();
		expect( getByText( 'the WooCommerce blog' ) ).toBeInTheDocument();
		expect(
			getByText( / for more tips on marketing your store/i )
		).toBeInTheDocument();
	} );

	it( 'should not display the pagination', () => {
		const { queryByLabelText } = knowledgeBaseWrapper;

		expect(
			queryByLabelText( 'Previous Page', { selector: 'button' } )
		).toBeNull();
		expect(
			queryByLabelText( 'Next Page', { selector: 'button' } )
		).toBeNull();
	} );
} );

describe( 'Clicking on a post', () => {
	afterAll( () => jest.clearAllMocks() );

	it( 'should record an event when clicked', () => {
		const { getByRole } = render(
			<KnowledgeBase
				posts={ mockPosts }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);

		userEvent.click( getByRole( 'link', { name: /Post 1/ } ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_knowledge_article',
			{
				title: 'WooCommerce Blog Post 1',
			}
		);

		userEvent.click( getByRole( 'link', { name: /Post 2/ } ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 2 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_knowledge_article',
			{
				title: 'WooCommerce Blog Post 2',
			}
		);
	} );
} );

describe( 'Pagination', () => {
	afterAll( () => jest.clearAllMocks() );

	it( 'should be able to click forward and back', async () => {
		const { container, getByLabelText } = render(
			<KnowledgeBase
				posts={ mockPosts }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);

		userEvent.click(
			getByLabelText( 'Next Page', { selector: 'button' } )
		);

		await waitFor( () =>
			expect(
				container.getElementsByClassName(
					'woocommerce-marketing-slider animate-left'
				)
			).toHaveLength( 1 )
		);

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_knowledge_carousel',
			{
				direction: 'forward',
				page: 2,
			}
		);

		userEvent.click(
			getByLabelText( 'Previous Page', { selector: 'button' } )
		);

		await waitFor( () =>
			expect(
				container.getElementsByClassName(
					'woocommerce-marketing-slider animate-right'
				)
			).toHaveLength( 1 )
		);

		expect( recordEvent ).toHaveBeenCalledTimes( 2 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_knowledge_carousel',
			{
				direction: 'back',
				page: 1,
			}
		);
	} );
} );

describe( 'Page with single post', () => {
	let knowledgeBaseWrapper;

	const mockPost = [
		{
			title: 'WooCommerce Blog Post 1',
			date: '2020-05-28T15:00:00',
			link: 'https://woo.com/posts/woo-blog-post-1/',
			author_name: 'John Doe',
			author_avatar: 'https://avatar.domain/avatar1.png',
		},
	];

	beforeEach( () => {
		knowledgeBaseWrapper = render(
			<KnowledgeBase
				posts={ mockPost }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	it( 'should display with correct class', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName( 'page-with-single-post' )
		).toHaveLength( 1 );
	} );

	it( 'should display a single post', () => {
		const { container } = knowledgeBaseWrapper;
		expect(
			container.getElementsByClassName(
				'woocommerce-marketing-knowledgebase-card__post'
			)
		).toHaveLength( 1 );
	} );
} );

describe( 'Custom title and description', () => {
	it( 'should override defaults', () => {
		const { getByText } = render(
			<KnowledgeBase
				posts={ mockPosts }
				isLoading={ false }
				category={ 'marketing' }
				title={ 'Custom Title' }
				description={ 'Custom Description' }
			/>
		);

		expect( getByText( 'Custom Title' ) ).toBeInTheDocument();
		expect( getByText( 'Custom Description' ) ).toBeInTheDocument();
	} );
} );
