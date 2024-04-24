/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { PanelBody, ColorPicker, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { generateEntireSiteStyles } from './styles';

export default function Edit( { attributes, setAttributes } ) {
<<<<<<< HEAD
	const { color, storeOnly } = attributes;
	const blockProps = { ...useBlockProps() };
=======
	const { color, storeOnly, fullPageHeading } = attributes;
>>>>>>> 313ad95b71 (Add fullPageHeading attribute)

	if ( storeOnly ) {
		return (
			<>
				<div { ...blockProps }>
					<InnerBlocks />
				</div>
				<style>{ `.woocommerce-breadcrumb {display: none;}` }</style>
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Coming soon page settings', 'woocommerce' ) }
				>
					<h3>{ __( 'Full page layout', 'woocommerce' ) }</h3>
					<ToggleControl
						label={ __(
							'Display heading in full page mode',
							'woocommerce'
						) }
						checked={ fullPageHeading }
						onChange={ () =>
							setAttributes( {
								fullPageHeading: ! fullPageHeading,
							} )
						}
					/>
					<h3>{ __( 'Background color', 'woocommerce' ) }</h3>
					<ColorPicker
						color={ color }
						onChange={ ( newColor: string ) =>
							setAttributes( { color: newColor } )
						}
						enableAlpha
						defaultValue="#bea0f2"
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<InnerBlocks />
			</div>
			<style>
				{ generateEntireSiteStyles( color, fullPageHeading ) }
			</style>
		</>
	);
}
