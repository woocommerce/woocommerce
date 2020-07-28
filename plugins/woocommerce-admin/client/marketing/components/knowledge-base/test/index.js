/**
 * External dependencies
 */
import { shallow, mount } from 'enzyme';
import { recordEvent } from 'lib/tracks';
import { Spinner } from '@wordpress/components';

/**
 * WooCommerce dependencies
 */
import { Card, Pagination, EmptyContent } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { KnowledgeBase } from '../index.js';
import Slider from '../../slider';

jest.mock( 'lib/tracks' );

const mockPosts = [
	{
		title: 'WooCommerce Blog Post 1',
		date: '2020-05-28T15:00:00',
		link: 'https://woocommerce.com/posts/woo-blog-post-1/',
		author_name: 'John Doe',
		author_avatar: 'https://avatar.domain/avatar1.png',
	},
	{
		title: 'WooCommerce Blog Post 2',
		date: '2020-04-29T12:00:00',
		link: 'https://woocommerce.com/posts/woo-blog-post-2/',
		author_name: 'Jane Doe',
		author_avatar: 'https://avatar.domain/avatar2.png',
	},
	{
		title: 'WooCommerce Blog Post 3',
		date: '2020-03-29T12:00:00',
		link: 'https://woocommerce.com/posts/woo-blog-post-3/',
		author_name: 'Jim Doe',
		author_avatar: 'https://avatar.domain/avatar3.png',
	},
];

describe( 'Posts and not loading', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = shallow(
			<KnowledgeBase
				posts={ mockPosts }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should not display the spinner', () => {
		const spinner = knowledgeBaseWrapper.find( Spinner );
		expect( spinner.length ).toBe( 0 );
	} );

	it( 'should display default title and description', () => {
		const cardWrapper = knowledgeBaseWrapper.find( Card );
		expect( cardWrapper.prop( 'title' ) ).toBe(
			'WooCommerce knowledge base'
		);
		expect( cardWrapper.prop( 'description' ) ).toBe(
			'Learn the ins and outs of successful marketing from the experts at WooCommerce.'
		);
	} );

	it( 'should display posts wrapper', () => {
		const postsContainer = knowledgeBaseWrapper.find(
			'div.woocommerce-marketing-knowledgebase-card__posts'
		);
		expect( postsContainer.length ).toBe( 1 );
	} );

	it( 'should display the slider', () => {
		const sliderWrapper = knowledgeBaseWrapper.find( Slider );
		expect( sliderWrapper.length ).toBe( 1 );
	} );

	it( 'should display correct number of posts', () => {
		const pageContainer = knowledgeBaseWrapper.find(
			'div.woocommerce-marketing-knowledgebase-card__page'
		);
		expect( pageContainer.length ).toBe( 1 );

		const posts = knowledgeBaseWrapper.find(
			'a.woocommerce-marketing-knowledgebase-card__post'
		);
		expect( posts.length ).toBe( 2 );
	} );

	it( 'should not display the empty content component', () => {
		const emptyContentWrapper = knowledgeBaseWrapper.find( EmptyContent );
		expect( emptyContentWrapper.length ).toBe( 0 );
	} );

	it( 'should display the pagination', () => {
		const pagerWrapper = knowledgeBaseWrapper.find( Pagination );
		expect( pagerWrapper.length ).toBe( 1 );
		const pagerButtons = pagerWrapper
			.render()
			.find( '.woocommerce-pagination__link' );
		expect( pagerButtons.length ).toBe( 2 );
	} );
} );

describe( 'No posts and loading', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = shallow(
			<KnowledgeBase
				posts={ [] }
				isLoading={ true }
				category={ 'marketing' }
			/>
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should display spinner', () => {
		const spinner = knowledgeBaseWrapper.find( Spinner );
		expect( spinner.length ).toBe( 1 );
	} );

	it( 'should not display posts wrapper', () => {
		const postsContainer = knowledgeBaseWrapper.find(
			'div.woocommerce-marketing-knowledgebase-card__posts'
		);
		expect( postsContainer.length ).toBe( 0 );
	} );

	it( 'should not display the empty content component', () => {
		const emptyContentWrapper = knowledgeBaseWrapper.find( EmptyContent );
		expect( emptyContentWrapper.length ).toBe( 0 );
	} );

	it( 'should not display the pagination', () => {
		const pagerWrapper = knowledgeBaseWrapper.find( Pagination );
		expect( pagerWrapper.length ).toBe( 0 );
	} );
} );

describe( 'No posts and not loading', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = shallow(
			<KnowledgeBase
				posts={ [] }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should not display the spinner', () => {
		const spinner = knowledgeBaseWrapper.find( Spinner );
		expect( spinner.length ).toBe( 0 );
	} );

	it( 'should not display posts wrapper', () => {
		const postsContainer = knowledgeBaseWrapper.find(
			'div.woocommerce-marketing-knowledgebase-card__posts'
		);
		expect( postsContainer.length ).toBe( 0 );
	} );

	it( 'should display the empty content component', () => {
		const emptyContentWrapper = knowledgeBaseWrapper.find( EmptyContent );
		expect( emptyContentWrapper.length ).toBe( 1 );
	} );

	it( 'should not display the pagination', () => {
		const pagerWrapper = knowledgeBaseWrapper.find( Pagination );
		expect( pagerWrapper.length ).toBe( 0 );
	} );
} );

describe( 'Clicking on a post', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = shallow(
			<KnowledgeBase
				posts={ mockPosts }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should record an event when clicked', () => {
		const pageContainer = knowledgeBaseWrapper.find(
			'div.woocommerce-marketing-knowledgebase-card__page'
		);
		expect( pageContainer.length ).toBe( 1 );

		const posts = knowledgeBaseWrapper.find(
			'a.woocommerce-marketing-knowledgebase-card__post'
		);
		expect( posts.length ).toBe( 2 );

		posts.at( 0 ).simulate( 'click' );
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_knowledge_article',
			{
				title: 'WooCommerce Blog Post 1',
			}
		);

		posts.at( 1 ).simulate( 'click' );
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
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = mount(
			<KnowledgeBase
				posts={ mockPosts }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should be able to click forward and back', () => {
		const postsContainer = knowledgeBaseWrapper.find(
			'div.woocommerce-marketing-knowledgebase-card__posts'
		);
		expect( postsContainer.length ).toBe( 1 );

		const pageContainer = knowledgeBaseWrapper.find(
			'div.woocommerce-marketing-knowledgebase-card__page'
		);
		expect( pageContainer.length ).toBe( 1 );

		const pagerWrapper = knowledgeBaseWrapper.find( Pagination );
		expect( pagerWrapper.length ).toBe( 1 );
		expect( pagerWrapper.prop( 'page' ) ).toBe( 1 );

		const pagerButtons = pagerWrapper.find(
			'button.woocommerce-pagination__link'
		);
		expect( pagerButtons.length ).toBe( 2 );

		pagerButtons.at( 1 ).simulate( 'click' ); // click forward
		expect( knowledgeBaseWrapper.find( Pagination ).prop( 'page' ) ).toBe(
			2
		);
		expect(
			knowledgeBaseWrapper.find( Slider ).prop( 'animationKey' )
		).toBe( 2 );
		expect( knowledgeBaseWrapper.find( Slider ).prop( 'animate' ) ).toBe(
			'left'
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_knowledge_carousel',
			{
				direction: 'forward',
				page: 2,
			}
		);

		pagerButtons.at( 0 ).simulate( 'click' ); // click back
		expect( knowledgeBaseWrapper.find( Pagination ).prop( 'page' ) ).toBe(
			1
		);
		expect(
			knowledgeBaseWrapper.find( Slider ).prop( 'animationKey' )
		).toBe( 1 );
		expect( knowledgeBaseWrapper.find( Slider ).prop( 'animate' ) ).toBe(
			'right'
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
			link: 'https://woocommerce.com/posts/woo-blog-post-1/',
			author_name: 'John Doe',
			author_avatar: 'https://avatar.domain/avatar1.png',
		},
	];

	beforeEach( () => {
		knowledgeBaseWrapper = shallow(
			<KnowledgeBase
				posts={ mockPost }
				isLoading={ false }
				category={ 'marketing' }
			/>
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should display with correct class', () => {
		const pageContainer = knowledgeBaseWrapper.find(
			'div.page-with-single-post'
		);
		expect( pageContainer.length ).toBe( 1 );
	} );

	it( 'should display a single post', () => {
		const posts = knowledgeBaseWrapper.find(
			'a.woocommerce-marketing-knowledgebase-card__post'
		);
		expect( posts.length ).toBe( 1 );
	} );
} );

describe( 'Custom title and description ', () => {
	let knowledgeBaseWrapper;

	beforeEach( () => {
		knowledgeBaseWrapper = shallow(
			<KnowledgeBase
				posts={ mockPosts }
				isLoading={ false }
				category={ 'marketing' }
				title={ 'Custom Title' }
				description={ 'Custom Description' }
			/>
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should override defaults', () => {
		const cardWrapper = knowledgeBaseWrapper.find( Card );
		expect( cardWrapper.prop( 'title' ) ).toBe( 'Custom Title' );
		expect( cardWrapper.prop( 'description' ) ).toBe(
			'Custom Description'
		);
	} );
} );
