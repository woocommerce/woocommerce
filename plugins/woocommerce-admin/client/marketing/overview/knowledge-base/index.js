/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import classNames from 'classnames';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, Pagination } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss'
import { recordEvent } from 'lib/tracks';
import { Slider } from '../../components';
import { STORE_KEY } from '../../data/constants';

class KnowledgeBase extends Component {

	constructor( props ) {
		super( props );
		this.state = {
			page: 1,
			animate: null,
			isLoading: true,
		};
		this.onPaginationPageChange = this.onPaginationPageChange.bind( this );
	}

	onPaginationPageChange( newPage ) {
		const { page } = this.state;
		let animate;

		if ( newPage > page ) {
			animate = 'left';
			recordEvent( 'marketing_knowledge_carousel', { direction: 'forward', page: newPage } );
		} else {
			animate = 'right';
			recordEvent( 'marketing_knowledge_carousel', { direction: 'back', page: newPage } );
		}

		this.setState( {
			page: newPage,
			animate,
		} );
	}

	onPostClick( post ) {
		recordEvent( 'marketing_knowledge_article', { title: post.title } );
	}

	/**
	 * Get the 2 posts we need for the current page
	 */
	getCurrentSlide() {
		const { posts } = this.props;
		const { page } = this.state;
		const currentPosts = posts.slice( ( page - 1 ) * 2, ( page - 1 ) * 2 + 2 );
		const pageClass = classNames( 'woocommerce-marketing-knowledgebase-card__page', {
			'page-with-single-post': currentPosts.length === 1,
		} );

		return (
			<div className={ pageClass }>
				{ currentPosts.map( ( post, index ) => {
					return (
						<a
							className="woocommerce-marketing-knowledgebase-card__post"
							href={ post.link }
							key={ index }
							onClick={ this.onPostClick( this, post ) }
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
											src={ post.author_avatar.replace( 's=96', 's=32' ) }
											className="woocommerce-gravatar"
											alt=""
											width="16"
											height="16"
										/>
									) }
								</p>
							</div>
						</a>
					)
				} ) }
			</div>
		);
	}

	render() {
		const { posts, isLoading } = this.props;
		const { page, animate } = this.state;

		return (
			<Card
				title={ __( 'WooCommerce knowledge base', 'woocommerce-admin' ) }
				description={ __( 'Learn the ins and outs of successful marketing from the experts at WooCommerce.', 'woocommerce-admin' ) }
				className="woocommerce-marketing-knowledgebase-card"
			>
				<Fragment>
					{ isLoading ? <Spinner /> : (
						<div className="woocommerce-marketing-knowledgebase-card__posts">
							<Slider animationKey={ page } animate={ animate }>
								{ this.getCurrentSlide() }
							</Slider>
							<Pagination
								page={ page }
								perPage={ 2 }
								total={ posts.length }
								onPageChange={ this.onPaginationPageChange }
								showPagePicker={ false }
								showPerPagePicker={ false }
								showPageArrowsLabel={ false }
							/>
						</div>
					) }
				</Fragment>
			</Card>
		)
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getBlogPosts, isResolving } = select( STORE_KEY );

		return {
			posts: getBlogPosts(),
			isLoading: isResolving( 'getBlogPosts' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( KnowledgeBase );
