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
import HeadingToolbar from '@woocommerce/editor-components/heading-toolbar';

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
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
				>
					<ToggleControl
						label={ __(
							'Link to Product Page',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Links the image to the single product listing.',
							'woo-gutenberg-products-block'
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
								'woo-gutenberg-products-block'
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
								'woo-gutenberg-products-block'
							) }
							colorSettings={ [
								{
									value: color.color,
									onChange: setColor,
									label: __(
										'Text color',
										'woo-gutenberg-products-block'
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
					'Choose a product to display its title.',
					'woo-gutenberg-products-block'
				),
			} ),
	  ] )( TitleEdit )
	: TitleEdit;

export default Title;
