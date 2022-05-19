/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
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
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useBackgroundImage } from './use-background-image';
import { BLOCK_NAMES } from './constants';

export const InspectorControls = ( {
	alt,
	backgroundImageSrc,
	contentPanel,
	dimRatio,
	focalPoint = { x: 0.5, y: 0.5 },
	hasParallax,
	isRepeated,
	imageFit,
	overlayColor,
	overlayGradient,
	setAttributes,
	setGradient,
	showDesc,
} ) => {
	// FocalPointPicker was introduced in Gutenberg 5.0 (WordPress 5.2),
	// so we need to check if it exists before using it.
	const focalPointPickerExists = typeof FocalPointPicker === 'function';

	const isImgElement = ! isRepeated && ! hasParallax;

	return (
		<GutenbergInspectorControls key="inspector">
			<PanelBody
				title={ __( 'Content', 'woo-gutenberg-products-block' ) }
			>
				<ToggleControl
					label={ __(
						'Show description',
						'woo-gutenberg-products-block'
					) }
					checked={ showDesc }
					onChange={ () => setAttributes( { showDesc: ! showDesc } ) }
				/>
				{ contentPanel }
			</PanelBody>
			{ !! backgroundImageSrc && (
				<>
					{ focalPointPickerExists && (
						<PanelBody
							title={ __(
								'Media settings',
								'woo-gutenberg-products-block'
							) }
						>
							<ToggleControl
								label={ __(
									'Fixed background',
									'woo-gutenberg-products-block'
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
									'woo-gutenberg-products-block'
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
									value={ imageFit }
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
							) }
							<FocalPointPicker
								label={ __(
									'Focal Point Picker',
									'woo-gutenberg-products-block'
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
										'woo-gutenberg-products-block'
									) }
									value={ alt }
									onChange={ ( value ) => {
										setAttributes( { alt: value } );
									} }
									help={
										<>
											<ExternalLink href="https://www.w3.org/WAI/tutorials/images/decision-tree">
												{ __(
													'Describe the purpose of the image',
													'woo-gutenberg-products-block'
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
						title={ __(
							'Overlay',
							'woo-gutenberg-products-block'
						) }
						initialOpen={ true }
						settings={ [
							{
								colorValue: overlayColor,
								gradientValue: overlayGradient,
								onColorChange: ( value ) =>
									setAttributes( { overlayColor: value } ),
								onGradientChange: ( value ) => {
									setGradient( value );
									setAttributes( {
										overlayGradient: value,
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
							value={ dimRatio }
							onChange={ ( value ) =>
								setAttributes( { dimRatio: value } )
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
	);
};

export const withInspectorControls = ( Component ) => ( props ) => {
	const { attributes, name, setAttributes } = props;
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
		showDesc,
		showPrice,
	} = attributes;

	const item =
		name === BLOCK_NAMES.featuredProduct ? props.product : props.category;

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

	const contentPanel = name === BLOCK_NAMES.featuredProduct && (
		<ToggleControl
			label={ __( 'Show price', 'woo-gutenberg-products-block' ) }
			checked={ showPrice }
			onChange={ () =>
				setAttributes( {
					showPrice: ! showPrice,
				} )
			}
		/>
	);

	return (
		<>
			<InspectorControls
				alt={ alt }
				backgroundImageSrc={ backgroundImageSrc }
				contentPanel={ contentPanel }
				dimRatio={ dimRatio }
				focalPoint={ focalPoint }
				hasParallax={ hasParallax }
				isRepeated={ isRepeated }
				imageFit={ imageFit }
				overlayColor={ overlayColor }
				overlayGradient={ overlayGradient }
				setAttributes={ setAttributes }
				setGradient={ setGradient }
				showDesc={ showDesc }
			/>
			<Component { ...props } />
		</>
	);
};
