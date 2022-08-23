/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Pagination, EmptyContent } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CollapsibleCard, ReadBlogMessage } from '../components';
import { STORE_KEY } from '../data/constants';
import './LearnMarketing.scss';

type Post = {
	author_avatar: string;
	author_name: string;
	date: string;
	image: string;
	link: string;
	title: string;
};

type PostTileProps = {
	post: Post;
};

const PlaceholderPostTile = () => {
	return (
		<div className="woocommerce-marketing-learn-marketing-card__post">
			<div className="woocommerce-marketing-learn-marketing-card__post-img woocommerce-marketing-learn-marketing-card__post-img--placeholder"></div>
			<div className="woocommerce-marketing-learn-marketing-card__post-title woocommerce-marketing-learn-marketing-card__post-title--placeholder"></div>
			<div className="woocommerce-marketing-learn-marketing-card__post-description woocommerce-marketing-learn-marketing-card__post-description--placeholder"></div>
		</div>
	);
};

const PostTile: React.FC< PostTileProps > = ( { post } ) => {
	return (
		<a
			className="woocommerce-marketing-learn-marketing-card__post"
			href={ post.link }
			target="_blank"
			rel="noopener noreferrer"
			onClick={ () => {
				recordEvent( 'marketing_knowledge_article', {
					title: post.title,
				} );
			} }
		>
			<div className="woocommerce-marketing-learn-marketing-card__post-img">
				{ post.image && <img src={ post.image } alt="" /> }
			</div>
			<div className="woocommerce-marketing-learn-marketing-card__post-title">
				{ post.title }
			</div>
			<div className="woocommerce-marketing-learn-marketing-card__post-description">
				{
					// translators: %s: author's name.
					sprintf( __( 'By %s', 'woocommerce' ), post.author_name )
				}
				{ post.author_avatar && (
					<img
						src={ post.author_avatar.replace( 's=96', 's=32' ) }
						alt=""
					/>
				) }
			</div>
		</a>
	);
};

const BLOG_POST_CATEGORY = 'marketing';
const PER_PAGE = 2;

const LearnMarketing = () => {
	const [ page, setPage ] = useState( 1 );
	const { isLoading, error, posts } = useSelect(
		( select ) => {
			const { getBlogPosts, getBlogPostsError, isResolving } =
				select( STORE_KEY );

			return {
				posts: getBlogPosts( BLOG_POST_CATEGORY ),
				isLoading: isResolving( 'getBlogPosts', [
					BLOG_POST_CATEGORY,
				] ),
				error: getBlogPostsError( BLOG_POST_CATEGORY ),
			};
		},
		[ BLOG_POST_CATEGORY ]
	);

	/**
	 * Renders card footer.
	 *
	 * - If loading is in progress, it returns a loading placeholder in the card footer.
	 * - If there is an error or there are no posts, there will be no card footer.
	 * - Returns a pagination component in the card footer for paging through the posts.
	 */
	const renderFooter = () => {
		if ( isLoading ) {
			return (
				<div className="woocommerce-marketing-learn-marketing-card__footer--placeholder"></div>
			);
		}

		if ( error || ! posts || posts.length === 0 ) {
			return null;
		}

		return (
			<Pagination
				showPagePicker={ false }
				showPerPagePicker={ false }
				page={ page }
				perPage={ PER_PAGE }
				total={ posts.length }
				onPageChange={ ( newPage: number ) => {
					setPage( newPage );
				} }
			/>
		);
	};

	/**
	 * Renders card body, which should display two posts in one page.
	 *
	 * - If loading is in progress, it returns two placeholder post tiles.
	 * - If there is an error, it returns an `EmptyContent` component with error message.
	 * - If there are no posts, it returns an `EmptyContent` component with "No posts yet" message.
	 * - Else, it returns two post tiles.
	 */
	const renderBody = () => {
		if ( isLoading ) {
			return [ ...Array( PER_PAGE ).keys() ].map( ( key ) => {
				return <PlaceholderPostTile key={ key } />;
			} );
		}

		if ( error ) {
			return (
				<EmptyContent
					title={ __(
						"Oops, our posts aren't loading right now",
						'woocommerce'
					) }
					message={ <ReadBlogMessage /> }
					illustration=""
					actionLabel=""
				/>
			);
		}

		if ( posts.length === 0 ) {
			return (
				<EmptyContent
					title={ __( 'No posts yet', 'woocommerce' ) }
					message={ <ReadBlogMessage /> }
					illustration=""
					actionLabel=""
				/>
			);
		}

		return posts
			.slice( ( page - 1 ) * PER_PAGE, page * PER_PAGE )
			.map( ( post: Post, index: number ) => {
				return <PostTile key={ index } post={ post } />;
			} );
	};

	return (
		<CollapsibleCard
			initialCollapsed
			className="woocommerce-marketing-learn-marketing-card"
			header={ __( 'Learn about marketing a store', 'woocommerce' ) }
			footer={ renderFooter() }
		>
			<div className="woocommerce-marketing-learn-marketing-card__body">
				{ renderBody() }
			</div>
		</CollapsibleCard>
	);
};

export default LearnMarketing;
