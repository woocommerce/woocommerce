/**
 * External dependencies
 */
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterOptions } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import './style.scss';

if ( isExperimentalBlocksEnabled() ) {
	const defaultAttributeId = getSetting< number >(
		'defaultProductFilterAttributeId',
		0
	);

	registerBlockType( metadata, {
		edit: Edit,
		icon: productFilterOptions,
		attributes: {
			...metadata.attributes,
			attributeId: {
				...metadata.attributes.attributeId,
				default: defaultAttributeId,
			},
		},
	} );
}
