/**
 * External dependencies
 */
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterOptions } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import { registerBlockType } from '@wordpress/blocks';
import { AttributeSetting } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import './style.scss';

if ( isExperimentalBlocksEnabled() ) {
	const defaultAttribute = getSetting< AttributeSetting >(
		'defaultProductFilterAttribute'
	);

	registerBlockType( metadata, {
		edit: Edit,
		icon: productFilterOptions,
		attributes: {
			...metadata.attributes,
			attributeId: {
				...metadata.attributes.attributeId,
				default: parseInt( defaultAttribute.attribute_id, 10 ),
			},
		},
	} );
}
