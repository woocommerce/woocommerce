/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import '../editor.scss';
import Editor from './edit';
import { IconReviewsByCategory } from '../../../components/icons';
import sharedAttributes from '../attributes';
import { getBlockClassName } from '../utils.js';

/**
 * Register and run the "Reviews by category" block.
 */
registerBlockType( 'woocommerce/reviews-by-category', {
	title: __( 'Reviews by Category', 'woo-gutenberg-products-block' ),
	icon: (
		<IconReviewsByCategory fillColor="#96588a" />
	),
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Show product reviews from specific categories.',
		'woo-gutenberg-products-block'
	),
	attributes: {
		...sharedAttributes,
		/**
		 * The ids of the categories to load reviews for.
		 */
		categoryIds: {
			type: 'array',
			default: [],
		},
		/**
		* Show the product name.
		*/
		showProductName: {
			type: 'boolean',
			default: true,
		},
	},

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <Editor { ...props } />;
	},

	/**
	 * Save the props to post content.
	 */
	save( { attributes } ) {
		const { imageType, orderby, categoryIds, reviewsOnPageLoad, reviewsOnLoadMore, showLoadMore, showOrderby } = attributes;

		const data = {
			'data-image-type': imageType,
			'data-category-ids': categoryIds.join( ',' ),
			'data-orderby': orderby,
			'data-reviews-on-page-load': reviewsOnPageLoad,
			'data-reviews-on-load-more': reviewsOnLoadMore,
			'data-show-load-more': showLoadMore,
			'data-show-orderby': showOrderby,
		};

		return (
			<div className={ getBlockClassName( 'wc-block-reviews-by-category', attributes ) } { ...data } />
		);
	},
} );
