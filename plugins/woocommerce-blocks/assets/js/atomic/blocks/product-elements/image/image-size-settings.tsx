/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockAttributes } from '@wordpress/blocks';
import {
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
// eslint-disable-next-line @woocommerce/dependency-group
import { unlock } from '@woocommerce/utils';

const { DimensionsTool } = unlock( blockEditorPrivateApis );

interface ImageSizeSettingProps {
	scale: string;
	width: string | undefined;
	height: string | undefined;
	aspectRatio: string | undefined;
	setAttributes: ( attrs: BlockAttributes ) => void;
}

const scaleHelp: Record< string, string > = {
	cover: __(
		'Image is scaled and cropped to fill the entire space without being distorted.',
		'woocommerce'
	),
	contain: __(
		'Image is scaled to fill the space without clipping nor distorting.',
		'woocommerce'
	),
	fill: __(
		'Image will be stretched and distorted to completely fill the space.',
		'woocommerce'
	),
};

export const ImageSizeSettings = ( {
	scale,
	width,
	height,
	aspectRatio,
	setAttributes,
}: ImageSizeSettingProps ) => {
	return (
		<ToolsPanel
			className="wc-block-product-image__tools-panel"
			label={ __( 'Image size', 'woocommerce' ) }
		>
			<DimensionsTool
				value={ { aspectRatio: aspectRatio ?? '1' } }
				onChange={ ( {
					aspectRatio: newAspectRatio,
				}: {
					aspectRatio: string;
				} ) => {
					setAttributes( {
						aspectRatio: newAspectRatio,
						scale: 'cover',
					} );
				} }
				defaultAspectRatio="1"
				tools={ [ 'aspectRatio' ] }
			/>

			<UnitControl
				label={ __( 'Height', 'woocommerce' ) }
				onChange={ ( value: string ) => {
					setAttributes( { height: value } );
				} }
				value={ height }
				units={ [
					{
						value: 'px',
						label: 'px',
					},
				] }
			/>
			<UnitControl
				label={ __( 'Width', 'woocommerce' ) }
				onChange={ ( value: string ) => {
					setAttributes( { width: value } );
				} }
				value={ width }
				units={ [
					{
						value: 'px',
						label: 'px',
					},
				] }
			/>
			{ height && (
				<ToolsPanelItem
					hasValue={ () => true }
					label={ __( 'Scale', 'woocommerce' ) }
				>
					<ToggleGroupControl
						label={ __( 'Scale', 'woocommerce' ) }
						value={ scale }
						help={ scaleHelp[ scale ] }
						onChange={ ( value: string ) =>
							setAttributes( {
								scale: value,
							} )
						}
						isBlock
					>
						<>
							<ToggleGroupControlOption
								value="cover"
								label={ __( 'Cover', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value="contain"
								label={ __( 'Contain', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value="fill"
								label={ __( 'Fill', 'woocommerce' ) }
							/>
						</>
					</ToggleGroupControl>
				</ToolsPanelItem>
			) }
		</ToolsPanel>
	);
};
