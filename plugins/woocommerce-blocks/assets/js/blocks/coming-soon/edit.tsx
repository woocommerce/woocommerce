/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { PanelBody, ColorPicker } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { generateStyles } from './styles';

export default function Edit( { attributes, setAttributes } ) {
	const { color, storeOnly } = attributes;

	if ( storeOnly ) {
		return (
			<>
				<div { ...useBlockProps() }>
					<InnerBlocks />
				</div>
				<style>{ `.woocommerce-breadcrumb {display: none;}` }</style>
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
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
			<div { ...useBlockProps() }>
				<InnerBlocks />
			</div>
			<style>{ generateStyles( color ) }</style>
		</>
	);
}
