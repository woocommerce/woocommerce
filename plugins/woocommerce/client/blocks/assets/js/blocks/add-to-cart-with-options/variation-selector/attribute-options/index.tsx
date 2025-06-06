/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, buttons } from '@wordpress/icons';
import { isBoolean } from '@woocommerce/types';
import { getSettingWithCoercion } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import AttributeOptionsEdit from './edit';
import './style.scss';

const isBlockTheme = getSettingWithCoercion( 'isBlockTheme', false, isBoolean );

if ( isBlockTheme ) {
	registerBlockType( metadata, {
		edit: AttributeOptionsEdit,
		attributes: metadata.attributes,
		icon: {
			src: <Icon icon={ buttons } />,
		},
		save: () => null,
	} );
}
