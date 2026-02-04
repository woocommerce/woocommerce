/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
import { WP_REST_API_Category } from 'wp-types';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls as GutenbergInspectorControls,
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	__experimentalUseGradient as useGradient,
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';
import {
	FocalPointPicker,
	PanelBody,
	RangeControl,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanelItem as ToolsPanelItem,
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
	clientId: string;
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
	backgroundColor,
	backgroundColorVisibilityStatus,
	backgroundImageSrc,
	clientId,
	dimRatio,
	focalPoint,
	hasParallax,
	imageFit,
	isRepeated,
	overlayColor,
	overlayGradient,
	setAttributes,
	setGradient,
}: InspectorControlsProps ) => {
	// FocalPointPicker was introduced in Gutenberg 5.0 (WordPress 5.2),
	// so we need to check if it exists before using it.
	const focalPointPickerExists = typeof FocalPointPicker === 'function';

	const isImgElement = ! isRepeated && ! hasParallax;

	const colorGradientSettings = useMultipleOriginColorsAndGradients();

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
					</>
				) }
			</GutenbergInspectorControls>
			{ colorGradientSettings.hasColorsOrGradients && (
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore - group prop is valid but not in TS definitions yet
				<GutenbergInspectorControls group="color">
					{ !! backgroundImageSrc && (
						<>
							<ColorGradientSettingsDropdown
								__experimentalIsRenderedInSidebar
								settings={ [
									{
										clearable: true,
										colorValue: overlayColor,
										gradientValue: overlayGradient,
										label: __( 'Overlay', 'woocommerce' ),
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
										isShownByDefault: true,
										resetAllFilter: () => ( {
											overlayColor: undefined,
											overlayGradient: undefined,
										} ),
									},
								] }
								panelId={ clientId }
								{ ...colorGradientSettings }
							/>
							<ToolsPanelItem
								isShownByDefault
								hasValue={ () => dimRatio !== 50 }
								label={ __( 'Overlay opacity', 'woocommerce' ) }
								onDeselect={ () =>
									setAttributes( { dimRatio: 50 } )
								}
								panelId={ clientId }
								resetAllFilter={ () => ( {
									dimRatio: 50,
								} ) }
							>
								<RangeControl
									required
									label={ __(
										'Overlay opacity',
										'woocommerce'
									) }
									max={ 100 }
									min={ 0 }
									onChange={ ( value ) =>
										setAttributes( {
											dimRatio: value as number,
										} )
									}
									step={ 10 }
									value={ dimRatio }
								/>
							</ToolsPanelItem>
						</>
					) }
					{ backgroundColorVisibilityStatus?.isBackgroundVisible ===
						false &&
						backgroundColorVisibilityStatus?.message &&
						backgroundColor && (
							<div className="image-bg-color-warning">
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ backgroundColorVisibilityStatus.message }
								</Notice>
							</div>
						) }
				</GutenbergInspectorControls>
			) }
		</>
	);
};

export const withInspectorControls =
	< T extends EditorBlock< T > >( Component: ComponentType< T > ) =>
	( props: WithInspectorControlsProps< T > ) => {
		const {
			attributes,
			backgroundColorVisibilityStatus,
			clientId,
			name,
			setAttributes,
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
					clientId={ clientId }
				/>
				<Component { ...props } />
			</>
		);
	};
