/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
import { useCallback, useState } from 'react';
import { __ } from '@wordpress/i18n';
import {
	AlignmentToolbar,
	BlockControls,
	InnerBlocks,
	InspectorControls,
	MediaReplaceFlow,
	RichText,
	__experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles,
	__experimentalPanelColorGradientSettings as PanelColorGradientSettings,
	__experimentalUseGradient as useGradient,
} from '@wordpress/block-editor';
import { withSelect } from '@wordpress/data';
import {
	Button,
	FocalPointPicker,
	PanelBody,
	Placeholder,
	RangeControl,
	ResizableBox,
	Spinner,
	ToggleControl,
	ToolbarGroup,
	withSpokenMessages,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { compose, createHigherOrderComponent } from '@wordpress/compose';
import { isEmpty } from 'lodash';
import PropTypes from 'prop-types';
import ProductControl from '@woocommerce/editor-components/product-control';
import ErrorPlaceholder from '@woocommerce/editor-components/error-placeholder';
import TextToolbarButton from '@woocommerce/editor-components/text-toolbar-button';
import { withProduct } from '@woocommerce/block-hocs';
import { Icon, starEmpty } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { calculateBackgroundImagePosition, dimRatioToClass } from './utils';
import {
	getImageSrcFromProduct,
	getImageIdFromProduct,
} from '../../utils/products';
import { useThrottle } from '../../utils/useThrottle';

export const ConstrainedResizable = ( {
	className = '',
	onResize,
	...props
} ) => {
	const [ isResizing, setIsResizing ] = useState( false );

	const classNames = classnames( className, {
		'is-resizing': isResizing,
	} );
	const throttledResize = useThrottle(
		( event, direction, elt ) => {
			if ( ! isResizing ) setIsResizing( true );
			onResize( event, direction, elt );
		},
		50,
		{ leading: true }
	);

	return (
		<ResizableBox
			className={ classNames }
			enable={ { bottom: true } }
			onResize={ throttledResize }
			onResizeStop={ ( ...args ) => {
				onResize( ...args );
				setIsResizing( false );
			} }
			{ ...props }
		/>
	);
};

/**
 * Component to handle edit mode of "Featured Product".
 *
 * @param {Object}            props                  Incoming props for the component.
 * @param {Object}            props.attributes       Incoming block attributes.
 * @param {function(any):any} props.debouncedSpeak   Function for delayed speak.
 * @param {string}            props.error            Error message.
 * @param {function(any):any} props.getProduct       Function for getting the product.
 * @param {boolean}           props.isLoading        Whether product is loading or not.
 * @param {boolean}           props.isSelected       Whether block is selected or not.
 * @param {Object}            props.product          Product object.
 * @param {function(any):any} props.setAttributes    Setter for attributes.
 * @param {function():any}    props.triggerUrlUpdate Function for triggering a url update for product.
 */
const FeaturedProduct = ( {
	attributes,
	debouncedSpeak,
	error,
	getProduct,
	isLoading,
	isSelected,
	product,
	setAttributes,
	triggerUrlUpdate = () => void null,
} ) => {
	const { setGradient } = useGradient( {
		gradientAttribute: 'overlayGradient',
		customGradientAttribute: 'overlayGradient',
	} );
	const onResize = useCallback(
		( _event, _direction, elt ) => {
			setAttributes( { minHeight: parseInt( elt.style.height, 10 ) } );
		},
		[ setAttributes ]
	);

	const renderApiError = () => (
		<ErrorPlaceholder
			className="wc-block-featured-product-error"
			error={ error }
			isLoading={ isLoading }
			onRetry={ getProduct }
		/>
	);

	const renderEditMode = () => {
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
			<>
				{ getBlockControls() }
				<Placeholder
					icon={ <Icon icon={ starEmpty } /> }
					label={ __(
						'Featured Product',
						'woo-gutenberg-products-block'
					) }
					className="wc-block-featured-product"
				>
					{ __(
						'Visually highlight a product or variation and encourage prompt action',
						'woo-gutenberg-products-block'
					) }
					<div className="wc-block-featured-product__selection">
						<ProductControl
							selected={ attributes.productId || 0 }
							showVariations
							onChange={ ( value = [] ) => {
								const id = value[ 0 ] ? value[ 0 ].id : 0;
								setAttributes( {
									productId: id,
									mediaId: 0,
									mediaSrc: '',
								} );
								triggerUrlUpdate();
							} }
						/>
						<Button isPrimary onClick={ onDone }>
							{ __( 'Done', 'woo-gutenberg-products-block' ) }
						</Button>
					</div>
				</Placeholder>
			</>
		);
	};

	const getBlockControls = () => {
		const { contentAlign, editMode, mediaSrc } = attributes;
		const mediaId = attributes.mediaId || getImageIdFromProduct( product );

		return (
			<BlockControls>
				<AlignmentToolbar
					value={ contentAlign }
					onChange={ ( nextAlign ) => {
						setAttributes( { contentAlign: nextAlign } );
					} }
				/>
				<ToolbarGroup>
					<MediaReplaceFlow
						mediaId={ mediaId }
						mediaURL={ mediaSrc }
						accept="image/*"
						onSelect={ ( media ) => {
							setAttributes( {
								mediaId: media.id,
								mediaSrc: media.url,
							} );
						} }
						allowedTypes={ [ 'image' ] }
					/>
					{ mediaId && mediaSrc ? (
						<TextToolbarButton
							onClick={ () =>
								setAttributes( { mediaId: 0, mediaSrc: '' } )
							}
						>
							{ __( 'Reset', 'woo-gutenberg-products-block' ) }
						</TextToolbarButton>
					) : null }
				</ToolbarGroup>
				<ToolbarGroup
					controls={ [
						{
							icon: 'edit',
							title: __(
								'Edit selected product',
								'woo-gutenberg-products-block'
							),
							onClick: () =>
								setAttributes( { editMode: ! editMode } ),
							isActive: editMode,
						},
					] }
				/>
			</BlockControls>
		);
	};

	const getInspectorControls = () => {
		const url = attributes.mediaSrc || getImageSrcFromProduct( product );
		const { focalPoint = { x: 0.5, y: 0.5 } } = attributes;
		// FocalPointPicker was introduced in Gutenberg 5.0 (WordPress 5.2),
		// so we need to check if it exists before using it.
		const focalPointPickerExists = typeof FocalPointPicker === 'function';

		return (
			<>
				<InspectorControls key="inspector">
					<PanelBody
						title={ __(
							'Content',
							'woo-gutenberg-products-block'
						) }
					>
						<ToggleControl
							label={ __(
								'Show description',
								'woo-gutenberg-products-block'
							) }
							checked={ attributes.showDesc }
							onChange={ () =>
								setAttributes( {
									showDesc: ! attributes.showDesc,
								} )
							}
						/>
						<ToggleControl
							label={ __(
								'Show price',
								'woo-gutenberg-products-block'
							) }
							checked={ attributes.showPrice }
							onChange={ () =>
								setAttributes( {
									showPrice: ! attributes.showPrice,
								} )
							}
						/>
					</PanelBody>
					{ !! url && (
						<>
							{ focalPointPickerExists && (
								<PanelBody
									title={ __(
										'Media settings',
										'woo-gutenberg-products-block'
									) }
								>
									<ToggleGroupControl
										help={
											<>
												<p>
													{ __(
														'Choose “Cover” if you want the image to scale automatically to always fit its container.',
														'woo-gutenberg-products-block'
													) }
												</p>
												<p>
													{ __(
														'Note: by choosing “Cover” you will lose the ability to freely move the focal point precisely.',
														'woo-gutenberg-products-block'
													) }
												</p>
											</>
										}
										label={ __(
											'Image fit',
											'woo-gutenberg-products-block'
										) }
										value={ attributes.imageFit }
										onChange={ ( value ) =>
											setAttributes( {
												imageFit: value,
											} )
										}
									>
										<ToggleGroupControlOption
											label={ __(
												'None',
												'woo-gutenberg-products-block'
											) }
											value="none"
										/>
										<ToggleGroupControlOption
											/* translators: "Cover" is a verb that indicates an image covering the entire container. */
											label={ __(
												'Cover',
												'woo-gutenberg-products-block'
											) }
											value="cover"
										/>
									</ToggleGroupControl>
									<FocalPointPicker
										label={ __(
											'Focal Point Picker',
											'woo-gutenberg-products-block'
										) }
										url={ url }
										value={ focalPoint }
										onChange={ ( value ) =>
											setAttributes( {
												focalPoint: value,
											} )
										}
									/>
								</PanelBody>
							) }
							<PanelColorGradientSettings
								__experimentalHasMultipleOrigins
								__experimentalIsRenderedInSidebar
								title={ __(
									'Overlay',
									'woo-gutenberg-products-block'
								) }
								initialOpen={ true }
								settings={ [
									{
										colorValue: attributes.overlayColor,
										gradientValue:
											attributes.overlayGradient,
										onColorChange: ( overlayColor ) =>
											setAttributes( { overlayColor } ),
										onGradientChange: (
											overlayGradient
										) => {
											setGradient( overlayGradient );
											setAttributes( {
												overlayGradient,
											} );
										},
										label: __(
											'Color',
											'woo-gutenberg-products-block'
										),
									},
								] }
							>
								<RangeControl
									label={ __(
										'Opacity',
										'woo-gutenberg-products-block'
									) }
									value={ attributes.dimRatio }
									onChange={ ( dimRatio ) =>
										setAttributes( { dimRatio } )
									}
									min={ 0 }
									max={ 100 }
									step={ 10 }
									required
								/>
							</PanelColorGradientSettings>
						</>
					) }
				</InspectorControls>
				<InspectorControls __experimentalGroup="dimensions"></InspectorControls>
			</>
		);
	};

	const renderProduct = () => {
		const {
			contentAlign,
			dimRatio,
			focalPoint,
			imageFit,
			mediaSrc,
			minHeight,
			overlayColor,
			overlayGradient,
			showDesc,
			showPrice,
			style,
		} = attributes;

		const classes = classnames(
			'wc-block-featured-product',
			dimRatioToClass( dimRatio ),
			{
				'is-selected': isSelected && attributes.productId !== 'preview',
				'is-loading': ! product && isLoading,
				'is-not-found': ! product && ! isLoading,
				'has-background-dim': dimRatio !== 0,
			},
			contentAlign !== 'center' && `has-${ contentAlign }-content`
		);

		const containerStyle = {
			borderRadius: style?.border?.radius,
		};

		const wrapperStyle = {
			...getSpacingClassesAndStyles( attributes ).style,
			minHeight,
		};

		const backgroundImageSrc =
			mediaSrc || getImageSrcFromProduct( product );

		const backgroundImageStyle = {
			...calculateBackgroundImagePosition( focalPoint ),
			objectFit: imageFit,
		};

		const overlayStyle = {
			background: overlayGradient,
			backgroundColor: overlayColor,
		};

		return (
			<>
				<ConstrainedResizable
					enable={ { bottom: true } }
					onResize={ onResize }
					showHandle={ isSelected }
					style={ { minHeight } }
				/>
				<div className={ classes } style={ containerStyle }>
					<div
						className="wc-block-featured-product__wrapper"
						style={ wrapperStyle }
					>
						<div
							className="wc-block-featured-product__overlay"
							style={ overlayStyle }
						/>
						<img
							alt={ product.short_description }
							className="wc-block-featured-product__background-image"
							src={ backgroundImageSrc }
							style={ backgroundImageStyle }
						/>
						<h2
							className="wc-block-featured-product__title"
							dangerouslySetInnerHTML={ {
								__html: product.name,
							} }
						/>
						{ ! isEmpty( product.variation ) && (
							<h3
								className="wc-block-featured-product__variation"
								dangerouslySetInnerHTML={ {
									__html: product.variation,
								} }
							/>
						) }
						{ showDesc && (
							<div
								className="wc-block-featured-product__description"
								dangerouslySetInnerHTML={ {
									__html: product.short_description,
								} }
							/>
						) }
						{ showPrice && (
							<div
								className="wc-block-featured-product__price"
								dangerouslySetInnerHTML={ {
									__html: product.price_html,
								} }
							/>
						) }
						<div className="wc-block-featured-product__link">
							{ renderButton() }
						</div>
					</div>
				</div>
			</>
		);
	};

	const renderButton = () => {
		const buttonClasses = classnames(
			'wp-block-button__link',
			'is-style-fill'
		);
		const buttonStyle = {
			backgroundColor: 'vivid-green-cyan',
			borderRadius: '5px',
		};
		const wrapperStyle = {
			width: '100%',
		};
		return attributes.productId === 'preview' ? (
			<div className="wp-block-button aligncenter" style={ wrapperStyle }>
				<RichText.Content
					tagName="a"
					className={ buttonClasses }
					href={ product.permalink }
					title={ attributes.linkText }
					style={ buttonStyle }
					value={ attributes.linkText }
					target={ product.permalink }
				/>
			</div>
		) : (
			<InnerBlocks
				template={ [
					[
						'core/buttons',
						{
							layout: { type: 'flex', justifyContent: 'center' },
						},
						[
							[
								'core/button',
								{
									text: __(
										'Shop now',
										'woo-gutenberg-products-block'
									),
									url: product.permalink,
								},
							],
						],
					],
				] }
				templateLock="all"
			/>
		);
	};

	const renderNoProduct = () => (
		<Placeholder
			className="wc-block-featured-product"
			icon={ <Icon icon={ starEmpty } /> }
			label={ __( 'Featured Product', 'woo-gutenberg-products-block' ) }
		>
			{ isLoading ? (
				<Spinner />
			) : (
				__( 'No product is selected.', 'woo-gutenberg-products-block' )
			) }
		</Placeholder>
	);

	const { editMode } = attributes;

	if ( error ) {
		return renderApiError();
	}

	if ( editMode ) {
		return renderEditMode();
	}

	return (
		<>
			{ getBlockControls() }
			{ getInspectorControls() }
			{ product ? renderProduct() : renderNoProduct() }
		</>
	);
};

FeaturedProduct.propTypes = {
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
	// from withProduct
	error: PropTypes.object,
	getProduct: PropTypes.func,
	isLoading: PropTypes.bool,
	product: PropTypes.shape( {
		name: PropTypes.node,
		variation: PropTypes.node,
		description: PropTypes.node,
		price_html: PropTypes.node,
		permalink: PropTypes.string,
	} ),
	// from withSpokenMessages
	debouncedSpeak: PropTypes.func.isRequired,
	triggerUrlUpdate: PropTypes.func,
};

export default compose( [
	withProduct,
	withSpokenMessages,
	withSelect( ( select, { clientId }, { dispatch } ) => {
		const Block = select( 'core/block-editor' ).getBlock( clientId );
		const buttonBlockId = Block?.innerBlocks[ 0 ]?.clientId || '';
		const currentButtonAttributes =
			Block?.innerBlocks[ 0 ]?.attributes || {};
		const updateBlockAttributes = ( attributes ) => {
			if ( buttonBlockId ) {
				dispatch( 'core/block-editor' ).updateBlockAttributes(
					buttonBlockId,
					attributes
				);
			}
		};
		return { updateBlockAttributes, currentButtonAttributes };
	} ),
	createHigherOrderComponent( ( ProductComponent ) => {
		class WrappedComponent extends Component {
			state = {
				doUrlUpdate: false,
			};
			componentDidUpdate() {
				const {
					attributes,
					updateBlockAttributes,
					currentButtonAttributes,
					product,
				} = this.props;
				if (
					this.state.doUrlUpdate &&
					! attributes.editMode &&
					product?.permalink &&
					currentButtonAttributes?.url &&
					product.permalink !== currentButtonAttributes.url
				) {
					updateBlockAttributes( {
						...currentButtonAttributes,
						url: product.permalink,
					} );
					this.setState( { doUrlUpdate: false } );
				}
			}
			triggerUrlUpdate = () => {
				this.setState( { doUrlUpdate: true } );
			};
			render() {
				return (
					<ProductComponent
						triggerUrlUpdate={ this.triggerUrlUpdate }
						{ ...this.props }
					/>
				);
			}
		}
		return WrappedComponent;
	}, 'withUpdateButtonAttributes' ),
] )( FeaturedProduct );
