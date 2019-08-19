/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
} from '@wordpress/editor';
import {
	Button,
	PanelBody,
	Placeholder,
	Spinner,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import { SearchListItem } from '@woocommerce/components';
import { Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ApiErrorPlaceholder from '../../../components/api-error-placeholder';
import EditorBlock from './editor-block.js';
import ProductControl from '../../../components/product-control';
import { IconReviewsByProduct } from '../../../components/icons';
import { withProduct } from '../../../hocs';
import { ENABLE_REVIEW_RATING, SHOW_AVATARS } from '../../../constants';
import { getSharedReviewContentControls, getSharedReviewListControls } from '../edit.js';
import { getBlockClassName } from '../utils.js';

/**
 * Component to handle edit mode of "Reviews by Product".
 */
const ReviewsByProductEditor = ( { attributes, debouncedSpeak, error, getProduct, isLoading, product, setAttributes } ) => {
	attributes.showReviewImage = ( SHOW_AVATARS || attributes.imageType === 'product' ) && attributes.showReviewImage;
	attributes.showReviewRating = ENABLE_REVIEW_RATING && attributes.showReviewRating;

	const { editMode, productId, showReviewDate, showReviewerName, showReviewContent, showReviewImage, showReviewRating } = attributes;

	const getBlockControls = () => (
		<BlockControls>
			<Toolbar
				controls={ [
					{
						icon: 'edit',
						title: __( 'Edit' ),
						onClick: () => setAttributes( { editMode: ! editMode } ),
						isActive: editMode,
					},
				] }
			/>
		</BlockControls>
	);

	const renderProductControlItem = ( args ) => {
		const { item = 0 } = args;

		return (
			<SearchListItem
				{ ...args }
				countLabel={ sprintf(
					_n(
						'%d Review',
						'%d Reviews',
						item.review_count,
						'woo-gutenberg-products-block'
					),
					item.review_count
				) }
				showCount
				aria-label={ sprintf(
					_n(
						'%s, has %d review',
						'%s, has %d reviews',
						item.review_count,
						'woo-gutenberg-products-block'
					),
					item.name,
					item.review_count
				) }
			/>
		);
	};

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Product', 'woo-gutenberg-products-block' ) }
					initialOpen={ false }
				>
					<ProductControl
						selected={ attributes.productId || 0 }
						onChange={ ( value = [] ) => {
							const id = value[ 0 ] ? value[ 0 ].id : 0;
							setAttributes( { productId: id } );
						} }
						renderItem={ renderProductControlItem }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Content', 'woo-gutenberg-products-block' ) }>
					{ getSharedReviewContentControls( attributes, setAttributes ) }
				</PanelBody>
				<PanelBody title={ __( 'List Settings', 'woo-gutenberg-products-block' ) }>
					{ getSharedReviewListControls( attributes, setAttributes ) }
				</PanelBody>
			</InspectorControls>
		);
	};

	const renderApiError = () => (
		<ApiErrorPlaceholder
			className="wc-block-featured-product-error"
			error={ error }
			isLoading={ isLoading }
			onRetry={ getProduct }
		/>
	);

	const renderLoadingScreen = () => {
		return (
			<Placeholder
				icon={ <IconReviewsByProduct className="block-editor-block-icon" /> }
				label={ __( 'Reviews by Product', 'woo-gutenberg-products-block' ) }
				className="wc-block-reviews-by-product"
			>
				<Spinner />
			</Placeholder>
		);
	};

	const renderEditMode = () => {
		const onDone = () => {
			setAttributes( { editMode: false } );
			debouncedSpeak(
				__(
					'Showing Reviews by Product block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};

		return (
			<Placeholder
				icon={ <IconReviewsByProduct className="block-editor-block-icon" /> }
				label={ __( 'Reviews by Product', 'woo-gutenberg-products-block' ) }
				className="wc-block-reviews-by-product"
			>
				{ __(
					'Show reviews of your product to build trust',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-reviews-by-product__selection">
					<ProductControl
						selected={ attributes.productId || 0 }
						onChange={ ( value = [] ) => {
							const id = value[ 0 ] ? value[ 0 ].id : 0;
							setAttributes( { productId: id } );
						} }
						queryArgs={ {
							orderby: 'comment_count',
							order: 'desc',
						} }
						renderItem={ renderProductControlItem }
					/>
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	};

	const renderHiddenContentPlaceholder = () => {
		return (
			<Placeholder
				className="wc-block-reviews-by-product"
				icon={ <IconReviewsByProduct className="block-editor-block-icon" /> }
				label={ __( 'Reviews by Product', 'woo-gutenberg-products-block' ) }
			>
				{ __( 'The content for this block is hidden due to block settings.', 'woo-gutenberg-products-block' ) }
			</Placeholder>
		);
	};

	const renderViewMode = () => {
		if ( ! showReviewContent && ! showReviewRating && ! showReviewDate && ! showReviewerName && ! showReviewImage ) {
			return renderHiddenContentPlaceholder();
		}

		return (
			<div className={ getBlockClassName( 'wc-block-reviews-by-product', attributes ) }>
				<EditorBlock attributes={ attributes } />
			</div>
		);
	};

	if ( error ) {
		return renderApiError();
	}

	if ( ! productId || editMode ) {
		return renderEditMode();
	}

	if ( ! product || isLoading ) {
		return renderLoadingScreen();
	}

	return (
		<Fragment>
			{ getBlockControls() }
			{ getInspectorControls() }
			{ renderViewMode() }
		</Fragment>
	);
};

ReviewsByProductEditor.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * The register block name.
	 */
	name: PropTypes.string.isRequired,
	/**
	 * A callback to update attributes.
	 */
	setAttributes: PropTypes.func.isRequired,
	// from withProduct
	error: PropTypes.object,
	getProduct: PropTypes.func,
	isLoading: PropTypes.bool,
	product: PropTypes.shape( {
		name: PropTypes.node,
		review_count: PropTypes.number,
	} ),
	// from withSpokenMessages
	debouncedSpeak: PropTypes.func.isRequired,
};

export default compose( [
	withProduct,
	withSpokenMessages,
] )( ReviewsByProductEditor );
