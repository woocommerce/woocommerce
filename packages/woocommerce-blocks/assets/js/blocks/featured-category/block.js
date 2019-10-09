/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	AlignmentToolbar,
	BlockControls,
	InnerBlocks,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	PanelColorSettings,
	withColors,
} from '@wordpress/editor';
import {
	Button,
	FocalPointPicker,
	IconButton,
	PanelBody,
	Placeholder,
	RangeControl,
	ResizableBox,
	Spinner,
	ToggleControl,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { debounce, isObject } from 'lodash';
import PropTypes from 'prop-types';
import { IconFolderStar } from '../../components/icons';

/**
 * Internal dependencies
 */
import ProductCategoryControl from '../../components/product-category-control';

/**
 * The min-height for the block content.
 */
const MIN_HEIGHT = wc_product_block_data.min_height;

/**
 * Get the src from a category object, unless null (no image).
 *
 * @param {object|null} category A product category object from the API.
 * @return {string}
 */
function getCategoryImageSrc( category ) {
	if ( isObject( category.image ) ) {
		return category.image.src;
	}
	return '';
}

/**
 * Get the attachment ID from a category object, unless null (no image).
 *
 * @param {object|null} category A product category object from the API.
 * @return {int}
 */
function getCategoryImageID( category ) {
	if ( isObject( category.image ) ) {
		return category.image.id;
	}
	return 0;
}

/**
 * Generate a style object given either a product category image from the API or URL to an image.
 *
 * @param {string} url An image URL.
 * @return {object} A style object with a backgroundImage set (if a valid image is provided).
 */
function backgroundImageStyles( url ) {
	if ( url ) {
		return { backgroundImage: `url(${ url })` };
	}
	return {};
}

/**
 * Convert the selected ratio to the correct background class.
 *
 * @param {number} ratio Selected opacity from 0 to 100.
 * @return {string} The class name, if applicable (not used for ratio 0 or 50).
 */
function dimRatioToClass( ratio ) {
	return ratio === 0 || ratio === 50 ?
		null :
		`has-background-dim-${ 10 * Math.round( ratio / 10 ) }`;
}

/**
 * Component to handle edit mode of "Featured Category".
 */
class FeaturedCategory extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			category: false,
			loaded: false,
		};

		this.debouncedGetCategory = debounce( this.getCategory.bind( this ), 200 );
	}

	componentDidMount() {
		this.getCategory();
	}

	componentDidUpdate( prevProps ) {
		if ( prevProps.attributes.categoryId !== this.props.attributes.categoryId ) {
			this.debouncedGetCategory();
		}
	}

	getCategory() {
		const { categoryId } = this.props.attributes;
		if ( ! categoryId ) {
			// We've removed the selected product, or no product is selected yet.
			this.setState( { category: false, loaded: true } );
			return;
		}
		apiFetch( {
			path: `/wc/blocks/products/categories/${ categoryId }`,
		} )
			.then( ( category ) => {
				this.setState( { category, loaded: true } );
			} )
			.catch( () => {
				this.setState( { category: false, loaded: true } );
			} );
	}

	getInspectorControls() {
		const {
			attributes,
			setAttributes,
			overlayColor,
			setOverlayColor,
		} = this.props;

		const url =
			attributes.mediaSrc || getCategoryImageSrc( this.state.category );
		const { focalPoint = { x: 0.5, y: 0.5 } } = attributes;
		// FocalPointPicker was introduced in Gutenberg 5.0 (WordPress 5.2),
		// so we need to check if it exists before using it.
		const focalPointPickerExists = typeof FocalPointPicker === 'function';

		return (
			<InspectorControls key="inspector">
				<PanelBody title={ __( 'Content', 'woo-gutenberg-products-block' ) }>
					<ToggleControl
						label={ __( 'Show description', 'woo-gutenberg-products-block' ) }
						checked={ attributes.showDesc }
						onChange={ () => setAttributes( { showDesc: ! attributes.showDesc } ) }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'Overlay', 'woo-gutenberg-products-block' ) }
					colorSettings={ [
						{
							value: overlayColor.color,
							onChange: setOverlayColor,
							label: __( 'Overlay Color', 'woo-gutenberg-products-block' ),
						},
					] }
				>
					{ !! url && (
						<Fragment>
							<RangeControl
								label={ __( 'Background Opacity', 'woo-gutenberg-products-block' ) }
								value={ attributes.dimRatio }
								onChange={ ( ratio ) => setAttributes( { dimRatio: ratio } ) }
								min={ 0 }
								max={ 100 }
								step={ 10 }
							/>
							{ focalPointPickerExists &&
								<FocalPointPicker
									label={ __( 'Focal Point Picker' ) }
									url={ url }
									value={ focalPoint }
									onChange={ ( value ) => setAttributes( { focalPoint: value } ) }
								/>
							}
						</Fragment>
					) }
				</PanelColorSettings>
			</InspectorControls>
		);
	}

	renderEditMode() {
		const { attributes, debouncedSpeak, setAttributes } = this.props;
		const onDone = () => {
			setAttributes( { editMode: false } );
			debouncedSpeak(
				__(
					'Showing Featured Product block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};

		return (
			<Placeholder
				icon={ <IconFolderStar /> }
				label={ __( 'Featured Category', 'woo-gutenberg-products-block' ) }
				className="wc-block-featured-category"
			>
				{ __(
					'Visually highlight a product category and encourage prompt action',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-featured-category__selection">
					<ProductCategoryControl
						selected={ [ attributes.categoryId ] }
						onChange={ ( value = [] ) => {
							const id = value[ 0 ] ? value[ 0 ].id : 0;
							setAttributes( { categoryId: id, mediaId: 0, mediaSrc: '' } );
						} }
						isSingle
					/>
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	}

	render() {
		const { attributes, isSelected, overlayColor, setAttributes } = this.props;
		const {
			className,
			contentAlign,
			dimRatio,
			editMode,
			focalPoint,
			height,
			showDesc,
		} = attributes;
		const { loaded, category } = this.state;
		const classes = classnames(
			'wc-block-featured-category',
			{
				'is-selected': isSelected,
				'is-loading': ! category && ! loaded,
				'is-not-found': ! category && loaded,
				'has-background-dim': dimRatio !== 0,
			},
			dimRatioToClass( dimRatio ),
			contentAlign !== 'center' && `has-${ contentAlign }-content`,
			className,
		);
		const mediaId = attributes.mediaId || getCategoryImageID( category );
		const mediaSrc = attributes.mediaSrc || getCategoryImageSrc( this.state.category );
		const style = !! category ?
			backgroundImageStyles( mediaSrc ) :
			{};
		if ( overlayColor.color ) {
			style.backgroundColor = overlayColor.color;
		}
		if ( focalPoint ) {
			style.backgroundPosition = `${ focalPoint.x * 100 }% ${ focalPoint.y *
				100 }%`;
		}

		const onResizeStop = ( event, direction, elt ) => {
			setAttributes( { height: parseInt( elt.style.height ) } );
		};

		return (
			<Fragment>
				<BlockControls>
					<AlignmentToolbar
						value={ contentAlign }
						onChange={ ( nextAlign ) => {
							setAttributes( { contentAlign: nextAlign } );
						} }
					/>
					<MediaUploadCheck>
						<Toolbar>
							<MediaUpload
								onSelect={ ( media ) => {
									setAttributes( { mediaId: media.id, mediaSrc: media.url } );
								} }
								allowedTypes={ [ 'image' ] }
								value={ mediaId }
								render={ ( { open } ) => (
									<IconButton
										className="components-toolbar__control"
										label={ __( 'Edit media' ) }
										icon="format-image"
										onClick={ open }
										disabled={ ! this.state.category }
									/>
								) }
							/>
						</Toolbar>
					</MediaUploadCheck>
				</BlockControls>
				{ ! attributes.editMode && this.getInspectorControls() }
				{ editMode ? (
					this.renderEditMode()
				) : (
					<Fragment>
						{ !! category ? (
							<ResizableBox
								className={ classes }
								size={ { height } }
								minHeight={ MIN_HEIGHT }
								enable={ { bottom: true } }
								onResizeStop={ onResizeStop }
								style={ style }
							>
								<div className="wc-block-featured-category__wrapper">
									<h2
										className="wc-block-featured-category__title"
										dangerouslySetInnerHTML={ {
											__html: category.name,
										} }
									/>
									{ showDesc && (
										<div
											className="wc-block-featured-category__description"
											dangerouslySetInnerHTML={ {
												__html: category.description,
											} }
										/>
									) }
									<div className="wc-block-featured-category__link">
										<InnerBlocks
											template={ [
												[
													'core/button',
													{
														text: __(
															'Shop now',
															'woo-gutenberg-products-block'
														),
														url: category.permalink,
														align: 'center',
													},
												],
											] }
											templateLock="all"
										/>
									</div>
								</div>
							</ResizableBox>
						) : (
							<Placeholder
								className="wc-block-featured-category"
								icon={ <IconFolderStar /> }
								label={ __( 'Featured Category', 'woo-gutenberg-products-block' ) }
							>
								{ ! loaded ? (
									<Spinner />
								) : (
									__( 'No product category is selected.', 'woo-gutenberg-products-block' )
								) }
							</Placeholder>
						) }
					</Fragment>
				) }
			</Fragment>
		);
	}
}

FeaturedCategory.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * Whether this block is currently active.
	 */
	isSelected: PropTypes.bool.isRequired,
	/**
	 * The register block name.
	 */
	name: PropTypes.string.isRequired,
	/**
	 * A callback to update attributes.
	 */
	setAttributes: PropTypes.func.isRequired,
	// from withColors
	overlayColor: PropTypes.object,
	setOverlayColor: PropTypes.func.isRequired,
	// from withSpokenMessages
	debouncedSpeak: PropTypes.func.isRequired,
};

export default compose( [
	withColors( { overlayColor: 'background-color' } ),
	withSpokenMessages,
] )( FeaturedCategory );
