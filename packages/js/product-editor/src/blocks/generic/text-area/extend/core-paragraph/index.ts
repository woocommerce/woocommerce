/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import coreParagraphWithTextAreaRole from './components/core-paragraph-with-text-area-role';

/**
 * Extend core/paragraph block with attributes
 * to
 *
 * @param {BlockConfiguration} settings - The block settings.
 * @param {string}             name     - The block name.
 * @return {BlockConfiguration} The updated block settings.
 */
export default function coreParagraphWithTextArea(
	settings: BlockConfiguration,
	name: string
) {
	if ( name !== 'core/paragraph' ) {
		return settings;
	}

	/*
	 * Extend core/paragraph block with attributes
	 * to support product text area block.
	 */
	return {
		...settings,
		supports: {
			align: true,
		},
		attributes: {
			...settings.attributes,
			property: {
				type: 'string',
			},
			label: {
				type: 'string',
			},
			help: {
				type: 'string',
			},
			note: {
				type: 'string',
			},
			required: {
				type: 'boolean',
				default: false,
			},
			role: {
				type: 'string',
				default: 'product-editor/text-area-field',
			},
		},
		edit: settings.edit
			? coreParagraphWithTextAreaRole( settings.edit )
			: null,
	};
}

// Create a core/paragraph variation__
// registerBlockVariation( 'core/paragraph', {
// 	name: 'woocommerce/product-text-area-field-var',
// 	title: __( 'Product textarea block', 'woocommerce' ),
// 	icon: postContent,
// 	attributes: {
// 		placeholder: __( 'Enter text here', 'woocommerce' ),
// 	},
// 	isActive: ( blockAttributes ) => {
// 		const isActive =
// 			blockAttributes.context ===
// 			'woocommerce/product-editor-summary-field';
// 		return isActive;
// 	},
// } );
