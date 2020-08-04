/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import {
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
	withColors,
	PanelColorSettings,
	FontSizePicker,
	withFontSizes,
} from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import HeadingToolbar from '@woocommerce/block-components/heading-toolbar';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';

const TitleEdit = ( {
	color,
	fontSize,
	setFontSize,
	setColor,
	attributes,
	setAttributes,
} ) => {
	const { headingLevel, productLink, align } = attributes;
	return (
		<>
			<BlockControls>
				<HeadingToolbar
					isCollapsed={ true }
					minLevel={ 1 }
					maxLevel={ 7 }
					selectedLevel={ headingLevel }
					onChange={ ( newLevel ) =>
						setAttributes( { headingLevel: newLevel } )
					}
				/>
				{ isFeaturePluginBuild() && (
					<AlignmentToolbar
						value={ align }
						onChange={ ( newAlign ) => {
							setAttributes( { align: newAlign } );
						} }
					/>
				) }
			</BlockControls>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content', 'woocommerce' ) }
				>
					<ToggleControl
						label={ __(
							'Link to Product Page',
							'woocommerce'
						) }
						help={ __(
							'Links the image to the single product listing.',
							'woocommerce'
						) }
						checked={ productLink }
						onChange={ () =>
							setAttributes( {
								productLink: ! productLink,
							} )
						}
					/>
				</PanelBody>
				{ isFeaturePluginBuild() && (
					<>
						<PanelBody
							title={ __(
								'Text settings',
								'woocommerce'
							) }
						>
							<FontSizePicker
								value={ fontSize.size }
								onChange={ setFontSize }
							/>
						</PanelBody>
						<PanelColorSettings
							title={ __(
								'Color settings',
								'woocommerce'
							) }
							colorSettings={ [
								{
									value: color.color,
									onChange: setColor,
									label: __(
										'Text color',
										'woocommerce'
									),
								},
							] }
						></PanelColorSettings>
					</>
				) }
			</InspectorControls>
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</>
	);
};

const Title = isFeaturePluginBuild()
	? compose( [
			withFontSizes( 'fontSize' ),
			withColors( 'color', { textColor: 'color' } ),
			withProductSelector( {
				icon: BLOCK_ICON,
				label: BLOCK_TITLE,
				description: __(
					"Choose a product to display it's title.",
					'woocommerce'
				),
			} ),
	  ] )( TitleEdit )
	: TitleEdit;

export default Title;
