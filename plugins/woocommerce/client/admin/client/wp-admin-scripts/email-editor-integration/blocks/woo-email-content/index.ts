/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Edit } from './edit';
import { Save } from './save';

export const wooContentPlaceholderBlock = {
	title: __( 'Woo Email Content', 'woocommerce' ),
	category: 'text',
	attributes: {},
	edit: Edit,
	save: Save,
};
