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

const blogPostCategory = 'marketing';
const perPage = 2;

const LearnMarketing = () => {
	const [ page, setPage ] = useState( 1 );
	const { isLoading, error, posts } = useSelect( ( select ) => {
		const { getBlogPosts, getBlogPostsError, isResolving } =
			select( STORE_KEY );

		return {
			posts: getBlogPosts( blogPostCategory ),
			isLoading: isResolving( 'getBlogPosts', [ blogPostCategory ] ),
			error: getBlogPostsError( blogPostCategory ),
		};
	}, [] );

	const renderFooter = () => {
		if ( isLoading ) {
			return (
				<div className="woocommerce-marketing-learn-marketing-card__footer--placeholder"></div>
			);
		}

		if ( posts.length ) {
			return (
				<Pagination
					showPagePicker={ false }
					showPerPagePicker={ false }
					page={ page }
					perPage={ perPage }
					total={ posts && posts.length }
					onPageChange={ ( newPage: number ) => {
						setPage( newPage );
					} }
				/>
			);
		}

		return null;
	};

	return (
		<CollapsibleCard
			initialCollapsed={ false }
			className="woocommerce-marketing-learn-marketing-card"
			header={ __( 'Learn about marketing a store', 'woocommerce' ) }
			footer={ renderFooter() }
		>
			<div className="woocommerce-marketing-learn-marketing-card__posts">
				{ isLoading && (
					<>
						<PlaceholderPostTile />
						<PlaceholderPostTile />
					</>
				) }
				{ error && (
					<EmptyContent
						title={ __(
							"Oops, our posts aren't loading right now",
							'woocommerce'
						) }
						message={ <ReadBlogMessage /> }
						illustration=""
						actionLabel=""
					/>
				) }
				{ posts.length > 0 && (
					<>
						<PostTile post={ posts[ ( page - 1 ) * perPage ] } />
						<PostTile
							post={ posts[ ( page - 1 ) * perPage + 1 ] }
						/>
					</>
				) }
			</div>
		</CollapsibleCard>
	);
};

export default LearnMarketing;
