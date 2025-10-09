/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
import { WP_REST_API_Category } from 'wp-types';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls as GutenbergInspectorControls,
	__experimentalPanelColorGradientSettings as PanelColorGradientSettings,
	__experimentalUseGradient as useGradient,
} from '@wordpress/block-editor';
import {
	FocalPointPicker,
	PanelBody,
	RangeControl,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	TextareaControl,
	ExternalLink,
	Notice,
} from '@wordpress/components';
import { LooselyMustHave, ProductResponseItem } from '@woocommerce/types';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { useBackgroundImage } from './use-background-image';
import { BLOCK_NAMES } from './constants';
import { FeaturedItemRequiredAttributes } from './with-featured-item';
import { EditorBlock, ImageFit } from './types';

type InspectorControlRequiredKeys =
	| 'dimRatio'
	| 'focalPoint'
	| 'hasParallax'
	| 'imageFit'
	| 'isRepeated'
	| 'overlayColor'
	| 'overlayGradient';

interface InspectorControlsRequiredAttributes
	extends LooselyMustHave<
		FeaturedItemRequiredAttributes,
		InspectorControlRequiredKeys
	> {
	alt: string;
	backgroundImageSrc: string;
}

interface InspectorControlsProps extends InspectorControlsRequiredAttributes {
	setAttributes: (
		attrs: Partial< InspectorControlsRequiredAttributes >
	) => void;
	// Gutenberg doesn't provide some types, so we have to hard-code them here
	setGradient: ( newGradientValue: string ) => void;
}

interface WithInspectorControlsRequiredProps< T > {
	attributes: InspectorControlsRequiredAttributes &
		EditorBlock< T >[ 'attributes' ];
	setAttributes: InspectorControlsProps[ 'setAttributes' ];
	backgroundColorVisibilityStatus: {
		isBackgroundVisible: boolean;
		message: string | null;
	};
}

interface WithInspectorControlsCategoryProps< T >
	extends WithInspectorControlsRequiredProps< T > {
	category: WP_REST_API_Category;
	product: never;
}

interface WithInspectorControlsProductProps< T >
	extends WithInspectorControlsRequiredProps< T > {
	category: never;
	product: ProductResponseItem;
}

type WithInspectorControlsProps< T extends EditorBlock< T > > =
	| ( T & WithInspectorControlsCategoryProps< T > )
	| ( T & WithInspectorControlsProductProps< T > );

export const InspectorControls = ( {
	alt,
	backgroundImageSrc,
	dimRatio,
	focalPoint,
	hasParallax,
	imageFit,
	isRepeated,
	overlayColor,
	overlayGradient,
	setAttributes,
	setGradient,
	backgroundColorVisibilityStatus,
	backgroundColor,
}: InspectorControlsProps ) => {
	// FocalPointPicker was introduced in Gutenberg 5.0 (WordPress 5.2),
	// so we need to check if it exists before using it.
	const focalPointPickerExists = typeof FocalPointPicker === 'function';

	const isImgElement = ! isRepeated && ! hasParallax;

	return (
		<>
			<GutenbergInspectorControls key="inspector">
				{ !! backgroundImageSrc && (
					<>
						{ focalPointPickerExists && (
							<PanelBody
								title={ __( 'Media settings', 'woocommerce' ) }
							>
								<ToggleControl
									label={ __(
										'Fixed background',
										'woocommerce'
									) }
									checked={ hasParallax }
									onChange={ () => {
										setAttributes( {
											hasParallax: ! hasParallax,
										} );
									} }
								/>
								<ToggleControl
									label={ __(
										'Repeated background',
										'woocommerce'
									) }
									checked={ isRepeated }
									onChange={ () => {
										setAttributes( {
											isRepeated: ! isRepeated,
										} );
									} }
								/>
								{ ! isRepeated && (
									<ToggleGroupControl
										help={
											<>
												<span
													style={ {
														display: 'block',
														marginBottom: '1em',
													} }
												>
													{ __(
														'Select “Cover” to have the image automatically fit its container.',
														'woocommerce'
													) }
												</span>
												<span>
													{ __(
														'This may affect your ability to freely move the focal point of the image.',
														'woocommerce'
													) }
												</span>
											</>
										}
										label={ __(
											'Image fit',
											'woocommerce'
										) }
										isBlock
										value={ imageFit }
										onChange={ ( value: ImageFit ) =>
											setAttributes( {
												imageFit: value,
											} )
										}
									>
										<ToggleGroupControlOption
											label={ __(
												'None',
												'woocommerce'
											) }
											value="none"
										/>
										<ToggleGroupControlOption
											/* translators: "Cover" is a verb that indicates an image covering the entire container. */
											label={ __(
												'Cover',
												'woocommerce'
											) }
											value="cover"
										/>
									</ToggleGroupControl>
								) }
								<FocalPointPicker
									label={ __(
										'Focal Point Picker',
										'woocommerce'
									) }
									url={ backgroundImageSrc }
									value={ focalPoint }
									onChange={ ( value ) =>
										setAttributes( {
											focalPoint: value,
										} )
									}
								/>
								{ isImgElement && (
									<TextareaControl
										label={ __(
											'Alt text (alternative text)',
											'woocommerce'
										) }
										value={ alt }
										onChange={ ( value: string ) => {
											setAttributes( { alt: value } );
										} }
										help={
											<>
												<ExternalLink href="https://www.w3.org/WAI/tutorials/images/decision-tree">
													{ __(
														'Describe the purpose of the image',
														'woocommerce'
													) }
												</ExternalLink>
											</>
										}
									/>
								) }
							</PanelBody>
						) }
						<PanelColorGradientSettings
							__experimentalHasMultipleOrigins
							__experimentalIsRenderedInSidebar
							title={ __( 'Overlay', 'woocommerce' ) }
							initialOpen={ true }
							settings={ [
								{
									colorValue: overlayColor,
									gradientValue: overlayGradient,
									onColorChange: ( value: string ) =>
										setAttributes( {
											overlayColor: value,
										} ),
									onGradientChange: ( value: string ) => {
										setGradient( value );
										setAttributes( {
											overlayGradient: value,
										} );
									},
									label: __( 'Color', 'woocommerce' ),
								},
							] }
						>
							<RangeControl
								label={ __( 'Opacity', 'woocommerce' ) }
								value={ dimRatio }
								onChange={ ( value ) =>
									setAttributes( {
										dimRatio: value as number,
									} )
								}
								min={ 0 }
								max={ 100 }
								step={ 10 }
								required
							/>
						</PanelColorGradientSettings>
					</>
				) }
			</GutenbergInspectorControls>
			<GutenbergInspectorControls group="color">
				{ backgroundColorVisibilityStatus &&
					backgroundColorVisibilityStatus.isBackgroundVisible ===
						false &&
					backgroundColorVisibilityStatus.message &&
					backgroundColor && (
						<div className="image-bg-color-warning">
							<Notice status="warning" isDismissible={ false }>
								{ backgroundColorVisibilityStatus.message }
							</Notice>
						</div>
					) }
			</GutenbergInspectorControls>
		</>
	);
};

export const withInspectorControls =
	< T extends EditorBlock< T > >( Component: ComponentType< T > ) =>
	( props: WithInspectorControlsProps< T > ) => {
		const {
			attributes,
			name,
			setAttributes,
			backgroundColorVisibilityStatus,
		} = props;
		const {
			alt,
			dimRatio,
			focalPoint,
			hasParallax,
			isRepeated,
			imageFit,
			mediaId,
			mediaSrc,
			overlayColor,
			overlayGradient,
			backgroundColor,
			style,
		} = attributes;

		const item =
			name === BLOCK_NAMES.featuredProduct
				? props.product
				: props.category;

		const { setGradient } = useGradient( {
			gradientAttribute: 'overlayGradient',
			customGradientAttribute: 'overlayGradient',
		} );

		const { backgroundImageSrc } = useBackgroundImage( {
			item,
			mediaId,
			mediaSrc,
			blockName: name,
		} );

		return (
			<>
				<InspectorControls
					alt={ alt }
					backgroundImageSrc={ backgroundImageSrc }
					dimRatio={ dimRatio }
					focalPoint={ focalPoint }
					hasParallax={ hasParallax }
					isRepeated={ isRepeated }
					imageFit={ imageFit }
					overlayColor={ overlayColor }
					overlayGradient={ overlayGradient }
					setAttributes={ setAttributes }
					setGradient={ setGradient }
					backgroundColorVisibilityStatus={
						backgroundColorVisibilityStatus
					}
					backgroundColor={
						backgroundColor || style?.color?.background
					}
				/>
				<Component { ...props } />
			</>
		);
	};
