/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import classNames from 'classnames';
import { withDispatch, withSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { recordEvent } from 'lib/tracks';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { Card, Pagination, EmptyContent } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import { Slider } from '../../components';
import { STORE_KEY } from '../../data/constants';

const KnowledgeBase = ( {
	posts,
	isLoading,
	title,
	description,
	category,
} ) => {
	const [ page, updatePage ] = useState( 1 );
	const [ animate, updateAnimate ] = useState( null );

	const onPaginationPageChange = ( newPage ) => {
		let newAnimate;
		if ( newPage > page ) {
			newAnimate = 'left';
			recordEvent( 'marketing_knowledge_carousel', {
				direction: 'forward',
				page: newPage,
			} );
		} else {
			newAnimate = 'right';
			recordEvent( 'marketing_knowledge_carousel', {
				direction: 'back',
				page: newPage,
			} );
		}
		updatePage( newPage );
		updateAnimate( newAnimate );
	};

	const onPostClick = ( post ) => {
		recordEvent( 'marketing_knowledge_article', { title: post.title } );
	};

	/**
	 * Get the 2 posts we need for the current page
	 */
	const getCurrentSlide = () => {
		const currentPosts = posts.slice(
			( page - 1 ) * 2,
			( page - 1 ) * 2 + 2
		);
		const pageClass = classNames(
			'woocommerce-marketing-knowledgebase-card__page',
			{
				'page-with-single-post': currentPosts.length === 1,
			}
		);

		const displayPosts = currentPosts.map( ( post, index ) => {
			return (
				<a
					className="woocommerce-marketing-knowledgebase-card__post"
					href={ post.link }
					key={ index }
					onClick={ () => {
						onPostClick( post );
					} }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ post.image && (
						<div className="woocommerce-marketing-knowledgebase-card__post-img">
							<img src={ post.image } alt="" />
						</div>
					) }
					<div className="woocommerce-marketing-knowledgebase-card__post-text">
						<h3>{ post.title }</h3>
						<p className="woocommerce-marketing-knowledgebase-card__post-meta">
							By { post.author_name }
							{ post.author_avatar && (
								<img
									src={ post.author_avatar.replace(
										's=96',
										's=32'
									) }
									className="woocommerce-gravatar"
									alt=""
									width="16"
									height="16"
								/>
							) }
						</p>
					</div>
				</a>
			);
		} );

		return <div className={ pageClass }>{ displayPosts }</div>;
	};

	const renderEmpty = () => {
		const emptyTitle = __(
			'There was an error loading knowledge base posts. Please check again later.',
			'woocommerce-admin'
		);

		return (
			<EmptyContent
				title={ emptyTitle }
				illustrationWidth={ 250 }
				actionLabel=""
			/>
		);
	};

	const renderPosts = () => {
		return (
			<div className="woocommerce-marketing-knowledgebase-card__posts">
				<Slider animationKey={ page } animate={ animate }>
					{ getCurrentSlide() }
				</Slider>
				<Pagination
					page={ page }
					perPage={ 2 }
					total={ posts.length }
					onPageChange={ onPaginationPageChange }
					showPagePicker={ false }
					showPerPagePicker={ false }
					showPageArrowsLabel={ false }
				/>
			</div>
		);
	};

	const renderCardBody = () => {
		if ( isLoading ) {
			return <Spinner />;
		}
		return posts.length === 0 ? renderEmpty() : renderPosts();
	};

	const categoryClass = category
		? `woocommerce-marketing-knowledgebase-card__category-${ category }`
		: '';

	return (
		<Card
			title={ title }
			description={ description }
			className={ classNames(
				'woocommerce-marketing-knowledgebase-card',
				categoryClass
			) }
		>
			{ renderCardBody() }
		</Card>
	);
};

KnowledgeBase.propTypes = {
	/**
	 * Array of posts.
	 */
	posts: PropTypes.arrayOf( PropTypes.object ).isRequired,
	/**
	 * Whether the card is loading.
	 */
	isLoading: PropTypes.bool.isRequired,
	/**
	 * Cart title.
	 */
	title: PropTypes.string,
	/**
	 * Card description.
	 */
	description: PropTypes.string,
	/**
	 * Category of extensions to display.
	 */
	category: PropTypes.string,
};

KnowledgeBase.defaultProps = {
	title: __( 'WooCommerce knowledge base', 'woocommerce-admin' ),
	description: __(
		'Learn the ins and outs of successful marketing from the experts at WooCommerce.',
		'woocommerce-admin'
	),
};

export { KnowledgeBase };

export default compose(
	withSelect( ( select, props ) => {
		const { getBlogPosts, isResolving } = select( STORE_KEY );

		return {
			posts: getBlogPosts( props.category ),
			isLoading: isResolving( 'getBlogPosts', [ props.category ] ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( KnowledgeBase );
