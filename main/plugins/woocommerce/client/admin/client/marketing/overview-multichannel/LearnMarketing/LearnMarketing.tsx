/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Pagination, EmptyContent } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { CollapsibleCard, ReadBlogMessage } from '~/marketing/components';
import { PlaceholderPostTile } from './PlaceholderPostTile';
import { PostTile } from './PostTile';
import { useBlogPosts } from './useBlogPosts';
import './LearnMarketing.scss';

const BLOG_POST_CATEGORY = 'marketing';
const PER_PAGE = 2;

export const LearnMarketing = () => {
	const [ page, setPage ] = useState( 1 );
	const { isLoading, error, posts } = useBlogPosts( BLOG_POST_CATEGORY );

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
				<div
					role="progressbar"
					className="woocommerce-marketing-learn-marketing-card__footer--placeholder"
				/>
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
			.map( ( post, index ) => {
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
