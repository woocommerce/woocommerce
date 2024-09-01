/**
 * External dependencies
 */
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterAttribute } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import { registerBlockType } from '@wordpress/blocks';
import { AttributeSetting } from '@woocommerce/types';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import Save from './save';
import './style.scss';

if ( isExperimentalBlocksEnabled() ) {
	const defaultAttribute = getSetting< AttributeSetting >(
		'defaultProductFilterAttribute'
	);

	registerBlockType( metadata, {
		edit: Edit,
		icon: productFilterAttribute,
		attributes: {
			...metadata.attributes,
			attributeId: {
				...metadata.attributes.attributeId,
				default: parseInt( defaultAttribute.attribute_id, 10 ),
			},
		},
		save: Save,
	} );
}
