/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import Editor from './edit';
import { IconReviewsByProduct } from '../../components/icons';

/**
 * Register and run the "Reviews by Product" block.
 */
registerBlockType( 'woocommerce/reviews-by-product', {
	title: __( 'Reviews by Product', 'woo-gutenberg-products-block' ),
	icon: (
		<IconReviewsByProduct fillColor="#96588a" />
	),
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Show reviews of your product to build trust.',
		'woo-gutenberg-products-block'
	),
	attributes: {
		/**
		 * Toggle for edit mode in the block preview.
		 */
		editMode: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Whether to display the reviewer or product image.
		 */
		imageType: {
			type: 'string',
			default: 'reviewer',
		},

		/**
		 * Order to use for the reviews listing.
		 */
		orderby: {
			type: 'string',
			default: 'most-recent',
		},

		/**
		 * The id of the product to load reviews for.
		 */
		productId: {
			type: 'number',
		},

		/**
		 * Number of reviews to add when clicking on load more.
		 */
		reviewsOnLoadMore: {
			type: 'number',
			default: 10,
		},

		/**
		 * Number of reviews to display on page load.
		 */
		reviewsOnPageLoad: {
			type: 'number',
			default: 10,
		},

		/**
		 * Show the load more button.
		 */
		showLoadMore: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Show the order by selector.
		 */
		showOrderby: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Show the review date.
		 */
		showReviewDate: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Show the reviewer name.
		 */
		showReviewerName: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Show the review image..
		 */
		showReviewImage: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Show the product rating.
		 */
		showReviewRating: {
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
		const { className, imageType, orderby, productId, reviewsOnPageLoad, reviewsOnLoadMore, showLoadMore, showOrderby, showReviewDate, showReviewerName, showReviewImage, showReviewRating } = attributes;

		const classes = classNames( 'wc-block-reviews-by-product', className, {
			'has-date': showReviewDate,
			'has-name': showReviewerName,
			'has-image': showReviewImage,
			'has-rating': showReviewRating,
		} );
		const data = {
			'data-image-type': imageType,
			'data-product-id': productId,
			'data-orderby': orderby,
			'data-reviews-on-page-load': reviewsOnPageLoad,
			'data-reviews-on-load-more': reviewsOnLoadMore,
			'data-show-load-more': showLoadMore,
			'data-show-orderby': showOrderby,
		};

		return (
			<div className={ classes } { ...data } />
		);
	},
} );
