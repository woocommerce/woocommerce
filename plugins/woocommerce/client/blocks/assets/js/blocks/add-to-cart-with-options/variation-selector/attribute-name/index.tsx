/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, heading } from '@wordpress/icons';
import { isBoolean } from '@woocommerce/types';
import { getSettingWithCoercion } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import AttributeNameEdit from './edit';

const isBlockTheme = getSettingWithCoercion( 'isBlockTheme', false, isBoolean );

if ( isBlockTheme ) {
	registerBlockType( metadata, {
		edit: AttributeNameEdit,
		attributes: metadata.attributes,
		icon: {
			src: <Icon icon={ heading } />,
		},
		save: () => null,
	} );
}
