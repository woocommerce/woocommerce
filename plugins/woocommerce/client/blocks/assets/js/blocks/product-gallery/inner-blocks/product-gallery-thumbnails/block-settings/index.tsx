/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalUnitControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis, @woocommerce/dependency-group
	__experimentalUnitControl as UnitControl,
	SelectControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	type ProductGalleryThumbnailsSettingsProps,
	ProductGalleryActiveThumbnailStyle,
} from '../types';

const minValue = 10;
const maxValue = 50;
const defaultValue = 25;

export const ProductGalleryThumbnailsBlockSettings = ( {
	attributes,
	setAttributes,
}: ProductGalleryThumbnailsSettingsProps ) => {
	const { thumbnailSize, aspectRatio, activeThumbnailStyle } = attributes;

	const aspectRatioOptions = [
		{
			value: '',
			label: __( 'Select Aspect Ratio', 'woocommerce' ),
			disabled: true,
		},
		{
			value: 'auto',
			label: __( 'Auto', 'woocommerce' ),
		},
		{
			value: '1',
			label: __( 'Square - 1:1', 'woocommerce' ),
		},
		{
			value: '4/3',
			label: __( 'Standard - 4:3', 'woocommerce' ),
		},
		{
			value: '3/4',
			label: __( 'Portrait - 3:4', 'woocommerce' ),
		},
		{
			value: '3/2',
			label: __( 'Classic - 3:2', 'woocommerce' ),
		},
		{
			value: '2/3',
			label: __( 'Classic Portrait - 2:3', 'woocommerce' ),
		},
		{
			value: '16/9',
			label: __( 'Wide - 16:9', 'woocommerce' ),
		},
		{
			value: '9/16',
			label: __( 'Tall - 9:16', 'woocommerce' ),
		},
	];

	const activeThumbnailStyleOptions = [
		{
			value: ProductGalleryActiveThumbnailStyle.OVERLAY,
			label: __( 'Overlay', 'woocommerce' ),
		},
		{
			value: ProductGalleryActiveThumbnailStyle.OUTLINE,
			label: __( 'Outline', 'woocommerce' ),
		},
	];

	return (
		<ToolsPanel
			label={ __( 'Settings', 'woocommerce' ) }
			resetAll={ () => {
				setAttributes( {
					thumbnailSize: '25%',
					aspectRatio: '1',
					activeThumbnailStyle:
						ProductGalleryActiveThumbnailStyle.OVERLAY,
				} );
			} }
		>
			<ToolsPanelItem
				hasValue={ () => thumbnailSize !== '25%' }
				label={ __( 'Thumbnail Size', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { thumbnailSize: '25%' } ) }
				isShownByDefault
			>
				<UnitControl
					label={ __( 'Thumbnail Size', 'woocommerce' ) }
					value={ thumbnailSize }
					onChange={ ( value: string | undefined ) => {
						const numberValue = Number(
							value?.replace( '%', '' ) || defaultValue
						);
						const validated = Math.min(
							Math.max( numberValue, minValue ),
							maxValue
						);
						setAttributes( {
							thumbnailSize: validated + '%',
						} );
					} }
					units={ [ { value: '%', label: '%' } ] }
					min={ minValue }
					max={ maxValue }
					step={ 1 }
					size="default"
					__next40pxDefaultSize
					help={ __(
						'Choose the size of each thumbnail in respect to the product image. If thumbnails container size gets bigger than the product image, thumbnails will turn to slider.',
						'woocommerce'
					) }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => aspectRatio !== '1' }
				label={ __( 'Aspect Ratio', 'woocommerce' ) }
				onDeselect={ () => setAttributes( { aspectRatio: '1' } ) }
				isShownByDefault
			>
				<SelectControl
					__next40pxDefaultSize
					multiple={ false }
					value={ aspectRatio }
					options={ aspectRatioOptions }
					label={ __( 'Aspect Ratio', 'woocommerce' ) }
					onChange={ ( value ) => {
						setAttributes( {
							aspectRatio: value,
						} );
					} }
					help={ __(
						'Applies the selected aspect ratio to product thumbnails.',
						'woocommerce'
					) }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () =>
					activeThumbnailStyle !==
					ProductGalleryActiveThumbnailStyle.OVERLAY
				}
				label={ __( 'Active Thumbnail Style', 'woocommerce' ) }
				onDeselect={ () =>
					setAttributes( {
						activeThumbnailStyle:
							ProductGalleryActiveThumbnailStyle.OVERLAY,
					} )
				}
				isShownByDefault
			>
				<SelectControl
					__next40pxDefaultSize
					multiple={ false }
					value={ activeThumbnailStyle }
					options={ activeThumbnailStyleOptions }
					label={ __( 'Active Thumbnail Style', 'woocommerce' ) }
					onChange={ ( value ) => {
						if (
							value ===
								ProductGalleryActiveThumbnailStyle.OVERLAY ||
							value === ProductGalleryActiveThumbnailStyle.OUTLINE
						) {
							setAttributes( {
								activeThumbnailStyle: value,
							} );
						}
					} }
					help={ __(
						'Choose how the active thumbnail is highlighted.',
						'woocommerce'
					) }
				/>
			</ToolsPanelItem>
		</ToolsPanel>
	);
};
