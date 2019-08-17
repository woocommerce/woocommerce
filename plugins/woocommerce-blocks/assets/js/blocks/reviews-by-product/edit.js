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
	Disabled,
	Notice,
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
	Spinner,
	ToggleControl,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import classNames from 'classnames';
import { SearchListItem } from '@woocommerce/components';
import { Fragment, RawHTML } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { escapeHTML } from '@wordpress/escape-html';
import PropTypes from 'prop-types';
import { getAdminLink } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ApiErrorPlaceholder from '../../components/api-error-placeholder';
import EditorBlock from './editor-block.js';
import ProductControl from '../../components/product-control';
import ToggleButtonControl from '../../components/toggle-button-control';
import { IconReviewsByProduct } from '../../components/icons';
import { withProduct } from '../../hocs';
import { ENABLE_REVIEW_RATING, SHOW_AVATARS } from '../../constants';

/**
 * Component to handle edit mode of "Reviews by Product".
 */
const ReviewsByProductEditor = ( { attributes, debouncedSpeak, error, getProduct, isLoading, product, setAttributes } ) => {
	const { className, editMode, productId, showReviewDate, showReviewerName } = attributes;

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
		const minPerPage = 1;
		const maxPerPage = 20;

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
					<ToggleControl
						label={ __( 'Product rating', 'woo-gutenberg-products-block' ) }
						checked={ attributes.showReviewRating }
						onChange={ () => setAttributes( { showReviewRating: ! attributes.showReviewRating } ) }
					/>
					{ ( attributes.showReviewRating && ! ENABLE_REVIEW_RATING ) && (
						<Notice className="wc-block-reviews-by-product__notice" isDismissible={ false }>
							<RawHTML>
								{ sprintf( __( 'Product rating is disabled in your %sstore settings%s.', 'woo-gutenberg-products-block' ), `<a href="${ getAdminLink( 'admin.php?page=wc-settings&tab=products' ) }" target="_blank">`, '</a>' ) }
							</RawHTML>
						</Notice>
					) }
					<ToggleControl
						label={ __( 'Reviewer name', 'woo-gutenberg-products-block' ) }
						checked={ attributes.showReviewerName }
						onChange={ () => setAttributes( { showReviewerName: ! attributes.showReviewerName } ) }
					/>
					<ToggleControl
						label={ __( 'Image', 'woo-gutenberg-products-block' ) }
						checked={ attributes.showReviewImage }
						onChange={ () => setAttributes( { showReviewImage: ! attributes.showReviewImage } ) }
					/>
					<ToggleControl
						label={ __( 'Review date', 'woo-gutenberg-products-block' ) }
						checked={ attributes.showReviewDate }
						onChange={ () => setAttributes( { showReviewDate: ! attributes.showReviewDate } ) }
					/>
					{ attributes.showReviewImage && (
						<Fragment>
							<ToggleButtonControl
								label={ __( 'Review image', 'woo-gutenberg-products-block' ) }
								value={ attributes.imageType }
								options={ [
									{ label: __( 'Reviewer photo', 'woo-gutenberg-products-block' ), value: 'reviewer' },
									{ label: __( 'Product', 'woo-gutenberg-products-block' ), value: 'product' },
								] }
								onChange={ ( value ) => setAttributes( { imageType: value } ) }
							/>
							{ ( attributes.imageType === 'reviewer' && ! SHOW_AVATARS ) && (
								<Notice className="wc-block-reviews-by-product__notice" isDismissible={ false }>
									<RawHTML>
										{ sprintf( __( 'Reviewer photo is disabled in your %ssite settings%s.', 'woo-gutenberg-products-block' ), `<a href="${ getAdminLink( 'options-discussion.php' ) }" target="_blank">`, '</a>' ) }
									</RawHTML>
								</Notice>
							) }
						</Fragment>
					) }
				</PanelBody>
				<PanelBody title={ __( 'List Settings', 'woo-gutenberg-products-block' ) }>
					<ToggleControl
						label={ __( 'Order by', 'woo-gutenberg-products-block' ) }
						checked={ attributes.showOrderby }
						onChange={ () => setAttributes( { showOrderby: ! attributes.showOrderby } ) }
					/>
					<SelectControl
						label={ __( 'Order Product Reviews by', 'woo-gutenberg-products-block' ) }
						value={ attributes.orderby }
						options={ [
							{ label: 'Most recent', value: 'most-recent' },
							{ label: 'Highest Rating', value: 'highest-rating' },
							{ label: 'Lowest Rating', value: 'lowest-rating' },
						] }
						onChange={ ( orderby ) => setAttributes( { orderby } ) }
					/>
					<RangeControl
						label={ __( 'Starting Number of Reviews', 'woo-gutenberg-products-block' ) }
						value={ attributes.reviewsOnPageLoad }
						onChange={ ( reviewsOnPageLoad ) => setAttributes( { reviewsOnPageLoad } ) }
						max={ maxPerPage }
						min={ minPerPage }
					/>
					<ToggleControl
						label={ __( 'Load more', 'woo-gutenberg-products-block' ) }
						checked={ attributes.showLoadMore }
						onChange={ () => setAttributes( { showLoadMore: ! attributes.showLoadMore } ) }
					/>
					{ attributes.showLoadMore && (
						<RangeControl
							label={ __( 'Load More Reviews', 'woo-gutenberg-products-block' ) }
							value={ attributes.reviewsOnLoadMore }
							onChange={ ( reviewsOnLoadMore ) => setAttributes( { reviewsOnLoadMore } ) }
							max={ maxPerPage }
							min={ minPerPage }
						/>
					) }
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

	const renderViewMode = () => {
		const showReviewImage = ( SHOW_AVATARS || attributes.imageType === 'product' ) && attributes.showReviewImage;
		const showReviewRating = ENABLE_REVIEW_RATING && attributes.showReviewRating;
		const classes = classNames( 'wc-block-reviews-by-product', className, {
			'has-image': showReviewImage,
			'has-name': showReviewerName,
			'has-date': showReviewDate,
			'has-rating': showReviewRating,
		} );

		return (
			<Fragment>
				{ product.review_count === 0 ? (
					<Placeholder
						className="wc-block-reviews-by-product"
						icon={ <IconReviewsByProduct className="block-editor-block-icon" /> }
						label={ __( 'Reviews by Product', 'woo-gutenberg-products-block' ) }
					>
						<div dangerouslySetInnerHTML={ {
							__html: sprintf(
								__(
									"This block lists reviews for a selected product. %s doesn't have any reviews yet, but they will show up here when it does.",
									'woo-gutenberg-products-block'
								),
								'<strong>' + escapeHTML( product.name ) + '</strong>'
							),
						} } />
					</Placeholder>
				) : (
					<Disabled>
						<div className={ classes }>
							<EditorBlock attributes={ attributes } />
						</div>
					</Disabled>
				) }
			</Fragment>
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
